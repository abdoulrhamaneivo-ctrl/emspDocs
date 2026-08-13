<?php

declare(strict_types=1);

namespace App\Repositories\Admin;

use App\Core\DatabaseHelper;
use PDO;
use Throwable;

final class JournalAdminRepository
{
    public bool $hasAdmin;
    public bool $hasAuthor;
    public string $authorColumn;
    public bool $hasStartsAt;
    public bool $hasEndsAt;
    public bool $hasClosedAt;
    public bool $hasClosedBy;
    public bool $hasLifecycle;

    public function __construct(private PDO $pdo)
    {
        $this->hasAdmin = DatabaseHelper::columnExists($pdo, 'journal', 'admin_id');
        $this->hasAuthor = DatabaseHelper::columnExists($pdo, 'journal', 'author_id');
        $this->authorColumn = $this->hasAdmin ? 'admin_id' : ($this->hasAuthor ? 'author_id' : '');
        $this->hasStartsAt = DatabaseHelper::columnExists($pdo, 'journal', 'starts_at');
        $this->hasEndsAt = DatabaseHelper::columnExists($pdo, 'journal', 'ends_at');
        $this->hasClosedAt = DatabaseHelper::columnExists($pdo, 'journal', 'closed_at');
        $this->hasClosedBy = DatabaseHelper::columnExists($pdo, 'journal', 'closed_by');
        $this->hasLifecycle = $this->hasStartsAt && $this->hasEndsAt;
    }

    /** @return array<int, array<string, mixed>> */
    public function list(string $typeFilter, string $statusFilter, string $stateFilter): array
    {
        [$articles] = $this->listPaginated($typeFilter, $statusFilter, $stateFilter, PHP_INT_MAX, 0);
        return $articles;
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: int, 2: array{filtered:int,published:int,draft:int,open:int}}
     */
    public function listPaginated(string $typeFilter, string $statusFilter, string $stateFilter, int $perPage, int $offset): array
    {
        $rows = $this->fetchFilteredRows($typeFilter, $statusFilter);
        $articles = [];
        foreach ($rows as $row) {
            $row['state'] = emsp_journal_state($row);
            $row['excerpt'] = emsp_journal_excerpt((string) ($row['content'] ?? ''), 120);
            $row['author_label'] = $this->authorColumn !== ''
                ? trim(((string) ($row['first_name'] ?? '')) . ' ' . ((string) ($row['last_name'] ?? '')))
                : '';
            if ($stateFilter !== '' && (($row['state']['code'] ?? '') !== $stateFilter)) {
                continue;
            }
            $articles[] = $row;
        }

        $stats = $this->computeStatsFromRows($rows, $stateFilter);
        $total = count($articles);
        $pageItems = array_slice($articles, $offset, $perPage);

        return [$pageItems, $total, $stats];
    }

    /** @return array{filtered:int,published:int,draft:int,open:int} */
    private function computeStatsFromRows(array $rows, string $stateFilter): array
    {
        $stats = ['filtered' => 0, 'published' => 0, 'draft' => 0, 'open' => 0];
        foreach ($rows as $row) {
            $row['state'] = emsp_journal_state($row);
            if ($stateFilter !== '' && (($row['state']['code'] ?? '') !== $stateFilter)) {
                continue;
            }
            $stats['filtered']++;
            if (($row['status'] ?? '') === 'published') {
                $stats['published']++;
            }
            if (($row['status'] ?? '') === 'draft') {
                $stats['draft']++;
            }
            if (($row['state']['code'] ?? '') === 'open') {
                $stats['open']++;
            }
        }
        return $stats;
    }

    /** @return array<int, array<string, mixed>> */
    private function fetchFilteredRows(string $typeFilter, string $statusFilter): array
    {
        $selectTiming = $this->hasLifecycle ? 'j.starts_at, j.ends_at' : 'NULL AS starts_at, NULL AS ends_at';
        $selectClosed = $this->hasClosedAt ? 'j.closed_at' : 'NULL AS closed_at';
        $selectAuthor = $this->authorColumn !== '' ? ', u.first_name, u.last_name' : '';
        $joinAuthor = $this->authorColumn !== '' ? "LEFT JOIN users u ON u.id = j.{$this->authorColumn}" : '';

        $sql = "SELECT j.id, j.type, j.title, j.content, j.status, j.created_at, $selectTiming, $selectClosed $selectAuthor
                FROM journal j $joinAuthor";
        $where = [];
        $params = [];
        if ($typeFilter !== '') {
            $where[] = 'j.type = :type';
            $params['type'] = $typeFilter;
        }
        if ($statusFilter !== '') {
            $where[] = 'j.status = :status';
            $params['status'] = $statusFilter;
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY j.created_at DESC, j.id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findForEdit(int $id): ?array
    {
        $selectFields = 'id, title, content, type, status';
        if ($this->hasLifecycle) {
            $selectFields .= ', starts_at, ends_at';
        }
        if ($this->hasClosedAt) {
            $selectFields .= ', closed_at';
        }
        $stmt = $this->pdo->prepare("SELECT $selectFields FROM journal WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return string[] */
    public function pollOptions(int $journalId): array
    {
        $stmt = $this->pdo->prepare('SELECT label FROM journal_options WHERE journal_id = :jid ORDER BY id');
        $stmt->execute(['jid' => $journalId]);
        return array_map(static fn(array $r): string => emsp_fix_mojibake((string) ($r['label'] ?? '')), $stmt->fetchAll());
    }

    public function countVotes(int $journalId): int
    {
        return $this->countWhere('journal_votes', $journalId);
    }

    public function countDefis(int $journalId): int
    {
        return $this->countWhere('journal_defis', $journalId);
    }

    private function countWhere(string $table, int $journalId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE journal_id = :jid");
        $stmt->execute(['jid' => $journalId]);
        return (int) $stmt->fetchColumn();
    }

    public function toggleStatus(int $id, string $newStatus): array
    {
        $shouldNotify = false;
        $title = '';
        if ($newStatus === 'published') {
            $check = $this->pdo->prepare('SELECT status, title FROM journal WHERE id = :id LIMIT 1');
            $check->execute(['id' => $id]);
            $row = $check->fetch();
            if ($row) {
                $title = (string) ($row['title'] ?? '');
                $shouldNotify = ((string) ($row['status'] ?? '')) !== 'published';
            }
        }
        $this->pdo->prepare('UPDATE journal SET status = :status WHERE id = :id LIMIT 1')
            ->execute(['status' => $newStatus, 'id' => $id]);

        return ['notify' => $shouldNotify, 'title' => $title];
    }

    public function close(int $id, int $adminId): void
    {
        $this->pdo->prepare("UPDATE journal SET closed_at=NOW(), closed_by = :admin WHERE id = :id AND type IN ('sondage','defi') LIMIT 1")
            ->execute(['admin' => $adminId, 'id' => $id]);
    }

    public function reopen(int $id): void
    {
        $this->pdo->prepare("UPDATE journal SET closed_at=NULL, closed_by=NULL WHERE id = :id AND type IN ('sondage','defi') LIMIT 1")
            ->execute(['id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM journal WHERE id = :id LIMIT 1')
            ->execute(['id' => $id]);
    }

    /**
     * Transaction complète de sauvegarde (création/édition).
     *
     * @return array{ok: bool, id: int, notify: bool, title: string, status: string}|array{ok: false}
     */
    public function save(array $data): array
    {
        $editing = $data['editing'];
        $articleId = $data['id'];
        $type = $data['type'];
        $title = $data['title'];
        $contentClean = $data['content'];
        $status = $data['status'];
        $startsAt = $data['starts_at'];
        $endsAt = $data['ends_at'];
        $pollOptions = $data['poll_options'];
        $currentType = $data['current_type'];
        $existingStatus = $data['existing_status'];
        $typeChanged = $editing && $currentType !== '' && $currentType !== $type;

        $currentOptions = [];
        if ($editing && $currentType === 'sondage') {
            $currentOptions = $this->pollOptions($articleId);
        }
        $optionsChanged = false;
        if ($type === 'sondage' || $currentType === 'sondage') {
            $optionsChanged = array_map('trim', $currentOptions) !== array_map('trim', $pollOptions);
        }
        $willResetVotes = $editing && $currentType === 'sondage' && ($type !== 'sondage' || $optionsChanged);
        $willResetDefis = $editing && $currentType === 'defi' && $type !== 'defi';

        $this->pdo->beginTransaction();
        try {
            if ($editing) {
                $currentId = $articleId;
                if ($this->hasLifecycle) {
                    if ($type === 'annonce') {
                        $sql = ($this->hasClosedAt && $this->hasClosedBy)
                            ? "UPDATE journal SET title=:title, content=:content, type=:type, status=:status, starts_at=NULL, ends_at=NULL, closed_at=NULL, closed_by=NULL WHERE id=:id LIMIT 1"
                            : "UPDATE journal SET title=:title, content=:content, type=:type, status=:status, starts_at=NULL, ends_at=NULL WHERE id=:id LIMIT 1";
                        $this->pdo->prepare($sql)->execute([
                            'title' => $title, 'content' => $contentClean, 'type' => $type, 'status' => $status, 'id' => $articleId,
                        ]);
                    } elseif ($typeChanged && $this->hasClosedAt && $this->hasClosedBy) {
                        $this->pdo->prepare("UPDATE journal SET title=:title, content=:content, type=:type, status=:status, starts_at=:sa, ends_at=:ea, closed_at=NULL, closed_by=NULL WHERE id=:id LIMIT 1")
                            ->execute(['title' => $title, 'content' => $contentClean, 'type' => $type, 'status' => $status, 'sa' => $startsAt, 'ea' => $endsAt, 'id' => $articleId]);
                    } else {
                        $this->pdo->prepare("UPDATE journal SET title=:title, content=:content, type=:type, status=:status, starts_at=:sa, ends_at=:ea WHERE id=:id LIMIT 1")
                            ->execute(['title' => $title, 'content' => $contentClean, 'type' => $type, 'status' => $status, 'sa' => $startsAt, 'ea' => $endsAt, 'id' => $articleId]);
                    }
                } else {
                    $this->pdo->prepare("UPDATE journal SET title=:title, content=:content, type=:type, status=:status WHERE id=:id LIMIT 1")
                        ->execute(['title' => $title, 'content' => $contentClean, 'type' => $type, 'status' => $status, 'id' => $articleId]);
                }
            } else {
                $currentId = $this->insertNewArticle($data);
            }

            if ($editing && $willResetVotes) {
                $this->execSimple('DELETE FROM journal_votes WHERE journal_id = :id', $currentId);
                $this->execSimple('DELETE FROM journal_options WHERE journal_id = :id', $currentId);
            }
            if ($editing && $willResetDefis) {
                $this->execSimple('DELETE FROM journal_defis WHERE journal_id = :id', $currentId);
            }

            if ($type === 'sondage') {
                $refreshOptions = !$editing || $currentType !== 'sondage' || $optionsChanged;
                if ($refreshOptions) {
                    if ($editing && !$willResetVotes) {
                        $this->execSimple('DELETE FROM journal_options WHERE journal_id = :id', $currentId);
                    }
                    $ins = $this->pdo->prepare('INSERT INTO journal_options (journal_id, label) VALUES (:jid, :label)');
                    foreach ($pollOptions as $optionLabel) {
                        $ins->execute(['jid' => $currentId, 'label' => $optionLabel]);
                    }
                }
            } elseif ($editing && $currentType === 'sondage') {
                $this->execSimple('DELETE FROM journal_options WHERE journal_id = :id', $currentId);
            }

            $this->pdo->commit();

            $shouldNotify = $status === 'published' && (!$editing || $existingStatus !== 'published');

            return ['ok' => true, 'id' => $currentId, 'notify' => $shouldNotify, 'title' => $title, 'status' => $status];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            error_log('EMSP admin journal save failed: ' . $e->getMessage());
            return ['ok' => false];
        }
    }

    private function insertNewArticle(array $data): int
    {
        $type = $data['type'];
        $title = $data['title'];
        $contentClean = $data['content'];
        $status = $data['status'];
        $startsAt = $data['starts_at'];
        $endsAt = $data['ends_at'];

        if ($this->authorColumn !== '') {
            $authorId = $data['author_id'];
            if ($this->hasLifecycle) {
                $insertStartsAt = $type === 'annonce' ? null : $startsAt;
                $insertEndsAt = $type === 'annonce' ? null : $endsAt;
                $stmt = $this->pdo->prepare("INSERT INTO journal (type, title, content, status, {$this->authorColumn}, starts_at, ends_at, created_at) VALUES (:type, :title, :content, :status, :author, :sa, :ea, NOW())");
                $stmt->execute(['type' => $type, 'title' => $title, 'content' => $contentClean, 'status' => $status, 'author' => $authorId, 'sa' => $insertStartsAt, 'ea' => $insertEndsAt]);
            } else {
                $stmt = $this->pdo->prepare("INSERT INTO journal (type, title, content, status, {$this->authorColumn}, created_at) VALUES (:type, :title, :content, :status, :author, NOW())");
                $stmt->execute(['type' => $type, 'title' => $title, 'content' => $contentClean, 'status' => $status, 'author' => $authorId]);
            }
        } elseif ($this->hasLifecycle) {
            $insertStartsAt = $type === 'annonce' ? null : $startsAt;
            $insertEndsAt = $type === 'annonce' ? null : $endsAt;
            $stmt = $this->pdo->prepare("INSERT INTO journal (type, title, content, status, starts_at, ends_at, created_at) VALUES (:type, :title, :content, :status, :sa, :ea, NOW())");
            $stmt->execute(['type' => $type, 'title' => $title, 'content' => $contentClean, 'status' => $status, 'sa' => $insertStartsAt, 'ea' => $insertEndsAt]);
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO journal (type, title, content, status, created_at) VALUES (:type, :title, :content, :status, NOW())");
            $stmt->execute(['type' => $type, 'title' => $title, 'content' => $contentClean, 'status' => $status]);
        }

        return (int) $this->pdo->lastInsertId();
    }

    private function execSimple(string $sql, int $id): void
    {
        $this->pdo->prepare($sql)->execute(['id' => $id]);
    }
}
