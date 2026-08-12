<?php
/**
 * Sidebar admin. Version simplifiée : mêmes liens et mêmes badges de
 * compteurs que admin/includes/sidebar.php, mais en Bootstrap natif plutôt
 * qu'en thème CoreUI (voir README_PHASE5_ADMIN.md).
 */
$adminUser = current_user();
$adminRole = strtolower((string) ($adminUser['role'] ?? ''));
$isAdmin = $adminRole === 'admin';
$adminPending = $GLOBALS['emsp_admin_pending'] ?? ['docs' => 0, 'users' => 0];
$pendingDocs = (int) ($adminPending['docs'] ?? 0);
$pendingUsers = (int) ($adminPending['users'] ?? 0);

$navSections = [
    ['label' => 'Principal', 'items' => [
        ['href' => 'admin/journal', 'icon' => 'speedometer2', 'label' => 'Accueil admin', 'is_route' => true],
    ]],
    ['label' => 'Documents', 'items' => [
        ['href' => 'admin/validation-documents', 'icon' => 'check2-square', 'label' => 'Validation docs', 'badge' => $pendingDocs, 'is_route' => true],
        ['href' => 'admin/validation-documents', 'icon' => 'folder2-open', 'label' => 'Suivi des documents', 'is_route' => true],
    ]],
    ['label' => 'Utilisateurs', 'items' => [
        ['href' => 'admin/validation-comptes', 'icon' => 'person-check', 'label' => 'Validation comptes', 'badge' => $pendingUsers, 'is_route' => true],
        ['href' => 'admin/utilisateurs', 'icon' => 'people', 'label' => 'Tous les utilisateurs', 'is_route' => true],
        ['href' => 'admin/badge-or-batch.php', 'icon' => 'trophy', 'label' => 'Badge Or', 'only_admin' => true],
    ]],
    ['label' => 'Contenu', 'items' => [
        ['href' => 'admin/journal', 'icon' => 'newspaper', 'label' => 'Journal / News', 'is_route' => true],
        ['href' => 'admin/mediatheque', 'icon' => 'collection-play', 'label' => 'Médiathèque', 'is_route' => true],
        ['href' => 'admin/moderation-commentaires', 'icon' => 'chat-dots', 'label' => 'Commentaires', 'is_route' => true],
        ['href' => 'admin/edit-institution.php', 'icon' => 'building', 'label' => 'Institution'],
    ]],
    ['label' => 'Référentiels', 'items' => [
        ['href' => 'admin/filieres', 'icon' => 'diagram-3', 'label' => 'Filières', 'is_route' => true],
        ['href' => 'admin/licences', 'icon' => 'layers', 'label' => 'Niveaux / Licences', 'is_route' => true],
        ['href' => 'admin/modules', 'icon' => 'grid', 'label' => 'Modules', 'is_route' => true],
        ['href' => 'admin/matieres', 'icon' => 'book', 'label' => 'Matières', 'is_route' => true],
    ]],
    ['label' => 'Système', 'items' => [
        ['href' => 'admin/domaines-email', 'icon' => 'envelope-at', 'label' => 'Domaines email', 'only_admin' => true, 'is_route' => true],
        ['href' => 'admin/statistiques', 'icon' => 'graph-up', 'label' => 'Statistiques', 'is_route' => true],
        ['href' => 'admin/parametres', 'icon' => 'gear', 'label' => 'Paramètres', 'only_admin' => true, 'is_route' => true],
    ]],
];
$currentRoute = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '', '/');
$adminBasePath = trim((string) parse_url(url(''), PHP_URL_PATH), '/');
if ($adminBasePath !== '' && str_starts_with($currentRoute, $adminBasePath . '/')) {
    $currentRoute = substr($currentRoute, strlen($adminBasePath) + 1);
}
?>
<aside class="emsp-admin-sidebar" id="emsp-admin-sidebar">
    <div class="emsp-admin-brand">
        <img src="<?= asset('images/logo-emsp.png') ?>" alt="" height="30" class="emsp-admin-logo">
        <span>EMSP <strong>Admin</strong></span>
    </div>
    <div class="emsp-admin-user">
        <?= h(trim(($adminUser['first_name'] ?? '') . ' ' . ($adminUser['last_name'] ?? ''))) ?>
        <div><?= $isAdmin ? 'Administrateur' : 'Modérateur' ?></div>
    </div>

    <?php foreach ($navSections as $section): ?>
        <div class="emsp-admin-section-label"><?= h($section['label']) ?></div>
        <ul class="nav nav-pills flex-column mb-1">
            <?php foreach ($section['items'] as $item):
                if (!empty($item['only_admin']) && !$isAdmin) {
                    continue;
                }
                $isRoute = !empty($item['is_route']);
                $href = $isRoute ? url($item['href']) : url($item['href']);
                $isActive = $isRoute ? ($currentRoute === $item['href']) : (basename($item['href']) === basename($currentRoute));
                $badge = max(0, (int) ($item['badge'] ?? 0));
            ?>
                <li class="nav-item">
                    <a class="emsp-admin-nav-link <?= $isActive ? 'is-active' : '' ?>" href="<?= h($href) ?>">
                        <i class="bi bi-<?= h((string) $item['icon']) ?>"></i>
                        <span class="flex-grow-1"><?= h((string) $item['label']) ?></span>
                        <?php if ($badge > 0): ?>
                            <span class="emsp-admin-badge"><?= $badge > 99 ? '99+' : $badge ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endforeach; ?>

    <div class="emsp-admin-section-label">Session</div>
    <form method="post" action="<?= url('logout') ?>" class="m-0">
        <?= csrf_field() ?>
        <button type="submit" class="emsp-admin-logout" data-confirm="Te déconnecter ?" data-confirm-detail="Tu reviendras à l'accueil public de la plateforme." data-confirm-type="warning" data-confirm-ok="Oui, me déconnecter" data-emsp-confirm-auto="1"><i class="bi bi-box-arrow-right"></i> Déconnexion</button>
    </form>
</aside>
