<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AcademicRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function activeFilieres(): array
    {
        return $this->pdo
            ->query("SELECT id, name FROM filieres WHERE status='active' ORDER BY name")
            ->fetchAll();
    }

    public function activeLicences(): array
    {
        return $this->pdo
            ->query("SELECT id, name FROM licences WHERE status='active' ORDER BY name")
            ->fetchAll();
    }

    public function activeMatieres(): array
    {
        return $this->pdo
            ->query("SELECT id, name FROM matieres WHERE status='active' ORDER BY name")
            ->fetchAll();
    }

    public function filiereIsActive(int $id): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM filieres WHERE id = :id AND status='active'");
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function licenceIsActive(int $id): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM licences WHERE id = :id AND status='active'");
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
