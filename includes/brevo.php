<?php
// ------------------------------------------------------------
// Configuration Brevo (centralisée)
// ------------------------------------------------------------
include_once __DIR__ . '/../admin/config/config.php';
include_once __DIR__ . '/helpers.php';

if (!defined('BREVO_API_KEY')) {
    define('BREVO_API_KEY', '');
}
if (!defined('BREVO_FROM_EMAIL')) {
    define('BREVO_FROM_EMAIL', 'noreply@emsp.int');
}
if (!defined('BREVO_FROM_NAME')) {
    define('BREVO_FROM_NAME', 'EMSP Docs');
}
if (!defined('BREVO_SENDER_EMAIL')) {
    define('BREVO_SENDER_EMAIL', BREVO_FROM_EMAIL);
}
if (!defined('BREVO_SENDER_NAME')) {
    define('BREVO_SENDER_NAME', BREVO_FROM_NAME);
}
if (!defined('APP_URL')) {
    define('APP_URL', defined('BASE_URL') ? BASE_URL : '');
}
if (!defined('APP_NAME')) {
    define('APP_NAME', 'EMSP Docs');
}
if (!defined('SCHOOL_EMAIL_DOMAIN')) {
    define('SCHOOL_EMAIL_DOMAIN', '@emsp.int');
}
if (!defined('APP_ENV')) {
    define('APP_ENV', 'production');
}

if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        if ($needle === '') { return true; }
        $len = strlen($needle);
        return substr($haystack, -$len) === $needle;
    }
}

function brevo_ensure_email_log_table($con): bool {
    static $checked = false;
    if ($checked || !$con) {
        return (bool) $con;
    }
    $checked = true;

    $res = @mysqli_query($con, "SHOW TABLES LIKE 'email_log'");
    if ($res && mysqli_num_rows($res) > 0) {
        return true;
    }

    $sql = "CREATE TABLE IF NOT EXISTS email_log (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NULL,
        email_to VARCHAR(190) NOT NULL,
        subject VARCHAR(190) NOT NULL,
        template_name VARCHAR(64) NOT NULL,
        status VARCHAR(20) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_user_id (user_id),
        KEY idx_status (status),
        KEY idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    @mysqli_query($con, $sql);
    return true;
}

function brevo_recent_email_exists(?mysqli $con, int $user_id, string $template_name, int $minutes = 5): bool
{
    if (!$con || $user_id <= 0 || $template_name === '') {
        return false;
    }
    if (!brevo_ensure_email_log_table($con)) {
        return false;
    }

    $stmt = mysqli_prepare(
        $con,
        "SELECT 1
         FROM email_log
         WHERE user_id = ?
           AND template_name = ?
           AND status = 'sent'
           AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
         LIMIT 1"
    );
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 'isi', $user_id, $template_name, $minutes);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $exists = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $exists;
}

function brevo_app_url(): string {
    $app = rtrim((string) APP_URL, '/');
    if ($app !== '') {
        return $app;
    }

    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $base = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
    if ($base === '/' || $base === '\\') { $base = ''; }
    if (substr($base, -6) === '/admin') { $base = substr($base, 0, -6); }

    if (stripos($host, 'localhost') !== false || stripos($host, '127.0.0.1') !== false) {
        return $scheme . '://' . $host . $base;
    }

    if ($host !== '') {
        error_log('EMSP WARNING: APP_URL non défini — fallback HTTP_HOST pour les liens email');
        return $scheme . '://' . $host . $base;
    }

    return '';
}

// Styles communs pour les templates — conservés dans App\Services\EmailService.

// ------------------------------------------------------------
// Templates & Raccourcis (délégation vers EmailService)
// ------------------------------------------------------------

function brevo_send_verification($user_id, $email, $prenom, $nom, $token) {
    return (new \App\Services\EmailService())->sendVerification((int) $user_id, (string) $email, (string) $prenom, (string) $nom, (string) $token);
}

function brevo_send_password_reset($user_id, $email, $prenom, $nom, $token) {
    return (new \App\Services\EmailService())->sendPasswordReset((int) $user_id, (string) $email, (string) $prenom, (string) $nom, (string) $token);
}

function brevo_send_account_approved($user_id, $email, $prenom, $nom) {
    return (new \App\Services\EmailService())->sendAccountApproved((int) $user_id, (string) $email, (string) $prenom, (string) $nom);
}

function brevo_send_account_rejected($user_id, $email, $prenom, $nom, $motif) {
    return (new \App\Services\EmailService())->sendAccountRejected((int) $user_id, (string) $email, (string) $prenom, (string) $nom, (string) $motif);
}

function brevo_send_doc_approved($user_id, $email, $prenom, $nom, $doc_title) {
    return (new \App\Services\EmailService())->sendDocApproved((int) $user_id, (string) $email, (string) $prenom, (string) $nom, (string) $doc_title);
}

function brevo_send_doc_rejected($user_id, $email, $prenom, $nom, $doc_title, $motif) {
    return (new \App\Services\EmailService())->sendDocRejected((int) $user_id, (string) $email, (string) $prenom, (string) $nom, (string) $doc_title, (string) $motif);
}


