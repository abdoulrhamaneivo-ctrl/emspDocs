<?php

declare(strict_types=1);

$rootDir = dirname(__DIR__);

// --- 1. Chargement des variables d'environnement (même lib que l'existant) ---
$autoload = $rootDir . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

if (class_exists('Dotenv\\Dotenv')) {
    Dotenv\Dotenv::createImmutable($rootDir)->safeLoad();

    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '')));
    $isLocalHost = $host === 'localhost' || $host === '127.0.0.1' || str_starts_with($host, 'localhost:') || str_starts_with($host, '127.0.0.1:');
    $localEnvFile = $rootDir . '/.env.local';
    if (is_file($localEnvFile) && ($isLocalHost || PHP_SAPI === 'cli')) {
        Dotenv\Dotenv::createMutable($rootDir, '.env.local')->safeLoad();
    }
}

// --- 2. Config app + erreurs ---
$appConfig = require __DIR__ . '/app.php';
date_default_timezone_set($appConfig['timezone']);

$isLocal = $appConfig['env'] === 'local' || $appConfig['env'] === 'development';
ini_set('display_errors', $isLocal ? '1' : '0');
ini_set('log_errors', '1');
$logDir = $rootDir . '/storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
ini_set('error_log', $logDir . '/php-error.log');

// --- 3. Session (mêmes réglages durcis que l'ancien includes/bootstrap.php) ---
session_name('EMSP_DOCS');
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_start();
}

// --- 4. Autoload PSR-4 pour le namespace App\ ---
spl_autoload_register(static function (string $class) use ($rootDir): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $path = $rootDir . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

// --- 5. En-têtes de sécurité (identiques à l'existant) ---
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

// --- 6. Helpers globaux ---
function config(string $key, mixed $default = null): mixed
{
    static $items = null;
    if ($items === null) {
        $items = require __DIR__ . '/app.php';
    }
    return $items[$key] ?? $default;
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    $base = rtrim((string) config('base_url'), '/');
    if ($base === '') {
        $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        $base = $script === '/' ? '' : $script;
    }
    return $base . '/' . ltrim($path, '/');
}
/**
 * Expose le manifest PWA sur tous les hôtes HTTPS (y compris ProFreeHost /
 * unaux.com). L'installation repose sur l'UI native du navigateur
 * (beforeinstallprompt sans bannière custom).
 */
function emsp_can_expose_manifest(): bool
{
    if (PHP_SAPI === 'cli') {
        return true;
    }

    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === 'localhost' || $host === '127.0.0.1') {
        return true;
    }
    if (str_starts_with($host, 'localhost:') || str_starts_with($host, '127.0.0.1:')) {
        return true;
    }

    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    if (!$isHttps && !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $isHttps = strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https';
    }

    return $isHttps || $host === '';
}
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function asset_version(): string
{
    $version = config('asset_version', '20260814ah');
    return $version !== '' ? (string) $version : '20260814ah';
}

/** Image publique : dossier racine /image/ en priorité, sinon assets/images/. */
function public_image(string $filename): string
{
    $filename = ltrim(str_replace('\\', '/', $filename), '/');
    if ($filename === '') {
        return asset('images/');
    }
    $root = dirname(__DIR__);
    if (is_file($root . '/image/' . $filename)) {
        $encoded = implode('/', array_map('rawurlencode', explode('/', $filename)));
        return url('image/' . $encoded);
    }
    return asset('images/' . $filename);
}

function redirect(string $path, int $status = 302): never
{
    session_write_close();
    http_response_code($status);
    header('Location: ' . url($path));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function consume_flash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Alias legacy disponible des le bootstrap MVC (avant includes/csrf.php). */
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token(): string
    {
        return csrf_token();
    }
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string) $token);
}

function current_user(): ?array
{
    return $_SESSION['auth_user'] ?? null;
}

function require_auth(): void
{
    $user = current_user();
    if (!$user) {
        flash('warning', 'Connecte-toi pour accéder à ton espace EMSP.');
        redirect('login');
    }

    // Revalidation systématique du compte côté PDO MVC : une suspension,
    // un rejet ou un changement de rôle prend effet sans attendre
    // l'expiration de la session.
    static $statusChecked = false;
    static $statusAllowed = true;

    if (!$statusChecked) {
        $statusChecked = true;
        $uid = (int) ($user['id'] ?? 0);

        if ($uid <= 0) {
            $statusAllowed = false;
        } else {
            try {
                $users = new \App\Repositories\UserRepository(\App\Core\Database::pdo());
                $row = $users->findAuthState($uid);
                $status = strtolower(trim((string) ($row['status'] ?? '')));
                $role = strtolower(trim((string) ($row['role'] ?? '')));

                if (!$row || in_array($status, ['pending', 'rejected', 'suspended'], true)) {
                    $statusAllowed = false;
                } else {
                    $_SESSION['auth_user']['status'] = $status;
                    if ($role !== '') {
                        $_SESSION['auth_user']['role'] = $role;
                        $_SESSION['auth_role'] = $role;
                    }
                }
            } catch (\Throwable $e) {
                // Aucun accès protégé ne doit être accordé si l'état du
                // compte ne peut pas être vérifié.
                error_log('EMSP auth state check failed: ' . $e->getMessage());
                $statusAllowed = false;
            }
        }
    }

    if (!$statusAllowed) {
        unset(
            $_SESSION['auth'],
            $_SESSION['auth_user'],
            $_SESSION['auth_role'],
            $_SESSION['notif_count'],
            $_SESSION['notif_sections'],
            $_SESSION['emsp_notif_sections_fetched_at']
        );
        flash('warning', 'Votre session n’est plus autorisée. Merci de vous reconnecter.');
        redirect('login');
    }
}

function require_role(array $roles): void
{
    require_auth();
    $role = strtolower((string) (current_user()['role'] ?? ''));
    if (!in_array($role, $roles, true)) {
        flash('danger', 'Accès réservé à un autre profil.');
        redirect('dashboard');
    }
}
// --- 7. Helpers legacy de session (emsp_session_sync_auth_user, notifications).
// Toujours nécessaires même sur les routes 100% MVC qui ne passent pas par
// le pont mysqli legacy (App\Core\LegacyDb).
require_once $rootDir . '/includes/bootstrap.php';

// --- 8. Toasts flash (overlay, sans reflow layout) ---
require_once $rootDir . '/includes/flash.php';
