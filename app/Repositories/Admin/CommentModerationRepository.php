<?php

declare(strict_types=1);

namespace App\Repositories\Admin;

use PDO;

final class CommentModerationRepository
{
    private const SOURCE_TABLES = [
        'document_comment' => 'comments',
        'document_reply' => 'comment_replies',
        'journal_comment' => 'journal_comments',
    ];

    private const STATUS_MAP = ['visible' => 'visible', 'hidden' => 'hidden', 'delete' => 'deleted'];

    public function __construct(private PDO $pdo)
    {
    }

    private function unionSql(): string
    {
        return "
            SELECT c.id, 'document_comment' AS source_type, 'Commentaire document' AS source_label,
                   c.content, c.status, c.created_at, c.document_id AS target_id, d.title AS target_title,
                   CONCAT('../document.php?id=', c.document_id, '#comment-', c.id) AS target_url,
                   COALESCE(NULLIF(TRIM(CONCAT_WS(' ', u.first_name, u.last_name)), ''), u.email, 'Utilisateur') AS author_name
            FROM comments c
            INNER JOIN users u ON u.id = c.user_id
            INNER JOIN documents d ON d.id = c.document_id

            UNION ALL

            SELECT r.id, 'document_reply' AS source_type, 'Réponse document' AS source_label,
                   r.content, r.status, r.created_at, d.id AS target_id, d.title AS target_title,
                   CONCAT('../document.php?id=', d.id, '#comment-', c.id) AS target_url,
                   COALESCE(NULLIF(TRIM(CONCAT_WS(' ', u.first_name, u.last_name)), ''), u.email, 'Utilisateur') AS author_name
            FROM comment_replies r
            INNER JOIN users u ON u.id = r.user_id
            INNER JOIN comments c ON c.id = r.comment_id
            INNER JOIN documents d ON d.id = c.document_id

            UNION ALL

            SELECT jc.id, 'journal_comment' AS source_type, 'Commentaire journal' AS source_label,
                   jc.content, jc.status, jc.created_at, j.id AS target_id, j.title AS target_title,
                   CONCAT('../journal/article?id=', j.id) AS target_url,
                   COALESCE(NULLIF(TRIM(CONCAT_WS(' ', u.first_name, u.last_name)), ''), u.email, 'Utilisateur') AS author_name
            FROM journal_comments jc
            INNER JOIN users u ON u.id = jc.user_id
            INNER JOIN journal j ON j.id = jc.journal_id
        ";
    }

    /** @return array{0: array<int,array<string,mixed>>, 1: int, 2: array<string,int>} */
    public function list(string $status, string $source, int $perPage, int $offset): array
    {
        $union = $this->unionSql();
        $where = ['1=1'];
        $params = [];
        if ($status !== '') {
            $where[] = 'moderation.status = :status';
            $params['status'] = $status;
        }
        if ($source !== '') {
            $where[] = 'moderation.source_type = :source';
            $params['source'] = $source;
        }
        $whereSql = implode(' AND ', $where);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) AS total FROM ($union) moderation WHERE $whereSql");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $scStmt = $this->pdo->query("SELECT moderation.source_type, COUNT(*) AS total FROM ($union) moderation GROUP BY moderation.source_type");
        $sourceCounts = [];
        foreach ($scStmt->fetchAll() as $row) {
            $sourceCounts[(string) ($row['source_type'] ?? '')] = (int) ($row['total'] ?? 0);
        }

        $listSql = "SELECT * FROM ($union) moderation WHERE $whereSql ORDER BY moderation.created_at DESC LIMIT :limit OFFSET :offset";
        $listStmt = $this->pdo->prepare($listSql);
        foreach ($params as $key => $value) {
            $listStmt->bindValue($key, $value);
        }
        $listStmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $listStmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $listStmt->execute();
        $items = $listStmt->fetchAll();

        foreach ($items as &$item) {
            foreach (['content', 'target_title', 'author_name', 'source_label'] as $field) {
                if (isset($item[$field]) && is_string($item[$field])) {
                    $item[$field] = emsp_fix_mojibake($item[$field]);
                }
            }
        }
        unset($item);

        return [$items, $total, $sourceCounts];
    }

    public function updateStatus(string $sourceType, int $itemId, string $action): bool
    {
        if ($itemId <= 0 || !isset(self::SOURCE_TABLES[$sourceType], self::STATUS_MAP[$action])) {
            return false;
        }
        $table = self::SOURCE_TABLES[$sourceType];
        $newStatus = self::STATUS_MAP[$action];
        $stmt = $this->pdo->prepare("UPDATE {$table} SET status = :status WHERE id = :id");
        $stmt->execute(['status' => $newStatus, 'id' => $itemId]);
        return $stmt->rowCount() > 0;
    }
}
