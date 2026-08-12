<?php
// admin/manage-filiere.php — page migrée vers /admin/filieres/form (FilieresController).
require __DIR__ . '/../config/bootstrap.php';
$id = (int) ($_GET['id'] ?? 0);
redirect('admin/filieres/form' . ($id > 0 ? '?id=' . $id : ''), 301);
