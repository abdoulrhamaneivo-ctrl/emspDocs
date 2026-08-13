<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\Admin\UserAdminRepository;

final class PendingUsersController extends AdminController
{
    public function index(): void
    {
        [$con, $adminUser] = $this->guard();
        $users = new UserAdminRepository($con);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action'], $_POST['user_id'])) {
            $this->handleAction($users, (int) $adminUser['id']);
            return;
        }

        $perPage = emsp_per_page_from_request(25);
        $pageVerified = max(1, (int) ($_GET['page'] ?? 1));
        $pageUnverified = max(1, (int) ($_GET['page_uv'] ?? 1));

        [, $totalVerified] = $users->pendingVerifiedPaginated(1, 0);
        $paginationVerified = emsp_paginate($totalVerified, $pageVerified, $perPage);
        [$verified] = $users->pendingVerifiedPaginated($paginationVerified['perPage'], $paginationVerified['offset']);

        [, $totalUnverified] = $users->pendingUnverifiedPaginated(1, 0);
        $paginationUnverified = emsp_paginate($totalUnverified, $pageUnverified, $perPage);
        [$unverified] = $users->pendingUnverifiedPaginated($paginationUnverified['perPage'], $paginationUnverified['offset']);

        $this->view('admin/pending-users/index', [
            'pendingVerifiedUsers' => $verified,
            'pendingUnverifiedUsers' => $unverified,
            'paginationVerified' => $paginationVerified,
            'paginationUnverified' => $paginationUnverified,
        ]);
    }

    private function handleAction(UserAdminRepository $users, int $adminId): void
    {
        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            flash('danger', 'Token de sécurité invalide.');
            redirect('admin/validation-comptes');
        }

        $userId = (int) $_POST['user_id'];
        $action = (string) $_POST['action'];
        $motif = trim((string) ($_POST['motif'] ?? ''));

        $user = $users->findPendingById($userId);
        if (!$user || $user['status'] !== 'pending') {
            flash('warning', "Ce compte a déjà été traité ou n'existe plus.");
            redirect('admin/validation-comptes');
        }

        $emailConfirmed = !empty($user['email_verified_at']) || empty($user['verification_token']);
        if (!$emailConfirmed) {
            flash('warning', "L'étudiant doit confirmer son adresse email avant validation.");
            redirect('admin/validation-comptes');
        }

        $studentName = trim($user['first_name'] . ' ' . $user['last_name']);

        $emailService = new \App\Services\EmailService();

        if ($action === 'approve') {
            $users->approve($userId);
            $sent = $emailService->sendAccountApproved((int) $user['id'], (string) $user['email'], (string) $user['first_name'], (string) $user['last_name']);
            if (!$sent) {
                error_log('EMSP account approved email failed for user_id=' . $userId);
            }
            try {
                (new \App\Services\NotificationService())->notifyAccountApproved($userId);
            } catch (\Throwable $e) {
                error_log('EMSP account approved notification failed: ' . $e->getMessage());
            }
            log_audit(\App\Core\Database::pdo(), $adminId, 'account_activated', 'user', $userId, (string) $user['email']);
            flash('success', $sent
                ? $studentName . ' peut maintenant se connecter et accéder à la plateforme. Un email de bienvenue lui a été envoyé.'
                : $studentName . ' peut maintenant se connecter, mais l\'email de notification n\'a pas pu être envoyé.');
        } elseif ($action === 'reject') {
            if ($motif === '') {
                flash('warning', 'Veuillez indiquer un motif clair pour refuser cette inscription.');
                redirect('admin/validation-comptes');
            }
            $users->reject($userId, $motif);
            $sent = $emailService->sendAccountRejected((int) $user['id'], (string) $user['email'], (string) $user['first_name'], (string) $user['last_name'], $motif);
            if (!$sent) {
                error_log('EMSP account rejected email failed for user_id=' . $userId);
            }
            log_audit(\App\Core\Database::pdo(), $adminId, 'account_rejected', 'user', $userId, $motif);
            flash('warning', $sent
                ? 'La demande de ' . $studentName . ' a été refusée avec le motif fourni. L\'étudiant a été notifié par email.'
                : 'La demande de ' . $studentName . ' a été refusée, mais l\'email de notification n\'a pas pu être envoyé.');
        }

        redirect('admin/validation-comptes');
    }
}
