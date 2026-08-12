<?php
// admin/journal.php — page migrée vers /admin/journal (JournalController).
// Préserve les paramètres de filtre/JSON/export CSV.
require __DIR__ . '/../config/bootstrap.php';
$qs = $_GET ? '?' . http_build_query($_GET) : '';
redirect('admin/journal' . $qs, 301);
