<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\NotificationService;

final class NotificationController
{
    public function unreadCount(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        if (empty($_SESSION['auth_user']['id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'auth-required']);
            return;
        }

        $uid = (int) $_SESSION['auth_user']['id'];
        $service = new NotificationService();
        $unread = $service->unreadCount($uid);
        $sections = $service->unreadSectionCounts($uid);
        $service->syncSessionCounters($uid);

        echo json_encode([
            'ok' => true,
            'unread' => $unread,
            'sections' => $sections,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recent(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        if (empty($_SESSION['auth_user']['id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'auth-required']);
            return;
        }

        require_once dirname(__DIR__, 2) . '/includes/notif-helper.php';

        $uid = (int) $_SESSION['auth_user']['id'];
        $limit = max(1, min(20, (int) ($_GET['limit'] ?? 10)));
        $service = new NotificationService();
        $rows = $service->listRecent($uid, $limit);
        $items = [];
        $typeLabels = emsp_notification_type_labels();

        foreach ($rows as $row) {
            $payload = $row;
            if (!empty($row['doc_id_resolved'])) {
                $payload['document_id'] = (int) $row['doc_id_resolved'];
            }
            foreach (['message', 'doc_title', 'from_first', 'from_last'] as $field) {
                if (isset($payload[$field]) && is_string($payload[$field]) && function_exists('emsp_fix_mojibake')) {
                    $payload[$field] = emsp_fix_mojibake($payload[$field]);
                }
            }

            $meta = emsp_notification_present($payload);
            $type = (string) ($row['type'] ?? '');
            $items[] = [
                'id' => (int) ($row['id'] ?? 0),
                'type' => $type,
                'type_label' => $typeLabels[strtolower($type)] ?? 'Notification',
                'message' => (string) ($payload['message'] ?? ''),
                'is_read' => !empty($row['is_read']),
                'created_at' => (string) ($row['created_at'] ?? ''),
                'link' => $meta['link'],
                'icon' => $meta['icon'],
                'icon_color' => $meta['icon_color'],
                'doc_title' => (string) ($payload['doc_title'] ?? ''),
            ];
        }

        echo json_encode([
            'ok' => true,
            'items' => $items,
            'unread' => $service->unreadCount($uid),
        ], JSON_UNESCAPED_UNICODE);
    }

    public function markRead(): void
    {
        $wantsJson = self::wantsJson();

        if ($wantsJson) {
            header('Content-Type: application/json; charset=UTF-8');
        }

        if (empty($_SESSION['auth_user']['id'])) {
            if ($wantsJson) {
                http_response_code(401);
                echo json_encode(['ok' => false, 'error' => 'auth-required']);
                return;
            }
            redirect('login');
        }

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            if ($wantsJson) {
                http_response_code(405);
                echo json_encode(['ok' => false, 'error' => 'method']);
                return;
            }
            redirect('dashboard');
        }

        require_once dirname(__DIR__, 2) . '/includes/notif-helper.php';

        if (!self::verifyCsrfRequest()) {
            if ($wantsJson) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'csrf']);
                return;
            }
            verify_csrf_token();
        }

        $uid = (int) $_SESSION['auth_user']['id'];
        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        $redirectRaw = (string) ($_POST['redirect_to'] ?? '');
        $redirectTo = self::safeLocalRedirect($redirectRaw, 'dashboard');
        $service = new NotificationService();

        if ($notificationId > 0) {
            $notifRow = $service->findForUser($uid, $notificationId) ?? [];
            if ($redirectRaw === '' && $notifRow !== []) {
                $redirectTo = emsp_notification_target($notifRow, 'dashboard');
            }

            $service->markOneRead($uid, $notificationId);
        } else {
            $service->markAllRead($uid);
        }

        if ($wantsJson) {
            echo json_encode([
                'ok' => true,
                'unread' => $service->unreadCount($uid),
                'sections' => $service->unreadSectionCounts($uid),
                'redirect' => $redirectTo,
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        redirect($redirectTo);
    }

    private static function wantsJson(): bool
    {
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        if (str_contains($accept, 'application/json')) {
            return true;
        }

        return strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    private static function verifyCsrfRequest(): bool
    {
        $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');
        $requestToken = (string) (
            $_POST['csrf_token']
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? ''
        );

        return $sessionToken !== ''
            && $requestToken !== ''
            && hash_equals($sessionToken, $requestToken);
    }

    private static function safeLocalRedirect(string $target, string $default = 'dashboard'): string
    {
        $target = trim($target);
        if ($target === '') {
            return $default;
        }

        $target = str_replace('\\', '/', $target);
        if (preg_match('#^(?:[a-z]+:)?//#i', $target)) {
            return $default;
        }

        $target = ltrim($target, '/');

        if ($target === '' || preg_match('/[\r\n]/', $target)) {
            return $default;
        }

        return $target;
    }
}
