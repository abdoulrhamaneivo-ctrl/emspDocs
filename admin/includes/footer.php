<?php // admin/includes/footer.php ?>
        </section><!-- /#main-content.container-fluid -->
      </section><!-- /.body -->

      <footer class="footer px-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
        <div>
          <strong>EMSP Admin</strong>
          <span class="text-body-secondary ms-1">&copy; <?= date('Y') ?> Ecole Multinationale Superieure des Postes.</span>
        </div>
        <div class="ms-md-auto text-body-secondary small">
          Propulse par
          <a href="https://coreui.io/" target="_blank" rel="noopener" class="text-decoration-none">CoreUI</a>
          &amp;
          <a href="https://getbootstrap.com/" target="_blank" rel="noopener" class="text-decoration-none">Bootstrap 5</a>
        </div>
      </footer>
    </main><!-- /.wrapper -->
</div><!-- /#admin-wrapper -->

<?php $admin_asset = $admin_asset ?? 'assets/'; ?>

<?php include_once __DIR__ . '/scripts.php'; ?>
</body>
</html>
