<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\LegacyDb;
use App\Services\EmailService;

final class PendingStatusController extends Controller
{
    public function index(): void
    {
        if (empty($_SESSION['auth']) || empty($_SESSION['auth_user']['id'])) {
            flash('warning', 'Connexion requise : vous devez être connecté pour consulter le statut de votre demande.');
            redirect('login');
        }

        $con = LegacyDb::mysqli();

        $uid = (int) ($_SESSION['auth_user']['id'] ?? 0);
        $stmt = mysqli_prepare(
            $con,
            "SELECT id, first_name, last_name, email, status, email_verified_at, verification_token, created_at, rejection_reason
             FROM users
             WHERE id=?
             LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, 'i', $uid);
        mysqli_stmt_execute($stmt);
        $user = emsp_stmt_fetch_assoc($stmt);
        mysqli_stmt_close($stmt);

        if (!$user) {
            session_destroy();
            flash('error', 'Session expirée : veuillez vous reconnecter pour continuer.');
            redirect('login');
        }

        $emailConfirmed = !empty($user['email_verified_at']) || empty($user['verification_token']);
        $status = strtolower(trim((string) ($user['status'] ?? 'pending')));
        $firstName = trim((string) ($user['first_name'] ?? ''));

        if ($status === 'active') {
            redirect('dashboard');
        }

        $emailService = new EmailService();
        $emailConfigured = $emailService->isConfigured();
        $lastVerificationEmail = $emailService->getLastEmailLog($uid, 'verification');
        $verificationEmailSent = ($lastVerificationEmail['status'] ?? '') === 'sent';
        $verificationEmailFailed = ($lastVerificationEmail['status'] ?? '') === 'failed';
        $cooldownRemaining = $emailConfirmed ? 0 : $emailService->resendCooldownSeconds($uid, 'verification', 5);
        $lastResendAt = $_SESSION['last_verification_email_sent_at']
            ?? $_SESSION['pending_last_resend_at']
            ?? null;

        if ($status === 'rejected') {
            $accountStatus = 'rejected';
        } elseif (!$emailConfirmed) {
            $accountStatus = 'pending_email';
        } else {
            $accountStatus = 'pending_admin';
        }

        if (!empty($_GET['verified']) && $emailConfirmed) {
            flash('success', 'Email confirmé. Votre dossier est en cours d\'examen par l\'administration.');
        }

        $this->view('pending-status/index', [
            'user' => $user,
            'emailConfirmed' => $emailConfirmed,
            'status' => $status,
            'firstName' => $firstName,
            'accountStatus' => $accountStatus,
            'userEmail' => (string) ($user['email'] ?? ''),
            'emailConfigured' => $emailConfigured,
            'emailSendFailed' => $verificationEmailFailed,
            'verificationEmailSent' => $verificationEmailSent,
            'verificationEmailFailed' => $verificationEmailFailed,
            'cooldownRemaining' => $cooldownRemaining,
            'resendCooldown' => $cooldownRemaining,
            'mailConfigured' => $emailConfigured,
            'lastResendAt' => $lastResendAt,
            'page_scripts' => '<script src="' . h(asset('js/emsp-pending-status.js')) . '?v=' . h(asset_version()) . '"></script>',
        ]);
    }
}
