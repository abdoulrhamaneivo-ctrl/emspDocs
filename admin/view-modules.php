<?php
// admin/view-modules.php — page migrée vers /admin/modules (TaxonomyController).
require __DIR__ . '/../config/bootstrap.php';
redirect('admin/modules', 301);
