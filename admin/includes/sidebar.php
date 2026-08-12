<?php
include_once __DIR__ . '/../bootstrap.php';
include_once __DIR__ . '/../../includes/helpers.php';

$auth_user = $_SESSION['auth_user'] ?? [];
$auth_role = strtolower((string) ($_SESSION['auth_role'] ?? ''));
$is_admin = $auth_role === 'admin';
$role_label = $is_admin ? 'Administrateur' : 'Moderateur';
$initials = strtoupper(mb_substr((string) ($auth_user['first_name'] ?? 'A'), 0, 1) . mb_substr((string) ($auth_user['last_name'] ?? ''), 0, 1));
$photo_src = '';
if (!empty($auth_user['photo_path'])) {
    $photo_src = emsp_user_photo_src((string) $auth_user['photo_path']);
    if ($photo_src !== '' && !preg_match('#^https?://#i', $photo_src)) {
        $photo_src = '../' . ltrim($photo_src, '/');
    }
}

$current = basename((string) ($_SERVER['PHP_SELF'] ?? ''));
// Utilise le compteur partagé chargé une seule fois dans bootstrap.php (fix anti-doublons)
$pending_docs  = (int) ($GLOBALS['emsp_admin_pending']['docs']  ?? 0);
$pending_users = (int) ($GLOBALS['emsp_admin_pending']['users'] ?? 0);

$nav_sections = [
    [
        'label' => 'Principal',
        'items' => [
            ['href' => 'index.php', 'icon' => 'speedometer2', 'label' => 'Dashboard'],
        ],
    ],
    [
        'label' => 'Documents',
        'items' => [
            ['href' => 'pending-documents.php', 'icon' => 'check2-square', 'label' => 'Validation docs', 'badge' => $pending_docs],
            ['href' => 'view-documents.php', 'icon' => 'folder2-open', 'label' => 'Tous les documents'],
        ],
    ],
    [
        'label' => 'Utilisateurs',
        'items' => [
            ['href' => 'pending-users.php', 'icon' => 'person-check', 'label' => 'Validation comptes', 'badge' => $pending_users],
            ['href' => 'view-users.php', 'icon' => 'people', 'label' => 'Tous les utilisateurs'],
            ['href' => 'badge-or-batch.php', 'icon' => 'trophy', 'label' => 'Badge Or', 'only_admin' => true],
        ],
    ],
    [
        'label' => 'Contenu',
        'items' => [
            ['href' => 'journal.php', 'icon' => 'newspaper', 'label' => 'Journal / News'],
            ['href' => 'mediatheque.php', 'icon' => 'collection-play', 'label' => 'Médiathèque'],
            ['href' => 'media-categories.php', 'icon' => 'tags', 'label' => 'Categories medias'],
            ['href' => 'comments-moderation.php', 'icon' => 'chat-dots', 'label' => 'Commentaires'],
            ['href' => 'edit-institution.php', 'icon' => 'building', 'label' => 'Institution'],
        ],
    ],
    [
        'label' => 'Referentiels',
        'items' => [
            ['href' => 'view-filieres.php', 'icon' => 'diagram-3', 'label' => 'Filieres'],
            ['href' => 'view-licences.php', 'icon' => 'layers', 'label' => 'Niveaux / Licences'],
            ['href' => 'view-modules.php', 'icon' => 'grid', 'label' => 'Modules'],
            ['href' => 'view-matieres.php', 'icon' => 'book', 'label' => 'Matieres'],
        ],
    ],
    [
        'label' => 'Systeme',
        'items' => [
            ['href' => 'school-domains.php', 'icon' => 'envelope-at', 'label' => 'Domaines email', 'only_admin' => true],
            ['href' => 'stats.php', 'icon' => 'graph-up', 'label' => 'Statistiques'],
            ['href' => 'settings.php', 'icon' => 'gear', 'label' => 'Parametres', 'only_admin' => true],
        ],
    ],
];
?>
<aside class="sidebar sidebar-fixed emsp-slim-sidebar" id="miniSidebar">
  <div class="sidebar-header border-bottom">
    <div class="sidebar-brand">
      <img src="../assets/images/logo-emsp.png" alt="Logo EMSP" height="32" class="sidebar-brand-full bg-white rounded p-1">
      <span class="sidebar-brand-full ms-2 fs-5 fw-bold">EMSP Admin</span>
      <img src="../assets/images/logo-emsp.png" alt="" height="28" class="sidebar-brand-narrow bg-white rounded p-1">
    </div>
    <button class="btn-close d-lg-none" type="button" data-coreui-theme="dark" aria-label="Fermer le menu"></button>
  </div>

  <div class="admin-user-info d-flex align-items-center gap-3 px-3 py-3 border-bottom border-white border-opacity-10">
    <?php if ($photo_src): ?>
      <img src="<?= h($photo_src) ?>" alt="" class="rounded-circle" width="40" height="40" style="object-fit:cover;">
    <?php else: ?>
      <div class="admin-avatar-circle"><?= h($initials) ?></div>
    <?php endif; ?>
    <div class="overflow-hidden flex-grow-1">
      <div class="text-white fw-semibold text-truncate" style="font-size:.92rem;">
        <?= h(trim((string) (($auth_user['first_name'] ?? '') . ' ' . ($auth_user['last_name'] ?? '')))) ?>
      </div>
      <div class="text-white-50 small text-truncate"><?= h($role_label) ?></div>
    </div>
  </div>

  <ul class="sidebar-nav" data-coreui="navigation" data-simplebar>
    <?php foreach ($nav_sections as $section): ?>
      <li class="nav-title"><?= h((string) $section['label']) ?></li>
      <?php foreach ($section['items'] as $item): ?>
        <?php
        if (!empty($item['only_admin']) && !$is_admin) continue;
        $isActive = ($item['href'] ?? '') === $current;
        $badge = max(0, (int) ($item['badge'] ?? 0));
        ?>
        <li class="nav-item">
          <a class="nav-link<?= $isActive ? ' active' : '' ?>" href="<?= h((string) ($item['href'] ?? '#')) ?>">
            <i class="nav-icon bi bi-<?= h((string) ($item['icon'] ?? 'circle')) ?>"></i>
            <span class="nav-label"><?= h((string) ($item['label'] ?? '')) ?></span>
            <?php if ($badge > 0): ?>
              <span class="badge badge-sm bg-danger ms-auto"><?= $badge > 99 ? '99+' : $badge ?></span>
            <?php endif; ?>
          </a>
        </li>
      <?php endforeach; ?>
    <?php endforeach; ?>

    <li class="nav-title">Session</li>
    <li class="nav-item">
      <form method="post" action="../logout" class="m-0">
        <?= csrf_input(); ?>
        <button type="submit" class="nav-link text-warning border-0 bg-transparent w-100 text-start" data-confirm="Te deconnecter ?" data-confirm-detail="Tu reviendras a l'accueil public de la plateforme." data-confirm-type="warning" data-confirm-ok="Oui, me deconnecter" data-emsp-confirm-auto="1">
          <i class="nav-icon bi bi-box-arrow-right"></i>
          <span class="nav-label">Deconnexion</span>
        </button>
      </form>
    </li>
    <li class="nav-item emsp-install-nav-item d-none">
      <button type="button" class="nav-link w-100 text-start emsp-install-app-btn" id="emspInstallAppBtn">
        <i class="nav-icon bi bi-download"></i>
        <span class="nav-label">Installer l'app</span>
      </button>
    </li>
  </ul>

</aside>

<main class="wrapper d-flex flex-column min-vh-100 bg-body" role="main">
