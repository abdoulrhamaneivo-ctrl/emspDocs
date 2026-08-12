<?php
// admin/add-journal.php — page migrée vers /admin/journal/form (JournalController).
require __DIR__ . '/../config/bootstrap.php';
$id = (int) ($_GET['id'] ?? 0);
redirect('admin/journal/form' . ($id > 0 ? '?id=' . $id : ''), 301);
