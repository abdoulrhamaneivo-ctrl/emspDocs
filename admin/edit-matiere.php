<?php
// admin/edit-matiere.php — migré vers /admin/matieres/form (TaxonomyController).
require __DIR__ . '/../config/bootstrap.php';
$id = (int) ($_GET['id'] ?? 0);
redirect('admin/matieres/form' . ($id > 0 ? '?id=' . $id : ''), 301);
