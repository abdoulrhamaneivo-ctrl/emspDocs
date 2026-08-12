<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $config = require dirname(__DIR__, 2) . '/config/database.php';
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['host'], $config['name'], $config['charset']);

        try {
            self::$pdo = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            self::$pdo->exec("SET time_zone = '+00:00'");
        } catch (PDOException $e) {
            // Jamais de détail de connexion affiché au visiteur (même logique que
            // l'ancien admin/config/dbcon.php).
            error_log('EMSP DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
            die("Erreur de connexion à la base de données. Contactez l'administrateur.");
        }

        return self::$pdo;
    }
}
