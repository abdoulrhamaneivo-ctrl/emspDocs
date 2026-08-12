<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\DatabaseHelper;
use PDO;

final class DocumentRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function hasColumn(string $table, string $column): bool
    {
        return DatabaseHelper::columnExists($this->pdo, $table, $column);
    }

    /**
     * Jeu de données complet (non paginé) pour le workspace JS de la
     * bibliothèque. Reproduit exactement la requête workspace de l'ancien
     * bibliotheque.php.
     *
     * @return array<int, array<string, mixed>>
     */
    public function workspaceDocuments(bool $isStaff, int $currentUserId): array
    {
        $hasSemesterColumn = $this->hasColumn('documents', 'semester');
        $hasMimeTypeColumn = $this->hasColumn('documents', 'mime_type');
        $hasFileSizeBytesColumn = $this->hasColumn('documents', 'file_size_bytes');
        $hasLikeCountColumn = $this->hasColumn('documents', 'like_count');
        $hasIsPublicColumn = $this->hasColumn('documents', 'is_public');

        DatabaseHelper::ensureThumbColumn($this->pdo);
        $hasThumbPathColumn = DatabaseHelper::thumbColumnExists($this->pdo);

        $semesterSelect = $hasSemesterColumn ? 'd.semester' : "'' AS semester";
        $mimeTypeSelect = $hasMimeTypeColumn ? 'd.mime_type' : "'' AS mime_type";
        $fileSizeSelect = $hasFileSizeBytesColumn ? 'd.file_size_bytes' : '0 AS file_size_bytes';
        $likeCountSelect = $hasLikeCountColumn ? 'd.like_count' : '0 AS like_count';
        $isPublicSelect = $hasIsPublicColumn ? 'd.is_public' : '1 AS is_public';
        $thumbPathSelect = $hasThumbPathColumn ? 'd.thumb_path' : "'' AS thumb_path";

        $params = [];
        if ($isStaff) {
            $where = 'WHERE 1=1';
        } else {
            $publicClause = $hasIsPublicColumn
                ? "(d.status = 'approved' AND d.is_public = 1)"
                : "(d.status = 'approved')";
            $where = "WHERE ($publicClause OR d.uploader_id = :uid)";
            $params['uid'] = $currentUserId;
        }

        $sql = "SELECT d.id, d.title, d.description, d.doc_type, $semesterSelect,
                       d.file_path, $thumbPathSelect, $mimeTypeSelect, $fileSizeSelect,
                       d.download_count, $likeCountSelect, d.created_at,
                       d.status, $isPublicSelect, d.uploader_id,
                       d.filiere_id, d.licence_id, d.matiere_id,
                       u.first_name, u.last_name,
                       f.name AS filiere_name,
                       l.name AS licence_name,
                       ma.name AS matiere_name
                FROM documents d
                JOIN users u ON u.id = d.uploader_id
                LEFT JOIN filieres f ON f.id = d.filiere_id
                LEFT JOIN licences l ON l.id = d.licence_id
                LEFT JOIN matieres ma ON ma.id = d.matiere_id
                $where
                ORDER BY d.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $docs = $stmt->fetchAll();

        foreach ($docs as &$d) {
            foreach (['title', 'description', 'first_name', 'last_name', 'filiere_name', 'licence_name', 'matiere_name'] as $field) {
                if (isset($d[$field]) && is_string($d[$field])) {
                    $d[$field] = emsp_fix_mojibake($d[$field]);
                }
            }
        }
        unset($d);

        $ids = array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $docs);
        $filiereLabels = DatabaseHelper::fetchDocumentFiliereLabels($this->pdo, $ids);
        foreach ($docs as &$d) {
            $labels = $filiereLabels[(int) ($d['id'] ?? 0)] ?? [];
            $d['filiere_labels'] = $labels;
            $d['filiere_label_display'] = !empty($labels) ? implode(', ', $labels) : trim((string) ($d['filiere_name'] ?? ''));
            $d['matiere_display'] = trim((string) ($d['matiere_name'] ?? ''));
        }
        unset($d);

        return $docs;
    }

    /** @return string[] */
    public function activeNames(string $table): array
    {
        $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $stmt = $this->pdo->query("SELECT name FROM `$safeTable` WHERE status='active' ORDER BY name");
        $names = [];
        foreach ($stmt->fetchAll() as $row) {
            $value = trim(emsp_fix_mojibake((string) ($row['name'] ?? '')));
            if ($value !== '') {
                $names[] = $value;
            }
        }
        return $names;
    }

    /** @return int[] */
    public function distinctSemesters(): array
    {
        if (!$this->hasColumn('documents', 'semester')) {
            return [];
        }
        $stmt = $this->pdo->query("SELECT DISTINCT semester FROM documents WHERE semester IS NOT NULL AND semester <> '' ORDER BY semester");
        return array_column($stmt->fetchAll(), 'semester');
    }

    // -----------------------------------------------------------------
    // Fiche document : favoris, likes, commentaires, réponses
    // -----------------------------------------------------------------

    public function isFavorite(int $userId, int $docId): bool
    {
        $stmt = $this->pdo->prepare("SELECT id FROM favorites WHERE user_id = :uid AND document_id = :did LIMIT 1");
        $stmt->execute(['uid' => $userId, 'did' => $docId]);
        return (bool) $stmt->fetch();
    }

    public function setFavorite(int $userId, int $docId, bool $add): void
    {
        if ($add) {
            $stmt = $this->pdo->prepare("INSERT IGNORE INTO favorites (user_id, document_id) VALUES (:uid, :did)");
        } else {
            $stmt = $this->pdo->prepare("DELETE FROM favorites WHERE user_id = :uid AND document_id = :did");
        }
        $stmt->execute(['uid' => $userId, 'did' => $docId]);
    }

    public function removeFavorite(int $userId, int $docId): void
    {
        $this->setFavorite($userId, $docId, false);
    }

    public function isLiked(int $userId, int $docId): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM document_likes WHERE user_id = :uid AND document_id = :did LIMIT 1");
        $stmt->execute(['uid' => $userId, 'did' => $docId]);
        return (bool) $stmt->fetch();
    }

    public function setLike(int $userId, int $docId, bool $add): void
    {
        if ($add) {
            $stmt = $this->pdo->prepare("INSERT IGNORE INTO document_likes (user_id, document_id) VALUES (:uid, :did)");
        } else {
            $stmt = $this->pdo->prepare("DELETE FROM document_likes WHERE user_id = :uid AND document_id = :did");
        }
        $stmt->execute(['uid' => $userId, 'did' => $docId]);
    }

    public function addComment(int $userId, int $docId, string $content, int $ownerId, string $docTitle, string $authorFirst, string $authorLast): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO comments (user_id, document_id, content) VALUES (:uid, :did, :content)");
        $stmt->execute(['uid' => $userId, 'did' => $docId, 'content' => $content]);
        $newCommentId = (int) $this->pdo->lastInsertId();

        if ($ownerId !== $userId) {
            $me = trim($authorFirst . ' ' . $authorLast);
            $msg = $me . ' a commenté votre document « ' . mb_substr($docTitle, 0, 50) . ' »';
            $notifService = new \App\Services\NotificationService($this->pdo);
            $notifService->sendNotification($ownerId, 'new_comment', $msg, $docId, $newCommentId, null, $userId);
        }
    }

    public function addReply(int $userId, int $parentCommentId, string $content, int $docId, string $docTitle, string $authorFirst, string $authorLast): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO comment_replies (comment_id, user_id, content) VALUES (:cid, :uid, :content)");
        $stmt->execute(['cid' => $parentCommentId, 'uid' => $userId, 'content' => $content]);
        $replyId = (int) $this->pdo->lastInsertId();

        $ps = $this->pdo->prepare("SELECT user_id FROM comments WHERE id = :cid LIMIT 1");
        $ps->execute(['cid' => $parentCommentId]);
        $row = $ps->fetch();
        $parentAuthor = $row ? (int) $row['user_id'] : 0;

        if ($parentAuthor && $parentAuthor !== $userId) {
            $me = trim($authorFirst . ' ' . $authorLast);
            $msg = $me . ' a répondu à votre commentaire sur « ' . mb_substr($docTitle, 0, 50) . ' »';
            $notifService = new \App\Services\NotificationService($this->pdo);
            $notifService->sendNotification($parentAuthor, 'comment_reply', $msg, $docId, $parentCommentId, $replyId, $userId);
        }
    }

    // -----------------------------------------------------------------
    // Dépôt de document (upload)
    // -----------------------------------------------------------------

    public function fileHashExists(string $hash): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM documents WHERE file_hash = :hash LIMIT 1 FOR UPDATE');
        $stmt->execute(['hash' => $hash]);
        return (bool) $stmt->fetch();
    }

    /**
     * Transaction complète : vérifie le doublon (FOR UPDATE), insère le
     * document, synchronise les filières.
     *
     * @return int|string L'id du document créé, ou 'duplicate'.
     */
    public function createFromUpload(array $data): int|string
    {
        $this->pdo->beginTransaction();

        try {
            if ($this->fileHashExists($data['file_hash'])) {
                $this->pdo->rollBack();
                return 'duplicate';
            }

            $hasThumbPathColumn = DatabaseHelper::thumbColumnExists($this->pdo);
            $hasPendingMatiere = DatabaseHelper::pendingMatiereEnabled($this->pdo);

            $columns = 'uploader_id, title, description, doc_type, semester,
                         filiere_id, licence_id, module_id, matiere_id';
            $placeholders = ':uploader_id, :title, :description, :doc_type, :semester,
                              :filiere_id, :licence_id, :module_id, :matiere_id';

            $params = [
                'uploader_id' => $data['uploader_id'],
                'title' => $data['title'],
                'description' => $data['description'],
                'doc_type' => $data['doc_type'],
                'semester' => $data['semester'],
                'filiere_id' => $data['filiere_id'],
                'licence_id' => $data['licence_id'],
                'module_id' => $data['module_id'],
                'matiere_id' => $data['matiere_id'],
            ];

            if ($hasPendingMatiere) {
                $columns .= ', matiere_label_pending';
                $placeholders .= ', :matiere_free_text';
                $params['matiere_free_text'] = $data['matiere_free_text'];
            }
            if ($hasThumbPathColumn) {
                $columns .= ', thumb_path';
                $placeholders .= ', :thumb_path';
                $params['thumb_path'] = $data['thumb_path'];
            }

            $columns .= ', exam_session, exam_section, exam_year,
                          file_path, mime_type, file_size_bytes, is_public, file_hash, status';
            $placeholders .= ', :exam_session, :exam_section, :exam_year,
                               :file_name, :mime_type, :size, :is_public, :file_hash, :status';

            $params += [
                'exam_session' => $data['exam_session'],
                'exam_section' => $data['exam_section'],
                'exam_year' => $data['exam_year'],
                'file_name' => $data['file_name'],
                'mime_type' => $data['mime_type'],
                'size' => $data['size'],
                'is_public' => $data['is_public'],
                'file_hash' => $data['file_hash'],
                'status' => 'pending',
            ];

            $sql = "INSERT INTO documents ($columns) VALUES ($placeholders)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $documentId = (int) $this->pdo->lastInsertId();
            if ($documentId > 0 && !empty($data['filiere_ids'])) {
                DatabaseHelper::syncDocumentFilieres($this->pdo, $documentId, $data['filiere_ids']);
            }
            $this->pdo->commit();

            return $documentId;
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            // Code 23000 = Integrity constraint violation (duplicate)
            if ($e->getCode() === '23000') {
                return 'duplicate';
            }
            error_log('EMSP createFromUpload failed: ' . $e->getMessage());
            return 0;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            error_log('EMSP createFromUpload failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Documents favoris de l'utilisateur (non paginé, pour le workspace JS).
     */
    public function workspaceFavorites(int $userId): array
    {
        DatabaseHelper::ensureThumbColumn($this->pdo);
        $hasThumbPathColumn = DatabaseHelper::thumbColumnExists($this->pdo);
        $thumbPathSelect = $hasThumbPathColumn ? 'd.thumb_path' : "'' AS thumb_path";

        $sql = "SELECT d.id, d.title, d.description, d.doc_type, d.semester, d.download_count, d.like_count,
                       d.file_size_bytes, d.file_path, $thumbPathSelect, d.mime_type, d.created_at AS doc_created_at,
                       d.status, d.is_public, d.uploader_id, d.licence_id,
                       f.created_at AS fav_date,
                       u.first_name, u.last_name,
                       fi.name AS filiere_name, li.name AS licence_name, ma.name AS matiere_name
                FROM favorites f
                JOIN documents d ON d.id = f.document_id
                JOIN users u ON u.id = d.uploader_id
                LEFT JOIN filieres fi ON fi.id = d.filiere_id
                LEFT JOIN licences li ON li.id = d.licence_id
                LEFT JOIN matieres ma ON ma.id = d.matiere_id
                WHERE f.user_id = :uid AND d.status = 'approved'
                ORDER BY f.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        $docs = $stmt->fetchAll();

        foreach ($docs as &$d) {
            foreach (['title', 'description', 'first_name', 'last_name', 'filiere_name', 'licence_name', 'matiere_name'] as $field) {
                if (isset($d[$field]) && is_string($d[$field])) {
                    $d[$field] = emsp_fix_mojibake($d[$field]);
                }
            }
            $d['filiere_label_display'] = trim((string) ($d['filiere_name'] ?? ''));
            $d['matiere_display'] = trim((string) ($d['matiere_name'] ?? ''));
            $d['created_at'] = (string) ($d['doc_created_at'] ?? '');
        }
        unset($d);

        return $docs;
    }

    // -----------------------------------------------------------------
    // Page publique "Concours" (accessible sans connexion)
    // -----------------------------------------------------------------

    /** @return array<int, array<string, mixed>> */
    public function publicConcoursDocuments(int $yearFilter, string $search): array
    {
        $hasExamYear = $this->hasColumn('documents', 'exam_year');
        $hasIsPublic = $this->hasColumn('documents', 'is_public');
        $hasThumbPath = $this->hasColumn('documents', 'thumb_path');
        if (!$hasExamYear) {
            $yearFilter = 0;
        }

        $examYearSelect = $hasExamYear ? 'd.exam_year' : 'NULL AS exam_year';
        $thumbPathSelect = $hasThumbPath ? 'd.thumb_path' : "'' AS thumb_path";
        $publicWhere = $hasIsPublic ? 'AND d.is_public = 1' : '';

        $sql = "SELECT d.id, d.title, d.description, d.download_count, d.created_at, d.file_path, $thumbPathSelect, d.mime_type, $examYearSelect,
                       u.first_name, u.last_name, f.name AS filiere_name, ma.name AS matiere_name
                FROM documents d
                JOIN users u ON u.id = d.uploader_id
                LEFT JOIN filieres f ON f.id = d.filiere_id
                LEFT JOIN matieres ma ON ma.id = d.matiere_id
                WHERE d.doc_type = 'concours' AND d.status = 'approved' $publicWhere";
        $params = [];
        if ($yearFilter > 0) {
            $sql .= ' AND d.exam_year = :year';
            $params['year'] = $yearFilter;
        }
        if ($search !== '') {
            $sql .= ' AND d.title LIKE :search';
            $params['search'] = '%' . $search . '%';
        }
        $sql .= $hasExamYear ? ' ORDER BY d.exam_year DESC, d.created_at DESC' : ' ORDER BY d.created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) {
            foreach (['title', 'description', 'first_name', 'last_name', 'filiere_name', 'matiere_name'] as $f) {
                if (isset($r[$f]) && is_string($r[$f])) {
                    $r[$f] = emsp_fix_mojibake($r[$f]);
                }
            }
        }
        unset($r);

        return $rows;
    }

    /** @return int[] */
    public function publicConcoursYears(): array
    {
        if (!$this->hasColumn('documents', 'exam_year')) {
            return [];
        }
        $publicWhere = $this->hasColumn('documents', 'is_public') ? 'AND is_public = 1' : '';
        $stmt = $this->pdo->query(
            "SELECT DISTINCT exam_year FROM documents WHERE doc_type='concours' AND status='approved' $publicWhere AND exam_year IS NOT NULL ORDER BY exam_year DESC"
        );
        return array_column($stmt->fetchAll(), 'exam_year');
    }
}
