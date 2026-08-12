<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\LegacyDb;

final class PushController
{
    public function subscribe(): void
    {
        $con = $this->boot();

        if (!$this->requirePost() || !$this->requireAuth() || !$this->requireCsrf()) {
            return;
        }

        $endpoint = trim((string) ($_POST['endpoint'] ?? ''));
        $p256dh = trim((string) ($_POST['p256dh'] ?? ''));
        $auth = trim((string) ($_POST['auth'] ?? ''));
        $deviceLabel = trim((string) ($_POST['device_label'] ?? ''));
        $userAgent = trim((string) ($_POST['user_agent'] ?? ''));

        if ($endpoint === '' || $p256dh === '' || $auth === '') {
            self::json(['ok' => false, 'error' => 'invalid'], 400);
            return;
        }

        $uid = (int) $_SESSION['auth_user']['id'];
        if (!emsp_ensure_web_push_table($con)) {
            self::json(['ok' => false, 'error' => 'schema'], 500);
            return;
        }

        $stmt = mysqli_prepare(
            $con,
            "INSERT INTO web_push_subscriptions (user_id, endpoint, p256dh, auth, device_label, user_agent)
             VALUES (?, ?, ?, ?, NULLIF(?,''), NULLIF(?, ''))
             ON DUPLICATE KEY UPDATE user_id=VALUES(user_id), p256dh=VALUES(p256dh), auth=VALUES(auth),
                                     device_label=VALUES(device_label), user_agent=VALUES(user_agent)"
        );
        if (!$stmt) {
            self::json(['ok' => false, 'error' => 'db'], 500);
            return;
        }
        mysqli_stmt_bind_param($stmt, 'isssss', $uid, $endpoint, $p256dh, $auth, $deviceLabel, $userAgent);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        self::json(['ok' => true]);
    }

    public function unsubscribe(): void
    {
        $con = $this->boot();

        if (!$this->requirePost() || !$this->requireAuth() || !$this->requireCsrf()) {
            return;
        }

        $endpoint = trim((string) ($_POST['endpoint'] ?? ''));
        if ($endpoint === '') {
            self::json(['ok' => false, 'error' => 'invalid'], 400);
            return;
        }

        $uid = (int) $_SESSION['auth_user']['id'];
        if (!emsp_ensure_web_push_table($con)) {
            self::json(['ok' => false, 'error' => 'schema'], 500);
            return;
        }

        $stmt = mysqli_prepare($con, "DELETE FROM web_push_subscriptions WHERE user_id=? AND endpoint=?");
        if (!$stmt) {
            self::json(['ok' => false, 'error' => 'db'], 500);
            return;
        }
        mysqli_stmt_bind_param($stmt, 'is', $uid, $endpoint);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        self::json(['ok' => true]);
    }

    private function boot(): \mysqli
    {
        $con = LegacyDb::mysqli();
        $rootDir = dirname(__DIR__, 2);
        require_once $rootDir . '/includes/push-helper.php';
        header('Content-Type: application/json; charset=UTF-8');
        return $con;
    }

    private function requirePost(): bool
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            self::json(['ok' => false, 'error' => 'method'], 405);
            return false;
        }
        return true;
    }

    private function requireAuth(): bool
    {
        if (empty($_SESSION['auth_user']['id'])) {
            self::json(['ok' => false, 'error' => 'auth-required'], 401);
            return false;
        }
        return true;
    }

    private function requireCsrf(): bool
    {
        $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');
        $postToken = (string) ($_POST['csrf_token'] ?? '');
        if ($sessionToken === '' || $postToken === '' || !hash_equals($sessionToken, $postToken)) {
            self::json(['ok' => false, 'error' => 'csrf'], 403);
            return false;
        }
        return true;
    }

    private static function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        echo json_encode($payload);
    }
}
