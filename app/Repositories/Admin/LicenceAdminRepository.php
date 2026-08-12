<?php

declare(strict_types=1);

namespace App\Repositories\Admin;

use PDO;
use Throwable;

final class LicenceAdminRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        $sql = "SELECT l.id, l.name, l.status,
                       GROUP_CONCAT(f.name ORDER BY f.name SEPARATOR ', ') AS filiere_names
                FROM licences l
                LEFT JOIN licence_filieres lf ON lf.licence_id = l.id
                LEFT JOIN filieres f ON f.id = lf.filiere_id
                GROUP BY l.id, l.name, l.status
                ORDER BY l.name";
        $rows = [];
        foreach ($this->pdo->query($sql)->fetchAll() as $row) {
            foreach (['name', 'filiere_names'] as $f) {
                if (isset($row[$f]) && is_string($row[$f])) {
                    $row[$f] = emsp_fix_mojibake($row[$f]);
                }
            }
            $rows[] = $row;
        }
        return $rows;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM licences WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if ($row && isset($row['name']) && is_string($row['name'])) {
            $row['name'] = emsp_fix_mojibake($row['name']);
        }
        return $row ?: null;
    }

    /** @return int[] */
    public function selectedFiliereIds(int $licenceId): array
    {
        $stmt = $this->pdo->prepare('SELECT filiere_id FROM licence_filieres WHERE licence_id = :lid');
        $stmt->execute(['lid' => $licenceId]);
        return array_column($stmt->fetchAll(), 'filiere_id');
    }

    /** @return array<int, array{id:int, name:string}> */
    public function activeFilieres(): array
    {
        $rows = [];
        foreach ($this->pdo->query("SELECT id, name FROM filieres WHERE status='active' ORDER BY name")->fetchAll() as $row) {
            $row['name'] = emsp_fix_mojibake((string) ($row['name'] ?? ''));
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * @param int[] $filiereIds
     * @return bool true si succès
     */
    public function save(int $id, string $name, string $status, array $filiereIds): bool
    {
        $this->pdo->beginTransaction();
        try {
            if ($id > 0) {
                $s = $this->pdo->prepare('UPDATE licences SET name = :name, status = :status WHERE id = :id');
                $s->execute(['name' => $name, 'status' => $status, 'id' => $id]);

                $del = $this->pdo->prepare('DELETE FROM licence_filieres WHERE licence_id = :lid');
                $del->execute(['lid' => $id]);

                $licenceId = $id;
            } else {
                $s = $this->pdo->prepare('INSERT INTO licences (name, status) VALUES (:name, :status)');
                $s->execute(['name' => $name, 'status' => $status]);
                $licenceId = (int) $this->pdo->lastInsertId();
            }

            if (!empty($filiereIds)) {
                $ins = $this->pdo->prepare('INSERT IGNORE INTO licence_filieres (licence_id, filiere_id) VALUES (:lid, :fid)');
                foreach ($filiereIds as $fid) {
                    if ($fid > 0) {
                        $ins->execute(['lid' => $licenceId, 'fid' => $fid]);
                    }
                }
            }

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            error_log('EMSP admin licence save failed: ' . $e->getMessage());
            return false;
        }
    }

    public function toggleStatus(int $id): void
    {
        $stmt = $this->pdo->prepare("UPDATE licences SET status = IF(status='active','inactive','active') WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
}
