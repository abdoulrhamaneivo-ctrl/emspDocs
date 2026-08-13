<?php

if (!defined('EMSP_ASSET_VERSION')) {
    define('EMSP_ASSET_VERSION', '20260814ah');
}

if (!function_exists('emsp_nav_current_path')) {
    /**
     * Route courante normalisée (MVC + legacy), sans extension .php.
     */
    function emsp_nav_current_path(): string
    {
        $requestPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/');
        $requestPath = rtrim($requestPath, '/') ?: '/';

        $basePath = '';
        if (function_exists('url')) {
            $basePath = rtrim((string) (parse_url(url(''), PHP_URL_PATH) ?: ''), '/');
        }
        if ($basePath !== '' && $basePath !== '/' && str_starts_with($requestPath, $basePath)) {
            $requestPath = substr($requestPath, strlen($basePath));
            $requestPath = ($requestPath === '' || $requestPath === false) ? '/' : $requestPath;
        }

        $requestPath = trim((string) $requestPath, '/');
        if ($requestPath === '') {
            return 'index.php';
        }

        if (preg_match('#^admin(?:/|$)#i', $requestPath)) {
            $adminPath = preg_replace('/\.php$/i', '', $requestPath) ?: 'admin';
            return $adminPath;
        }

        $segment = basename('/' . $requestPath);
        $segment = preg_replace('/\.php$/i', '', (string) $segment) ?: 'index.php';

        return $segment === '' ? 'index.php' : $segment;
    }
}

if (!function_exists('emsp_mobile_chrome_hidden_paths')) {
    /** Routes sans barre mobile (flux email uniquement). Pending-status garde la bottom nav. */
    function emsp_mobile_chrome_hidden_paths(): array
    {
        return [
            'resend-verification',
            'verify-email',
        ];
    }
}

if (!function_exists('emsp_mobile_deposit_fab_hidden_paths')) {
    /** Routes où le FAB dépôt/scanner est masqué (déjà sur la page dépôt, etc.). */
    function emsp_mobile_deposit_fab_hidden_paths(): array
    {
        return [
            'upload',
        ];
    }
}

if (!function_exists('emsp_is_home_path')) {
    /** Accueil public (index.php ou route racine). */
    function emsp_is_home_path(?string $path = null): bool
    {
        $path = $path ?? emsp_nav_current_path();

        return in_array($path, ['index.php', 'index', ''], true);
    }
}

if (!function_exists('emsp_mobile_home_fab_hidden_paths')) {
    /** Routes où les FAB flottants invité / médiathèque sont masqués sur mobile. */
    function emsp_mobile_home_fab_hidden_paths(): array
    {
        return [
            'index.php',
            'index',
        ];
    }
}

if (!function_exists('emsp_is_admin_nav_path')) {
    function emsp_is_admin_nav_path(string $path): bool
    {
        return (bool) preg_match('#^admin(?:/|$)#i', $path);
    }
}

if (!function_exists('emsp_should_show_mobile_bottom_nav')) {
    /** Barre mobile : invités + authentifiés sur les routes publiques principales. */
    function emsp_should_show_mobile_bottom_nav(): bool
    {
        $path = emsp_nav_current_path();

        if (in_array($path, emsp_mobile_chrome_hidden_paths(), true)) {
            return false;
        }

        if (emsp_is_admin_nav_path($path)) {
            return false;
        }

        return true;
    }
}

if (!function_exists('emsp_should_show_mobile_deposit_fab')) {
    /** FAB dépôt/scanner : compte actif uniquement, hors dépôt et admin. */
    function emsp_should_show_mobile_deposit_fab(): bool
    {
        $uid = (int) ($_SESSION['auth_user']['id'] ?? 0);
        $isAuth = $uid > 0 || !empty($_SESSION['auth']);
        if (!$isAuth) {
            return false;
        }

        $accountStatus = strtolower(trim((string) ($_SESSION['auth_user']['status'] ?? '')));
        if ($accountStatus !== '' && $accountStatus !== 'active') {
            return false;
        }

        $path = emsp_nav_current_path();
        if (in_array($path, emsp_mobile_deposit_fab_hidden_paths(), true)) {
            return false;
        }

        if (in_array($path, emsp_mobile_chrome_hidden_paths(), true)) {
            return false;
        }

        if (emsp_is_admin_nav_path($path)) {
            return false;
        }

        return true;
    }
}

if (!function_exists('emsp_should_show_mobile_guest_fab')) {
    /** FAB invité : inscription / connexion rapide sur mobile public. */
    function emsp_should_show_mobile_guest_fab(): bool
    {
        $uid = (int) ($_SESSION['auth_user']['id'] ?? 0);
        if ($uid > 0 || !empty($_SESSION['auth'])) {
            return false;
        }

        $path = emsp_nav_current_path();
        if (in_array($path, emsp_mobile_deposit_fab_hidden_paths(), true)) {
            return false;
        }

        if (in_array($path, emsp_mobile_home_fab_hidden_paths(), true)) {
            return false;
        }

        if (in_array($path, emsp_mobile_chrome_hidden_paths(), true)) {
            return false;
        }

        if (emsp_is_admin_nav_path($path)) {
            return false;
        }

        return true;
    }
}

if (!function_exists('emsp_should_show_mobile_mediatheque_fab')) {
    /** FAB médiathèque flottant : masqué sur l'accueil mobile (bottom nav suffit). */
    function emsp_should_show_mobile_mediatheque_fab(): bool
    {
        if (emsp_is_home_path()) {
            return false;
        }

        return true;
    }
}

if (!function_exists('asset_version')) {
    function asset_version(): string
    {
        if (function_exists('config')) {
            $configured = config('asset_version');
            if ($configured !== null && $configured !== '') {
                return (string) $configured;
            }
        }
        return EMSP_ASSET_VERSION;
    }
}

if (!function_exists('h')) {
    function h($s): string
    {
        $val = (string) $s;
        if (function_exists('emsp_fix_mojibake')) {
            $val = emsp_fix_mojibake($val);
        }
        return htmlspecialchars($val, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

// Shared shells (front navbar, admin chrome, profile badges) rely on these
// path helpers, so they must live in the common helper layer, not in an
// optional content-only include.
if (!function_exists('emsp_is_external_url')) {
    function emsp_is_external_url(string $path): bool
    {
        return (bool) preg_match('#^https?://#i', $path);
    }
}

if (!function_exists('emsp_media_src')) {
    function emsp_media_src(string $filePath): string
    {
        $path = trim($filePath);
        if ($path === '') {
            return '';
        }

        if (emsp_is_external_url($path)) {
            return $path;
        }

        $clean = ltrim(str_replace('\\', '/', $path), '/');
        if (strpos($clean, 'uploads/') === 0 || strpos($clean, 'assets/') === 0) {
            return $clean;
        }

        return 'uploads/media/' . $clean;
    }
}

if (!function_exists('emsp_media_path')) {
    function emsp_media_path(?string $path): string
    {
        return emsp_media_src((string) $path);
    }
}

if (!function_exists('emsp_user_photo_src')) {
    function emsp_user_photo_src(?string $photoPath): string
    {
        $path = trim((string) $photoPath);
        if ($path === '') {
            return '';
        }

        if (emsp_is_external_url($path)) {
            return $path;
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');
        if (strpos($path, 'profiles/') === 0) {
            $path = 'uploads/' . $path;
        } elseif (strpos($path, 'uploads/profiles/') !== 0 && strpos($path, 'assets/') !== 0) {
            $path = 'uploads/profiles/' . basename($path);
        }

        $projectRoot = dirname(__DIR__);
        $localCandidate = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (is_file($localCandidate)) {
            return $path;
        }

        return '';
    }
}

if (!function_exists('emsp_document_relative_path')) {
    function emsp_document_relative_path(?string $rawPath): string
    {
        $path = trim((string) $rawPath);
        if ($path === '' || emsp_is_external_url($path)) {
            return '';
        }

        $path = str_replace('\\', '/', $path);
        $pos = strpos($path, 'uploads/documents/');
        if ($pos !== false) {
            $path = substr($path, $pos + strlen('uploads/documents/'));
        } elseif (strpos($path, 'documents/') === 0) {
            $path = substr($path, strlen('documents/'));
        }

        return ltrim($path, '/');
    }
}

if (!function_exists('emsp_document_local_path')) {
    function emsp_document_local_path(?string $rawPath): string
    {
        $relativePath = emsp_document_relative_path($rawPath);
        if ($relativePath === '') {
            return '';
        }

        $uploadsRoot = realpath(__DIR__ . '/../uploads/documents');
        if ($uploadsRoot === false) {
            return '';
        }

        $candidate = realpath($uploadsRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
        if ($candidate === false || !is_file($candidate)) {
            return '';
        }

        $rootPrefix = rtrim($uploadsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strpos($candidate, $rootPrefix) !== 0) {
            return '';
        }

        return $candidate;
    }
}

if (!function_exists('emsp_document_file_available')) {
    function emsp_document_file_available(?string $rawPath): bool
    {
        return emsp_document_local_path($rawPath) !== '';
    }
}

if (!function_exists('emsp_user_initials')) {
    function emsp_user_initials(?string $firstName, ?string $lastName, string $fallback = 'EM'): string
    {
        $firstName = trim((string) $firstName);
        $lastName = trim((string) $lastName);
        $initials = '';

        if ($firstName !== '') {
            $initials .= mb_strtoupper(mb_substr($firstName, 0, 1));
        }
        if ($lastName !== '') {
            $initials .= mb_strtoupper(mb_substr($lastName, 0, 1));
        }

        return $initials !== '' ? $initials : $fallback;
    }
}

if (!function_exists('emsp_render_comment_avatar')) {
    /**
     * Avatar rond pour fil de commentaires (photo profil ou initiales).
     */
    function emsp_render_comment_avatar(?string $photoPath, ?string $firstName, ?string $lastName, string $alt = ''): string
    {
        $photoSrc = emsp_user_photo_src((string) $photoPath);
        $initials = emsp_user_initials($firstName, $lastName);
        $altAttr = htmlspecialchars($alt !== '' ? $alt : trim($firstName . ' ' . $lastName), ENT_QUOTES, 'UTF-8');

        if ($photoSrc !== '') {
            return '<img src="' . htmlspecialchars($photoSrc, ENT_QUOTES, 'UTF-8') . '" class="emsp-comment-avatar" width="40" height="40" alt="' . $altAttr . '" loading="lazy" decoding="async">';
        }

        return '<span class="emsp-comment-avatar emsp-comment-avatar--fallback" aria-hidden="true">' . htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') . '</span>';
    }
}

if (!function_exists('emsp_tronc_commun_filiere_value')) {
    /** Valeur formulaire pour filière non encore affectée (stockée en base comme NULL). */
    function emsp_tronc_commun_filiere_value(): int
    {
        return 0;
    }
}

if (!function_exists('emsp_user_filiere_label')) {
    function emsp_user_filiere_label(?string $filiereName): string
    {
        $name = trim((string) $filiereName);

        return $name !== '' ? $name : 'Tronc commun';
    }
}

if (!function_exists('emsp_public_profile_url')) {
    function emsp_public_profile_url(int $userId): string
    {
        if ($userId <= 0) {
            return '';
        }

        return url('profil-public?id=' . $userId);
    }
}

if (!function_exists('emsp_render_comment_author_link')) {
    function emsp_render_comment_author_link(int $userId, string $innerHtml, string $extraClass = ''): string
    {
        if ($userId <= 0) {
            return $innerHtml;
        }

        $href = emsp_public_profile_url($userId);
        $class = trim('emsp-comment-author-link ' . $extraClass);

        return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8')
            . '" class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8')
            . '" rel="nofollow">' . $innerHtml . '</a>';
    }
}

if (!function_exists('emsp_is_admin_role')) {
    function emsp_is_admin_role(?string $role): bool
    {
        return strtolower(trim((string) $role)) === 'admin';
    }
}

if (!function_exists('emsp_can_assign_user_roles')) {
    function emsp_can_assign_user_roles(array $actor): bool
    {
        return emsp_is_admin_role((string) ($actor['role'] ?? ''));
    }
}

if (!function_exists('emsp_can_manage_user_account')) {
    function emsp_can_manage_user_account(array $actor, array $target): bool
    {
        if (emsp_can_assign_user_roles($actor)) {
            return true;
        }

        // Moderators can manage student/moderator accounts, but never admin accounts.
        return !emsp_is_admin_role((string) ($target['role'] ?? ''));
    }
}

if (!function_exists('emsp_is_ajax_request')) {
    function emsp_is_ajax_request(): bool
    {
        return strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }
}

if (!function_exists('emsp_json_response')) {
    function emsp_json_response(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit(0);
    }
}

if (!function_exists('emsp_ensure_audit_log_table')) {
    function emsp_ensure_audit_log_table(mysqli $con): bool
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }

        $sql = "CREATE TABLE IF NOT EXISTS audit_log (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            admin_id INT NOT NULL,
            action VARCHAR(100) NOT NULL,
            target_type VARCHAR(50) NOT NULL,
            target_id INT NOT NULL DEFAULT 0,
            details TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_audit_admin (admin_id),
            KEY idx_audit_target (target_type, target_id),
            KEY idx_audit_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $ready = mysqli_query($con, $sql) === true;
        return $ready;
    }
}

if (!function_exists('log_audit')) {
    function log_audit(
        mysqli|\PDO $con,
        int $admin_id,
        string $action,
        string $target_type,
        int $target_id = 0,
        ?string $details = null
    ): void {
        if ($con instanceof \PDO) {
            \App\Core\DatabaseHelper::logAudit($con, $admin_id, $action, $target_type, $target_id, $details);
            return;
        }

        if ($admin_id <= 0 || !emsp_ensure_audit_log_table($con)) {
            return;
        }

        $stmt = mysqli_prepare(
            $con,
            "INSERT INTO audit_log (admin_id, action, target_type, target_id, details) VALUES (?,?,?,?,?)"
        );
        if (!$stmt) {
            return;
        }

        mysqli_stmt_bind_param($stmt, 'issis', $admin_id, $action, $target_type, $target_id, $details);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('emsp_per_page_from_request')) {
    /** Valeurs autorisées pour ?per_page= (défaut 25). */
    function emsp_per_page_from_request(int $default = 25): int
    {
        $allowed = [10, 20, 25, 50];
        $perPage = (int) ($_GET['per_page'] ?? $default);
        return in_array($perPage, $allowed, true) ? $perPage : $default;
    }
}

if (!function_exists('emsp_paginate')) {
    /**
     * Calcule offset, bornes d'affichage et nombre de pages (page 1-based).
     *
     * @return array{page:int,perPage:int,total:int,totalPages:int,offset:int,from:int,to:int}
     */
    function emsp_paginate(int $total, int $page, int $perPage = 25): array
    {
        $perPage = max(1, min(100, $perPage));
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;
        $from = $total > 0 ? $offset + 1 : 0;
        $to = min($offset + $perPage, $total);

        return [
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => $totalPages,
            'offset' => $offset,
            'from' => $from,
            'to' => $to,
        ];
    }
}

if (!function_exists('emsp_include_pagination')) {
    /**
     * Affiche le résumé + contrôles Bootstrap (partial includes/partials/pagination.php).
     *
     * @param array<string, mixed> $queryParams Paramètres GET à conserver dans les liens
     */
    function emsp_include_pagination(array $pagination, string $baseRoute, array $queryParams = [], string $pageParam = 'page'): void
    {
        $partial = __DIR__ . '/partials/pagination.php';
        if (!is_file($partial)) {
            return;
        }
        include $partial;
    }
}

