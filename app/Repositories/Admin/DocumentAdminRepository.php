<?php

declare(strict_types=1);

namespace App\Repositories\Admin;

use App\Core\DatabaseHelper;
use PDO;
use RuntimeException;
use Throwable;

final class DocumentAdminRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function pendingList(): array
    {
        $pendingMatiereEnabled = DatabaseHelper::pendingMatiereEnabled($this->pdo);
        $matiereLabelSelect = $pendingMatiereEnabled ? 'd.matiere_label_pending,' : 'NULL AS matiere_label_pending,';

        $sql = "SELECT d.id, d.title, d.description, d.doc_type, d.file_size_bytes, d.created_at,
                       d.is_public, d.semester, d.matiere_id, $matiereLabelSelect
                       u.first_name, u.last_name, u.email,
                       l.name AS licence_name, ma.name AS matiere_name
                FROM documents d
                JOIN users u ON u.id = d.uploader_id
                LEFT JOIN licences l ON l.id = d.licence_id
                LEFT JOIN matieres ma ON ma.id = d.matiere_id
                WHERE d.status='pending'
                ORDER BY d.created_at ASC";
        $docs = [];
        foreach ($this->pdo->query($sql)->fetchAll() as $row) {
            foreach (['title', 'description', 'first_name', 'last_name', 'email', 'licence_name', 'matiere_name', 'matiere_label_pending'] as $field) {
                if (isset($row[$field]) && is_string($row[$field])) {
                    $row[$field] = emsp_fix_mojibake($row[$field]);
                }
            }
            $docs[] = $row;
        }

        $docIds = array_map(static fn(array $r): int => (int) ($r['id'] ?? 0), $docs);
        $filiereLabels = DatabaseHelper::fetchDocumentFiliereLabels($this->pdo, $docIds);

        foreach ($docs as &$d) {
            $labels = $filiereLabels[(int) $d['id']] ?? [];
            $d['filiere_label'] = !empty($labels) ? implode(', ', $labels) : 'Non renseignée';
            $matiereName = trim((string) ($d['matiere_name'] ?? ''));
            $d['matiere_display'] = $matiereName !== ''
                ? $matiereName
                : (trim((string) ($d['matiere_label_pending'] ?? '')) !== '' ? $d['matiere_label_pending'] . ' (à valider)' : 'À valider');
        }
        unset($d);

        return $docs;
    }

    /** @return array<int, array{id:int, name:string}> */
    public function activeMatieres(): array
    {
        $rows = [];
        foreach ($this->pdo->query("SELECT id, name FROM matieres WHERE status='active' ORDER BY name")->fetchAll() as $row) {
            $row['name'] = emsp_fix_mojibake((string) ($row['name'] ?? ''));
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Transaction d'approbation : verrouillage FOR UPDATE, résolution
     * de la matière en attente, synchronisation filières, badge.
     *
     * @return array{ok: bool, error: ?string, doc: ?array}
     */
    public function approve(int $docId, int $adminId, int $reviewMatiereId, string $reviewMatiereLabel): array
    {
        $pendingMatiereEnabled = DatabaseHelper::pendingMatiereEnabled($this->pdo);
        $this->pdo->beginTransaction();

        try {
            $doc = $this->lockPendingDocument($docId, $pendingMatiereEnabled);
            if (!$doc) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'not_found', 'doc' => null];
            }

            $currentFiliereIds = $this->currentFiliereIds($docId, (int) $doc['filiere_id']);

            $resolvedMatiereId = (int) ($doc['matiere_id'] ?? 0);
            $pendingLabel = trim((string) ($doc['matiere_label_pending'] ?? ''));

            if ($pendingLabel !== '') {
                if ($reviewMatiereId <= 0 && $reviewMatiereLabel === '') {
                    $this->pdo->rollBack();
                    return ['ok' => false, 'error' => 'matiere_required', 'doc' => null];
                }
                $matiereError = null;
                $resolvedMatiereId = emsp_resolve_matiere_id(
                    $this->pdo,
                    $reviewMatiereId > 0 ? $reviewMatiereId : null,
                    $reviewMatiereLabel !== '' ? $reviewMatiereLabel : $pendingLabel,
                    $matiereError
                );
                if ($resolvedMatiereId <= 0) {
                    $this->pdo->rollBack();
                    return ['ok' => false, 'error' => $matiereError ?: 'matiere_invalid', 'doc' => null];
                }
            }

            $approveSql = "UPDATE documents
                           SET status='approved', approved_by = :admin, approved_at=NOW(),
                               rejection_reason=NULL, matiere_id = :matiere"
                . ($pendingMatiereEnabled ? ', matiere_label_pending=NULL' : '')
                . " WHERE id = :id AND status='pending'";
            $upd = $this->pdo->prepare($approveSql);
            $upd->execute(['admin' => $adminId, 'matiere' => $resolvedMatiereId, 'id' => $docId]);
            $affected = $upd->rowCount();

            if ($affected !== 1) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'already_processed', 'doc' => null];
            }

            DatabaseHelper::syncDocumentFilieres($this->pdo, $docId, $currentFiliereIds);
            $this->incrementUploadCountAndBadge((int) $doc['uploader_id']);

            $this->pdo->commit();
            return ['ok' => true, 'error' => null, 'doc' => $doc];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            error_log('EMSP pending-documents approve failed: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'technical', 'doc' => null];
        }
    }

    /** @return array{ok: bool, error: ?string, doc: ?array} */
    public function reject(int $docId, int $adminId, string $motif): array
    {
        $this->pdo->beginTransaction();
        try {
            $doc = $this->lockPendingDocument($docId, DatabaseHelper::pendingMatiereEnabled($this->pdo));
            if (!$doc) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'not_found', 'doc' => null];
            }

            $upd = $this->pdo->prepare(
                "UPDATE documents SET status='rejected', approved_by = :admin, approved_at=NOW(), rejection_reason = :motif
                 WHERE id = :id AND status='pending'"
            );
            $upd->execute(['admin' => $adminId, 'motif' => $motif, 'id' => $docId]);
            $affected = $upd->rowCount();

            if ($affected !== 1) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'already_processed', 'doc' => null];
            }

            $this->pdo->commit();
            return ['ok' => true, 'error' => null, 'doc' => $doc];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            error_log('EMSP pending-documents reject failed: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'technical', 'doc' => null];
        }
    }

    private function lockPendingDocument(int $docId, bool $pendingMatiereEnabled): ?array
    {
        $selectSql = 'SELECT d.id, d.title, d.status, d.uploader_id, d.filiere_id, d.matiere_id, '
            . ($pendingMatiereEnabled ? 'd.matiere_label_pending, ' : 'NULL AS matiere_label_pending, ')
            . 'u.first_name, u.last_name, u.email
               FROM documents d
               JOIN users u ON u.id = d.uploader_id
               WHERE d.id = :id LIMIT 1 FOR UPDATE';
        $s = $this->pdo->prepare($selectSql);
        $s->execute(['id' => $docId]);
        $doc = $s->fetch();

        if ($doc) {
            foreach (['title', 'status', 'first_name', 'last_name', 'email', 'matiere_label_pending'] as $field) {
                if (isset($doc[$field]) && is_string($doc[$field])) {
                    $doc[$field] = emsp_fix_mojibake($doc[$field]);
                }
            }
        }

        return ($doc && $doc['status'] === 'pending') ? $doc : null;
    }

    /** @return int[] */
    private function currentFiliereIds(int $docId, int $fallbackFiliereId): array
    {
        if (DatabaseHelper::documentFilieresEnabled($this->pdo)) {
            $stmt = $this->pdo->prepare('SELECT filiere_id FROM document_filieres WHERE document_id = :did');
            $stmt->execute(['did' => $docId]);
            $ids = array_column($stmt->fetchAll(), 'filiere_id');
            $ids = array_values(array_filter(array_map('intval', $ids), fn(int $id) => $id > 0));
        } else {
            $ids = [];
        }
        if (empty($ids) && $fallbackFiliereId > 0) {
            $ids = [$fallbackFiliereId];
        }
        return $ids;
    }

    private function incrementUploadCountAndBadge(int $uploaderId): void
    {
        $this->pdo->prepare('UPDATE users SET upload_count = upload_count + 1 WHERE id = :id')
            ->execute(['id' => $uploaderId]);

        $uc = $this->pdo->prepare('SELECT upload_count, badge_level FROM users WHERE id = :id LIMIT 1 FOR UPDATE');
        $uc->execute(['id' => $uploaderId]);
        $ur = $uc->fetch();

        if (!$ur) {
            return;
        }
        $currentBadge = (string) ($ur['badge_level'] ?? 'none');
        $uploadCount = (int) ($ur['upload_count'] ?? 0);
        $newBadge = $currentBadge;
        if ($uploadCount >= 5) {
            $newBadge = 'argent';
        } elseif ($uploadCount >= 1) {
            $newBadge = 'bronze';
        }
        if ($newBadge !== $currentBadge) {
            $this->pdo->prepare('UPDATE users SET badge_level = :badge WHERE id = :id')
                ->execute(['badge' => $newBadge, 'id' => $uploaderId]);
        }
    }
}
