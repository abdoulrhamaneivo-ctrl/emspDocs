<?php
include_once __DIR__ . '/bootstrap.php';
include_once __DIR__ . '/helpers.php';
include_once __DIR__ . '/../admin/config/dbcon.php';
include_once __DIR__ . '/csrf.php';
include_once __DIR__ . '/flash.php';

if (empty($GLOBALS['csp_nonce'])) {
    $GLOBALS['csp_nonce'] = base64_encode(random_bytes(16));
}
$csp_nonce = (string) $GLOBALS['csp_nonce'];

if (!function_exists('emsp_output_filter')) {
    function emsp_output_filter(string $buffer): string
    {
        if (function_exists('emsp_fix_mojibake')) {
            $buffer = emsp_fix_mojibake($buffer);
        }

        $nonce = (string) ($GLOBALS['csp_nonce'] ?? '');
        if ($nonce !== '') {
            $replacement = '<script nonce="' . htmlspecialchars($nonce, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"$1>';
            $buffer = preg_replace('/<script(?![^>]*\bnonce=)([^>]*)>/i', $replacement, $buffer) ?? $buffer;
        }

        return $buffer;
    }
}

if (!isset($GLOBALS['emsp_output_filter_started'])) {
    $GLOBALS['emsp_output_filter_started'] = true;
    ob_start('emsp_output_filter');
}

if (!headers_sent()) {
    // Charset HTML
    header('Content-Type: text/html; charset=UTF-8');
    // Clickjacking protection
    header('X-Frame-Options: SAMEORIGIN');
    // MIME sniffing protection
    header('X-Content-Type-Options: nosniff');
    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // Permissions policy
    header("Permissions-Policy: camera=(self), microphone=(), geolocation=()");
    // CSP — compatible ProFreeHost (bannière injectée) + PDF.js (blob: workers) + Bootstrap Icons local
    header("Content-Security-Policy: default-src 'self' https://profreehost.com https://*.profreehost.com https://ezyro.com https://*.ezyro.com https://*.unaux.com; script-src 'self' 'nonce-{$csp_nonce}' blob: https://cdnjs.cloudflare.com https://profreehost.com https://*.profreehost.com https://*.ezyro.com https://*.unaux.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com https://profreehost.com https://*.profreehost.com https://*.ezyro.com; font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://profreehost.com https://*.profreehost.com https://*.ezyro.com; img-src 'self' data: blob: https://img.youtube.com https://i.ytimg.com https://api.dicebear.com https://profreehost.com https://*.profreehost.com https://*.ezyro.com https://*.unaux.com; frame-src 'self' blob: https://www.youtube.com https://youtube.com https://www.youtube-nocookie.com https://profreehost.com https://*.profreehost.com; worker-src 'self' blob:; connect-src 'self' blob: https://www.disify.com https://disify.com https://profreehost.com https://*.profreehost.com https://*.ezyro.com https://*.unaux.com;");
}

$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$scriptDir = rtrim(dirname($scriptName), '/');
if ($scriptDir === '.' || $scriptDir === '\\') {
    $scriptDir = '';
}
$base = $scriptDir === '' ? '/' : ($scriptDir . '/');
$asset = $base . 'assets/';
$host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$canExposeManifest = !function_exists('emsp_can_expose_manifest') || emsp_can_expose_manifest();
$faviconPath = __DIR__ . '/../assets/images/favicon.png';
$faviconHref = $asset . 'images/favicon.png';

$page_title = $page_title ?? 'Plateforme EMSP Docs';
$currentBodyPath = function_exists('emsp_nav_current_path')
    ? emsp_nav_current_path()
    : basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
if ($currentBodyPath === '') {
    $currentBodyPath = 'index.php';
}
$harvardThemeEnabled = true;
$htmlClasses = $harvardThemeEnabled ? 'theme-harvard' : '';
$bodyClasses = ['emsp-route-' . preg_replace('/[^a-z0-9]+/i', '-', str_replace('.php', '', $currentBodyPath))];
if ($harvardThemeEnabled) {
    $bodyClasses[] = 'theme-harvard';
}
if (function_exists('emsp_should_show_mobile_bottom_nav') && emsp_should_show_mobile_bottom_nav()) {
    $bodyClasses[] = 'emsp-mobile-bottom-nav-enabled';
}
if (function_exists('emsp_should_show_mobile_deposit_fab') && emsp_should_show_mobile_deposit_fab()) {
    $bodyClasses[] = 'emsp-mobile-fab-visible';
}
if (function_exists('emsp_should_show_mobile_guest_fab') && emsp_should_show_mobile_guest_fab()) {
    $bodyClasses[] = 'emsp-mobile-guest-fab-visible';
}
$bodyRole = (string) ($_SESSION['auth_role'] ?? ($_SESSION['auth_user']['role'] ?? 'guest'));
$bodyIsAuthenticated = !empty($_SESSION['auth']) || !empty($_SESSION['auth_user']['id']);
?>
<!doctype html>
<html lang="fr" class="<?= h($htmlClasses) ?>">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <?php if ($currentBodyPath === 'login.php' || (isset($_GET['open_login']) && $_GET['open_login'] === '1')): ?>
    <noscript>
        <meta http-equiv="refresh" content="0;url=login.php?noscript=1">
    </noscript>
    <?php endif; ?>
    <title><?= h($page_title) ?> &mdash; EMSP Docs</title>
    <meta name="theme-color" content="#006B3C">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="mobile-web-app-capable" content="yes">
    <?php if (is_file($faviconPath)): ?>
    <link rel="icon" href="<?= h($faviconHref) ?>" type="image/png">
    <link rel="shortcut icon" href="<?= h($faviconHref) ?>" type="image/png">
    <link rel="apple-touch-icon" href="<?= h($asset) ?>images/logo-emsp-192.png">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:wght@400;700&family=Crimson+Pro:ital,wght@0,400;0,600;0,700;0,800;1,400&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<?php $__av = asset_version(); ?>
    <link rel="stylesheet" href="<?= $asset ?>css/emsp-fonts.css?v=<?= h($__av) ?>">
    <!-- Bootstrap local -->
    <link rel="stylesheet" href="<?= $asset ?>css/bootstrap5.min.css">
    <link rel="stylesheet" href="<?= $asset ?>css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $asset ?>vendor/sweetalert2/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= $asset ?>vendor/select2/select2.min.css">
    <link rel="stylesheet" href="<?= $asset ?>css/emsp-theme.css?v=<?= h($__av) ?>">
    <link rel="stylesheet" href="<?= $asset ?>css/emsp-fixes.css?v=<?= h($__av) ?>">
    <?php if ($canExposeManifest): ?>
    <link rel="manifest" href="<?= $base ?>manifest.json">
    <?php endif; ?>
    <?php if (!empty($extra_head_tags)) { echo $extra_head_tags; } ?>
    <!-- Master EMSP App Stylesheet -->
    <link rel="stylesheet" href="<?= $asset ?>css/emsp-app.css?v=<?= h($__av) ?>">
    <link rel="stylesheet" href="<?= $asset ?>css/emsp-editorial-shell.css?v=<?= h($__av) ?>">
</head>
<body class="<?= h(trim(implode(' ', $bodyClasses))) ?>" data-role="<?= h($bodyRole) ?>" data-authenticated="<?= $bodyIsAuthenticated ? '1' : '0' ?>" data-route="<?= h($currentBodyPath) ?>">
<a class="emsp-skip-link" href="#main-content-anchor">Aller au contenu principal</a>
<div id="emsp-progress-bar"></div>
<?php
$_pwaScope = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/') . '/';
if ($_pwaScope === '/' || $_pwaScope === '//') {
    $_pwaScope = '/';
}
?>
<script>
window.__emspPwaConfig = {
  basePath: <?= json_encode($_pwaScope, JSON_UNESCAPED_SLASHES) ?>,
  swUrl: <?= json_encode($_pwaScope . 'sw.js', JSON_UNESCAPED_SLASHES) ?>,
  manifestUrl: <?= json_encode($_pwaScope . 'manifest.json', JSON_UNESCAPED_SLASHES) ?>
};
</script>
<script src="<?= $asset ?>js/emsp-shell-init.js"></script>
<script src="<?= $asset ?>js/pwa.js?v=<?= h($__av) ?>"></script>
<?php if (!empty($_SESSION['auth_user']['id'])): ?>
<script src="<?= $asset ?>js/emsp-push.js"></script>
<?php
// Init globale push — disponible sur toutes les pages authentifiées
include_once __DIR__ . '/push-helper.php';
$_vapidPublic = (function_exists('emsp_push_public_key') && isset($con))
    ? htmlspecialchars(emsp_push_public_key($con), ENT_QUOTES | ENT_HTML5, 'UTF-8')
    : '';
$_swScope = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/') . '/';
if (empty($_swScope) || $_swScope === '/') $_swScope = '/';
?>
<?php if ($_vapidPublic): ?>
<script>
(function(){
  if (!window.emspPush) return;
  window.emspPush.setPublicKey('<?= $_vapidPublic ?>');
  window.emspPush.setCsrfToken('<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>');
  window.emspPush.setEndpoints({
    subscribe:          '<?= htmlspecialchars($_swScope, ENT_QUOTES) ?>push-subscribe',
    unsubscribe:        '<?= htmlspecialchars($_swScope, ENT_QUOTES) ?>push-unsubscribe',
    serviceWorkerUrl:   '<?= htmlspecialchars($_swScope, ENT_QUOTES) ?>sw.js',
    serviceWorkerScope: '<?= htmlspecialchars($_swScope, ENT_QUOTES) ?>'
  });
})();
</script>
<?php endif; ?>
<?php endif; ?>

<?php flash_render(); ?>
<?php include __DIR__ . '/partials/desktop-mobile-hint.php'; ?>
<?php include __DIR__ . '/navbar.php'; ?>
<?php include __DIR__ . '/banner.php'; ?>
<main id="main-content-anchor" class="site-main" tabindex="-1">
