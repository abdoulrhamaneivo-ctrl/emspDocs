<?php

declare(strict_types=1);

/**
 * Helpers PWA légers — sans session ni base de données.
 * Utilisés par manifest.php et pwa-diagnostics.php.
 */
function emsp_pwa_app_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/app.php';
    }
    return $config;
}

function emsp_pwa_is_https_request(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        return strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https';
    }
    return false;
}

function emsp_pwa_can_expose(): bool
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

    return emsp_pwa_is_https_request();
}

function emsp_pwa_scope_path(): string
{
    $appUrl = rtrim((string) (emsp_pwa_app_config()['base_url'] ?? ''), '/');
    if ($appUrl !== '') {
        $path = (string) (parse_url($appUrl, PHP_URL_PATH) ?: '/');
    } else {
        $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        $path = ($script === '/' || $script === '.' || $script === '') ? '/' : $script;
    }

    $path = rtrim($path, '/');
    return $path === '' ? '/' : $path;
}

function emsp_pwa_origin(): string
{
    $appUrl = rtrim((string) (emsp_pwa_app_config()['base_url'] ?? ''), '/');
    if ($appUrl !== '' && preg_match('#^https?://#i', $appUrl)) {
        return (string) (parse_url($appUrl, PHP_URL_SCHEME) . '://' . parse_url($appUrl, PHP_URL_HOST)
            . (parse_url($appUrl, PHP_URL_PORT) ? ':' . parse_url($appUrl, PHP_URL_PORT) : ''));
    }

    $scheme = emsp_pwa_is_https_request() ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    return $scheme . '://' . $host;
}

function emsp_pwa_absolute_url(string $path): string
{
    $path = '/' . ltrim($path, '/');
    $scope = emsp_pwa_scope_path();
    if ($scope !== '/') {
        $prefix = rtrim($scope, '/');
        if ($path === '/' || $path === $prefix . '/') {
            return emsp_pwa_origin() . $prefix . '/';
        }
        if (str_starts_with($path, $prefix . '/')) {
            return emsp_pwa_origin() . $path;
        }
        return emsp_pwa_origin() . $prefix . $path;
    }

    return emsp_pwa_origin() . $path;
}

/**
 * Chemin relatif au manifest (./…) — préféré par Chrome pour start_url / scope.
 */
function emsp_pwa_manifest_relative(string $path = ''): string
{
    $path = ltrim($path, '/');
    return $path === '' ? './' : './' . $path;
}

/** @return array<string, mixed> */
function emsp_pwa_manifest_payload(): array
{
    $scopePath = emsp_pwa_scope_path();
    $scope = './';
    $startUrl = './';
    $appId = ($scopePath === '/' ? '/' : rtrim($scopePath, '/'));

    $icon192 = emsp_pwa_manifest_relative('assets/images/logo-emsp-192.png');
    $icon512 = emsp_pwa_manifest_relative('assets/images/logo-emsp-512.png');

    return [
        'id' => $appId,
        'name' => 'EMSP Docs',
        'short_name' => 'EMSP Docs',
        'description' => 'Plateforme academique EMSP pour cours, documents et actualites.',
        'start_url' => $startUrl,
        'scope' => $scope,
        'display' => 'standalone',
        'orientation' => 'portrait-primary',
        'lang' => 'fr',
        'dir' => 'ltr',
        'background_color' => '#006B3C',
        'theme_color' => '#006B3C',
        'categories' => ['education', 'productivity', 'reference'],
        'shortcuts' => [
            [
                'name' => 'Bibliotheque',
                'short_name' => 'Docs',
                'description' => 'Ouvrir la bibliotheque',
                'url' => emsp_pwa_manifest_relative('documents'),
                'icons' => [[
                    'src' => $icon192,
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ]],
            ],
            [
                'name' => 'Formations',
                'short_name' => 'Formations',
                'description' => 'Voir les filieres et niveaux',
                'url' => emsp_pwa_manifest_relative('formations'),
                'icons' => [[
                    'src' => $icon192,
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ]],
            ],
            [
                'name' => 'Journal',
                'short_name' => 'Journal',
                'description' => 'Lire les actualites',
                'url' => emsp_pwa_manifest_relative('journal'),
                'icons' => [[
                    'src' => $icon192,
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ]],
            ],
        ],
        'icons' => [
            [
                'src' => $icon192,
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => $icon512,
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => $icon192,
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ],
            [
                'src' => $icon512,
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ],
        ],
    ];
}
