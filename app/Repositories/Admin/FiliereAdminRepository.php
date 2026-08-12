<?php

declare(strict_types=1);

namespace App\Repositories\Admin;

use App\Core\DatabaseHelper;
use PDO;
use Throwable;

final class FiliereAdminRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function ensureEditorialColumns(): void
    {
        DatabaseHelper::ensureFiliereEditorialColumns($this->pdo);
    }

    public function programColumnEnabled(): bool
    {
        return DatabaseHelper::columnExists($this->pdo, 'filieres', 'formation_program');
    }

    public function editorialEnabled(): bool
    {
        return DatabaseHelper::columnExists($this->pdo, 'filieres', 'summary')
            && DatabaseHelper::columnExists($this->pdo, 'filieres', 'description_html')
            && DatabaseHelper::columnExists($this->pdo, 'filieres', 'cover_image_path');
    }

    /** @return string[] */
    private function selectColumns(bool $withCode = false): array
    {
        $columns = ['id', 'name', 'status'];
        if ($withCode) {
            $columns[] = 'code';
        }
        if ($this->editorialEnabled()) {
            $columns = array_merge($columns, ['summary', 'description_html', 'cover_image_path']);
        }
        if ($this->programColumnEnabled()) {
            $columns[] = 'formation_program';
        }

        return $columns;
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        $sql = 'SELECT ' . implode(', ', $this->selectColumns()) . ' FROM filieres ORDER BY name';
        return $this->pdo->query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $sql = 'SELECT ' . implode(', ', $this->selectColumns(true)) . ' FROM filieres WHERE id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function codeExists(string $code, ?int $excludeId): bool
    {
        if ($excludeId) {
            $stmt = $this->pdo->prepare('SELECT id FROM filieres WHERE code = :code AND id <> :eid LIMIT 1');
            $stmt->execute(['code' => $code, 'eid' => $excludeId]);
        } else {
            $stmt = $this->pdo->prepare('SELECT id FROM filieres WHERE code = :code LIMIT 1');
            $stmt->execute(['code' => $code]);
        }
        return (bool) $stmt->fetch();
    }

    /**
     * @param array{name:string,code:string,status:string,summary:?string,description_html:?string,cover_image_path:?string,formation_program:?string} $data
     */
    public function save(?int $id, array $data): bool
    {
        $editorial = $this->editorialEnabled();
        $programColumn = $this->programColumnEnabled();
        try {
            if ($id) {
                if ($editorial && $programColumn) {
                    $stmt = $this->pdo->prepare('UPDATE filieres SET name=:name, code=:code, status=:status, summary=:summary, description_html=:desc, cover_image_path=:cover, formation_program=:program WHERE id=:id');
                    $stmt->execute(['name' => $data['name'], 'code' => $data['code'], 'status' => $data['status'], 'summary' => $data['summary'], 'desc' => $data['description_html'], 'cover' => $data['cover_image_path'], 'program' => $data['formation_program'] ?? '', 'id' => $id]);
                } elseif ($editorial) {
                    $stmt = $this->pdo->prepare('UPDATE filieres SET name=:name, code=:code, status=:status, summary=:summary, description_html=:desc, cover_image_path=:cover WHERE id=:id');
                    $stmt->execute(['name' => $data['name'], 'code' => $data['code'], 'status' => $data['status'], 'summary' => $data['summary'], 'desc' => $data['description_html'], 'cover' => $data['cover_image_path'], 'id' => $id]);
                } else {
                    $stmt = $this->pdo->prepare('UPDATE filieres SET name=:name, code=:code, status=:status WHERE id=:id');
                    $stmt->execute(['name' => $data['name'], 'code' => $data['code'], 'status' => $data['status'], 'id' => $id]);
                }
            } else {
                if ($editorial && $programColumn) {
                    $stmt = $this->pdo->prepare('INSERT INTO filieres (name, code, status, summary, description_html, cover_image_path, formation_program) VALUES (:name, :code, :status, :summary, :desc, :cover, :program)');
                    $stmt->execute(['name' => $data['name'], 'code' => $data['code'], 'status' => $data['status'], 'summary' => $data['summary'], 'desc' => $data['description_html'], 'cover' => $data['cover_image_path'], 'program' => $data['formation_program'] ?? '']);
                } elseif ($editorial) {
                    $stmt = $this->pdo->prepare('INSERT INTO filieres (name, code, status, summary, description_html, cover_image_path) VALUES (:name, :code, :status, :summary, :desc, :cover)');
                    $stmt->execute(['name' => $data['name'], 'code' => $data['code'], 'status' => $data['status'], 'summary' => $data['summary'], 'desc' => $data['description_html'], 'cover' => $data['cover_image_path']]);
                } else {
                    $stmt = $this->pdo->prepare('INSERT INTO filieres (name, code, status) VALUES (:name, :code, :status)');
                    $stmt->execute(['name' => $data['name'], 'code' => $data['code'], 'status' => $data['status']]);
                }
            }
            return true;
        } catch (Throwable $e) {
            error_log('EMSP admin filiere save failed: ' . $e->getMessage());
            return false;
        }
    }

    public function toggleStatus(int $id): void
    {
        $stmt = $this->pdo->prepare("UPDATE filieres SET status = IF(status='active','inactive','active') WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public function usageCount(int $id): int
    {
        if ($id <= 0) {
            return 0;
        }

        $total = 0;
        $checks = [
            'SELECT COUNT(*) FROM users WHERE filiere_id = :id',
            'SELECT COUNT(*) FROM documents WHERE filiere_id = :id',
            'SELECT COUNT(*) FROM licence_filieres WHERE filiere_id = :id',
        ];

        if (DatabaseHelper::documentFilieresEnabled($this->pdo)) {
            $checks[] = 'SELECT COUNT(*) FROM document_filieres WHERE filiere_id = :id';
        }

        foreach ($checks as $sql) {
            try {
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute(['id' => $id]);
                $total += (int) $stmt->fetchColumn();
            } catch (Throwable) {
                continue;
            }
        }

        return $total;
    }

    public function delete(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $item = $this->find($id);
        if (!$item) {
            return false;
        }

        try {
            if (DatabaseHelper::documentFilieresEnabled($this->pdo)) {
                $delPivot = $this->pdo->prepare('DELETE FROM document_filieres WHERE filiere_id = :id');
                $delPivot->execute(['id' => $id]);
            }

            if (DatabaseHelper::tableExists($this->pdo, 'licence_filieres')) {
                $delLicence = $this->pdo->prepare('DELETE FROM licence_filieres WHERE filiere_id = :id');
                $delLicence->execute(['id' => $id]);
            }

            $stmt = $this->pdo->prepare('DELETE FROM filieres WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            error_log('EMSP admin filiere delete failed: ' . $e->getMessage());
            return false;
        }
    }
}
