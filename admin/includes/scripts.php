<?php
$asset = $asset ?? '../assets/';
$adminSidebarScriptFile = __DIR__ . '/../../assets/js/admin-sidebar-toggle.js';
$adminSidebarScriptVersion = is_file($adminSidebarScriptFile) ? (string) filemtime($adminSidebarScriptFile) : '1';
?>
<!-- 1. jQuery (requis par Select2) -->
<script src="<?= $asset ?>js/jquery.min.js"></script>
<!-- 2. CoreUI Bundle (inclut Popper) -->
<script src="https://cdn.jsdelivr.net/npm/@coreui/coreui@5.0.2/dist/js/coreui.bundle.min.js"></script>
<!-- 3. Simplebar -->
<script src="https://cdn.jsdelivr.net/npm/simplebar@latest/dist/simplebar.min.js"></script>
<!-- 4. Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<!-- 5. CoreUI ChartJS adapter -->
<script src="https://cdn.jsdelivr.net/npm/@coreui/chartjs@4.0.0/dist/js/coreui-chartjs.min.js"></script>
<!-- 6. Vendors locaux -->
<script src="<?= $asset ?>vendor/sweetalert2/sweetalert2.all.min.js"></script>
<script src="<?= $asset ?>vendor/select2/select2.full.min.js"></script>
<!-- 7. EMSP UI helpers -->
<script src="<?= $asset ?>js/emsp-ui.js"></script>
<script src="<?= $asset ?>js/emsp-admin-experience-upgrade.js"></script>
<script src="<?= $asset ?>js/emsp-fixes.js"></script>
<script src="<?= $asset ?>js/admin-sidebar-toggle.js?v=<?= h($adminSidebarScriptVersion) ?>"></script>
<!-- 8. Scripts inline de la page -->
<?php if (!empty($page_scripts)) { echo $page_scripts; } ?>
