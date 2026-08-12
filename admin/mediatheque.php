<?php
// admin/mediatheque.php — page migrée vers /admin/mediatheque (MediaController).
require __DIR__ . '/../config/bootstrap.php';
$qs = $_GET ? '?' . http_build_query($_GET) : '';
redirect('admin/mediatheque' . $qs, 301);
