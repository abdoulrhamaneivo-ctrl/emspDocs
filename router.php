<?php

declare(strict_types=1);

/**
 * Front controller EMSP Docs — MVC.
 *
 * Point d'entree unique pour toute URL "propre" ne correspondant à aucun
 * fichier/dossier reel existant (cf. .htaccess), y compris la page
 * d'accueil ('/') depuis que l'ancien index.php a ete migre vers
 * App\Controllers\HomeController.
 */

require __DIR__ . '/config/bootstrap.php';

$routes = require __DIR__ . '/routes/web.php';
$requestUri = (string) $_SERVER['REQUEST_URI'];
$requestPath = (string) (parse_url($requestUri, PHP_URL_PATH) ?: '/');
$basePath = rtrim((string) (parse_url((string) config('base_url'), PHP_URL_PATH) ?: ''), '/');
if ($basePath === '') {
    $basePath = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/router.php'))), '/');
}

// Une installation locale XAMPP peut vivre dans un sous-dossier (par exemple
// /emsp-docs). Les routes applicatives restent cependant définies depuis la
// racine. On retire donc uniquement ce préfixe local, sans toucher aux URL de
// production installées directement à la racine du domaine.
if ($basePath !== '' && $basePath !== '/' && ($requestPath === $basePath || str_starts_with($requestPath, $basePath . '/'))) {
    $relativePath = substr($requestPath, strlen($basePath));
    $requestUri = ($relativePath === '' ? '/' : $relativePath)
        . (($query = parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_QUERY)) !== null ? '?' . $query : '');
}

$matched = (new App\Core\Router($routes))->dispatch(
    $_SERVER['REQUEST_METHOD'],
    $requestUri
);

if (!$matched) {
    http_response_code(404);
    (new App\Controllers\ErrorController())->notFound();
}
