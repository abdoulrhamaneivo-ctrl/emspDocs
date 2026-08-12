<?php

declare(strict_types=1);

// Mêmes variables d'environnement que l'ancien admin/config/dbcon.php,
// aucune valeur en dur ici : tout vient de .env (jamais commité).
return [
    'host' => $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost',
    'name' => $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: '',
    'user' => $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root',
    'pass' => $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '',
    'charset' => 'utf8mb4',
];