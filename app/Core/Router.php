<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    public function __construct(private array $routes)
    {
    }

    /**
     * Retourne true si une route MVC a pris en charge la requête.
     * Retourne false si aucune route ne correspond (l'appelant peut alors
     * laisser Apache servir un ancien fichier .php existant, ou afficher 404).
     */
    public function dispatch(string $method, string $uri): bool
    {
        $path = trim((string) (parse_url($uri, PHP_URL_PATH) ?: '/'), '/');
        $path = $path === '' ? '/' : '/' . $path;

        foreach ($this->routes as $route) {
            [$verb, $pattern, $handler] = $route;
            if ($verb !== $method) {
                continue;
            }
            $regex = '#^' . preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
            if (preg_match($regex, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                [$class, $action] = $handler;
                (new $class())->$action($params);
                return true;
            }
        }

        return false;
    }
}
