<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\LegacyDb;

final class PendingStatusController extends Controller
{
    public function index(): void
    {
        if (empty($_SESSION['auth']) || empty($_SESSION['auth_user']['id'])) {
            flash('warning', 'Connexion requise : vous devez etre connecte pour consulter le statut de votre demande.');
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
            flash('error', 'Session expiree : veuillez vous reconnecter pour continuer.');
            redirect('login');
        }

        $emailConfirmed = !empty($user['email_verified_at']) || empty($user['verification_token']);
        $status = strtolower(trim((string) ($user['status'] ?? 'pending')));
        $firstName = trim((string) ($user['first_name'] ?? ''));

        if ($status === 'active') {
            redirect('dashboard');
        }

        $this->view('pending-status/index', [
            'user' => $user,
            'emailConfirmed' => $emailConfirmed,
            'status' => $status,
            'firstName' => $firstName,
        ]);
    }
}
