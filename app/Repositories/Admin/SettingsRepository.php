<?php

declare(strict_types=1);

namespace App\Repositories\Admin;

use PDO;

final class SettingsRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function ensureTableExists(): void
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS app_settings (
            skey VARCHAR(80) NOT NULL PRIMARY KEY,
            svalue TEXT NULL
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    /** @return array<string, string> */
    public function all(): array
    {
        $settings = [];
        foreach ($this->pdo->query('SELECT skey, svalue FROM app_settings')->fetchAll() as $row) {
            $settings[(string) $row['skey']] = (string) ($row['svalue'] ?? '');
        }
        return $settings;
    }

    public function set(string $key, string $value): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO app_settings (skey, svalue) VALUES (:key, :val) ON DUPLICATE KEY UPDATE svalue = :val2'
        );
        $stmt->execute(['key' => $key, 'val' => $value, 'val2' => $value]);
    }
}
