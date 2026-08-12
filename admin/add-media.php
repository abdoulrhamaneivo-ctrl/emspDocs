<?php
// admin/add-media.php — page migrée vers /admin/mediatheque/form (MediaController).
require __DIR__ . '/../config/bootstrap.php';
$id = (int) ($_GET['id'] ?? 0);
redirect('admin/mediatheque/form' . ($id > 0 ? '?id=' . $id : ''), 301);
