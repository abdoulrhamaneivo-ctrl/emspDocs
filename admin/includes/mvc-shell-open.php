<?php
/**
 * Shell admin MVC unifié pour pages legacy (badge-or, edit-institution, etc.)
 */
if (!function_exists('asset_version')) {
    require_once __DIR__ . '/../../includes/helpers.php';
}
$page_title = $page_title ?? 'Admin EMSP';
$asset = '../assets/';
$extra_head_tags = $extra_head_tags ?? '';
$page_scripts = $page_scripts ?? '';
$fixesVer = asset_version();
$appVer = asset_version();
?>
<!doctype html>
<html lang="fr" class="theme-harvard">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= h($page_title) ?> — EMSP Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:wght@400;700&family=Crimson+Pro:ital,wght@0,400;0,600;0,700;0,800;1,400&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $asset ?>css/bootstrap5.min.css">
    <link rel="stylesheet" href="<?= $asset ?>css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $asset ?>css/emsp-fonts.css?v=<?= h($fixesVer) ?>">
    <link rel="stylesheet" href="<?= $asset ?>css/emsp-theme.css?v=<?= h($fixesVer) ?>">
    <link rel="stylesheet" href="<?= $asset ?>css/emsp-fixes.css?v=<?= h($fixesVer) ?>">
    <link rel="stylesheet" href="<?= $asset ?>css/emsp-app.css?v=<?= h($appVer) ?>">
    <link rel="stylesheet" href="<?= $asset ?>css/emsp-editorial-shell.css?v=<?= h($appVer) ?>">
    <link rel="stylesheet" href="../assets/css/emsp-admin-canonical.css?v=<?= h($appVer) ?>">
    <?= $extra_head_tags ?>
</head>
<body class="emsp-admin-body emsp-admin-mobile-enabled">
<div class="emsp-admin-shell" id="emsp-admin-shell">
    <div class="emsp-admin-overlay" id="emsp-admin-overlay" hidden aria-hidden="true"></div>
    <?php require __DIR__ . '/../../app/Views/partials/admin-sidebar.php'; ?>
    <main class="emsp-admin-main">
        <div class="emsp-admin-topbar">
            <div class="emsp-admin-topbar-start">
                <button type="button" class="emsp-admin-menu-toggle" id="emsp-admin-menu-toggle" aria-controls="emsp-admin-sidebar" aria-expanded="false" aria-label="Ouvrir le menu">
                    <span class="emsp-hamburger" aria-hidden="true"><span></span><span></span><span></span></span>
                </button>
                <div>
                    <span class="emsp-admin-eyebrow">EMSP DOCS</span>
                    <strong><?= h($page_title) ?></strong>
                </div>
            </div>
            <a class="emsp-admin-back" href="<?= url('dashboard') ?>"><i class="bi bi-arrow-left"></i><span>Retour à la plateforme</span></a>
        </div>
        <div class="emsp-admin-content">
        <?php
        if (function_exists('flash_render')) {
            flash_render();
        }
        if (!empty($_SESSION['message'])):
        ?>
            <div class="alert alert-info alert-dismissible fade show">
                <?= h((string) $_SESSION['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
