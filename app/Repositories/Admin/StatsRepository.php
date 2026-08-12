<?php

declare(strict_types=1);

namespace App\Repositories\Admin;

use PDO;

final class StatsRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<string, int> */
    public function globals(): array
    {
        $stats = [
            'users_total' => 0, 'users_active' => 0, 'users_pending' => 0,
            'docs_total' => 0, 'docs_approved' => 0, 'docs_pending' => 0, 'docs_rejected' => 0,
            'total_downloads' => 0, 'total_favorites' => 0, 'total_comments' => 0,
            'badge_or' => 0, 'badge_argent' => 0, 'badge_bronze' => 0,
        ];
        $sql = "SELECT
                    (SELECT COUNT(*) FROM users WHERE role='etudiant') AS users_total,
                    (SELECT COUNT(*) FROM users WHERE status='active' AND role='etudiant') AS users_active,
                    (SELECT COUNT(*) FROM users WHERE status='pending' AND role='etudiant') AS users_pending,
                    (SELECT COUNT(*) FROM documents) AS docs_total,
                    (SELECT COUNT(*) FROM documents WHERE status='approved') AS docs_approved,
                    (SELECT COUNT(*) FROM documents WHERE status='pending') AS docs_pending,
                    (SELECT COUNT(*) FROM documents WHERE status='rejected') AS docs_rejected,
                    (SELECT COALESCE(SUM(download_count), 0) FROM documents) AS total_downloads,
                    (SELECT COUNT(*) FROM favorites) AS total_favorites,
                    (SELECT COUNT(*) FROM comments WHERE status='visible') AS total_comments,
                    (SELECT COUNT(*) FROM users WHERE badge_level='or' AND role='etudiant') AS badge_or,
                    (SELECT COUNT(*) FROM users WHERE badge_level='argent' AND role='etudiant') AS badge_argent,
                    (SELECT COUNT(*) FROM users WHERE badge_level='bronze' AND role='etudiant') AS badge_bronze";
        $row = $this->pdo->query($sql)->fetch();
        if ($row) {
            foreach (array_keys($stats) as $key) {
                $stats[$key] = (int) ($row[$key] ?? 0);
            }
        }
        return $stats;
    }

    /** @return array<string, int> doc_type => count */
    public function docsByType(): array
    {
        $rows = [];
        foreach ($this->pdo->query("SELECT doc_type, COUNT(*) AS cnt FROM documents WHERE status='approved' GROUP BY doc_type ORDER BY cnt DESC")->fetchAll() as $row) {
            $rows[(string) $row['doc_type']] = (int) $row['cnt'];
        }
        return $rows;
    }

    /** @return array<int, array<string, mixed>> */
    public function topUploaders(): array
    {
        return $this->pdo->query(
            "SELECT first_name, last_name, upload_count, badge_level FROM users
             WHERE role='etudiant' AND status='active' ORDER BY upload_count DESC LIMIT 5"
        )->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function topDocuments(): array
    {
        $rows = $this->pdo->query(
            "SELECT d.id, d.title, d.doc_type, d.download_count, u.first_name, u.last_name
             FROM documents d JOIN users u ON u.id=d.uploader_id
             WHERE d.status='approved' ORDER BY d.download_count DESC LIMIT 5"
        )->fetchAll();
        foreach ($rows as &$row) {
            $row['title'] = emsp_fix_mojibake((string) ($row['title'] ?? ''));
        }
        return $rows;
    }

    /** @return array<int, array{mois:string, cnt:int}> */
    public function uploadsMonthly(): array
    {
        $rows = [];
        foreach ($this->pdo->query(
            "SELECT DATE_FORMAT(created_at,'%Y-%m') AS mois, COUNT(*) AS cnt
             FROM documents WHERE status='approved' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
             GROUP BY mois ORDER BY mois ASC"
        )->fetchAll() as $row) {
            $rows[] = ['mois' => (string) $row['mois'], 'cnt' => (int) $row['cnt']];
        }
        return $rows;
    }
}
