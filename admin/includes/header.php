<?php
include_once __DIR__ . '/../../includes/bootstrap.php';

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(self)');
}
if (empty($_SESSION['auth']) || !in_array($_SESSION['auth_role'] ?? '', ['admin','moderateur'])) {
    header('Location: ../index.php?open_login=1');
    exit;
}

// Affiche une erreur explicite en admin si un Fatal Error survient
// (ProFreeHost coupe souvent display_errors => page blanche).
if (!function_exists('emsp_admin_fatal_notice')) {
    function emsp_admin_fatal_notice(): void
    {
        $e = error_get_last();
        if (!$e || !is_array($e)) {
            return;
        }
        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (!in_array((int) ($e['type'] ?? 0), $fatalTypes, true)) {
            return;
        }

        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }

        $fatalCss = '<style>'
            . '.emsp-admin-fatal{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;padding:16px;margin:16px;border-radius:12px;max-width:1200px;}'
            . '.emsp-admin-fatal--warn{border:1px solid rgba(245,158,11,.35);background:#fffbeb;color:#92400e;}'
            . '.emsp-admin-fatal--error{border:1px solid rgba(220,38,38,.35);background:#fef2f2;color:#7f1d1d;}'
            . '.emsp-admin-fatal-title{font-weight:800;}'
            . '.emsp-admin-fatal-body{margin-top:6px;}'
            . '.emsp-admin-fatal-meta{margin-top:8px;font-size:13px;opacity:.85;}'
            . '</style>';

        if (defined('APP_ENV') && APP_ENV !== 'development') {
            error_log('EMSP FATAL: ' . ($e['message'] ?? '') . ' in ' . ($e['file'] ?? '') . ':' . ($e['line'] ?? 0));
            echo $fatalCss
               . '<div class="emsp-admin-fatal emsp-admin-fatal--warn">'
               . 'Une erreur est survenue. Contactez l\'administrateur.'
               . '</div>';
            return;
        }

        $msg  = htmlspecialchars((string) ($e['message'] ?? 'Erreur inconnue'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $file = htmlspecialchars(basename((string) ($e['file'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $line = (int) ($e['line'] ?? 0);

        echo $fatalCss
           . '<div class="emsp-admin-fatal emsp-admin-fatal--error">'
           . '<div class="emsp-admin-fatal-title">Erreur PHP (Admin)</div>'
           . '<div class="emsp-admin-fatal-body">' . $msg . '</div>'
           . '<div class="emsp-admin-fatal-meta">' . $file . ':' . $line . '</div>'
           . '</div>';
    }
    register_shutdown_function('emsp_admin_fatal_notice');
}
include_once __DIR__ . '/../config/dbcon.php';
include_once __DIR__ . '/../authentication.php';
include_once __DIR__ . '/../../includes/csrf.php';
include_once __DIR__ . '/../../includes/flash.php';

// Fix mojibake globally in HTML output (handles hardcoded strings too).
if (function_exists('emsp_fix_mojibake')) {
    if (!function_exists('emsp_ob_fix_mojibake')) {
        function emsp_ob_fix_mojibake(string $buffer): string
        {
            return function_exists('emsp_fix_mojibake') ? emsp_fix_mojibake($buffer) : $buffer;
        }
    }
    if (!isset($GLOBALS['emsp_mojibake_ob_started'])) {
        $GLOBALS['emsp_mojibake_ob_started'] = true;
        ob_start('emsp_ob_fix_mojibake');
    }
}

$asset = $asset ?? '../assets/';
$admin_asset = $admin_asset ?? 'assets/';
$adminCssFile = __DIR__ . '/../assets/css/emsp-fixes.css';
$adminCssVersion = is_file($adminCssFile) ? (string) filemtime($adminCssFile) : '1';
$harvardThemeEnabled = false;
$htmlClasses = 'expanded';
$currentAdminBodyPath = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
if ($currentAdminBodyPath === '') {
    $currentAdminBodyPath = 'index.php';
}
$adminRouteClass = 'admin-route-' . preg_replace('/[^a-z0-9]+/i', '-', str_replace('.php', '', $currentAdminBodyPath));
$bodyClasses = trim('bg-body ' . $adminRouteClass);
?>
<!doctype html>
<html lang="fr" class="<?= h($htmlClasses) ?>" data-coreui-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#006B3C">
    <meta name="color-scheme" content="light dark">
    <title><?= h($page_title ?? 'Admin EMSP') ?></title>
    <link rel="icon" href="../pwa-icon.svg" type="image/svg+xml">

    <!-- CSS essentiels uniquement -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@latest/dist/simplebar.css">
    <link href="https://cdn.jsdelivr.net/npm/@coreui/coreui@5.0.2/dist/css/coreui.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $asset ?>vendor/sweetalert2/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= $asset ?>vendor/select2/select2.min.css">
    <link rel="stylesheet" href="<?= $admin_asset ?>css/admin-final-logic.css">
    <link rel="stylesheet" href="<?= $admin_asset ?>css/emsp-fixes.css?v=<?= h($adminCssVersion) ?>">
    <link rel="stylesheet" href="<?= $admin_asset ?>css/emsp-admin-canonical.css?v=<?= h((string) (@filemtime(__DIR__ . '/../assets/css/emsp-admin-canonical.css') ?: 1)) ?>">
    <?php if (!empty($extra_head_tags)) { echo $extra_head_tags; } ?>

</head>
<body class="<?= h($bodyClasses) ?>">
<div id="emsp-progress-bar"></div>
<script src="<?= $asset ?>js/emsp-shell-init.js"></script>
<div id="sidebar-overlay"></div>
<button class="emsp-mobile-sidebar-fab d-lg-none" type="button" data-sidebar-toggle="mobile" aria-label="Ouvrir le menu" title="Ouvrir le menu" style="position:fixed;left:20px;bottom:25px;z-index:2140;width:64px;height:64px;border-radius:999px;border:3px solid #F5A800;background:#006B3C;color:#fff;display:flex;align-items:center;justify-content:center;padding:0;box-shadow:0 10px 30px rgba(0,107,60,.4);">
    <i class="bi bi-grid-fill fs-2"></i>
</button>
<div id="admin-wrapper" class="admin-wrapper">
