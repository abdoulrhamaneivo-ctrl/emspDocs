<?php

declare(strict_types=1);

namespace App\Repositories\Admin;

use PDO;

/**
 * Repository générique pour les référentiels pédagogiques "simples"
 * (nom + statut actif/inactif + un parent optionnel). Couvre Matières et
 * Modules.
 */
final class TaxonomyRepository
{
    public function __construct(
        private PDO $pdo,
        private string $table,
        private ?string $parentTable = null,
        private ?string $parentColumn = null,
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        $extraJoin = '';
        $extraSelect = '';
        if ($this->table === 'matieres') {
            $extraJoin = 'LEFT JOIN licences l ON l.id = md.licence_id';
            $extraSelect = ', l.name AS licence_name';
        }

        $parentAlias = $this->table === 'matieres' ? 'md' : 'p';
        $parentJoin = $this->parentTable
            ? "LEFT JOIN {$this->parentTable} $parentAlias ON $parentAlias.id = t.{$this->parentColumn}"
            : '';
        $parentSelect = $this->parentTable
            ? ", $parentAlias.name AS parent_name"
            : ', NULL AS parent_name';

        $sql = "SELECT t.id, t.name, t.status $parentSelect $extraSelect
                FROM {$this->table} t
                $parentJoin
                $extraJoin
                ORDER BY t.name";

        $rows = [];
        foreach ($this->pdo->query($sql)->fetchAll() as $row) {
            foreach (['name', 'parent_name', 'licence_name'] as $f) {
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
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if ($row && isset($row['name']) && is_string($row['name'])) {
            $row['name'] = emsp_fix_mojibake($row['name']);
        }
        return $row ?: null;
    }

    /** @return array<int, array{id:int, name:string}> Options actives du parent */
    public function parentOptions(): array
    {
        if (!$this->parentTable) {
            return [];
        }
        $rows = [];
        foreach ($this->pdo->query("SELECT id, name FROM {$this->parentTable} WHERE status='active' ORDER BY name")->fetchAll() as $row) {
            $row['name'] = emsp_fix_mojibake((string) ($row['name'] ?? ''));
            $rows[] = $row;
        }
        return $rows;
    }

    public function save(int $id, string $name, ?int $parentId, string $status): void
    {
        if ($id > 0) {
            $stmt = $this->pdo->prepare("UPDATE {$this->table} SET name = :name, {$this->parentColumn} = :pid, status = :status WHERE id = :id");
            $stmt->execute(['name' => $name, 'pid' => $parentId, 'status' => $status, 'id' => $id]);
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO {$this->table} (name, {$this->parentColumn}, status) VALUES (:name, :pid, :status)");
            $stmt->execute(['name' => $name, 'pid' => $parentId, 'status' => $status]);
        }
    }

    public function toggleStatus(int $id): void
    {
        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET status = IF(status='active','inactive','active') WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
}
