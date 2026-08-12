<?php
// admin/edit-user.php — page migrée vers /admin/utilisateurs/{id}/modifier
// (UsersController::edit/update). Préserve le paramètre ?id=.
require __DIR__ . '/../config/bootstrap.php';
$id = (int) ($_GET['id'] ?? ($_POST['id'] ?? 0));
if ($id <= 0) {
    redirect('admin/utilisateurs');
}
redirect('admin/utilisateurs/' . $id . '/modifier', 301);
