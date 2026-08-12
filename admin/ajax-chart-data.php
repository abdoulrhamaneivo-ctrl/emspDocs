<?php
ob_start();
include_once __DIR__ . '/bootstrap.php';
include_once __DIR__ . '/config/dbcon.php';
include_once __DIR__ . '/authentication.php';

header('Content-Type: application/json; charset=UTF-8');

if (empty($_SESSION['auth']) || !in_array($_SESSION['auth_role'] ?? '', ['admin', 'moderateur'], true)) {
    http_response_code(403);
    echo json_encode(['error' => 'Acces refuse']);
    exit;
}

$period = strtolower(trim((string) ($_GET['period'] ?? 'month')));
if (!in_array($period, ['day', 'month', 'year'], true)) {
    $period = 'month';
}

$tz = new DateTimeZone('Africa/Abidjan');
$now = new DateTimeImmutable('now', $tz);

$slots = [];
$slotKeys = [];
$sqlFormat = '';
$start = null;
$labelFormatter = static function (DateTimeImmutable $dt): string {
    return $dt->format('d/m');
};

if ($period === 'day') {
    $start = $now->modify('-23 hours')->setTime((int) $now->modify('-23 hours')->format('H'), 0, 0);
    $cursor = $start;
    for ($i = 0; $i < 24; $i++) {
        $key = $cursor->format('Y-m-d H:00:00');
        $slotKeys[] = $key;
        $slots[] = $cursor->format('H\h');
        $cursor = $cursor->modify('+1 hour');
    }
    $sqlFormat = '%Y-%m-%d %H:00:00';
} elseif ($period === 'year') {
    $start = $now->modify('first day of this month')->modify('-11 months')->setTime(0, 0, 0);
    $cursor = $start;
    for ($i = 0; $i < 12; $i++) {
        $key = $cursor->format('Y-m');
        $slotKeys[] = $key;
        $slots[] = $cursor->format('M Y');
        $cursor = $cursor->modify('+1 month');
    }
    $sqlFormat = '%Y-%m';
} else {
    $start = $now->modify('-29 days')->setTime(0, 0, 0);
    $cursor = $start;
    for ($i = 0; $i < 30; $i++) {
        $key = $cursor->format('Y-m-d');
        $slotKeys[] = $key;
        $slots[] = $cursor->format('d/m');
        $cursor = $cursor->modify('+1 day');
    }
    $sqlFormat = '%Y-%m-%d';
}

$startSql = $start->format('Y-m-d H:i:s');

$uploadsMap = array_fill_keys($slotKeys, 0);
$approvalsMap = array_fill_keys($slotKeys, 0);

$uploadStmt = mysqli_prepare(
    $con,
    "SELECT DATE_FORMAT(created_at, '{$sqlFormat}') AS bucket, COUNT(*) AS total
     FROM documents
     WHERE created_at >= ?
     GROUP BY bucket"
);
if ($uploadStmt) {
    mysqli_stmt_bind_param($uploadStmt, 's', $startSql);
    mysqli_stmt_execute($uploadStmt);
    $rows = emsp_stmt_fetch_all($uploadStmt);
    mysqli_stmt_close($uploadStmt);
    foreach ($rows as $row) {
        $bucket = (string) ($row['bucket'] ?? '');
        if (isset($uploadsMap[$bucket])) {
            $uploadsMap[$bucket] = (int) ($row['total'] ?? 0);
        }
    }
}

$approvalStmt = mysqli_prepare(
    $con,
    "SELECT DATE_FORMAT(approved_at, '{$sqlFormat}') AS bucket, COUNT(*) AS total
     FROM documents
     WHERE approved_at IS NOT NULL AND status='approved' AND approved_at >= ?
     GROUP BY bucket"
);
if ($approvalStmt) {
    mysqli_stmt_bind_param($approvalStmt, 's', $startSql);
    mysqli_stmt_execute($approvalStmt);
    $rows = emsp_stmt_fetch_all($approvalStmt);
    mysqli_stmt_close($approvalStmt);
    foreach ($rows as $row) {
        $bucket = (string) ($row['bucket'] ?? '');
        if (isset($approvalsMap[$bucket])) {
            $approvalsMap[$bucket] = (int) ($row['total'] ?? 0);
        }
    }
}

$uploads = [];
$approvals = [];
foreach ($slotKeys as $key) {
    $uploads[] = (int) ($uploadsMap[$key] ?? 0);
    $approvals[] = (int) ($approvalsMap[$key] ?? 0);
}

echo json_encode([
    'period' => $period,
    'labels' => $slots,
    'datasets' => [
        'uploads' => $uploads,
        'approvals' => $approvals,
    ],
], JSON_UNESCAPED_UNICODE);
