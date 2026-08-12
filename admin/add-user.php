<?php
// admin/add-user.php — page migrée vers /admin/utilisateurs/nouveau (UsersController::create/store).
require __DIR__ . '/../config/bootstrap.php';
redirect('admin/utilisateurs/nouveau', 301);
