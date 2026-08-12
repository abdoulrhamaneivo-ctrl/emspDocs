<?php
// admin/view-users.php — page migrée vers /admin/utilisateurs (UsersController::index).
require __DIR__ . '/../config/bootstrap.php';
$qs = $_GET ? '?' . http_build_query($_GET) : '';
redirect('admin/utilisateurs' . $qs, 301);
