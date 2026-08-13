<!doctype html>
<html lang="fr" class="theme-harvard">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= h(config('name')) ?></title>
    <link rel="icon" href="<?= asset('images/favicon.png') ?>" type="image/png">
    <link rel="shortcut icon" href="<?= asset('images/favicon.png') ?>" type="image/png">
    <link rel="apple-touch-icon" href="<?= asset('images/logo-emsp-192.png') ?>">
    <?php if (!function_exists('emsp_can_expose_manifest') || emsp_can_expose_manifest()): ?>
    <link rel="manifest" href="<?= rtrim((string) parse_url(url(''), PHP_URL_PATH), '/') ?>/manifest.json">
    <?php endif; ?>
    <meta name="theme-color" content="#006B3C">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="mobile-web-app-capable" content="yes">

    <link rel="stylesheet" href="<?= asset('css/emsp-fonts.css') ?>?v=<?= h(asset_version()) ?>">

    <!-- Meme pile CSS que le reste du site (assets/css), chargee dans le
         meme ordre que l'ancien includes/header.php, pour un rendu visuel
         strictement identique entre pages migrees et non-migrees : le
         theme "historique" (emsp-theme.css/emsp-fixes.css) d'abord, puis
         le systeme de composants le plus recent (emsp-app.css) en dernier
         pour qu'il ait la priorite sur les quelques classes communes. -->
    <link rel="stylesheet" href="<?= asset('css/bootstrap5.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/sweetalert2/sweetalert2.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/select2/select2.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/emsp-theme.css') ?>?v=<?= h(asset_version()) ?>">
    <link rel="stylesheet" href="<?= asset('css/emsp-fixes.css') ?>?v=<?= h(asset_version()) ?>">
    <?php if (!empty($extraHeadTags)): ?>
        <?= $extraHeadTags ?>
    <?php endif; ?>
<link rel="stylesheet" href="<?= asset('css/emsp-app.css') ?>?v=<?= h(asset_version()) ?>">
<link rel="stylesheet" href="<?= asset('css/emsp-editorial-shell.css') ?>?v=<?= h(asset_version()) ?>">
</head>
<?php
$__layoutAuth = !empty($_SESSION['auth']) || !empty($_SESSION['auth_user']['id']);
$__layoutRole = (string) ($_SESSION['auth_role'] ?? ($_SESSION['auth_user']['role'] ?? 'guest'));
$__layoutRoute = function_exists('emsp_nav_current_path')
    ? emsp_nav_current_path()
    : basename((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
if ($__layoutRoute === '') {
    $__layoutRoute = 'index.php';
}
$__layoutRouteSlug = preg_replace('/[^a-z0-9]+/i', '-', preg_replace('/\.php$/i', '', $__layoutRoute));
$__layoutBodyClasses = ['theme-harvard', 'emsp-route-' . trim((string) $__layoutRouteSlug, '-')];
if (in_array($__layoutRoute, ['mon-profil', 'profil-public'], true)) {
    $__layoutBodyClasses[] = 'emsp-profile-chrome';
}
if (function_exists('emsp_should_show_mobile_bottom_nav') && emsp_should_show_mobile_bottom_nav()) {
    $__layoutBodyClasses[] = 'emsp-mobile-bottom-nav-enabled';
}
if (function_exists('emsp_should_show_mobile_deposit_fab') && emsp_should_show_mobile_deposit_fab()) {
    $__layoutBodyClasses[] = 'emsp-mobile-fab-visible';
}
if (function_exists('emsp_should_show_mobile_guest_fab') && emsp_should_show_mobile_guest_fab()) {
    $__layoutBodyClasses[] = 'emsp-mobile-guest-fab-visible';
}

// Compatibility variables expected by the unified legacy/mobile navigation.
$__layoutBasePath = rtrim((string) parse_url(url(''), PHP_URL_PATH), '/') . '/';
if ($__layoutBasePath === '/') {
    $__layoutBasePath = '/';
}
$base = $__layoutBasePath;
$asset = $base . 'assets/';
$page_title = $page_title ?? config('name');
$__swScope = rtrim((string) parse_url(url(''), PHP_URL_PATH), '/') . '/';
if ($__swScope === '') {
    $__swScope = '/';
}
?>
<body class="<?= h(implode(' ', $__layoutBodyClasses)) ?>" data-role="<?= h($__layoutRole) ?>" data-authenticated="<?= $__layoutAuth ? '1' : '0' ?>" data-route="<?= h($__layoutRoute) ?>">
<a class="emsp-skip-link" href="#main-content-anchor">Aller au contenu principal</a>
<div id="emsp-progress-bar"></div>
<script>
window.__emspPwaConfig = {
  basePath: <?= json_encode($__swScope, JSON_UNESCAPED_SLASHES) ?>,
  swUrl: <?= json_encode($__swScope . 'sw.js', JSON_UNESCAPED_SLASHES) ?>,
  manifestUrl: <?= json_encode(rtrim($__swScope, '/') . '/manifest.json', JSON_UNESCAPED_SLASHES) ?>
};
</script>
<script src="<?= asset('js/emsp-shell-init.js') ?>"></script>
<script defer src="<?= asset('js/pwa.js') ?>?v=<?= h(asset_version()) ?>"></script>
<?php
/*
 * Push notifications are an optional enhancement for authenticated pages.
 * Never let an unavailable legacy MySQLi layer break the MVC shell.
 */
$__pushReady = false;
$__vapidPublic = '';

if ($__layoutAuth) {
    try {
        // Le shell MVC ne doit pas ouvrir la connexion MySQLi legacy uniquement
        // pour initialiser une amélioration optionnelle. La clé publique VAPID
        // est lue via la connexion PDO déjà canonique du MVC.
        $pdo = \App\Core\Database::pdo();
        $stmt = $pdo->prepare(
            "SELECT svalue FROM app_settings WHERE skey='push_vapid_public' LIMIT 1"
        );
        $stmt->execute();
        $key = (string) ($stmt->fetchColumn() ?: getenv('VAPID_PUBLIC_KEY') ?: '');
        if (trim($key) !== '') {
            $__vapidPublic = htmlspecialchars($key, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $__pushReady = true;
        }
    } catch (\Throwable $e) {
        // Push is non-critical. Keep the authenticated MVC page renderable.
        $__pushReady = false;
        $__vapidPublic = '';
        error_log('EMSP push shell init skipped: ' . $e->getMessage());
    }
}
?>
<?php if ($__layoutAuth): ?>
<script src="<?= asset('js/emsp-push.js') ?>"></script>
<?php endif; ?>
<?php if ($__pushReady): ?>
<script>
(function(){
  if (!window.emspPush) return;
  window.emspPush.setPublicKey('<?= $__vapidPublic ?>');
  window.emspPush.setCsrfToken('<?= htmlspecialchars(csrf_token(), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>');
  window.emspPush.setEndpoints({
    subscribe:          '<?= htmlspecialchars($__swScope, ENT_QUOTES) ?>push-subscribe',
    unsubscribe:        '<?= htmlspecialchars($__swScope, ENT_QUOTES) ?>push-unsubscribe',
    serviceWorkerUrl:   '<?= htmlspecialchars($__swScope, ENT_QUOTES) ?>sw.js',
    serviceWorkerScope: '<?= htmlspecialchars($__swScope, ENT_QUOTES) ?>'
  });
})();
</script>
<?php endif; ?>

<?php require_once dirname(__DIR__, 3) . '/includes/csrf.php'; ?>
<?php require dirname(__DIR__, 3) . '/includes/partials/desktop-mobile-hint.php'; ?>
<?php require dirname(__DIR__, 3) . '/includes/navbar.php'; ?>
<?php require dirname(__DIR__, 3) . '/includes/banner.php'; ?>

<main id="main-content-anchor" class="site-main" tabindex="-1">
<?= $content ?>
</main>

<footer class="site-footer emsp-site-footer">
    <div class="container">
        <div class="emsp-site-footer__inner">
            <div class="emsp-site-footer__brand">
                <strong>EMSP Docs</strong>
                <p>La plateforme documentaire de la communauté EMSP.</p>
                <span>&copy; <?= (int) date('Y') ?> École Multinationale Supérieure des Postes.</span>
            </div>
            <nav class="emsp-site-footer__links" aria-label="Explorer">
                <strong>Explorer</strong>
                <a href="<?= url('documents') ?>">Bibliothèque</a>
                <a href="<?= url('formations') ?>">Formations</a>
                <a href="<?= url('journal') ?>">Journal</a>
            </nav>
            <nav class="emsp-site-footer__links" aria-label="Accompagnement">
                <strong>Accompagnement</strong>
                <a href="<?= url('mediatheque') ?>">Médiathèque</a>
                <a href="<?= url('faq') ?>">Questions fréquentes</a>
                <a href="<?= url('register') ?>">Créer un compte</a>
            </nav>
        </div>
    </div>
</footer>

<script src="<?= asset('js/jquery.min.js') ?>"></script>
<script src="<?= asset('js/bootstrap5.bundle.min.js') ?>"></script>
<script src="<?= asset('vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>
<script src="<?= asset('vendor/select2/select2.full.min.js') ?>"></script>
<script src="<?= asset('js/jspdf.umd.min.js') ?>"></script>
<?php if (function_exists('flash_render')) {
    flash_render($flashes ?? []);
} ?>
<script src="<?= asset('js/emsp-flash.js') ?>?v=<?= h(asset_version()) ?>"></script>
<script src="<?= asset('js/emsp-ui.js') ?>"></script>
<?php if (!$__layoutAuth): ?>
<script src="<?= asset('js/emsp-login-modal.js') ?>?v=<?= h(asset_version()) ?>"></script>
<?php endif; ?>
<script src="<?= asset('js/emsp-fixes.js') ?>"></script>
<script defer src="<?= asset('js/emsp-scanner.js') ?>?v=<?= h(asset_version()) ?>"></script>
<script defer src="<?= asset('js/emsp-desktop-mobile-hint.js') ?>?v=<?= h(asset_version()) ?>"></script>
<script defer src="<?= asset('js/emsp-mobile-fab.js') ?>?v=<?= h(asset_version()) ?>"></script>
<script defer src="<?= asset('js/emsp-experience-upgrade.js') ?>?v=<?= h(asset_version()) ?>"></script>
<script defer src="<?= asset('js/emsp-navbar-enhance.js') ?>?v=<?= h(asset_version()) ?>"></script>
<script defer src="<?= asset('js/emsp-mediatheque-sheet.js') ?>?v=<?= h(asset_version()) ?>"></script>
<script defer src="<?= asset('js/emsp-mediatheque-preview-carousel.js') ?>?v=<?= h(asset_version()) ?>"></script>
<script defer src="<?= asset('js/emsp-harvard-motion.js') ?>?v=<?= h(asset_version()) ?>"></script>
<script defer src="<?= asset('js/emsp-motion.js') ?>?v=<?= h(asset_version()) ?>"></script>
<script defer src="<?= asset('js/emsp-pdf-preview.js') ?>?v=<?= h(asset_version()) ?>"></script>
<script defer src="<?= asset('js/emsp-modal-guard.js') ?>?v=<?= h(asset_version()) ?>"></script>
<?php require dirname(__DIR__, 3) . '/includes/partials/notifications-shell.php'; ?>
<?php if (!empty($page_scripts)) { echo $page_scripts; } ?>
<?php require dirname(__DIR__, 3) . '/includes/partials/pwa-install.php'; ?>
</body>
</html>
