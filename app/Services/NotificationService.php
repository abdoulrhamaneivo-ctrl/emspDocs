<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\NotificationRepository;
use PDO;

final class NotificationService
{
    private NotificationRepository $repository;

    public function __construct(private ?PDO $pdo = null)
    {
    }

    private function pdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = Database::pdo();
        }
        return $this->pdo;
    }

    private function repository(): NotificationRepository
    {
        return $this->repository ??= new NotificationRepository($this->pdo());
    }

    /**
     * Publier une notification globale pour un nouveau média.
     */
    public function notifyMediaPublished(int $mediaId, string $title, int $fromUserId = 0): int
    {
        $msg = 'Nouveau média publié : "' . $this->excerpt($title, 60) . '"';
        $stmt = $this->pdo()->prepare(
            "INSERT INTO notifications (user_id, type, document_id, comment_id, reply_id, from_user_id, message)
             SELECT id, 'media_published', NULLIF(:mid, 0), NULL, NULL, NULLIF(:from_uid, 0), :msg
             FROM users
             WHERE status = 'active'"
        );
        $stmt->execute([
            'mid' => $mediaId,
            'from_uid' => $fromUserId,
            'msg' => $msg,
        ]);

        return $stmt->rowCount();
    }

    /**
     * Publier une notification globale pour un nouveau journal.
     */
    public function notifyJournalPublished(int $journalId, string $title, int $fromUserId = 0): int
    {
        $msg = 'Nouveau journal publié : "' . $this->excerpt($title, 60) . '"';
        $stmt = $this->pdo()->prepare(
            "INSERT INTO notifications (user_id, type, document_id, comment_id, reply_id, from_user_id, message)
             SELECT id, 'journal_published', :jid, NULL, NULL, NULLIF(:from_uid, 0), :msg
             FROM users
             WHERE status = 'active'"
        );
        $stmt->execute([
            'jid' => $journalId,
            'from_uid' => $fromUserId,
            'msg' => $msg,
        ]);

        return $stmt->rowCount();
    }

    /**
     * Envoyer une notification individuelle à un utilisateur.
     */
    public function sendNotification(
        int $userId,
        string $type,
        string $message,
        ?int $documentId = null,
        ?int $commentId = null,
        ?int $replyId = null,
        ?int $fromUserId = null
    ): bool {
        if ($userId <= 0 || trim($type) === '' || trim($message) === '') {
            return false;
        }

        $stmt = $this->pdo()->prepare(
            "INSERT INTO notifications (user_id, type, document_id, comment_id, reply_id, from_user_id, message)
             VALUES (:uid, :type, NULLIF(:doc, 0), NULLIF(:com, 0), NULLIF(:rep, 0), NULLIF(:from, 0), :msg)"
        );

        $ok = $stmt->execute([
            'uid' => $userId,
            'type' => $type,
            'doc' => $documentId ?? 0,
            'com' => $commentId ?? 0,
            'rep' => $replyId ?? 0,
            'from' => $fromUserId ?? 0,
            'msg' => $message,
        ]);

        if ($ok) {
            $this->dispatchPush($userId, $type, $message, $documentId, $commentId, $replyId);
        }

        return $ok;
    }

    public function notifyAccountApproved(int $userId): bool
    {
        return $this->sendNotification(
            $userId,
            'account_approved',
            'Votre compte EMSP Docs a été validé. Bienvenue sur la plateforme !'
        );
    }

    public function notifyDocApproved(int $uploaderId, int $docId, string $docTitle, int $approverId): bool
    {
        $message = 'Votre document « ' . $this->excerpt($docTitle, 60) . ' » a été approuvé et publié.';
        return $this->sendNotification($uploaderId, 'doc_approved', $message, $docId, null, null, $approverId);
    }

    public function notifyDocRejected(int $uploaderId, int $docId, string $docTitle, string $reason, int $approverId): bool
    {
        $message = 'Votre document « ' . $this->excerpt($docTitle, 55) . ' » a été rejeté.';
        $reasonClean = trim($reason);
        if ($reasonClean !== '') {
            $message .= ' Motif : ' . $this->excerpt($reasonClean, 80);
        }
        return $this->sendNotification($uploaderId, 'doc_rejected', $message, $docId, null, null, $approverId);
    }

    /**
     * Marquer les notifications d'un document comme lues pour un utilisateur.
     */
    public function markDocumentNotificationsSeen(int $userId, int $documentId): int
    {
        if ($userId <= 0 || $documentId <= 0) {
            return 0;
        }

        $types = ['doc_approved', 'doc_rejected', 'new_comment', 'comment_reply', 'doc_liked', 'comment_reacted'];
        $affected = $this->repository()->markDocumentRead($userId, $documentId, $types);

        if ($affected > 0) {
            $this->syncSessionCounters($userId);
        }

        return $affected;
    }

    public function markOneRead(int $userId, int $notificationId): bool
    {
        $changed = $this->repository()->markOneRead($userId, $notificationId);
        if ($changed) {
            $this->syncSessionCounters($userId);
        }

        return $changed;
    }

    public function markAllRead(int $userId): int
    {
        $affected = $this->repository()->markAllRead($userId);
        if ($affected > 0 || $this->unreadCount($userId) === 0) {
            $this->syncSessionCounters($userId);
        }

        return $affected;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listRecent(int $userId, int $limit = 20): array
    {
        return $this->repository()->listRecent($userId, $limit);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForUser(int $userId, int $notificationId): ?array
    {
        return $this->repository()->findForUser($userId, $notificationId);
    }

    /**
     * Obtenir le nombre de notifications non lues d'un utilisateur.
     */
    public function unreadCount(int $userId): int
    {
        return $this->repository()->unreadCount($userId);
    }

    /**
     * @return array{journal: int, media: int}
     */
    public function unreadSectionCounts(int $userId): array
    {
        return $this->repository()->unreadSectionCounts($userId);
    }

    public function syncSessionCounters(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $rootDir = dirname(__DIR__, 2);
        if (!function_exists('emsp_session_set_notif_count')) {
            require_once $rootDir . '/includes/bootstrap.php';
        }

        emsp_session_set_notif_count($this->unreadCount($userId));
        emsp_session_set_notif_sections($this->unreadSectionCounts($userId));
        $_SESSION['emsp_notif_sections_fetched_at'] = time();
    }

    /**
     * Tronquer un texte proprement.
     */
    private function excerpt(string $text, int $max = 80): string
    {
        $clean = trim($text);
        if ($clean === '') {
            return '';
        }
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($clean) > $max ? (mb_substr($clean, 0, $max) . '...') : $clean;
        }

        return strlen($clean) > $max ? (substr($clean, 0, $max) . '...') : $clean;
    }

    private function dispatchPush(
        int $userId,
        string $type,
        string $message,
        ?int $documentId,
        ?int $commentId,
        ?int $replyId
    ): void {
        $rootDir = dirname(__DIR__, 2);
        require_once $rootDir . '/includes/notif-helper.php';

        if (!function_exists('emsp_push_payload')) {
            require_once $rootDir . '/includes/push-helper.php';
        }

        if (!function_exists('emsp_push_send_to_user') || !function_exists('emsp_push_payload')) {
            return;
        }

        try {
            $con = \App\Core\LegacyDb::mysqli();
            $url = '/' . ltrim(emsp_notification_target([
                'type' => $type,
                'document_id' => $documentId ?? 0,
                'comment_id' => $commentId ?? 0,
                'reply_id' => $replyId ?? 0,
            ], 'dashboard'), '/');
            $payload = emsp_push_payload('EMSP Docs', $message, $url);
            emsp_push_send_to_user($con, $userId, $payload);
        } catch (\Throwable $e) {
            error_log('EMSP notification push skipped: ' . $e->getMessage());
        }
    }
}
