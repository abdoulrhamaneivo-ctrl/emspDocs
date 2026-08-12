<?php

// Les pages legacy de l'administration doivent appliquer la même revalidation
// du compte que les routes MVC : un rôle stocké en session ne suffit pas si le
// compte a été suspendu/révoqué depuis une autre session.
require_once __DIR__ . '/../config/bootstrap.php';

if (empty($_SESSION['auth_user']['id'])) {
    header('Location: ../login');
    exit;
}

try {
    $adminPdo = \App\Core\Database::pdo();
    $adminStmt = $adminPdo->prepare(
        'SELECT id, role, status FROM users WHERE id=? LIMIT 1'
    );
    $adminStmt->execute([(int) $_SESSION['auth_user']['id']]);
    $adminState = $adminStmt->fetch();

    $adminRole = strtolower(trim((string) ($adminState['role'] ?? '')));
    $adminStatus = strtolower(trim((string) ($adminState['status'] ?? '')));

    if (!$adminState || !in_array($adminRole, ['admin', 'moderateur'], true) || $adminStatus !== 'active') {
        unset($_SESSION['auth'], $_SESSION['auth_user'], $_SESSION['auth_role']);
        header('Location: ../login');
        exit;
    }

    $_SESSION['auth_role'] = $adminRole;
    $_SESSION['auth_user']['role'] = $adminRole;
    $_SESSION['auth_user']['status'] = $adminStatus;
} catch (Throwable $e) {
    error_log('EMSP legacy admin auth check failed: ' . $e->getMessage());
    unset($_SESSION['auth'], $_SESSION['auth_user'], $_SESSION['auth_role']);
    header('Location: ../login');
    exit;
}



// Compteurs partagés sidebar + navbar — 1 seule requête par page au lieu de 4
if (!isset($GLOBALS['emsp_admin_pending'])) {
    $GLOBALS['emsp_admin_pending'] = ['docs' => 0, 'users' => 0];
    if (isset($con) && $con instanceof mysqli) {
        $cq = mysqli_query($con,
            "SELECT
                (SELECT COUNT(*) FROM documents WHERE status='pending') AS docs,
                (SELECT COUNT(*) FROM users    WHERE status='pending') AS users"
        );
        if ($cq) {
            $GLOBALS['emsp_admin_pending'] = mysqli_fetch_assoc($cq) ?: ['docs' => 0, 'users' => 0];
        }
    }
}
