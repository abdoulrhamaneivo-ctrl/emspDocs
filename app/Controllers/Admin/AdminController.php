<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use PDO;

abstract class AdminController extends Controller
{
    /**
     * Expiration de session (1h d'inactivité), re-synchronisation fraîche
     * depuis la base, contrôle du rôle (admin/modérateur) et du statut du compte.
     *
     * @return array{0: PDO, 1: array<string, mixed>} [$pdo, $adminUser]
     */
    protected function guard(): array
    {
        $pdo = Database::pdo();

        if (!current_user()) {
            flash('warning', "Connectez-vous pour accéder à l'administration.");
            redirect('login');
        }

        $lifetime = 3600;
        if (isset($_SESSION['last_activity']) && (time() - (int) $_SESSION['last_activity']) > $lifetime) {
            session_unset();
            session_destroy();
            redirect('login');
        }
        $_SESSION['last_activity'] = time();

        $uid = (int) (current_user()['id'] ?? 0);
        if ($uid <= 0) {
            redirect('login');
        }

        $stmt = $pdo->prepare(
            'SELECT id, first_name, last_name, email, role, status, badge_level, photo_path FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $uid]);
        $fresh = $stmt->fetch();

        if (!$fresh) {
            session_unset();
            session_destroy();
            redirect('login');
        }

        emsp_session_sync_auth_user($fresh);

        $role = strtolower((string) $fresh['role']);
        $status = strtolower((string) $fresh['status']);

        if (!in_array($role, ['admin', 'moderateur'], true)) {
            redirect('dashboard');
        }
        if ($status === 'pending') {
            redirect('pending-status');
        }
        if (in_array($status, ['rejected', 'suspended'], true)) {
            unset(
                $_SESSION['auth'],
                $_SESSION['auth_user'],
                $_SESSION['auth_role'],
                $_SESSION['notif_count'],
                $_SESSION['notif_sections'],
                $_SESSION['emsp_notif_sections_fetched_at']
            );
            flash('danger', $status === 'rejected' ? 'Votre compte a été refusé.' : 'Votre compte est suspendu.');
            redirect('login');
        }

        $this->loadPendingCounts($pdo);

        return [$pdo, $fresh];
    }

    /**
     * Compteurs partagés sidebar (docs/utilisateurs en attente).
     */
    private function loadPendingCounts(PDO $pdo): void
    {
        if (isset($GLOBALS['emsp_admin_pending'])) {
            return;
        }
        $GLOBALS['emsp_admin_pending'] = ['docs' => 0, 'users' => 0];
        $row = $pdo->query(
            "SELECT (SELECT COUNT(*) FROM documents WHERE status='pending') AS docs,
                    (SELECT COUNT(*) FROM users WHERE status='pending') AS users"
        )->fetch();
        if ($row) {
            $GLOBALS['emsp_admin_pending'] = $row;
        }
    }

    protected function view(string $view, array $data = [], string $layout = 'admin'): void
    {
        parent::view($view, $data, $layout);
    }
}
