<?php
// admin/pending-users.php — page migrée vers /admin/validation-comptes
// (PendingUsersController::index).
require __DIR__ . '/../config/bootstrap.php';
redirect('admin/validation-comptes', 301);
