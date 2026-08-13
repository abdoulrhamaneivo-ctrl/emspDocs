<?php
/**
 * Configuration client pour le polling des badges notifications.
 */
$__notifAuth = !empty($_SESSION['auth']) || !empty($_SESSION['auth_user']['id']);
$__notifActive = $__notifAuth
    && in_array(strtolower(trim((string) ($_SESSION['auth_user']['status'] ?? 'active'))), ['', 'active'], true);

$__notifBase = function_exists('url') ? rtrim((string) parse_url(url(''), PHP_URL_PATH), '/') . '/' : './';
if ($__notifBase === '/') {
    $__notifBase = '/';
}

if (!function_exists('csrf_token')) {
    include_once dirname(__DIR__) . '/csrf.php';
}
?>
<script>
window.__emspNotifConfig = <?= json_encode([
    'authenticated' => $__notifActive,
    'unreadUrl' => $__notifBase . 'notifications/unread-count',
    'recentUrl' => $__notifBase . 'notifications/recent',
    'markReadUrl' => $__notifBase . 'notifications/mark-read',
    'appBase' => $__notifBase,
    'pollInterval' => 60000,
    'csrfToken' => $__notifActive ? csrf_token() : '',
    'loginUrl' => $__notifBase . 'login',
    'dashboardUrl' => $__notifBase . 'dashboard',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script defer src="<?= function_exists('asset') ? asset('js/emsp-notifications.js') : ($__notifBase . 'assets/js/emsp-notifications.js') ?>?v=<?= h(function_exists('asset_version') ? asset_version() : (defined('EMSP_ASSET_VERSION') ? EMSP_ASSET_VERSION : '20260814ah')) ?>"></script>
