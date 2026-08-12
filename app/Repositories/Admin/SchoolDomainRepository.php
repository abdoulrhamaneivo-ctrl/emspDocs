<?php

declare(strict_types=1);

namespace App\Repositories\Admin;

use PDO;

final class SchoolDomainRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function ensureTableExists(): void
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS school_email_domains (
          id INT AUTO_INCREMENT PRIMARY KEY,
          domain VARCHAR(100) NOT NULL UNIQUE,
          status ENUM('active','inactive') NOT NULL DEFAULT 'active',
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public static function normalize(string $input): string
    {
        $d = strtolower(trim($input));
        if ($d === '') {
            return '';
        }
        $pos = strpos($d, '@');
        $d = $pos !== false ? substr($d, $pos) : '@' . $d;
        return (string) preg_replace('/\s+/', '', $d);
    }

    public static function isValid(string $domain): bool
    {
        return (bool) preg_match('/^@[a-z0-9.-]+\.[a-z]{2,}$/', $domain);
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->pdo->query('SELECT * FROM school_email_domains ORDER BY status DESC, domain ASC')->fetchAll();
    }

    public function add(string $domain): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO school_email_domains (domain, status) VALUES (:domain, 'active')");
        $stmt->execute(['domain' => $domain]);
    }

    public function update(int $id, string $domain): void
    {
        $stmt = $this->pdo->prepare('UPDATE school_email_domains SET domain = :domain WHERE id = :id');
        $stmt->execute(['domain' => $domain, 'id' => $id]);
    }

    public function toggle(int $id): void
    {
        $stmt = $this->pdo->prepare("UPDATE school_email_domains SET status = IF(status='active','inactive','active') WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM school_email_domains WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
    }
}
