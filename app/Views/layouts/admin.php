<!doctype html>
<html lang="fr" class="theme-harvard">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= h(config('name')) ?> — Administration</title>
    <link rel="stylesheet" href="<?= asset('css/bootstrap5.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/emsp-fonts.css') ?>?v=<?= h(asset_version()) ?>">
    <link rel="stylesheet" href="<?= asset('css/emsp-theme.css') ?>?v=<?= h(asset_version()) ?>">
    <link rel="stylesheet" href="<?= asset('css/emsp-fixes.css') ?>?v=<?= h(asset_version()) ?>">
    <link rel="stylesheet" href="<?= asset('css/emsp-app.css') ?>?v=<?= h(asset_version()) ?>">
    <link rel="stylesheet" href="<?= asset('css/emsp-editorial-shell.css') ?>?v=<?= h(asset_version()) ?>">
    <link rel="stylesheet" href="<?= url('admin/assets/css/emsp-admin-canonical.css') ?>?v=<?= h(asset_version()) ?>">
</head>
<body class="emsp-admin-body emsp-admin-mobile-enabled">
<?php
$adminPendingTop = $GLOBALS['emsp_admin_pending'] ?? ['docs' => 0, 'users' => 0];
$adminPendingDocs = max(0, (int) ($adminPendingTop['docs'] ?? 0));
$adminPendingUsers = max(0, (int) ($adminPendingTop['users'] ?? 0));
?>
<div class="emsp-admin-shell" id="emsp-admin-shell">
    <div class="emsp-admin-overlay" id="emsp-admin-overlay" hidden aria-hidden="true"></div>
    <?php require __DIR__ . '/../partials/admin-sidebar.php'; ?>

    <main class="emsp-admin-main">
        <div class="emsp-admin-topbar">
            <div class="emsp-admin-topbar-start">
                <button type="button" class="emsp-admin-menu-toggle" id="emsp-admin-menu-toggle" aria-controls="emsp-admin-sidebar" aria-expanded="false" aria-label="Ouvrir le menu">
                    <span class="emsp-hamburger" aria-hidden="true"><span></span><span></span><span></span></span>
                </button>
                <div>
                    <span class="emsp-admin-eyebrow">EMSP DOCS</span>
                    <strong>Espace administration</strong>
                </div>
            </div>
            <?php if ($adminPendingDocs > 0 || $adminPendingUsers > 0): ?>
            <div class="emsp-admin-topbar-alerts d-lg-none" aria-label="Éléments en attente de validation">
                <?php if ($adminPendingDocs > 0): ?>
                    <a class="emsp-admin-topbar-alert emsp-admin-topbar-alert--docs" href="<?= url('admin/validation-documents') ?>" title="Documents en attente">
                        <i class="bi bi-file-earmark-check" aria-hidden="true"></i>
                        <span class="emsp-admin-topbar-alert__count"><?= $adminPendingDocs > 99 ? '99+' : $adminPendingDocs ?></span>
                        <span class="visually-hidden"><?= $adminPendingDocs ?> document(s) en attente</span>
                    </a>
                <?php endif; ?>
                <?php if ($adminPendingUsers > 0): ?>
                    <a class="emsp-admin-topbar-alert emsp-admin-topbar-alert--users" href="<?= url('admin/validation-comptes') ?>" title="Comptes en attente">
                        <i class="bi bi-person-check" aria-hidden="true"></i>
                        <span class="emsp-admin-topbar-alert__count"><?= $adminPendingUsers > 99 ? '99+' : $adminPendingUsers ?></span>
                        <span class="visually-hidden"><?= $adminPendingUsers ?> compte(s) en attente</span>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <a class="emsp-admin-back" href="<?= url('dashboard') ?>"><i class="bi bi-arrow-left"></i><span>Retour à la plateforme</span></a>
        </div>
        <div class="emsp-admin-content">
        <?php foreach ($flashes as $f): ?>
            <div class="alert alert-<?= h($f['type']) ?>"><?= h($f['message']) ?></div>
        <?php endforeach; ?>

        <?= $content ?>
        </div>
    </main>
    <?php require __DIR__ . '/../partials/admin-bottom-nav.php'; ?>
</div>

<script src="<?= asset('js/bootstrap5.bundle.min.js') ?>"></script>
<script src="<?= asset('js/emsp-admin-shell.js') ?>?v=<?= h(asset_version()) ?>"></script>
</body>
</html>
