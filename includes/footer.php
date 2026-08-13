<?php
$asset = $asset ?? 'assets/';
$base = $base ?? './';
$page_scripts = $page_scripts ?? '';
$footer_year = (int) date('Y');
?>
    </main>

    <footer class="site-footer emsp-site-footer">
        <div class="container">
            <div class="emsp-site-footer__inner">
                <div class="emsp-site-footer__brand">
                <strong>EMSP Docs</strong>
                <p>La plateforme documentaire de la communauté EMSP.</p>
                <span>&copy; <?= $footer_year ?> École Multinationale Supérieure des Postes.</span>
            </div>
            <nav class="emsp-site-footer__links" aria-label="Explorer">
                <strong>Explorer</strong>
                <a href="<?= $base ?>documents">Bibliothèque</a>
                <a href="<?= $base ?>formations">Formations</a>
                <a href="<?= $base ?>journal">Journal</a>
            </nav>
            <nav class="emsp-site-footer__links" aria-label="Accompagnement">
                <strong>Accompagnement</strong>
                <a href="<?= $base ?>mediatheque">Médiathèque</a>
                <a href="<?= $base ?>faq">Questions fréquentes</a>
                <a href="<?= $base ?>register">Créer un compte</a>
            </nav>
            </div>
        </div>
    </footer>

    <script src="<?= $asset ?>js/jquery.min.js"></script>
    <script src="<?= $asset ?>js/bootstrap5.bundle.min.js"></script>
    <script src="<?= $asset ?>vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <script src="<?= $asset ?>vendor/select2/select2.full.min.js"></script>
    <script src="<?= $asset ?>js/jspdf.umd.min.js"></script>
    <script src="<?= $asset ?>js/emsp-flash.js?v=<?= h(asset_version()) ?>"></script>
    <script src="<?= $asset ?>js/emsp-ui.js"></script>
    <?php if (empty($_SESSION['auth']) && empty($_SESSION['auth_user']['id'])): ?>
    <script src="<?= $asset ?>js/emsp-login-modal.js?v=<?= h(asset_version()) ?>"></script>
    <?php endif; ?>
    <script src="<?= $asset ?>js/emsp-fixes.js"></script>
    <script defer src="<?= $asset ?>js/emsp-scanner.js?v=<?= h(asset_version()) ?>"></script>
    <script defer src="<?= $asset ?>js/emsp-desktop-mobile-hint.js?v=<?= h(asset_version()) ?>"></script>
    <script defer src="<?= $asset ?>js/emsp-mobile-fab.js?v=<?= h(asset_version()) ?>"></script>
    <script defer src="<?= $asset ?>js/emsp-experience-upgrade.js?v=<?= h(asset_version()) ?>"></script>
    <script defer src="<?= $asset ?>js/emsp-navbar-enhance.js?v=<?= h(asset_version()) ?>"></script>
    <script defer src="<?= $asset ?>js/emsp-mediatheque-sheet.js?v=<?= h(asset_version()) ?>"></script>
    <script defer src="<?= $asset ?>js/emsp-mediatheque-preview-carousel.js?v=<?= h(asset_version()) ?>"></script>
    <script defer src="<?= $asset ?>js/emsp-harvard-motion.js?v=<?= h(asset_version()) ?>"></script>
    <script defer src="<?= $asset ?>js/emsp-motion.js?v=<?= h(asset_version()) ?>"></script>
    <script defer src="<?= $asset ?>js/emsp-pdf-preview.js?v=<?= h(asset_version()) ?>"></script>
    <script defer src="<?= $asset ?>js/emsp-modal-guard.js?v=<?= h(asset_version()) ?>"></script>
    <?php require __DIR__ . '/partials/notifications-shell.php'; ?>
    <?php if (!empty($page_scripts)) { echo $page_scripts; } ?>
    <?php require __DIR__ . '/partials/pwa-install.php'; ?>
</body>
</html>
