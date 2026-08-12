<?php

declare(strict_types=1);

namespace App\Repositories\Admin;

use App\Core\DatabaseHelper;
use PDO;

final class MediaAdminRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function ensureSchema(): void
    {
        $this->ensureTableExists();
        $this->ensureCategoriesTable();
        DatabaseHelper::ensureMediaColumns($this->pdo);
    }

    public function ensureTableExists(): bool
    {
        $check = $this->pdo->query("SHOW TABLES LIKE 'media'");
        if ($check && $check->rowCount() > 0) {
            return true;
        }
        $sql = "CREATE TABLE IF NOT EXISTS media (
                  id INT(11) NOT NULL AUTO_INCREMENT,
                  title VARCHAR(200) NOT NULL,
                  description TEXT DEFAULT NULL,
                  type ENUM('image','video','lien') NOT NULL,
                  file_path VARCHAR(255) NOT NULL,
                  poster_path VARCHAR(255) DEFAULT NULL,
                  category VARCHAR(100) DEFAULT NULL,
                  category_id INT(11) DEFAULT NULL,
                  is_public TINYINT(1) NOT NULL DEFAULT 1,
                  status ENUM('published','archived') DEFAULT 'published',
                  display_order INT NOT NULL DEFAULT 0,
                  created_by INT(11) NOT NULL,
                  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->pdo->exec($sql);
        return true;
    }

    private function ensureCategoriesTable(): void
    {
        if (DatabaseHelper::tableExists($this->pdo, 'media_categories')) {
            return;
        }
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS media_categories (
              id INT(11) NOT NULL AUTO_INCREMENT,
              name VARCHAR(100) NOT NULL,
              description VARCHAR(255) DEFAULT NULL,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (id),
              UNIQUE KEY name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    /** @return string[] */
    public function allowedStatuses(): array
    {
        $allowed = ['published', 'archived'];
        $res = $this->pdo->query("SHOW COLUMNS FROM media LIKE 'status'");
        if ($res) {
            $row = $res->fetch();
            if (!empty($row['Type']) && preg_match_all("/'([^']+)'/", $row['Type'], $m) && !empty($m[1])) {
                $allowed = $m[1];
            }
        }
        return $allowed;
    }

    /** @return array{total:int, images:int, videos:int, publics:int} */
    public function counters(): array
    {
        $get = fn(string $sql): int => (int) $this->pdo->query($sql)->fetchColumn();
        return [
            'total' => $get('SELECT COUNT(*) FROM media'),
            'images' => $get("SELECT COUNT(*) FROM media WHERE type='image'"),
            'videos' => $get("SELECT COUNT(*) FROM media WHERE type IN ('video','lien')"),
            'publics' => $get('SELECT COUNT(*) FROM media WHERE is_public=1'),
        ];
    }

    /** @return array<int, array{id:int, name:string}> */
    public function categories(): array
    {
        if (!DatabaseHelper::tableExists($this->pdo, 'media_categories')) {
            return [];
        }
        $rows = [];
        foreach ($this->pdo->query('SELECT id, name FROM media_categories ORDER BY name ASC')->fetchAll() as $row) {
            $row['name'] = emsp_fix_mojibake((string) ($row['name'] ?? ''));
            $rows[] = $row;
        }
        return $rows;
    }

    /** @return array<int, array<string, mixed>> */
    public function listCategoriesFull(): array
    {
        if (!DatabaseHelper::tableExists($this->pdo, 'media_categories')) {
            return [];
        }
        $rows = [];
        foreach ($this->pdo->query('SELECT * FROM media_categories ORDER BY created_at DESC')->fetchAll() as $row) {
            foreach (['name', 'description'] as $f) {
                if (isset($row[$f]) && is_string($row[$f])) {
                    $row[$f] = emsp_fix_mojibake($row[$f]);
                }
            }
            $rows[] = $row;
        }
        return $rows;
    }

    /** @return array<int, array<string, mixed>> */
    public function list(string $filter): array
    {
        $orderCol = DatabaseHelper::columnExists($this->pdo, 'media', 'display_order')
            ? 'm.display_order DESC, m.created_at DESC, m.id DESC'
            : 'm.created_at DESC, m.id DESC';

        $sql = "SELECT m.id, m.title, m.description, m.type, m.file_path, m.poster_path, m.category, m.category_id,
                       m.is_public, m.status, m.created_at,
                       u.first_name, u.last_name, mc.name AS cat_name
                FROM media m
                LEFT JOIN users u ON u.id = m.created_by
                LEFT JOIN media_categories mc ON mc.id = m.category_id";

        $params = [];
        if ($filter === 'image' || $filter === 'video') {
            if ($filter === 'video') {
                $sql .= " WHERE m.type IN ('video','lien')";
            } else {
                $sql .= ' WHERE m.type = :filter';
                $params['filter'] = $filter;
            }
        } elseif ($filter === 'public' || $filter === 'private') {
            $sql .= ' WHERE m.is_public = :filter';
            $params['filter'] = $filter === 'public' ? 1 : 0;
        }
        $sql .= ' ORDER BY ' . $orderCol;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            foreach (['title', 'description', 'category', 'cat_name', 'first_name', 'last_name'] as $f) {
                if (isset($row[$f]) && is_string($row[$f])) {
                    $row[$f] = emsp_fix_mojibake($row[$f]);
                }
            }
        }
        unset($row);

        return $rows;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT title, is_public, status, file_path, poster_path, type FROM media WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findFull(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM media WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        foreach (['title', 'description', 'category'] as $f) {
            if (isset($row[$f]) && is_string($row[$f])) {
                $row[$f] = emsp_fix_mojibake($row[$f]);
            }
        }
        return $row;
    }

    public function toggleVisibility(int $id): void
    {
        $this->pdo->prepare('UPDATE media SET is_public = IF(is_public=1, 0, 1) WHERE id = :id LIMIT 1')
            ->execute(['id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM media WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function resolveLocalPath(string $filePath): ?string
    {
        $trimmed = trim($filePath);
        if ($trimmed === '' || preg_match('#^https?://#i', $trimmed)) {
            return null;
        }
        $projectRoot = dirname(__DIR__, 3);
        $candidates = [];
        if (strpos($trimmed, 'uploads/') === 0 || strpos($trimmed, 'assets/') === 0) {
            $candidates[] = $projectRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $trimmed);
        } else {
            $base = basename($trimmed);
            $candidates[] = $projectRoot . '/uploads/media/' . $base;
            $candidates[] = $projectRoot . '/uploads/media/images/' . $base;
            $candidates[] = $projectRoot . '/uploads/media/videos/' . $base;
            $candidates[] = $projectRoot . '/uploads/media/posters/' . $base;
        }
        foreach ($candidates as $candidate) {
            $real = realpath($candidate);
            if ($real === false || !is_file($real)) {
                continue;
            }
            $uploadsMediaRoot = realpath($projectRoot . '/uploads/media');
            if ($uploadsMediaRoot !== false && strpos($real, $uploadsMediaRoot) === 0) {
                return $real;
            }
        }
        return null;
    }

    /** @return array{category: ?string, category_id: ?int} */
    public function resolveCategory(int $categoryId, string $categoryRaw): array
    {
        $categoryName = null;
        if ($categoryId > 0) {
            $cs = $this->pdo->prepare('SELECT name FROM media_categories WHERE id = :id LIMIT 1');
            $cs->execute(['id' => $categoryId]);
            $categoryName = $cs->fetchColumn() ?: null;
        }
        $categoryIdDb = ($categoryId > 0 && $categoryName) ? $categoryId : null;
        $category = $categoryName ?: ($categoryRaw !== '' ? $categoryRaw : null);

        return ['category' => $category, 'category_id' => $categoryIdDb];
    }

    /** @return array{ok: bool, error?: string, id?: int} */
    public function create(array $data): array
    {
        $cat = $this->resolveCategory((int) ($data['category_id'] ?? 0), (string) ($data['category_raw'] ?? ''));

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO media (title, description, type, file_path, poster_path, category, category_id,
                                    is_public, status, display_order, created_by, created_at)
                 VALUES (:title, :desc, :type, :path, :poster, :cat, :catid, :public, :status, :ord, :by, NOW())"
            );
            $stmt->execute([
                'title' => $data['title'],
                'desc' => $data['description'],
                'type' => $data['type'],
                'path' => $data['file_path'],
                'poster' => $data['poster_path'] ?? null,
                'cat' => $cat['category'],
                'catid' => $cat['category_id'],
                'public' => $data['is_public'],
                'status' => $data['status'],
                'ord' => (int) ($data['display_order'] ?? 0),
                'by' => $data['created_by'],
            ]);
            return ['ok' => true, 'id' => (int) $this->pdo->lastInsertId()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /** @return array{ok: bool, error?: string} */
    public function update(int $id, array $data): array
    {
        $cat = $this->resolveCategory((int) ($data['category_id'] ?? 0), (string) ($data['category_raw'] ?? ''));

        try {
            $stmt = $this->pdo->prepare(
                "UPDATE media SET title = :title, description = :desc, type = :type, file_path = :path,
                                    poster_path = :poster, category = :cat, category_id = :catid,
                                    is_public = :public, status = :status, display_order = :ord
                 WHERE id = :id LIMIT 1"
            );
            $stmt->execute([
                'title' => $data['title'],
                'desc' => $data['description'],
                'type' => $data['type'],
                'path' => $data['file_path'],
                'poster' => $data['poster_path'] ?? null,
                'cat' => $cat['category'],
                'catid' => $cat['category_id'],
                'public' => $data['is_public'],
                'status' => $data['status'],
                'ord' => (int) ($data['display_order'] ?? 0),
                'id' => $id,
            ]);
            return ['ok' => true];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function updatePoster(int $id, string $posterPath): bool
    {
        $stmt = $this->pdo->prepare('UPDATE media SET poster_path = :poster WHERE id = :id LIMIT 1');
        $stmt->execute(['poster' => $posterPath, 'id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /** @return array{ok: bool, error?: string, duplicate?: bool} */
    public function createCategory(string $name, string $description): array
    {
        try {
            $stmt = $this->pdo->prepare('INSERT INTO media_categories (name, description) VALUES (:name, :desc)');
            $stmt->execute(['name' => $name, 'desc' => $description !== '' ? $description : null]);
            return ['ok' => true];
        } catch (\Throwable $e) {
            $duplicate = str_contains($e->getMessage(), '1062') || str_contains(strtolower($e->getMessage()), 'duplicate');
            return ['ok' => false, 'error' => $e->getMessage(), 'duplicate' => $duplicate];
        }
    }

    /** @return array{ok: bool, error?: string, duplicate?: bool} */
    public function updateCategory(int $id, string $name, string $description): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE media_categories SET name = :name, description = :desc WHERE id = :id LIMIT 1'
            );
            $stmt->execute([
                'name' => $name,
                'desc' => $description !== '' ? $description : null,
                'id' => $id,
            ]);
            return ['ok' => $stmt->rowCount() > 0];
        } catch (\Throwable $e) {
            $duplicate = str_contains($e->getMessage(), '1062') || str_contains(strtolower($e->getMessage()), 'duplicate');
            return ['ok' => false, 'error' => $e->getMessage(), 'duplicate' => $duplicate];
        }
    }

    public function deleteCategory(int $id): void
    {
        $this->pdo->prepare('UPDATE media SET category_id = NULL WHERE category_id = :id')->execute(['id' => $id]);
        $this->pdo->prepare('DELETE FROM media_categories WHERE id = :id LIMIT 1')->execute(['id' => $id]);
    }

    public function categoryMediaCount(int $id): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM media WHERE category_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }
}
