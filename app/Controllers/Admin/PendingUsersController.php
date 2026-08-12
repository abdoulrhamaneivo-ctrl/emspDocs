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

        [$verified, $unverified] = $users->pendingUsers();

        $this->view('admin/pending-users/index', [
            'pendingVerifiedUsers' => $verified,
            'pendingUnverifiedUsers' => $unverified,
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
            $emailService->sendAccountApproved((int) $user['id'], (string) $user['email'], (string) $user['first_name'], (string) $user['last_name']);
            try {
                (new \App\Services\NotificationService())->notifyAccountApproved($userId);
            } catch (\Throwable $e) {
                error_log('EMSP account approved notification failed: ' . $e->getMessage());
            }
            log_audit(\App\Core\Database::pdo(), $adminId, 'account_activated', 'user', $userId, (string) $user['email']);
            flash('success', $studentName . ' peut maintenant se connecter et accéder à la plateforme. Un email de bienvenue lui a été envoyé.');
        } elseif ($action === 'reject') {
            if ($motif === '') {
                flash('warning', 'Veuillez indiquer un motif clair pour refuser cette inscription.');
                redirect('admin/validation-comptes');
            }
            $users->reject($userId, $motif);
            $emailService->sendAccountRejected((int) $user['id'], (string) $user['email'], (string) $user['first_name'], (string) $user['last_name'], $motif);
            log_audit(\App\Core\Database::pdo(), $adminId, 'account_rejected', 'user', $userId, $motif);
            flash('warning', 'La demande de ' . $studentName . ' a été refusée avec le motif fourni. L\'étudiant a été notifié par email.');
        }

        redirect('admin/validation-comptes');
    }
}
