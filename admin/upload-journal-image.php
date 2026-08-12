<?php
ob_start();
include_once __DIR__ . '/bootstrap.php';

if (empty($_SESSION['auth']) ||
    !in_array($_SESSION['auth_role'] ?? '', ['admin','moderateur'], true)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['error' => 'Accès refusé']);
    exit;
}

include_once __DIR__ . '/config/dbcon.php';
include_once __DIR__ . '/../includes/csrf.php';
include_once __DIR__ . '/../includes/services/admin-image-upload.php';

header('Content-Type: application/json; charset=UTF-8');

$csrf = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
$sessionToken = $_SESSION['csrf_token'] ?? '';
if ($sessionToken === '' || $csrf === '' || !hash_equals($sessionToken, $csrf)) {
    http_response_code(403);
    echo json_encode(['error' => 'Token CSRF invalide']);
    exit;
}

if (empty($_FILES['image']) || !is_array($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Aucun fichier reçu ou erreur upload']);
    exit;
}

$maxSize = emsp_max_upload_size(defined('MAX_UPLOAD_SIZE') ? MAX_UPLOAD_SIZE : (5 * 1024 * 1024));
$upload = emsp_upload_image_asset(
    $_FILES['image'],
    __DIR__ . '/../uploads/media/journal',
    'uploads/media/journal',
    $maxSize,
    1920,
    1920
);
if (empty($upload['ok'])) {
    $msg = (string) ($upload['error'] ?? 'Erreur upload');
    $status = str_contains($msg, 'trop lourde') ? 413 : 400;
    if (str_contains($msg, 'Format non autorise')) {
        $status = 415;
    }
    if (str_contains($msg, 'dossier') || str_contains($msg, 'enregistrer')) {
        $status = 500;
    }
    http_response_code($status);
    echo json_encode(['error' => $msg]);
    exit;
}

echo json_encode(['url' => (string) ($upload['path'] ?? '')]);
exit;


