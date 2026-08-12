<?php
include_once __DIR__ . '/../bootstrap.php';
include_once __DIR__ . '/../../includes/helpers.php';

$auth_user = $_SESSION['auth_user'] ?? [];
$is_admin = strtolower((string) ($_SESSION['auth_role'] ?? '')) === 'admin';
$role_label = $is_admin ? 'Administrateur' : 'Moderateur';
$page_title = $page_title ?? '';
// Utilise le compteur partagé chargé une seule fois dans bootstrap.php (fix anti-doublons)
$pending_docs  = (int) ($GLOBALS['emsp_admin_pending']['docs']  ?? 0);
$pending_users = (int) ($GLOBALS['emsp_admin_pending']['users'] ?? 0);

$initials = strtoupper(mb_substr($auth_user['first_name'] ?? 'A', 0, 1) . mb_substr($auth_user['last_name'] ?? '', 0, 1));
$photo_src = '';
if (!empty($auth_user['photo_path'])) {
    $photo_src = emsp_user_photo_src((string) $auth_user['photo_path']);
    if ($photo_src !== '' && !preg_match('#^https?://#i', $photo_src)) {
        $photo_src = '../' . ltrim($photo_src, '/');
    }
}
?>
<header class="header header-sticky navbar-glass p-0 mb-4">
  <div class="container-fluid border-bottom px-4 d-flex align-items-center">
    <button class="header-toggler d-lg-none" type="button" aria-label="Menu">
      <i class="bi bi-grid-fill fs-2"></i>
    </button>

    <nav class="d-none d-md-flex ms-3" aria-label="breadcrumb">
      <ol class="breadcrumb my-0">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-body-secondary">Admin</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= h($page_title ?: 'Dashboard') ?></li>
      </ol>
    </nav>

    <ul class="header-nav ms-auto gap-1 align-items-center">
      <li class="nav-item d-none d-md-block">
        <a class="nav-link position-relative topbar-icon-link" href="pending-documents.php" title="Documents en attente">
          <i class="bi bi-file-earmark-check fs-5"></i>
          <?php $cls_docs = $pending_docs > 0 ? 'bg-danger' : 'bg-secondary'; ?>
          <span class="badge rounded-pill topbar-notif-badge <?= $cls_docs ?>"><?= $pending_docs ?></span>
        </a>
      </li>
      <li class="nav-item d-none d-md-block">
        <a class="nav-link position-relative topbar-icon-link" href="pending-users.php" title="Comptes en attente">
          <i class="bi bi-person-check fs-5"></i>
          <?php $cls_users = $pending_users > 0 ? 'bg-warning text-dark' : 'bg-secondary'; ?>
          <span class="badge rounded-pill topbar-notif-badge <?= $cls_users ?>"><?= $pending_users ?></span>
        </a>
      </li>
      <?php if ($pending_docs > 0 || $pending_users > 0): ?>
      <li class="nav-item d-md-none">
        <div class="emsp-admin-topbar-alerts emsp-admin-topbar-alerts--legacy" aria-label="Éléments en attente">
          <?php if ($pending_docs > 0): ?>
            <a class="emsp-admin-topbar-alert emsp-admin-topbar-alert--docs" href="pending-documents.php" title="Documents en attente">
              <i class="bi bi-file-earmark-check" aria-hidden="true"></i>
              <span class="emsp-admin-topbar-alert__count"><?= $pending_docs > 99 ? '99+' : $pending_docs ?></span>
            </a>
          <?php endif; ?>
          <?php if ($pending_users > 0): ?>
            <a class="emsp-admin-topbar-alert emsp-admin-topbar-alert--users" href="pending-users.php" title="Comptes en attente">
              <i class="bi bi-person-check" aria-hidden="true"></i>
              <span class="emsp-admin-topbar-alert__count"><?= $pending_users > 99 ? '99+' : $pending_users ?></span>
            </a>
          <?php endif; ?>
        </div>
      </li>
      <?php endif; ?>
    </ul>

    <ul class="header-nav align-items-center">
      <li class="nav-item dropdown">
        <button class="btn btn-link nav-link py-2 px-2 d-flex align-items-center topbar-icon-link" type="button"
                aria-label="Theme" data-coreui-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-circle-half fs-5"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="--cui-dropdown-min-width: 9rem;">
          <li>
            <button type="button" class="dropdown-item d-flex align-items-center gap-2" data-emsp-theme-value="light">
              <i class="bi bi-sun-fill"></i> Clair
            </button>
          </li>
          <li>
            <button type="button" class="dropdown-item d-flex align-items-center gap-2" data-emsp-theme-value="dark">
              <i class="bi bi-moon-stars-fill"></i> Sombre
            </button>
          </li>
          <li>
            <button type="button" class="dropdown-item d-flex align-items-center gap-2 active" data-emsp-theme-value="auto">
              <i class="bi bi-circle-half"></i> Auto
            </button>
          </li>
        </ul>
      </li>
      <li class="nav-item d-none d-md-flex align-items-center">
        <div class="vr h-75 mx-2 text-body text-opacity-25"></div>
      </li>
      <li class="nav-item d-none d-md-block">
        <a href="../index.php" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2 topbar-back-btn" title="Retour au site">
          <i class="bi bi-box-arrow-up-right"></i>
          <span class="topbar-back-label">Retour au site</span>
        </a>
      </li>
      <li class="nav-item d-none d-md-flex align-items-center">
        <div class="vr h-75 mx-2 text-body text-opacity-25"></div>
      </li>
      <li class="nav-item dropdown topbar-profile-item">
        <a class="nav-link py-1 pe-0 d-flex align-items-center gap-2 admin-profile-toggle" data-coreui-toggle="dropdown" href="#"
           role="button" aria-haspopup="true" aria-expanded="false" aria-label="Menu profil">
          <div class="avatar avatar-md admin-profile-avatar">
            <?php if ($photo_src): ?>
              <img src="<?= h($photo_src) ?>" class="avatar-img rounded-circle" alt="">
            <?php else: ?>
              <span class="admin-avatar-circle"><?= h($initials) ?></span>
            <?php endif; ?>
          </div>
          <span class="d-none d-md-flex flex-column lh-sm admin-profile-meta">
            <span class="fw-semibold small text-body"><?= h($auth_user['first_name'] ?? 'Admin') ?></span>
            <span class="small admin-role-chip"><?= h($role_label) ?></span>
          </span>
        </a>
        <div class="dropdown-menu dropdown-menu-end pt-0 shadow-lg border-0" style="min-width:220px;border-radius:.75rem;">
          <div class="dropdown-header bg-body-tertiary text-body-secondary fw-semibold rounded-top mb-2">
            Compte
          </div>
          <a class="dropdown-item py-2" href="../mon-profil"><i class="bi bi-person me-2"></i> Mon profil</a>
          <a class="dropdown-item py-2" href="../dashboard"><i class="bi bi-grid me-2"></i> Espace etudiant</a>
          <?php if ($is_admin): ?>
            <a class="dropdown-item py-2" href="settings.php"><i class="bi bi-gear me-2"></i> Parametres</a>
          <?php endif; ?>
          <div class="dropdown-divider"></div>
          <form method="post" action="../logout" class="m-0">
            <?= csrf_input(); ?>
            <button type="submit" class="dropdown-item py-2 text-danger border-0 bg-transparent w-100 text-start" data-confirm="Te deconnecter ?" data-confirm-detail="Tu reviendras a l'accueil public de la plateforme." data-confirm-type="warning" data-confirm-ok="Oui, me deconnecter" data-emsp-confirm-auto="1">
              <i class="bi bi-box-arrow-right me-2"></i> Deconnexion
            </button>
          </form>
        </div>
      </li>
    </ul>
  </div>
</header>
<?php flash_render(); ?>

<section class="body flex-grow-1">
  <!-- FIX #2 : id="main-content" ajouté ici pour que le CSS de padding
       et max-width s'applique à TOUTES les pages admin automatiquement -->
  <section id="main-content" class="container-fluid px-4">

<script>
(function () {
    var STORAGE_KEY = 'coreui-free-theme';
    var html = document.documentElement;

    function applyTheme(theme) {
        var resolved = theme;
        if (theme === 'auto') {
            resolved = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        html.dataset.coreuiTheme = resolved;
        document.querySelectorAll('[data-emsp-theme-value]').forEach(function (el) {
            el.classList.toggle('active', el.getAttribute('data-emsp-theme-value') === theme);
        });
    }

    var initial = (function () {
        try { return localStorage.getItem(STORAGE_KEY) || 'auto'; } catch (e) { return 'auto'; }
    })();
    applyTheme(initial);

    document.querySelectorAll('[data-emsp-theme-value]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var v = btn.getAttribute('data-emsp-theme-value') || 'auto';
            try { localStorage.setItem(STORAGE_KEY, v); } catch (e) {}
            applyTheme(v);
        });
    });

    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
            var stored = 'auto';
            try { stored = localStorage.getItem(STORAGE_KEY) || 'auto'; } catch (e) {}
            if (stored === 'auto') applyTheme('auto');
        });
    }
})();
</script>
