<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class NotificationRepository
{
    private ?bool $hasCommentsTable = null;

    public function __construct(private PDO $pdo)
    {
    }

    public function unreadCount(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0'
        );
        $stmt->execute(['uid' => $userId]);

        return max(0, (int) $stmt->fetchColumn());
    }

    /**
     * @return array{journal: int, media: int}
     */
    public function unreadSectionCounts(int $userId): array
    {
        $counts = ['journal' => 0, 'media' => 0];
        if ($userId <= 0) {
            return $counts;
        }

        $stmt = $this->pdo->prepare(
            "SELECT
                SUM(CASE WHEN type = 'journal_published' AND is_read = 0 THEN 1 ELSE 0 END) AS journal_unread,
                SUM(CASE WHEN type = 'media_published' AND is_read = 0 THEN 1 ELSE 0 END) AS media_unread
             FROM notifications
             WHERE user_id = :uid"
        );
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'journal' => max(0, (int) ($row['journal_unread'] ?? 0)),
            'media' => max(0, (int) ($row['media_unread'] ?? 0)),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listRecent(int $userId, int $limit = 20): array
    {
        if ($userId <= 0) {
            return [];
        }

        $limit = max(1, min(50, $limit));

        if ($this->commentsTableExists()) {
            $sql = "SELECT n.*,
                           COALESCE(d.title, d2.title) AS doc_title,
                           COALESCE(d.id, d2.id, 0) AS doc_id_resolved,
                           fu.first_name AS from_first,
                           fu.last_name AS from_last
                    FROM notifications n
                    LEFT JOIN documents d ON d.id = n.document_id
                    LEFT JOIN comments c ON c.id = n.comment_id
                    LEFT JOIN documents d2 ON d2.id = c.document_id
                    LEFT JOIN users fu ON fu.id = n.from_user_id
                    WHERE n.user_id = :uid
                    ORDER BY n.created_at DESC
                    LIMIT {$limit}";
        } else {
            $sql = "SELECT n.*,
                           COALESCE(d.title, '') AS doc_title,
                           COALESCE(d.id, 0) AS doc_id_resolved,
                           fu.first_name AS from_first,
                           fu.last_name AS from_last
                    FROM notifications n
                    LEFT JOIN documents d ON d.id = n.document_id
                    LEFT JOIN users fu ON fu.id = n.from_user_id
                    WHERE n.user_id = :uid
                    ORDER BY n.created_at DESC
                    LIMIT {$limit}";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForUser(int $userId, int $notificationId): ?array
    {
        if ($userId <= 0 || $notificationId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, type, document_id, comment_id, reply_id, is_read
             FROM notifications
             WHERE user_id = :uid AND id = :nid
             LIMIT 1'
        );
        $stmt->execute(['uid' => $userId, 'nid' => $notificationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function markOneRead(int $userId, int $notificationId): bool
    {
        if ($userId <= 0 || $notificationId <= 0) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE notifications SET is_read = 1
             WHERE user_id = :uid AND id = :nid AND is_read = 0'
        );
        $stmt->execute(['uid' => $userId, 'nid' => $notificationId]);

        return $stmt->rowCount() > 0;
    }

    public function markAllRead(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND is_read = 0'
        );
        $stmt->execute(['uid' => $userId]);

        return max(0, $stmt->rowCount());
    }

    /**
     * @param list<string> $types
     */
    public function markDocumentRead(int $userId, int $documentId, array $types): int
    {
        if ($userId <= 0 || $documentId <= 0 || $types === []) {
            return 0;
        }

        $placeholders = [];
        $params = ['uid' => $userId, 'doc' => $documentId, 'doc2' => $documentId];
        foreach ($types as $index => $type) {
            $key = 't' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $type;
        }

        if ($this->commentsTableExists()) {
            $sql = 'UPDATE notifications n
                    LEFT JOIN comments c ON c.id = n.comment_id
                    SET n.is_read = 1
                    WHERE n.user_id = :uid
                      AND n.is_read = 0
                      AND n.type IN (' . implode(',', $placeholders) . ')
                      AND (n.document_id = :doc OR c.document_id = :doc2)';
        } else {
            $sql = 'UPDATE notifications
                    SET is_read = 1
                    WHERE user_id = :uid
                      AND document_id = :doc
                      AND is_read = 0
                      AND type IN (' . implode(',', $placeholders) . ')';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return max(0, $stmt->rowCount());
    }

    private function commentsTableExists(): bool
    {
        if ($this->hasCommentsTable !== null) {
            return $this->hasCommentsTable;
        }

        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'comments'");
            $this->hasCommentsTable = (bool) $stmt?->fetchColumn();
        } catch (\Throwable) {
            $this->hasCommentsTable = false;
        }

        return $this->hasCommentsTable;
    }
}
