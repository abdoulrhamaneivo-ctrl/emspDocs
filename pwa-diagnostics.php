<?php

declare(strict_types=1);

/**
 * Diagnostic PWA côté serveur — vérifier manifest, icônes, SW, HTTPS.
 * Accès : /pwa-diagnostics.php (JSON)
 */
require __DIR__ . '/config/pwa-manifest.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

/**
 * Sonde HTTP légère (HEAD puis GET si besoin) — complète les checks locaux.
 *
 * @return array{ok: bool, status: int|null, content_type: string|null, detail: string}
 */
function emsp_pwa_http_probe(string $url): array
{
    $status = null;
    $contentType = null;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'EMSP-PWA-Diagnostics/1.0',
        ]);
        curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: null;
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'HEAD',
                'timeout' => 10,
                'ignore_errors' => true,
                'header' => "User-Agent: EMSP-PWA-Diagnostics/1.0\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);
        $headers = @get_headers($url, true, $context);
        if (is_array($headers) && isset($headers[0]) && preg_match('#\s(\d{3})\s#', (string) $headers[0], $m)) {
            $status = (int) $m[1];
        }
        if (is_array($headers)) {
            foreach ($headers as $key => $value) {
                if (is_string($key) && strtolower($key) === 'content-type') {
                    $contentType = is_array($value) ? (string) end($value) : (string) $value;
                    break;
                }
            }
        }
    }

    $ok = $status !== null && $status >= 200 && $status < 400;

    return [
        'ok' => $ok,
        'status' => $status,
        'content_type' => $contentType,
        'detail' => $ok
            ? ('HTTP ' . $status . ($contentType ? ' — ' . $contentType : ''))
            : ('HTTP ' . ($status ?? 'erreur') . ' — requête échouée ou ressource inaccessible'),
    ];
}

$scope = emsp_pwa_scope_path();
$scopeSlash = ($scope === '/' ? '/' : rtrim($scope, '/') . '/');
$origin = emsp_pwa_origin();
$manifestUrl = emsp_pwa_absolute_url('/manifest.json');
$swUrl = emsp_pwa_absolute_url('/sw.js');
$icon192Url = emsp_pwa_absolute_url('/assets/images/logo-emsp-192.png');
$icon512Url = emsp_pwa_absolute_url('/assets/images/logo-emsp-512.png');

$checks = [];

$checks['https'] = [
    'ok' => emsp_pwa_is_https_request(),
    'detail' => emsp_pwa_is_https_request() ? 'HTTPS actif' : 'HTTPS requis pour installation PWA',
];

$checks['manifest_exposed'] = [
    'ok' => emsp_pwa_can_expose(),
    'detail' => emsp_pwa_can_expose() ? 'Manifest autorisé sur cet hôte' : 'Manifest masqué (HTTP non sécurisé)',
];

$manifestPayload = emsp_pwa_manifest_payload();
$checks['manifest_json'] = [
    'ok' => is_array($manifestPayload) && !empty($manifestPayload['name']),
    'detail' => 'Payload manifest généré localement (PHP)',
];

$manifestHttp = emsp_pwa_http_probe($manifestUrl);
$manifestTypeOk = $manifestHttp['content_type'] === null
    || stripos((string) $manifestHttp['content_type'], 'manifest') !== false
    || stripos((string) $manifestHttp['content_type'], 'json') !== false;
$checks['manifest_http'] = array_merge($manifestHttp, [
    'url' => $manifestUrl,
    'content_type_ok' => $manifestTypeOk,
    'ok' => $manifestHttp['ok'] && $manifestTypeOk,
    'detail' => $manifestHttp['ok']
        ? ($manifestTypeOk
            ? 'GET /manifest.json OK (rewrite htaccess → manifest.php)'
            : 'GET OK mais Content-Type inattendu : ' . ($manifestHttp['content_type'] ?? 'n/a'))
        : $manifestHttp['detail'],
]);

foreach (['192' => $icon192Url, '512' => $icon512Url] as $label => $url) {
    $localPath = __DIR__ . '/assets/images/logo-emsp-' . $label . '.png';
    $exists = is_file($localPath);
    $http = emsp_pwa_http_probe($url);
    $checks['icon_' . $label] = [
        'ok' => $exists && $http['ok'],
        'url' => $url,
        'local_file' => $exists,
        'http' => $http['status'],
        'detail' => $exists
            ? ($http['ok'] ? 'Fichier local + HTTP OK' : 'Fichier local mais HTTP ' . ($http['status'] ?? 'erreur'))
            : 'Fichier manquant sur le serveur',
    ];
}

$swLocal = __DIR__ . '/sw.js';
$swHttp = emsp_pwa_http_probe($swUrl);
$swTypeOk = $swHttp['content_type'] === null
    || stripos((string) $swHttp['content_type'], 'javascript') !== false;
$checks['service_worker_file'] = [
    'ok' => is_file($swLocal) && is_readable($swLocal) && $swHttp['ok'] && $swTypeOk,
    'url' => $swUrl,
    'scope' => $scopeSlash,
    'local_file' => is_file($swLocal),
    'http' => $swHttp['status'],
    'content_type' => $swHttp['content_type'],
    'detail' => is_file($swLocal)
        ? ($swHttp['ok']
            ? 'sw.js local + GET HTTP OK (vérifier Service-Worker-Allowed: / dans DevTools)'
            : 'sw.js local mais GET HTTP ' . ($swHttp['status'] ?? 'erreur'))
        : 'sw.js introuvable à la racine document',
];

$startUrl = (string) ($manifestPayload['start_url'] ?? '');
$scopeManifest = (string) ($manifestPayload['scope'] ?? '');
$startInScope = $startUrl === './'
    || $startUrl === '/'
    || str_starts_with($startUrl, './')
    || str_starts_with($startUrl, $scopeManifest);
$checks['start_url_in_scope'] = [
    'ok' => $startInScope,
    'start_url' => $startUrl,
    'scope' => $scopeManifest,
    'detail' => $startInScope
        ? 'start_url relatif dans scope (./ recommandé par Chrome)'
        : 'start_url hors scope manifest',
];

$allOk = true;
foreach ($checks as $check) {
    if (empty($check['ok'])) {
        $allOk = false;
        break;
    }
}

echo json_encode([
    'app' => 'EMSP Docs',
    'generated_at' => gmdate('c'),
    'origin' => $origin,
    'scope_path' => $scopeSlash,
    'manifest_url' => $manifestUrl,
    'sw_url' => $swUrl,
    'installable_hint' => $allOk
        ? 'Critères serveur OK — ouvrir DevTools > Application > Manifest + Service Workers ; console [PWA] pour l’enregistrement client'
        : 'Corriger les checks en échec ci-dessous',
    'client_steps' => [
        'chrome' => 'DevTools > Application : Manifest valide + SW actif ; console doit afficher [PWA] SW registered. Icône ⊕ barre d’adresse ou menu ⋮ > Installer. Engagement requis (2 visites ~5 min).',
        'safari_ios' => 'Pas de prompt auto : Partager > Sur l’écran d’accueil. Safari uniquement.',
        'safari_macos' => 'Fichier > Ajouter au Dock (Sonoma+) si critères remplis.',
    ],
    'checks' => $checks,
    'manifest_preview' => $manifestPayload,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
