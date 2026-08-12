<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\LegacyDb;
use App\Repositories\Admin\DocumentAdminRepository;

final class PendingDocumentsController extends AdminController
{
    public function index(): void
    {
        [$con, $adminUser] = $this->guard();
        LegacyDb::documentHelpers();
        $documents = new DocumentAdminRepository($con);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action'], $_POST['doc_id'])) {
            $this->handleAction($documents, (int) $adminUser['id']);
            return;
        }

        $this->view('admin/pending-documents/index', [
            'docs' => $documents->pendingList(),
            'availableMatieres' => $documents->activeMatieres(),
        ]);
    }

    private function handleAction(DocumentAdminRepository $documents, int $adminId): void
    {
        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            flash('danger', 'Token de sécurité invalide.');
            redirect('admin/validation-documents');
        }

        $docId = (int) ($_POST['doc_id'] ?? 0);
        $action = (string) ($_POST['action'] ?? '');
        $motif = trim((string) ($_POST['motif'] ?? ''));
        $reviewMatiereId = (int) ($_POST['review_matiere_id'] ?? 0);
        $reviewMatiereLabel = trim((string) ($_POST['review_matiere_label'] ?? ''));

        if (!in_array($action, ['approve', 'reject'], true)) {
            flash('danger', "L'action demandée est invalide.");
            redirect('admin/validation-documents');
        }
        if ($action === 'reject' && $motif === '') {
            flash('warning', 'Veuillez indiquer un motif clair pour refuser ce document.');
            redirect('admin/validation-documents');
        }

        $result = $action === 'approve'
            ? $documents->approve($docId, $adminId, $reviewMatiereId, $reviewMatiereLabel)
            : $documents->reject($docId, $adminId, $motif);

        if (!$result['ok']) {
            $messages = [
                'not_found' => "Le document sélectionné n'existe plus ou a déjà été traité.",
                'already_processed' => "Ce document a été traité avant votre action. Aucun email supplémentaire n'a été envoyé.",
                'matiere_required' => "Choisissez une matière existante ou corrigez le libellé de la nouvelle matière avant d'approuver.",
                'technical' => "Le traitement du document a échoué. Réessayez dans quelques instants.",
            ];
            flash('danger', $messages[$result['error']] ?? ($result['error'] ?: "Impossible de traiter ce document."));
            redirect('admin/validation-documents');
        }

        // The repository returns the processed document from its transaction.
        // Reuse it instead of relying on legacy controller-local variables.
        $doc = is_array($result['doc'] ?? null) ? $result['doc'] : [];
        $emailService = new \App\Services\EmailService();
        $uploaderName = trim(($doc['first_name'] ?? '') . ' ' . ($doc['last_name'] ?? ''));

        $notifService = new \App\Services\NotificationService();
        $uploaderId = (int) ($doc['uploader_id'] ?? 0);
        $docTitle = (string) ($doc['title'] ?? '');

        if ($action === 'approve') {
            $sent = $emailService->sendDocApproved($uploaderId, (string) $doc['email'], (string) $doc['first_name'], (string) $doc['last_name'], $docTitle);
            try {
                $notifService->notifyDocApproved($uploaderId, $docId, $docTitle, $adminId);
            } catch (\Throwable $e) {
                error_log('EMSP doc approved notification failed: ' . $e->getMessage());
            }
            log_audit(\App\Core\Database::pdo(), $adminId, 'doc_approved', 'document', $docId, $docTitle);
            flash('success', $sent
                ? 'Le document "' . $docTitle . '" est maintenant visible dans la bibliothèque. L\'étudiant ' . $uploaderName . ' a été notifié par email.'
                : 'Document approuvé, mais l\'email de notification n\'a pas pu être envoyé.');
        } else {
            $sent = $emailService->sendDocRejected($uploaderId, (string) $doc['email'], (string) $doc['first_name'], (string) $doc['last_name'], $docTitle, $motif);
            try {
                $notifService->notifyDocRejected($uploaderId, $docId, $docTitle, $motif, $adminId);
            } catch (\Throwable $e) {
                error_log('EMSP doc rejected notification failed: ' . $e->getMessage());
            }
            log_audit(\App\Core\Database::pdo(), $adminId, 'doc_rejected', 'document', $docId, $motif);
            flash('warning', $sent
                ? 'Le document a été refusé. L\'étudiant a été notifié avec le motif fourni.'
                : 'Document refusé, mais l\'email de notification n\'a pas pu être envoyé.');
        }

        redirect('admin/validation-documents');
    }
}
