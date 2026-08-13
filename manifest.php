<?php

declare(strict_types=1);

/**
 * Manifest PWA dynamique — bootstrap minimal (pas de session/DB) pour éviter
 * les erreurs 500 sur hébergements mutualisés (ProFreeHost / unaux.com).
 */
require __DIR__ . '/config/pwa-manifest.php';

$manifest = emsp_pwa_manifest_payload();

header('Content-Type: application/manifest+json; charset=UTF-8');
header('Cache-Control: public, max-age=3600');
echo json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
