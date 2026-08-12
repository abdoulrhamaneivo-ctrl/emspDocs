<?php
// admin/edit-licence.php — migré vers /admin/licences/form (LicencesController).
require __DIR__ . '/../config/bootstrap.php';
$id = (int) ($_GET['id'] ?? 0);
redirect('admin/licences/form' . ($id > 0 ? '?id=' . $id : ''), 301);
