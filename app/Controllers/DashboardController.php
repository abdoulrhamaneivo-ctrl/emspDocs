<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\LegacyDb;
use App\Services\NotificationService;
use mysqli;

final class DashboardController extends Controller
{
    public function index(): void
    {
        require_auth();

        $con = LegacyDb::mysqli();
        require_once dirname(__DIR__, 2) . '/includes/notif-helper.php';

        $uid = (int) $_SESSION['auth_user']['id'];
        $prenom = htmlspecialchars(emsp_fix_mojibake((string) ($_SESSION['auth_user']['first_name'] ?? '')));
        $badge = $_SESSION['auth_user']['badge_level'] ?? 'none';

        $hasCommentsTable = self::hasTable($con, 'comments');
        $hasNotificationsTable = self::hasTable($con, 'notifications');
        $hasFavoritesTable = self::hasTable($con, 'favorites');
        $hasLikeCountColumn = self::hasColumn($con, 'documents', 'like_count');
        $hasRejectionReasonColumn = self::hasColumn($con, 'documents', 'rejection_reason');
        $likeCountSelect = $hasLikeCountColumn ? 'd.like_count' : '0 AS like_count';
        $likeCountSum = $hasLikeCountColumn ? 'COALESCE(SUM(like_count),0)' : '0';
        $rejectionReasonSelect = $hasRejectionReasonColumn ? 'd.rejection_reason' : "'' AS rejection_reason";
        $commentsJoin = $hasCommentsTable
            ? "LEFT JOIN (
                 SELECT document_id, COUNT(*) AS nb_comments
                 FROM comments
                 WHERE status='visible'
                 GROUP BY document_id
             ) cc ON cc.document_id = d.id"
            : "LEFT JOIN (SELECT 0 AS document_id, 0 AS nb_comments) cc ON 1=0";

        // -- Mes documents --
        $docs = mysqli_prepare($con,
            "SELECT d.id, d.title, d.doc_type, d.status, d.created_at,
                    d.download_count, {$likeCountSelect}, {$rejectionReasonSelect},
                    COALESCE(cc.nb_comments, 0) AS nb_comments
              FROM documents d
              {$commentsJoin}
              WHERE d.uploader_id = ?
              ORDER BY d.created_at DESC");
        $mes_docs = [];
        if ($docs) {
            mysqli_stmt_bind_param($docs, 'i', $uid);
            mysqli_stmt_execute($docs);
            $mes_docs = emsp_stmt_fetch_all($docs);
            mysqli_stmt_close($docs);
        }
        foreach ($mes_docs as &$docRow) {
            foreach (['title', 'rejection_reason'] as $field) {
                if (isset($docRow[$field]) && is_string($docRow[$field])) {
                    $docRow[$field] = emsp_fix_mojibake($docRow[$field]);
                }
            }
        }
        unset($docRow);

        // -- Statistiques rapides --
        $stats_s = mysqli_prepare($con,
            "SELECT
                COUNT(*) AS total,
                SUM(status='pending')  AS pending,
                SUM(status='approved') AS approved,
                SUM(status='rejected') AS rejected,
                COALESCE(SUM(download_count),0) AS total_dl,
                {$likeCountSum}                AS total_likes
             FROM documents WHERE uploader_id=?");
        $stats = [
            'total' => 0,
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'total_dl' => 0,
            'total_likes' => 0,
        ];
        if ($stats_s) {
            mysqli_stmt_bind_param($stats_s, 'i', $uid);
            mysqli_stmt_execute($stats_s);
            $stats = emsp_stmt_fetch_assoc($stats_s) ?: $stats;
            mysqli_stmt_close($stats_s);
        }

        // -- Notifications --
        $notifService = new NotificationService();
        $notifs_res = $hasNotificationsTable ? $notifService->listRecent($uid, 20) : [];
        foreach ($notifs_res as &$notifRow) {
            foreach (['message', 'doc_title', 'from_first', 'from_last'] as $field) {
                if (isset($notifRow[$field]) && is_string($notifRow[$field])) {
                    $notifRow[$field] = emsp_fix_mojibake($notifRow[$field]);
                }
            }
        }
        unset($notifRow);

        $nb_unread = $hasNotificationsTable ? $notifService->unreadCount($uid) : 0;
        if ($hasNotificationsTable) {
            $notifService->syncSessionCounters($uid);
        } else {
            emsp_session_set_notif_count(0);
            emsp_session_set_notif_sections(['journal' => 0, 'media' => 0]);
        }

        $recent_activity = [];

        $uploads_s = mysqli_prepare(
            $con,
            "SELECT title, created_at
             FROM documents
             WHERE uploader_id = ?
             ORDER BY created_at DESC
             LIMIT 5"
        );
        if ($uploads_s) {
            mysqli_stmt_bind_param($uploads_s, 'i', $uid);
            mysqli_stmt_execute($uploads_s);
            $upload_rows = emsp_stmt_fetch_all($uploads_s);
            mysqli_stmt_close($uploads_s);
            foreach ($upload_rows as $row) {
                $recent_activity[] = [
                    'action_type' => 'upload',
                    'title' => emsp_fix_mojibake((string) ($row['title'] ?? 'Document')),
                    'created_at' => (string) ($row['created_at'] ?? ''),
                ];
            }
        }

        $favorites_s = $hasFavoritesTable ? mysqli_prepare(
            $con,
            "SELECT d.title, f.created_at
             FROM favorites f
             JOIN documents d ON d.id = f.document_id
             WHERE f.user_id = ?
             ORDER BY f.created_at DESC
             LIMIT 5"
        ) : false;
        if ($favorites_s) {
            mysqli_stmt_bind_param($favorites_s, 'i', $uid);
            mysqli_stmt_execute($favorites_s);
            $favorite_rows = emsp_stmt_fetch_all($favorites_s);
            mysqli_stmt_close($favorites_s);
            foreach ($favorite_rows as $row) {
                $recent_activity[] = [
                    'action_type' => 'favori',
                    'title' => emsp_fix_mojibake((string) ($row['title'] ?? 'Document')),
                    'created_at' => (string) ($row['created_at'] ?? ''),
                ];
            }
        }

        usort($recent_activity, static function (array $a, array $b): int {
            return strtotime((string) ($b['created_at'] ?? '')) <=> strtotime((string) ($a['created_at'] ?? ''));
        });
        $recent_activity = array_slice($recent_activity, 0, 5);

        $badge_labels = [
            'or'     => ['label' => 'OR', 'class' => 'bg-warning text-dark'],
            'argent' => ['label' => 'ARG', 'class' => 'bg-secondary text-white'],
            'bronze' => ['label' => 'BR', 'class' => 'bg-danger text-white'],
        ];
        $badge_html = '';
        if (isset($badge_labels[$badge])) {
            $b = $badge_labels[$badge];
            $badge_html = '<span class="badge ' . $b['class'] . ' me-2 align-middle">' . $b['label'] . '</span>';
        }
        $type_colors = [
            'cours'      => 'bg-primary',
            'td'         => 'bg-success',
            'correction' => 'bg-info text-dark',
            'concours'   => 'bg-warning text-dark',
            'examen'     => 'bg-danger',
        ];

        $this->view('dashboard/index', [
            'prenom' => $prenom,
            'badge_html' => $badge_html,
            'mes_docs' => $mes_docs,
            'stats' => $stats,
            'notifs_res' => $notifs_res,
            'nb_unread' => $nb_unread,
            'recent_activity' => $recent_activity,
            'type_colors' => $type_colors,
        ]);
    }

    private static function hasTable(mysqli $con, string $table): bool
    {
        $safe = mysqli_real_escape_string($con, $table);
        $result = mysqli_query($con, "SHOW TABLES LIKE '{$safe}'");
        return $result instanceof \mysqli_result && mysqli_num_rows($result) > 0;
    }

    private static function hasColumn(mysqli $con, string $table, string $column): bool
    {
        if (!self::hasTable($con, $table)) {
            return false;
        }
        $safeTable = mysqli_real_escape_string($con, $table);
        $safeColumn = mysqli_real_escape_string($con, $column);
        $result = mysqli_query($con, "SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
        return $result instanceof \mysqli_result && mysqli_num_rows($result) > 0;
    }
}
