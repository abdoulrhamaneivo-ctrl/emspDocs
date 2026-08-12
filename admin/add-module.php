<?php
// admin/add-module.php — page migrée vers /admin/modules/form (TaxonomyController).
require __DIR__ . '/../config/bootstrap.php';
$id = (int) ($_GET['id'] ?? 0);
redirect('admin/modules/form' . ($id > 0 ? '?id=' . $id : ''), 301);
