<?php
/**
 * Navigation mobile admin — accès rapide aux sections critiques + drawer complet.
 */
$adminUser = current_user();
$adminRole = strtolower((string) ($adminUser['role'] ?? ''));
$isAdmin = $adminRole === 'admin';
$adminPending = $GLOBALS['emsp_admin_pending'] ?? ['docs' => 0, 'users' => 0];
$pendingDocs = max(0, (int) ($adminPending['docs'] ?? 0));
$pendingUsers = max(0, (int) ($adminPending['users'] ?? 0));
$pendingTotal = $pendingDocs + $pendingUsers;

$currentRoute = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '', '/');
$adminBasePath = trim((string) parse_url(url(''), PHP_URL_PATH), '/');
if ($adminBasePath !== '' && str_starts_with($currentRoute, $adminBasePath . '/')) {
    $currentRoute = substr($currentRoute, strlen($adminBasePath) + 1);
}

$routeMatches = static function (array $prefixes) use ($currentRoute): bool {
    foreach ($prefixes as $prefix) {
        if ($currentRoute === $prefix || str_starts_with($currentRoute, $prefix . '/')) {
            return true;
        }
    }
    return false;
};

$navItems = [
    [
        'href' => 'admin/journal',
        'icon' => 'house-door',
        'label' => 'Accueil',
        'match' => ['admin/journal'],
    ],
    [
        'href' => 'admin/validation-documents',
        'icon' => 'check2-square',
        'label' => 'Docs',
        'badge' => $pendingDocs,
        'match' => ['admin/validation-documents'],
    ],
    [
        'href' => 'admin/validation-comptes',
        'icon' => 'person-check',
        'label' => 'Comptes',
        'badge' => $pendingUsers,
        'match' => ['admin/validation-comptes'],
    ],
];

if ($isAdmin) {
    $navItems[] = [
        'href' => 'admin/statistiques',
        'icon' => 'graph-up',
        'label' => 'Stats',
        'match' => ['admin/statistiques'],
    ];
} else {
    $navItems[] = [
        'href' => 'admin/mediatheque',
        'icon' => 'collection-play',
        'label' => 'Médias',
        'match' => ['admin/mediatheque'],
    ];
}
?>
<nav class="emsp-mobile-bottom-nav emsp-mobile-bottom-nav--dock emsp-admin-bottom-nav" data-emsp-admin-bottom-nav="1" aria-label="Navigation administration mobile">
    <div class="emsp-mobile-bottom-nav__inner emsp-admin-bottom-nav__inner">
        <?php foreach ($navItems as $item):
            $isActive = $routeMatches($item['match']);
            $badge = max(0, (int) ($item['badge'] ?? 0));
        ?>
            <a
                class="emsp-mobile-bottom-nav__item<?= $isActive ? ' active' : '' ?>"
                href="<?= h(url($item['href'])) ?>"
                aria-label="<?= h($item['label']) ?><?= $badge > 0 ? ' (' . ($badge > 99 ? '99+' : $badge) . ' en attente)' : '' ?>"
                aria-current="<?= $isActive ? 'page' : 'false' ?>"
                title="<?= h($item['label']) ?>"
            >
                <span class="emsp-mobile-bottom-nav__icon-wrap">
                    <i class="bi bi-<?= h($item['icon']) ?>" aria-hidden="true"></i>
                    <?php if ($badge > 0): ?>
                        <span class="emsp-mobile-bottom-nav__badge" aria-hidden="true"><?= $badge > 99 ? '99+' : $badge ?></span>
                    <?php endif; ?>
                </span>
                <span class="emsp-mobile-bottom-nav__label"><?= h($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
        <button
            type="button"
            class="emsp-mobile-bottom-nav__item emsp-admin-bottom-nav__menu"
            id="emsp-admin-bottom-menu"
            aria-controls="emsp-admin-sidebar"
            aria-expanded="false"
            aria-haspopup="true"
            aria-label="<?= $pendingTotal > 0 ? 'Plus d\'options (' . $pendingTotal . ' en attente)' : 'Plus d\'options' ?>"
            title="Plus"
        >
            <span class="emsp-mobile-bottom-nav__icon-wrap">
                <i class="bi bi-three-dots" aria-hidden="true"></i>
                <?php if ($pendingTotal > 0): ?>
                    <span class="emsp-mobile-bottom-nav__badge emsp-mobile-bottom-nav__badge--muted" aria-hidden="true"><?= $pendingTotal > 99 ? '99+' : $pendingTotal ?></span>
                <?php endif; ?>
            </span>
            <span class="emsp-mobile-bottom-nav__label">Plus</span>
        </button>
    </div>
</nav>
