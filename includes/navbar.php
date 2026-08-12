<?php
include_once __DIR__ . '/bootstrap.php';
include_once __DIR__ . '/helpers.php';
include_once __DIR__ . '/notif-helper.php';

$isAuth = !empty($_SESSION['auth']) || !empty($_SESSION['auth_user']['id']);
$authRole = strtolower(trim((string) ($_SESSION['auth_role'] ?? ($_SESSION['auth_user']['role'] ?? ''))));
$authUser = $_SESSION['auth_user'] ?? [];
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$currentPath = function_exists('emsp_nav_current_path')
    ? emsp_nav_current_path()
    : (basename($requestPath) ?: 'index.php');
if ($currentPath === '') {
    $currentPath = 'index.php';
}

$accountStatus = strtolower(trim((string) ($authUser['status'] ?? '')));
$isStaff = in_array($authRole, ['admin', 'moderateur'], true);
$isActiveAccount = $isAuth && ($accountStatus === '' || $accountStatus === 'active');
$canAccessAdmin = $isActiveAccount && $isStaff;
$canSearchDocuments = $isActiveAccount;
$notifCount = $isAuth ? emsp_session_get_notif_count() : 0;
$notifSections = $isAuth ? emsp_session_get_notif_sections() : ['journal' => 0, 'media' => 0];
$navLatestSections = ['journal' => 0, 'media' => 0];

if (isset($con) && $con instanceof mysqli && function_exists('emsp_nav_notification_context')) {
    $navContext = emsp_nav_notification_context($con, $isAuth, $authUser);
    $notifCount = max(0, (int) ($navContext['notif_count'] ?? $notifCount));
    $notifSections = is_array($navContext['notif_sections'] ?? null) ? $navContext['notif_sections'] : $notifSections;
    $navLatestSections = is_array($navContext['latest_sections'] ?? null) ? $navContext['latest_sections'] : $navLatestSections;
} elseif ($isActiveAccount && (int) ($authUser['id'] ?? 0) > 0) {
    try {
        $notifService = new \App\Services\NotificationService();
        $authUserId = (int) $authUser['id'];
        $notifCount = $notifService->unreadCount($authUserId);
        $notifSections = $notifService->unreadSectionCounts($authUserId);
        $notifService->syncSessionCounters($authUserId);
    } catch (\Throwable $e) {
        error_log('EMSP navbar notification sync skipped: ' . $e->getMessage());
    }
}

$firstName = emsp_fix_mojibake((string) ($authUser['first_name'] ?? ''));
$lastName = emsp_fix_mojibake((string) ($authUser['last_name'] ?? ''));
$initials = '';
if ($firstName !== '') {
    $initials .= mb_strtoupper(mb_substr($firstName, 0, 1));
}
if ($lastName !== '') {
    $initials .= mb_strtoupper(mb_substr($lastName, 0, 1));
}
if ($initials === '') {
    $initials = emsp_user_initials($firstName, $lastName);
}

$userLabel = trim($firstName . ($lastName !== '' ? ' ' . mb_strtoupper(mb_substr($lastName, 0, 1)) . '.' : ''));
$userBadge = [
    'none' => '',
    'bronze' => 'Bronze',
    'argent' => 'Argent',
    'or' => 'Or',
][$authUser['badge_level'] ?? 'none'] ?? '';
$statusLabel = [
    'pending' => 'Compte en attente',
    'rejected' => 'Compte refuse',
    'suspended' => 'Compte suspendu',
][$accountStatus] ?? ($userBadge !== '' ? $userBadge : 'Espace etudiant');
$isBadgeLevelStatus = $isActiveAccount && $userBadge !== '';

$photoSrc = emsp_user_photo_src((string) ($authUser['photo_path'] ?? ''));
if ($photoSrc !== '' && !preg_match('#^https?://#i', $photoSrc)) {
    $photoSrc = $base . ltrim($photoSrc, '/');
}

$routeLabels = [
    'index.php' => 'Accueil',
    'formations' => 'Formations',
    'mediatheque' => 'Médiathèque',
    'journal' => 'Journal',
    'article' => 'Article',
    'concours' => 'Concours',
    'faq' => 'FAQ',
    'login' => 'Connexion',
    'register' => 'Inscription',
    'forgot-password' => 'Mot de passe oublie',
    'reset-password' => 'Reinitialisation',
    'documents' => 'Bibliotheque',
    'dashboard' => 'Espace etudiant',
    'upload' => 'Déposer un document',
    'favoris' => 'Mes favoris',
    'historique' => 'Historique',
    'mon-profil' => 'Mon profil',
    'pending-status' => 'Statut du compte',
    'verify-email' => 'Verification email',
    'institution' => 'Institution',
    'document' => 'Document',
];
$breadcrumbLabel = trim((string) ($routeLabels[$currentPath] ?? ($page_title ?? '')));
if ($breadcrumbLabel === '' || $breadcrumbLabel === 'Plateforme EMSP Docs') {
    $breadcrumbLabel = 'Page';
}
$showBreadcrumb = !in_array($currentPath, ['index.php', ''], true);

$visitorDiscoverLinks = [
    ['label' => 'Institution', 'href' => 'institution', 'paths' => ['institution']],
    ['label' => 'Formations', 'href' => 'formations', 'paths' => ['formations']],
    ['label' => 'FAQ', 'href' => 'faq', 'paths' => ['faq']],
];
$visitorDesktopLinks = [
    ['label' => 'Accueil', 'href' => 'index.php', 'paths' => ['index.php', '']],
    ['label' => 'Découvrir', 'paths' => ['institution', 'formations', 'faq'], 'children' => $visitorDiscoverLinks],
    ['label' => 'Médiathèque', 'href' => 'mediatheque', 'paths' => ['mediatheque'], 'badge_section' => 'media'],
    ['label' => 'Journal', 'href' => 'journal', 'paths' => ['journal', 'article'], 'badge_section' => 'journal'],
    ['label' => 'Concours', 'href' => 'concours', 'paths' => ['concours']],
];
$visitorLinks = $visitorDesktopLinks;
$memberDiscoverLinks = [
    ['label' => 'Institution', 'href' => 'institution', 'paths' => ['institution']],
    ['label' => 'Formations', 'href' => 'formations', 'paths' => ['formations']],
    ['label' => 'FAQ', 'href' => 'faq', 'paths' => ['faq']],
];
$memberLinks = [
    ['label' => 'Accueil', 'href' => 'index.php', 'paths' => ['index.php', '']],
    ['label' => 'Bibliotheque', 'href' => 'documents', 'paths' => ['documents', 'document']],
    ['label' => 'Médiathèque', 'href' => 'mediatheque', 'paths' => ['mediatheque'], 'badge_section' => 'media'],
    ['label' => 'Journal', 'href' => 'journal', 'paths' => ['journal', 'article'], 'badge_section' => 'journal'],
];
$memberDesktopLinks = [
    ['label' => 'Accueil', 'href' => 'index.php', 'paths' => ['index.php', '']],
    ['label' => 'Bibliotheque', 'href' => 'documents', 'paths' => ['documents', 'document']],
    ['label' => 'Découvrir', 'paths' => ['institution', 'formations', 'faq'], 'children' => $memberDiscoverLinks],
    ['label' => 'Médiathèque', 'href' => 'mediatheque', 'paths' => ['mediatheque'], 'badge_section' => 'media'],
    ['label' => 'Journal', 'href' => 'journal', 'paths' => ['journal', 'article'], 'badge_section' => 'journal'],
];
$visitorOffcanvasLinks = [
    ['label' => 'Accueil', 'href' => 'index.php', 'paths' => ['index.php', '']],
    ['label' => 'Découvrir', 'paths' => ['institution', 'formations', 'faq'], 'children' => $visitorDiscoverLinks],
    ['label' => 'Médiathèque', 'href' => 'mediatheque', 'paths' => ['mediatheque'], 'badge_section' => 'media'],
    ['label' => 'Journal', 'href' => 'journal', 'paths' => ['journal', 'article'], 'badge_section' => 'journal'],
    ['label' => 'Concours', 'href' => 'concours', 'paths' => ['concours']],
];
$memberOffcanvasLinks = [
    ['label' => 'Accueil', 'href' => 'index.php', 'paths' => ['index.php', '']],
    ['label' => 'Bibliotheque', 'href' => 'documents', 'paths' => ['documents', 'document']],
    ['label' => 'Découvrir', 'paths' => ['institution', 'formations', 'faq'], 'children' => $memberDiscoverLinks],
    ['label' => 'Médiathèque', 'href' => 'mediatheque', 'paths' => ['mediatheque'], 'badge_section' => 'media'],
    ['label' => 'Journal', 'href' => 'journal', 'paths' => ['journal', 'article'], 'badge_section' => 'journal'],
];
$inactiveLinks = [
    ['label' => 'Accueil', 'href' => 'index.php', 'paths' => ['index.php', '']],
    ['label' => 'Institution', 'href' => 'institution', 'paths' => ['institution']],
    ['label' => 'Médiathèque', 'href' => 'mediatheque', 'paths' => ['mediatheque'], 'badge_section' => 'media'],
    ['label' => 'Journal', 'href' => 'journal', 'paths' => ['journal', 'article'], 'badge_section' => 'journal'],
    ['label' => 'Concours', 'href' => 'concours', 'paths' => ['concours']],
    ['label' => 'Suivi du compte', 'href' => 'pending-status', 'paths' => ['pending-status', 'verify-email']],
];

// Maintenance: keep role/status routing centralized here so mobile/desktop navs never drift apart.
$navLinks = !$isAuth ? $visitorLinks : ($isActiveAccount ? $memberLinks : $inactiveLinks);
$desktopNavLinks = !$isAuth ? $visitorDesktopLinks : ($isActiveAccount ? $memberDesktopLinks : $inactiveLinks);
$offcanvasNavLinks = !$isAuth ? $visitorOffcanvasLinks : ($isActiveAccount ? $memberOffcanvasLinks : $inactiveLinks);
$accountHomeHref = $isActiveAccount ? 'dashboard' : 'pending-status';
$accountHomeLabel = $isActiveAccount ? 'Mon espace' : 'Suivi du compte';
$mobileAccountShortcutHref = $isActiveAccount ? 'mon-profil' : $accountHomeHref;

$mobileChromeHiddenRoutes = function_exists('emsp_mobile_chrome_hidden_paths')
    ? emsp_mobile_chrome_hidden_paths()
    : [
        'register',
        'forgot-password',
        'reset-password',
        'resend-verification',
        'pending-status',
    ];
$authLandingRoutes = [
    'login',
    'register',
    'forgot-password',
    'reset-password',
    'resend-verification',
    'pending-status',
];
$showMobileSearch = $canSearchDocuments && !in_array($currentPath, $mobileChromeHiddenRoutes, true);
$showMobileBottomNav = function_exists('emsp_should_show_mobile_bottom_nav')
    ? emsp_should_show_mobile_bottom_nav()
    : false;
$hideGuestAuthButtons = in_array($currentPath, $authLandingRoutes, true);
$showGuestLoginButton = !$hideGuestAuthButtons && $currentPath !== 'login';
$showGuestRegisterButton = !$hideGuestAuthButtons && $currentPath !== 'register';
$loginModalEntryHref = $base . 'index.php?open_login=1';
$currentFeedSection = in_array($currentPath, ['journal', 'article'], true)
    ? 'journal'
    : (in_array($currentPath, ['mediatheque'], true) ? 'media' : '');

if (!function_exists('emsp_nav_active')) {
    function emsp_nav_active(array $paths, string $currentPath): string
    {
        return in_array($currentPath, $paths, true) ? 'active' : '';
    }
}

if (!function_exists('emsp_nav_badge_payload')) {
    function emsp_nav_badge_payload(?string $section, bool $isAuth, array $counts, array $latest): array
    {
        $section = strtolower(trim((string) $section));
        if (!in_array($section, ['journal', 'media'], true)) {
            return ['section' => '', 'count' => 0, 'latest' => 0, 'show' => false];
        }

        $count = $isAuth ? max(0, (int) ($counts[$section] ?? 0)) : 0;
        $latestId = max(0, (int) ($latest[$section] ?? 0));

        return [
            'section' => $section,
            'count' => $count,
            'latest' => $latestId,
            'show' => $count > 0,
        ];
    }
}

$guestBottomNav = [
    ['label' => 'Accueil', 'labelShort' => 'Accueil', 'href' => 'index.php', 'icon' => 'house-door-fill', 'paths' => ['index.php', '']],
    ['label' => 'Formations', 'labelShort' => 'Form.', 'href' => 'formations', 'icon' => 'mortarboard', 'paths' => ['formations']],
    ['label' => 'Journal', 'labelShort' => 'Journal', 'href' => 'journal', 'icon' => 'newspaper', 'paths' => ['journal', 'article'], 'badge_section' => 'journal'],
    ['label' => 'Médiathèque', 'labelShort' => 'Médias', 'href' => 'mediatheque', 'icon' => 'images', 'paths' => ['mediatheque'], 'badge_section' => 'media'],
    ['label' => 'Connexion', 'labelShort' => 'Compte', 'href' => 'index.php?open_login=1', 'icon' => 'person-circle', 'paths' => ['login', 'register'], 'open_login' => true, 'primary_action' => true],
];
$pendingBottomNav = [
    ['label' => 'Accueil', 'labelShort' => 'Accueil', 'href' => 'index.php', 'icon' => 'house-door-fill', 'paths' => ['index.php', '']],
    ['label' => 'Institution', 'labelShort' => 'Instit.', 'href' => 'institution', 'icon' => 'building', 'paths' => ['institution']],
    ['label' => 'Journal', 'labelShort' => 'Journal', 'href' => 'journal', 'icon' => 'newspaper', 'paths' => ['journal', 'article'], 'badge_section' => 'journal'],
    ['label' => 'Concours', 'labelShort' => 'Concours', 'href' => 'concours', 'icon' => 'trophy', 'paths' => ['concours']],
    ['label' => 'Suivi du compte', 'labelShort' => 'Suivi', 'href' => 'pending-status', 'icon' => 'hourglass-split', 'paths' => ['pending-status', 'verify-email', 'resend-verification']],
];
$studentBottomNav = [
    ['label' => 'Accueil', 'labelShort' => 'Accueil', 'href' => 'index.php', 'icon' => 'house-door-fill', 'paths' => ['index.php', '']],
    ['label' => 'Bibliothèque', 'labelShort' => 'Docs', 'href' => 'documents', 'icon' => 'collection', 'paths' => ['documents', 'document', 'upload', 'favoris']],
    ['label' => 'Journal', 'labelShort' => 'Journal', 'href' => 'journal', 'icon' => 'newspaper', 'paths' => ['journal', 'article'], 'badge_section' => 'journal'],
    ['label' => 'Médiathèque', 'labelShort' => 'Médias', 'href' => 'mediatheque', 'icon' => 'images', 'paths' => ['mediatheque'], 'badge_section' => 'media'],
    ['label' => 'Mon profil', 'labelShort' => 'Profil', 'href' => 'mon-profil', 'icon' => 'person-circle', 'paths' => ['mon-profil', 'historique']],
];
$staffBottomNav = [
    ['label' => 'Accueil', 'labelShort' => 'Accueil', 'href' => 'index.php', 'icon' => 'house-door-fill', 'paths' => ['index.php', '']],
    ['label' => 'Bibliothèque', 'labelShort' => 'Docs', 'href' => 'documents', 'icon' => 'collection', 'paths' => ['documents', 'document', 'upload', 'favoris']],
    ['label' => 'Journal', 'labelShort' => 'Journal', 'href' => 'journal', 'icon' => 'newspaper', 'paths' => ['journal', 'article'], 'badge_section' => 'journal'],
    ['label' => 'Médiathèque', 'labelShort' => 'Médias', 'href' => 'mediatheque', 'icon' => 'images', 'paths' => ['mediatheque'], 'badge_section' => 'media'],
    ['label' => 'Administration', 'labelShort' => 'Admin', 'href' => 'admin/journal', 'icon' => 'speedometer2', 'paths' => ['admin', 'admin/journal']],
];

$mobileBottomNavItems = !$isAuth
    ? $guestBottomNav
    : ($isActiveAccount
        ? ($canAccessAdmin ? $staffBottomNav : $studentBottomNav)
        : $pendingBottomNav);

$topbarStaticMessage = !$isAuth
    ? 'Ressources, journal, médiathèque et concours EMSP.'
    : ($isActiveAccount
        ? 'Bibliothèque, médiathèque, favoris et notifications en un clic.'
        : "Confirme ton email pour finaliser l'activation de ton espace EMSP Docs.");
$topbarStaticMessage = emsp_fix_mojibake($topbarStaticMessage);
?>

<header class="site-header emsp-app-header">
    <div class="emsp-topbar">
        <div class="emsp-container emsp-topbar-track emsp-topbar-track--static" data-emsp-topbar>
            <span class="emsp-topbar-badge">
                <i class="bi bi-mortarboard-fill" aria-hidden="true"></i>
                <?= h(emsp_fix_mojibake('Accès gratuit pour les étudiants EMSP')) ?>
            </span>
            <span class="emsp-topbar-message"><?= h($topbarStaticMessage) ?></span>
        </div>
    </div>

    <div class="emsp-navbar-wrap emsp-app-header-wrap">
        <nav class="navbar navbar-expand-xl emsp-navbar emsp-app-topbar" aria-label="En-tête application">
            <div class="container emsp-navbar-grid">
                <a class="emsp-brand emsp-navbar-zone-brand" href="<?= $base ?>index.php">
                    <img src="<?= $asset ?>images/logo-emsp.png" alt="Logo EMSP" class="emsp-brand-logo">
                    <div class="emsp-brand-label">
                        <p class="emsp-brand-title">EMSP Docs</p>
                        <p class="emsp-brand-subtitle"><?= h(emsp_fix_mojibake('Bibliothèque EMSP')) ?></p>
                    </div>
                </a>

                <div class="emsp-nav-desktop emsp-navbar-zone-nav d-none d-xl-flex order-xl-2">
                    <div class="emsp-nav-rail" role="navigation" aria-label="Navigation principale">
                    <ul class="navbar-nav flex-row flex-nowrap emsp-nav-rail__list">
                        <?php foreach ($desktopNavLinks as $link): ?>
                            <?php $linkBadge = emsp_nav_badge_payload($link['badge_section'] ?? '', $isAuth, $notifSections, $navLatestSections); ?>
                            <li class="nav-item">
                                <?php if (!empty($link['children']) && is_array($link['children'])): ?>
                                    <div class="dropdown emsp-nav-dropdown">
                                        <button class="nav-link dropdown-toggle emsp-nav-dropdown-toggle <?= emsp_nav_active($link['paths'], $currentPath) ?>" type="button" data-bs-toggle="dropdown" data-bs-display="static" data-bs-auto-close="true" aria-expanded="false">
                                            <span class="emsp-nav-link-label"><?= h($link['label']) ?></span>
                                        </button>
                                        <ul class="dropdown-menu emsp-nav-dropdown-menu">
                                            <?php foreach ($link['children'] as $child): ?>
                                                <li>
                                                    <a class="dropdown-item <?= emsp_nav_active($child['paths'], $currentPath) ?>" href="<?= $base . $child['href'] ?>">
                                                        <?= h($child['label']) ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php else: ?>
                                    <a class="nav-link <?= emsp_nav_active($link['paths'], $currentPath) ?>" href="<?= $base . $link['href'] ?>">
                                        <span class="emsp-nav-link-label"><?= h($link['label']) ?></span>
                                        <?php if ($linkBadge['section'] !== ''): ?>
                                            <?php // Public badges signal fresh content only; personal unread counts stay reserved for authenticated notification UI. ?>
                                            <span
                                                class="emsp-nav-link-badge<?= $linkBadge['show'] ? '' : ' is-hidden' ?>"
                                                data-emsp-section-badge
                                                data-section="<?= h($linkBadge['section']) ?>"
                                                data-latest-id="<?= (int) $linkBadge['latest'] ?>"
                                                data-authenticated="<?= $isAuth ? '1' : '0' ?>"
                                            >
                                                <span class="emsp-nav-link-badge__dot"></span>
                                                <span class="emsp-nav-link-badge__count"><?= $linkBadge['count'] > 99 ? '99+' : $linkBadge['count'] ?></span>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    </div>
                </div>

                <div class="emsp-nav-quick emsp-navbar-zone-actions order-xl-3">
                    <div class="emsp-navbar-actions-desktop">
                    <?php if (!$isAuth): ?>
                        <?php if ($showGuestLoginButton): ?>
                            <a class="btn btn-sm emsp-btn-outline emsp-open-login-modal"
                               href="<?= h($loginModalEntryHref) ?>"
                               data-bs-toggle="modal"
                               data-bs-target="#emspQuickLoginModal"
                               data-emsp-modal-link="1">
                                <span class="emsp-btn-label-full">Connexion</span>
                                <span class="emsp-btn-label-compact">Connexion</span>
                            </a>
                        <?php endif; ?>
                        <?php if ($showGuestRegisterButton): ?>
                            <a class="btn btn-sm emsp-btn-signup" href="<?= $base ?>register">
                                <span class="emsp-btn-label-full">S'inscrire</span>
                                <span class="emsp-btn-label-compact">Inscription</span>
                            </a>
                        <?php endif; ?>
                        <?php if (!$hideGuestAuthButtons): ?>
                            <?php if ($currentPath !== 'login'): ?>
                                <a class="btn btn-sm emsp-icon-btn emsp-mobile-auth-shortcut d-none emsp-open-login-modal"
                                   href="<?= h($loginModalEntryHref) ?>"
                                   data-bs-toggle="modal"
                                   data-bs-target="#emspQuickLoginModal"
                                   data-emsp-modal-link="1"
                                   aria-label="Connexion rapide"
                                   title="Connexion">
                                    <i class="bi bi-box-arrow-in-right"></i>
                                </a>
                            <?php endif; ?>
                            <?php if ($currentPath !== 'register'): ?>
                                <a class="btn btn-sm emsp-icon-btn emsp-mobile-auth-shortcut emsp-mobile-register-shortcut d-none"
                                   href="<?= $base ?>register"
                                   aria-label="Inscription rapide"
                                   title="Inscription">
                                    <i class="bi bi-person-plus-fill"></i>
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="emsp-navbar-actions-group emsp-navbar-actions-group--tools">
                        <?php if ($canAccessAdmin): ?>
                            <a class="btn btn-sm emsp-btn-admin emsp-admin-shortcut" href="<?= $base ?>admin/journal">
                                <i class="bi bi-speedometer2 emsp-btn-icon" aria-hidden="true"></i>
                                <span class="emsp-btn-label-full">Administration</span>
                                <span class="emsp-btn-label-compact">Admin</span>
                            </a>
                        <?php endif; ?>
                        <?php if ($isActiveAccount): ?>
                            <a
                                class="btn btn-sm emsp-mobile-account-shortcut d-none"
                                href="<?= $base . $mobileAccountShortcutHref ?>"
                                aria-label="Ouvrir mon profil"
                                title="Ouvrir mon profil"
                            >
                                <?php if ($photoSrc !== ''): ?>
                                    <img src="<?= h($photoSrc) ?>" alt="Profil" class="emsp-avatar emsp-mobile-account-shortcut__avatar">
                                <?php else: ?>
                                    <span class="emsp-avatar-fallback emsp-mobile-account-shortcut__avatar-fallback"><?= h($initials) ?></span>
                                <?php endif; ?>
                                <span class="emsp-mobile-account-shortcut__copy">
                                    <span class="emsp-mobile-account-shortcut__name"><?= h($userLabel !== '' ? $userLabel : 'Mon compte') ?></span>
                                    <span class="emsp-mobile-account-shortcut__status"><?= h($statusLabel) ?></span>
                                </span>
                                <i class="bi bi-chevron-right emsp-mobile-account-shortcut__chevron" aria-hidden="true"></i>
                            </a>
                            <a class="btn btn-sm emsp-icon-btn emsp-navbar-notifications d-none d-xl-inline-flex" href="<?= $base ?>dashboard#notifications" data-emsp-notif-trigger data-emsp-notif-label="Notifications" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Notifications<?= $notifCount > 0 ? ' (' . ($notifCount > 99 ? '99+' : $notifCount) . ' non lues)' : '' ?>" aria-label="Notifications<?= $notifCount > 0 ? ' (' . ($notifCount > 99 ? '99+' : $notifCount) . ' non lues)' : '' ?>">
                                <i class="bi bi-bell-fill"></i>
                                <?php if ($notifCount > 0): ?>
                                    <span class="emsp-navbar-notifications__badge"><?= $notifCount > 99 ? '99+' : $notifCount ?></span>
                                <?php endif; ?>
                            </a>
                        <?php else: ?>
                            <a class="btn btn-sm emsp-btn-outline" href="<?= $base ?>pending-status">
                                <i class="bi bi-hourglass-split me-1"></i>Suivi du compte
                            </a>
                        <?php endif; ?>
                        </div>

                        <?php if ($isActiveAccount): ?>
                        <span class="emsp-navbar-actions-divider" aria-hidden="true"></span>
                        <div class="emsp-navbar-actions-group emsp-navbar-actions-group--account">
                            <a class="btn btn-sm emsp-btn-deposit" href="<?= $base ?>upload" title="Déposer un document">
                                <i class="bi bi-cloud-arrow-up-fill emsp-btn-icon" aria-hidden="true"></i>
                                <span class="emsp-btn-label-full">Déposer un document</span>
                                <span class="emsp-btn-label-compact">Déposer</span>
                            </a>
                        <?php else: ?>
                        <span class="emsp-navbar-actions-divider" aria-hidden="true"></span>
                        <div class="emsp-navbar-actions-group emsp-navbar-actions-group--account">
                        <?php endif; ?>

                        <div class="dropdown emsp-navbar-account-menu">
                            <button class="btn emsp-avatar-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu compte">
                                <?php if ($photoSrc !== ''): ?>
                                    <img src="<?= h($photoSrc) ?>" alt="Profil" class="emsp-avatar">
                                <?php else: ?>
                                    <span class="emsp-avatar-fallback"><?= h($initials) ?></span>
                                <?php endif; ?>
                                <span class="text-start emsp-avatar-copy">
                                    <span class="d-block lh-1 emsp-avatar-name"><?= h($userLabel !== '' ? $userLabel : 'Mon compte') ?></span>
                                    <small class="fw-semibold emsp-avatar-status<?= $isBadgeLevelStatus ? ' emsp-user-badge-pill' : '' ?>"><?= h($statusLabel) ?></small>
                                </span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end emsp-navbar-account-dropdown shadow-sm border-0 p-2">
                                <li><a class="dropdown-item rounded-3" href="<?= $base . $accountHomeHref ?>"><i class="bi bi-grid me-2"></i><?= h($accountHomeLabel) ?></a></li>
                                <li><a class="dropdown-item rounded-3" href="<?= $base ?>mon-profil"><i class="bi bi-person me-2"></i>Mon profil</a></li>
                                <?php if ($isActiveAccount): ?>
                                    <li><a class="dropdown-item rounded-3" href="<?= $base ?>favoris"><i class="bi bi-star me-2"></i>Mes favoris</a></li>
                                    <li><a class="dropdown-item rounded-3" href="<?= $base ?>historique"><i class="bi bi-clock-history me-2"></i>Historique</a></li>
                                    <li><a class="dropdown-item rounded-3" href="<?= $base ?>upload"><i class="bi bi-cloud-arrow-up me-2"></i>Déposer un document</a></li>
                                <?php endif; ?>
                                <?php if ($canAccessAdmin): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item rounded-3 fw-semibold" href="<?= $base ?>admin/journal"><i class="bi bi-speedometer2 me-2"></i>Administration</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="post" action="<?= $base ?>logout" class="m-0">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="dropdown-item rounded-3" data-confirm="Te déconnecter ?" data-confirm-detail="Tu reviendras à l'accueil public de la plateforme." data-confirm-type="warning" data-confirm-ok="Oui, me déconnecter" data-emsp-confirm-auto="1">
                                            <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                        </div>
                    <?php endif; ?>
                    </div>

                    <?php if ($isActiveAccount): ?>
                        <a
                            class="btn btn-sm emsp-icon-btn emsp-navbar-notifications emsp-navbar-notifications--mobile d-xl-none"
                            href="<?= $base ?>dashboard#notifications"
                            data-emsp-notif-trigger
                            data-emsp-notif-label="Notifications"
                            aria-label="Notifications<?= $notifCount > 0 ? ' (' . ($notifCount > 99 ? '99+' : $notifCount) . ' non lues)' : '' ?>"
                            title="Notifications<?= $notifCount > 0 ? ' (' . ($notifCount > 99 ? '99+' : $notifCount) . ' non lues)' : '' ?>"
                        >
                            <i class="bi bi-bell-fill" aria-hidden="true"></i>
                            <?php if ($notifCount > 0): ?>
                                <span class="emsp-navbar-notifications__badge"><?= $notifCount > 99 ? '99+' : $notifCount ?></span>
                            <?php endif; ?>
                        </a>
                    <?php elseif (!$isAuth): ?>
                        <a
                            class="btn btn-sm emsp-icon-btn emsp-navbar-notifications emsp-navbar-notifications--mobile emsp-navbar-notifications--guest d-xl-none"
                            href="<?= $base ?>login"
                            data-emsp-notif-trigger
                            data-emsp-notif-label="Alertes"
                            aria-label="Connectez-vous pour voir vos alertes"
                            title="Connectez-vous pour voir vos alertes"
                        >
                            <i class="bi bi-bell" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>

                    <button class="navbar-toggler emsp-navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#emspMainOffcanvas" aria-controls="emspMainOffcanvas" aria-label="Ouvrir le menu principal" aria-expanded="false">
                        <i class="bi bi-list emsp-navbar-toggler__icon" aria-hidden="true"></i>
                        <i class="bi bi-x-lg emsp-navbar-toggler__icon emsp-navbar-toggler__icon--close" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </nav>

        <?php if ($showBreadcrumb): ?>
            <div class="emsp-nav-breadcrumb">
                <div class="emsp-container">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?= $base ?>index.php">Accueil</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?= h($breadcrumbLabel) ?></li>
                        </ol>
                    </nav>
                </div>
            </div>
        <?php endif; ?>
    </div>
</header>

<div class="offcanvas offcanvas-start emsp-navbar-offcanvas" tabindex="-1" id="emspMainOffcanvas" aria-labelledby="emspMainOffcanvasLabel">
    <div class="offcanvas-header">
        <div class="emsp-offcanvas-brand" id="emspMainOffcanvasLabel">
            <img src="<?= $asset ?>images/logo-emsp.png" alt="Logo EMSP">
            <div>
                <strong>EMSP Docs</strong>
                <small>Navigation et acces rapides</small>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fermer le menu"></button>
    </div>
    <div class="offcanvas-body">
        <div class="emsp-offcanvas-nav">
            <?php foreach ($offcanvasNavLinks as $index => $link): ?>
                <?php $linkBadge = emsp_nav_badge_payload($link['badge_section'] ?? '', $isAuth, $notifSections, $navLatestSections); ?>
                <?php if (!empty($link['children']) && is_array($link['children'])): ?>
                    <?php
                    $collapseId = 'emspOffcanvasSubnav' . $index;
                    $isChildOpen = emsp_nav_active($link['paths'], $currentPath) !== '';
                    ?>
                    <button
                        class="emsp-offcanvas-accordion-toggle<?= $isChildOpen ? ' is-open' : '' ?>"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#<?= h($collapseId) ?>"
                        aria-controls="<?= h($collapseId) ?>"
                        aria-expanded="<?= $isChildOpen ? 'true' : 'false' ?>"
                    >
                        <span><?= h($link['label']) ?></span>
                        <i class="bi bi-chevron-down emsp-offcanvas-chevron" aria-hidden="true"></i>
                    </button>
                    <div id="<?= h($collapseId) ?>" class="collapse emsp-offcanvas-accordion-panel<?= $isChildOpen ? ' show' : '' ?>" role="region" aria-label="<?= h($link['label']) ?>">
                        <?php foreach ($link['children'] as $child): ?>
                            <a class="nav-link emsp-offcanvas-subnav-link <?= emsp_nav_active($child['paths'], $currentPath) ?>" href="<?= $base . $child['href'] ?>">
                                <span class="emsp-nav-link-label"><?= h($child['label']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <a class="nav-link <?= emsp_nav_active($link['paths'], $currentPath) ?>" href="<?= $base . $link['href'] ?>">
                        <span class="emsp-nav-link-label"><?= h($link['label']) ?></span>
                        <?php if ($linkBadge['section'] !== ''): ?>
                            <span
                                class="emsp-nav-link-badge<?= $linkBadge['show'] ? '' : ' is-hidden' ?>"
                                data-emsp-section-badge
                                data-section="<?= h($linkBadge['section']) ?>"
                                data-latest-id="<?= (int) $linkBadge['latest'] ?>"
                                data-authenticated="<?= $isAuth ? '1' : '0' ?>"
                            >
                                <span class="emsp-nav-link-badge__dot"></span>
                                <span class="emsp-nav-link-badge__count"><?= $linkBadge['count'] > 99 ? '99+' : $linkBadge['count'] ?></span>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="emsp-offcanvas-meta">
            <?php if (!$isAuth): ?>
                <div class="emsp-mobile-card">
                    <div class="fw-bold mb-2">Commence ici</div>
                    <p class="small text-muted mb-3">Cree ton compte ou connecte-toi pour acceder a tes espaces reserves et suivre l'actualite EMSP.</p>
                    <div class="d-grid gap-2">
                        <a class="btn emsp-btn-signup" href="<?= $base ?>register">S'inscrire</a>
                    </div>
                    <a class="d-inline-flex align-items-center gap-2 small fw-semibold text-decoration-none mt-3 emsp-open-login-modal emsp-offcanvas-login-link"
                       href="<?= h($loginModalEntryHref) ?>"
                       data-bs-toggle="modal"
                       data-bs-target="#emspQuickLoginModal"
                       data-emsp-modal-link="1">
                        <i class="bi bi-box-arrow-in-right"></i>Deja inscrit ? Se connecter
                    </a>
                </div>
            <?php else: ?>
                <div class="emsp-mobile-account">
                    <div class="emsp-mobile-card">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <?php if ($photoSrc !== ''): ?>
                                <img src="<?= h($photoSrc) ?>" alt="Profil" class="emsp-avatar">
                            <?php else: ?>
                                <span class="emsp-avatar-fallback"><?= h($initials) ?></span>
                            <?php endif; ?>
                            <div>
                                <div class="fw-bold emsp-offcanvas-account-name"><?= h($userLabel !== '' ? $userLabel : 'Mon compte') ?></div>
                                <div class="small text-muted"><?= h($statusLabel) ?></div>
                            </div>
                        </div>
                        <div class="emsp-mobile-links">
                            <a href="<?= $base . $accountHomeHref ?>"><i class="bi bi-house-door"></i><?= h($accountHomeLabel) ?></a>
                            <a href="<?= $base ?>mon-profil"><i class="bi bi-person"></i>Mon profil</a>
                            <?php if ($isActiveAccount): ?>
                                <a href="<?= $base ?>favoris"><i class="bi bi-star"></i>Mes favoris</a>
                                <a href="<?= $base ?>historique"><i class="bi bi-clock-history"></i>Historique</a>
                                <a href="<?= $base ?>upload"><i class="bi bi-cloud-arrow-up"></i>Déposer un document</a>
                                <a href="<?= $base ?>dashboard#notifications"><i class="bi bi-bell"></i>Notifications<?= $notifCount > 0 ? ' (' . ($notifCount > 99 ? '99+' : $notifCount) . ')' : '' ?></a>
                            <?php endif; ?>
                            <?php if ($canAccessAdmin): ?>
                                <a href="<?= $base ?>admin/journal"><i class="bi bi-speedometer2"></i>Administration</a>
                            <?php endif; ?>
                            <form method="post" action="<?= $base ?>logout" class="m-0">
                                <?= csrf_field() ?>
                                <button type="submit" class="border-0 bg-transparent p-0 text-inherit w-100 text-start" data-confirm="Te déconnecter ?" data-confirm-detail="Tu reviendras à l'accueil public de la plateforme." data-confirm-type="warning" data-confirm-ok="Oui, me déconnecter" data-emsp-confirm-auto="1">
                                    <i class="bi bi-box-arrow-right"></i>Déconnexion
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($showMobileBottomNav): ?>
<style>
@media (max-width:767.98px){
  body.emsp-mobile-bottom-nav-enabled .emsp-mobile-bottom-nav.emsp-mobile-bottom-nav--dock,
  .emsp-mobile-bottom-nav.emsp-mobile-bottom-nav--dock{
    display:flex!important;
    visibility:visible!important;
    opacity:1!important;
    position:fixed!important;
    bottom:0;left:0;right:0;width:100%;
    z-index:9999!important;
    transform:none!important;
    pointer-events:auto!important;
  }
}
@media (min-width:768px){
  .emsp-mobile-bottom-nav.emsp-mobile-bottom-nav--dock{display:none!important;}
}
</style>
<nav class="emsp-mobile-bottom-nav emsp-mobile-bottom-nav--dock" data-emsp-bottom-nav="1" aria-label="Navigation mobile rapide">
    <div class="container emsp-mobile-bottom-nav__inner">
        <?php foreach ($mobileBottomNavItems as $item): ?>
            <?php
            $isActive = emsp_nav_active($item['paths'], $currentPath) !== '';
            if (!$isActive && ($item['href'] ?? '') === 'admin/journal' && str_starts_with($currentPath, 'admin')) {
                $isActive = true;
            }
            $bottomNavLabel = (string) ($item['label'] ?? '');
            $bottomNavShortLabel = (string) ($item['labelShort'] ?? $bottomNavLabel);
            $itemBadge = emsp_nav_badge_payload($item['badge_section'] ?? '', $isAuth, $notifSections, $navLatestSections);
            $isLoginShortcut = !empty($item['open_login']) && !$isAuth;
            $itemHref = $base . $item['href'];
            if ($isLoginShortcut) {
                $itemHref = $loginModalEntryHref;
            }
            ?>
            <a
                class="emsp-mobile-bottom-nav__item<?= $isActive ? ' active' : '' ?><?= !empty($item['primary_action']) ? ' is-primary-action' : '' ?><?= $isLoginShortcut ? ' emsp-open-login-modal' : '' ?>"
                href="<?= h($itemHref) ?>"
                <?php if ($isLoginShortcut): ?>
                    data-bs-toggle="modal"
                    data-bs-target="#emspQuickLoginModal"
                    data-emsp-modal-link="1"
                <?php endif; ?>
                aria-label="<?= h($bottomNavLabel) ?>"
                aria-current="<?= $isActive ? 'page' : 'false' ?>"
                title="<?= h($bottomNavLabel) ?>"
            >
                <span class="emsp-mobile-bottom-nav__icon-wrap">
                    <i class="bi bi-<?= h($item['icon']) ?>" aria-hidden="true"></i>
                    <?php if ($itemBadge['show']): ?>
                        <span class="emsp-mobile-bottom-nav__badge emsp-mobile-bottom-nav__section-badge" aria-hidden="true"></span>
                    <?php endif; ?>
                </span>
                <span class="emsp-mobile-bottom-nav__label"><?= h($bottomNavShortLabel) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</nav>
<?php endif; ?>

<?php require __DIR__ . '/partials/mobile-fab-deposit.php'; ?>
<?php require __DIR__ . '/partials/mobile-fab-guest.php'; ?>

<?php if (!$isAuth): ?>
<div class="modal fade emsp-login-modal" id="emspQuickLoginModal" tabindex="-1" aria-labelledby="emspQuickLoginModalLabel" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <button type="button" class="btn-close emsp-login-modal-close emsp-modal-close-touch" data-bs-dismiss="modal" aria-label="Fermer"></button>
            <div class="emsp-login-modal-scene">
                <div class="emsp-login-modal-card">
                    <div class="emsp-login-modal-card-head">
                        <span class="emsp-login-modal-kicker">Bienvenue</span>
                        <h2 id="emspQuickLoginModalLabel">Se connecter</h2>
                        <p>Accede rapidement a ton espace EMSP Docs.</p>
                    </div>
                    <div class="emsp-login-modal-card-brand">
                        <img src="<?= $asset ?>images/logo-emsp.png" alt="Logo EMSP" loading="lazy">
                    </div>
                    <div class="emsp-login-modal-form-wrap">
                        <form action="<?= $base ?>login" method="post" class="emsp-login-modal-form" data-emsp-submit="1">
                        <?php csrf_input(); ?>
                        <label for="emspQuickLoginEmail" class="form-label">Email</label>
                        <input type="email" id="emspQuickLoginEmail" name="email" class="form-control" required autocomplete="email" placeholder="nom@ecole.com">

                        <label for="emspQuickLoginPassword" class="form-label">Mot de passe</label>
                        <div class="emsp-password-wrap">
                            <input type="password" id="emspQuickLoginPassword" name="password" class="form-control" required autocomplete="current-password" placeholder="Mot de passe">
                            <button class="emsp-password-toggle" type="button" id="toggle-quick-login-password" aria-label="Afficher le mot de passe" aria-pressed="false">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>

                        <button class="btn btn-emsp w-100 mt-3" type="submit" data-loading-text="Connexion en cours...">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
                        </button>
                        </form>
                        <div class="emsp-login-modal-links">
                            <a href="<?= $base ?>forgot-password">Mot de passe oublie ?</a>
                            <a href="<?= $base ?>register">Creer un compte</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
(function () {
    var offcanvas = document.getElementById('emspMainOffcanvas');
    var navbarToggler = document.querySelector('.emsp-navbar-toggler');
    var mobileAccountShortcut = document.querySelector('.emsp-mobile-account-shortcut');
    var mobileBottomNav = document.querySelector('.emsp-mobile-bottom-nav');
    var offcanvasInstance = null;

    if (mobileBottomNav && document.body && !document.body.classList.contains('emsp-mobile-bottom-nav-enabled')) {
        document.body.classList.add('emsp-mobile-bottom-nav-enabled');
    }

    function getOffcanvasInstance() {
        if (!offcanvas || !window.bootstrap || !bootstrap.Offcanvas) {
            return null;
        }
        if (!offcanvasInstance) {
            offcanvasInstance = bootstrap.Offcanvas.getOrCreateInstance(offcanvas);
        }
        return offcanvasInstance;
    }

    function syncHamburgerState(isOpen) {
        if (!navbarToggler) {
            return;
        }
        navbarToggler.classList.toggle('is-open', !!isOpen);
        navbarToggler.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        navbarToggler.setAttribute('aria-label', isOpen ? 'Fermer le menu principal' : 'Ouvrir le menu principal');
    }

    if (offcanvas) {
        offcanvas.addEventListener('show.bs.offcanvas', function () {
            syncHamburgerState(true);
        });
        offcanvas.addEventListener('shown.bs.offcanvas', function () {
            syncHamburgerState(true);
        });
        offcanvas.addEventListener('hide.bs.offcanvas', function () {
            syncHamburgerState(false);
        });
        offcanvas.addEventListener('hidden.bs.offcanvas', function () {
            syncHamburgerState(false);
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
            document.querySelectorAll('.offcanvas-backdrop').forEach(function (node) {
                if (!document.querySelector('.offcanvas.show, .modal.show')) {
                    node.remove();
                }
            });
        });
    }

    function bindOffcanvasTrigger(trigger) {
        if (!trigger) {
            return;
        }
        trigger.addEventListener('click', function (event) {
            var instance = getOffcanvasInstance();
            if (!instance) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            if (offcanvas.classList.contains('show')) {
                instance.hide();
            } else {
                instance.show();
            }
        });
    }

    bindOffcanvasTrigger(navbarToggler);

    document.querySelectorAll('.emsp-offcanvas-accordion-toggle').forEach(function (toggle) {
        var targetId = toggle.getAttribute('data-bs-target');
        var panel = targetId ? document.querySelector(targetId) : null;
        if (!panel) {
            return;
        }

        panel.addEventListener('show.bs.collapse', function () {
            toggle.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
        });

        panel.addEventListener('hide.bs.collapse', function () {
            toggle.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        });
    });

    var badges = document.querySelectorAll('[data-emsp-section-badge]');
    if (badges.length && window.localStorage) {
        var isAuthenticated = <?= $isAuth ? 'true' : 'false' ?>;
        var latestSections = <?= json_encode($navLatestSections, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var currentSection = <?= json_encode($currentFeedSection, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

        function storageKey(section) {
            return 'emsp-section-last-seen:' + section;
        }

        function updateBadge(badge, count) {
            var countNode = badge.querySelector('.emsp-nav-link-badge__count');
            if (countNode) {
                countNode.textContent = count > 99 ? '99+' : String(count);
            }
            badge.classList.toggle('is-hidden', count <= 0);
        }

        if (!isAuthenticated && currentSection && latestSections[currentSection]) {
            try {
                window.localStorage.setItem(storageKey(currentSection), String(latestSections[currentSection]));
            } catch (error) {
                // Ignore quota/storage failures and keep navigation usable.
            }
        }

        badges.forEach(function (badge) {
            if (badge.getAttribute('data-authenticated') === '1') {
                return;
            }
            var section = badge.getAttribute('data-section') || '';
            var latestId = parseInt(badge.getAttribute('data-latest-id') || '0', 10);
            var lastSeen = 0;
            try {
                lastSeen = parseInt(window.localStorage.getItem(storageKey(section)) || '0', 10);
            } catch (error) {
                lastSeen = 0;
            }
            updateBadge(badge, latestId > lastSeen ? 1 : 0);
        });
    }

    document.querySelectorAll('[data-emsp-modal-link="1"]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            if (!window.bootstrap || !bootstrap.Modal) {
                return;
            }
            var targetSelector = link.getAttribute('data-bs-target');
            if (!targetSelector) {
                return;
            }
            var modalElement = document.querySelector(targetSelector);
            if (!modalElement) {
                return;
            }
            event.preventDefault();
            bootstrap.Modal.getOrCreateInstance(modalElement).show();
        });
    });

    var quickLoginModal = document.getElementById('emspQuickLoginModal');
    function tryOpenQuickLoginFromUrl() {
        if (!quickLoginModal || !window.bootstrap || !bootstrap.Modal) {
            return false;
        }
        var params = new URLSearchParams(window.location.search || '');
        var shouldOpenLoginModal = params.get('open_login') === '1' || params.get('login') === '1';
        if (shouldOpenLoginModal) {
            bootstrap.Modal.getOrCreateInstance(quickLoginModal).show();
            params.delete('open_login');
            params.delete('login');
            var nextQuery = params.toString();
            var nextUrl = window.location.pathname + (nextQuery ? ('?' + nextQuery) : '') + window.location.hash;
            if (window.history && typeof window.history.replaceState === 'function') {
                window.history.replaceState({}, document.title, nextUrl);
            }
        }
        return true;
    }
    if (quickLoginModal && !tryOpenQuickLoginFromUrl()) {
        window.addEventListener('load', tryOpenQuickLoginFromUrl, { once: true });
    }

    if (offcanvas) {
        offcanvas.querySelectorAll('a[href]').forEach(function (link) {
            link.addEventListener('click', function () {
                var instance = getOffcanvasInstance();
                if (instance) {
                    instance.hide();
                }
            });
        });
    }

    var quickPasswordInput = document.getElementById('emspQuickLoginPassword');
    var quickPasswordToggle = document.getElementById('toggle-quick-login-password');
    if (quickPasswordInput && quickPasswordToggle) {
        quickPasswordToggle.addEventListener('click', function () {
            var reveal = quickPasswordInput.type === 'password';
            quickPasswordInput.type = reveal ? 'text' : 'password';
            quickPasswordToggle.setAttribute('aria-pressed', reveal ? 'true' : 'false');
            quickPasswordToggle.setAttribute('aria-label', reveal ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
            quickPasswordToggle.innerHTML = reveal ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
        });
    }
})();
</script>
