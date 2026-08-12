        </div><!-- /.emsp-admin-content -->
    </main>
    <?php require __DIR__ . '/../../app/Views/partials/admin-bottom-nav.php'; ?>
</div><!-- /.emsp-admin-shell -->
<?php $asset = $asset ?? '../assets/'; ?>
<script src="<?= $asset ?>js/jquery.min.js"></script>
<script src="<?= $asset ?>vendor/sweetalert2/sweetalert2.all.min.js"></script>
<script src="<?= $asset ?>js/emsp-ui.js"></script>
<script src="<?= $asset ?>js/bootstrap5.bundle.min.js"></script>
<script src="<?= $asset ?>js/emsp-admin-shell.js?v=<?= h(asset_version()) ?>"></script>
<?php if (!empty($page_scripts)) { echo $page_scripts; } ?>
</body>
</html>
