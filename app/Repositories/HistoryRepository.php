<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class HistoryRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function countForUser(int $userId, string $filter): int
    {
        [$where, $params] = $this->buildWhere($userId, $filter);

        $stmt = $this->pdo->prepare("SELECT COUNT(DISTINCT h.document_id, h.action) FROM history h $where");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** @return array<int, array<string, mixed>> */
    public function listForUser(int $userId, string $filter, int $perPage, int $offset): array
    {
        [$where, $params] = $this->buildWhere($userId, $filter);
        $params['limit'] = $perPage;
        $params['offset'] = $offset;

        $stmt = $this->pdo->prepare(
            "SELECT h.action, MAX(h.created_at) AS last_date,
                    d.id AS doc_id, d.title, d.doc_type, d.status,
                    u.first_name, u.last_name
             FROM history h
             JOIN documents d ON d.id = h.document_id
             JOIN users u ON u.id = d.uploader_id
             $where
             GROUP BY h.document_id, h.action
             ORDER BY last_date DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) {
            if (isset($r['title']) && is_string($r['title'])) {
                $r['title'] = emsp_fix_mojibake($r['title']);
            }
        }
        unset($r);

        return $rows;
    }

    /** @return array{0: string, 1: array<string, mixed>} */
    private function buildWhere(int $userId, string $filter): array
    {
        $where = 'WHERE h.user_id = :user_id';
        $params = ['user_id' => $userId];

        if ($filter === 'view' || $filter === 'download') {
            $where .= ' AND h.action = :action';
            $params['action'] = $filter;
        }

        return [$where, $params];
    }
}
