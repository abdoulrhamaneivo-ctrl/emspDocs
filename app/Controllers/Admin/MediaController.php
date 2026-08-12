<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\LegacyDb;
use App\Repositories\Admin\MediaAdminRepository;

final class MediaController extends AdminController
{
    private const ALLOWED_FILTERS = ['all', 'image', 'video', 'public', 'private'];

    private const MIME_MAP = [
        'image/jpeg' => ['type' => 'image', 'ext' => 'jpg', 'max' => 8 * 1024 * 1024],
        'image/png' => ['type' => 'image', 'ext' => 'png', 'max' => 8 * 1024 * 1024],
        'image/webp' => ['type' => 'image', 'ext' => 'webp', 'max' => 8 * 1024 * 1024],
        'image/gif' => ['type' => 'image', 'ext' => 'gif', 'max' => 8 * 1024 * 1024],
        'video/mp4' => ['type' => 'video', 'ext' => 'mp4', 'max' => 70 * 1024 * 1024],
        'video/webm' => ['type' => 'video', 'ext' => 'webm', 'max' => 70 * 1024 * 1024],
        'video/ogg' => ['type' => 'video', 'ext' => 'ogv', 'max' => 70 * 1024 * 1024],
        'video/quicktime' => ['type' => 'video', 'ext' => 'mp4', 'max' => 70 * 1024 * 1024],
        'video/x-msvideo' => ['type' => 'video', 'ext' => 'mp4', 'max' => 70 * 1024 * 1024],
        'video/x-matroska' => ['type' => 'video', 'ext' => 'mp4', 'max' => 70 * 1024 * 1024],
    ];

    private const POSTER_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function index(): void
    {
        [$con, $adminUser] = $this->guard();
        LegacyDb::mediaHelpers();
        $media = $this->repo($con);
        $filter = $this->normalizeFilter((string) ($_GET['filter'] ?? 'all'));

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handleIndexPost($media, $filter, (int) $adminUser['id']);
            return;
        }

        $this->view('admin/media/index', [
            'items' => $media->list($filter),
            'counters' => $media->counters(),
            'categories' => $media->categories(),
            'filter' => $filter,
        ]);
    }

    public function form(): void
    {
        [$con] = $this->guard();
        LegacyDb::mediaHelpers();
        $media = $this->repo($con);

        $id = (int) ($_GET['id'] ?? 0);
        $defaults = [
            'title' => '',
            'description' => '',
            'type' => 'image',
            'file_path' => '',
            'poster_path' => '',
            'category_id' => 0,
            'category' => '',
            'is_public' => 1,
            'status' => 'published',
            'display_order' => 0,
        ];
        $item = $defaults;

        if ($id > 0) {
            $found = $media->findFull($id);
            if (!$found) {
                flash('warning', 'Média introuvable.');
                redirect('admin/mediatheque');
            }
            $item = array_merge($defaults, $found);
        }

        $filePath = (string) ($item['file_path'] ?? '');
        $isUploadMode = $filePath !== ''
            && !preg_match('#^https?://#i', $filePath)
            && str_starts_with(ltrim(str_replace('\\', '/', $filePath), '/'), 'uploads/');

        $this->view('admin/media/form', [
            'id' => $id,
            'item' => $item,
            'categories' => $media->categories(),
            'allowedStatuses' => $media->allowedStatuses(),
            'isUploadMode' => $isUploadMode,
            'fileSrc' => $filePath !== '' && function_exists('emsp_media_src') ? emsp_media_src($filePath) : '',
            'posterSrc' => !empty($item['poster_path']) && function_exists('emsp_media_src')
                ? emsp_media_src((string) $item['poster_path'])
                : '',
        ]);
    }

    public function save(): void
    {
        [$con, $adminUser] = $this->guard();
        LegacyDb::mediaHelpers();
        $media = $this->repo($con);

        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            flash('danger', 'Token de sécurité invalide.');
            redirect('admin/mediatheque');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $editing = $id > 0;
        $existing = $editing ? $media->findFull($id) : null;

        if ($editing && !$existing) {
            flash('warning', 'Média introuvable.');
            redirect('admin/mediatheque');
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $categoryRaw = trim((string) ($_POST['category'] ?? ''));
        $type = strtolower(trim((string) ($_POST['type'] ?? 'image')));
        $isPublic = isset($_POST['is_public']) ? 1 : 0;
        $status = strtolower(trim((string) ($_POST['status'] ?? 'published')));
        $displayOrder = (int) ($_POST['display_order'] ?? 0);
        $fileMode = strtolower(trim((string) ($_POST['file_mode'] ?? 'upload')));
        $linkPath = trim((string) ($_POST['file_path'] ?? ''));
        $removePoster = !empty($_POST['remove_poster']);
        $currentPoster = trim((string) ($_POST['current_poster_path'] ?? ($existing['poster_path'] ?? '')));
        $posterPath = $currentPoster !== '' && !$removePoster ? $currentPoster : null;

        $allowedStatus = $media->allowedStatuses();
        $redirectForm = 'admin/mediatheque/form' . ($editing ? '?id=' . $id : '');

        if ($title === '') {
            flash('danger', 'Veuillez renseigner un titre pour ce média.');
            redirect($redirectForm);
        }
        if (!in_array($type, ['image', 'video', 'lien'], true)) {
            flash('danger', 'Choisissez un type valide (image, vidéo ou lien).');
            redirect($redirectForm);
        }
        if (!in_array($status, $allowedStatus, true)) {
            $status = $allowedStatus[0] ?? 'published';
        }

        $filePath = '';
        $uploadedLocalPath = null;
        $oldFilePath = $existing ? (string) ($existing['file_path'] ?? '') : '';

        if ($type === 'lien' || $fileMode === 'link') {
            if ($linkPath === '') {
                flash('danger', 'Ajoutez un lien ou un chemin pour ce média.');
                redirect($redirectForm);
            }
            $filePath = $linkPath;
            $type = 'lien';
        } else {
            $reuse = $editing
                && $existing
                && $oldFilePath !== ''
                && str_starts_with(ltrim(str_replace('\\', '/', $oldFilePath), '/'), 'uploads/')
                && (string) ($existing['type'] ?? '') === $type;

            $uploadResult = $this->handleMediaUpload($type, $reuse ? $oldFilePath : '');
            if ($uploadResult['error'] !== '') {
                flash('danger', $uploadResult['error']);
                redirect($redirectForm);
            }
            $filePath = $uploadResult['path'];
            $uploadedLocalPath = $uploadResult['local'];
        }

        $posterUpload = $this->handlePosterUpload();
        if ($posterUpload['error'] !== '') {
            if ($uploadedLocalPath && is_file($uploadedLocalPath)) {
                @unlink($uploadedLocalPath);
            }
            flash('danger', $posterUpload['error']);
            redirect($redirectForm);
        }
        if ($posterUpload['path'] !== '') {
            if ($currentPoster !== '' && $currentPoster !== $posterUpload['path']) {
                $this->deleteLocalFile($media, $currentPoster);
            }
            $posterPath = $posterUpload['path'];
        } elseif ($removePoster && $currentPoster !== '') {
            $this->deleteLocalFile($media, $currentPoster);
            $posterPath = null;
        }

        $payload = [
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'type' => $type,
            'file_path' => $filePath,
            'poster_path' => $posterPath,
            'category_id' => $categoryId,
            'category_raw' => $categoryRaw,
            'is_public' => $isPublic,
            'status' => $status,
            'display_order' => $displayOrder,
            'created_by' => (int) $adminUser['id'],
        ];

        if ($editing) {
            $result = $media->update($id, $payload);
            if (!$result['ok']) {
                if ($uploadedLocalPath && is_file($uploadedLocalPath)) {
                    @unlink($uploadedLocalPath);
                }
                flash('danger', 'Impossible de mettre à jour le média.' . (!empty($result['error']) ? ' ' . $result['error'] : ''));
                redirect($redirectForm);
            }
            if ($oldFilePath !== '' && $oldFilePath !== $filePath) {
                $this->deleteLocalFile($media, $oldFilePath);
            }
            log_audit(Database::pdo(), (int) $adminUser['id'], 'media_updated', 'media', $id, $title);
            flash('success', 'Média mis à jour.');
        } else {
            $result = $media->create($payload);
            if (!$result['ok']) {
                if ($uploadedLocalPath && is_file($uploadedLocalPath)) {
                    @unlink($uploadedLocalPath);
                }
                flash('danger', "Impossible d'enregistrer le média." . (!empty($result['error']) ? ' ' . $result['error'] : ''));
                redirect($redirectForm);
            }
            $newId = (int) ($result['id'] ?? 0);
            if ($isPublic === 1 && $status === 'published' && $newId > 0) {
                $notifService = new \App\Services\NotificationService();
                $notifService->notifyMediaPublished($newId, $title, (int) $adminUser['id']);
            }
            flash('success', 'Média ajouté avec succès.');
        }

        redirect('admin/mediatheque');
    }

    public function categories(): void
    {
        [$con] = $this->guard();
        $media = $this->repo($con);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            if (!verify_csrf($_POST['csrf_token'] ?? null)) {
                flash('danger', 'Token de sécurité invalide.');
                redirect('admin/mediatheque/categories');
            }

            $action = (string) ($_POST['action'] ?? '');
            $name = trim((string) ($_POST['name'] ?? ''));
            $desc = trim((string) ($_POST['description'] ?? ''));
            $catId = (int) ($_POST['id'] ?? 0);

            if ($action === 'create' && $name !== '') {
                $result = $media->createCategory($name, $desc);
                if ($result['ok']) {
                    flash('success', 'Catégorie ajoutée.');
                } elseif (!empty($result['duplicate'])) {
                    flash('warning', 'Une catégorie avec ce nom existe déjà.');
                } else {
                    flash('danger', 'Impossible d\'ajouter la catégorie.');
                }
            } elseif ($action === 'update' && $catId > 0 && $name !== '') {
                $result = $media->updateCategory($catId, $name, $desc);
                if ($result['ok']) {
                    flash('success', 'Catégorie mise à jour.');
                } elseif (!empty($result['duplicate'])) {
                    flash('warning', 'Ce nom est déjà utilisé par une autre catégorie.');
                } else {
                    flash('danger', 'Impossible de mettre à jour la catégorie.');
                }
            } elseif ($action === 'delete' && $catId > 0) {
                $media->deleteCategory($catId);
                flash('info', 'Catégorie supprimée. Les médias associés ont été détachés.');
            }

            redirect('admin/mediatheque/categories');
        }

        $this->view('admin/media/categories', [
            'categories' => $media->listCategoriesFull(),
        ]);
    }

    public function poster(): void
    {
        [$con] = $this->guard();
        $media = $this->repo($con);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=UTF-8');

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'message' => 'Méthode invalide.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Token invalide.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $mediaId = (int) ($_POST['media_id'] ?? 0);
        if ($mediaId <= 0 || !$media->find($mediaId)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Média invalide.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $upload = $this->handlePosterUpload(true);
        if ($upload['error'] !== '') {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => $upload['error']], JSON_UNESCAPED_UNICODE);
            return;
        }

        $existing = $media->find($mediaId);
        $oldPoster = (string) ($existing['poster_path'] ?? '');
        $media->updatePoster($mediaId, $upload['path']);
        if ($oldPoster !== '' && $oldPoster !== $upload['path']) {
            $this->deleteLocalFile($media, $oldPoster);
        }

        echo json_encode(['ok' => true, 'poster_path' => $upload['path']], JSON_UNESCAPED_UNICODE);
    }

    public function youtubeTitle(): void
    {
        [$con] = $this->guard();
        LegacyDb::mediaHelpers();

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=UTF-8');

        $url = trim((string) ($_GET['url'] ?? ''));
        $title = $url !== '' && function_exists('emsp_youtube_title') ? emsp_youtube_title($url) : '';

        echo json_encode([
            'ok' => $title !== '',
            'title' => $title,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    private function repo(\PDO $con): MediaAdminRepository
    {
        $media = new MediaAdminRepository($con);
        $media->ensureSchema();
        return $media;
    }

    private function normalizeFilter(string $filter): string
    {
        $filter = strtolower(trim($filter));
        return in_array($filter, self::ALLOWED_FILTERS, true) ? $filter : 'all';
    }

    private function handleIndexPost(MediaAdminRepository $media, string $filter, int $adminId): void
    {
        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            flash('danger', 'Token de sécurité invalide.');
            redirect('admin/mediatheque?filter=' . rawurlencode($filter));
        }

        if (isset($_POST['toggle_visibility'])) {
            $id = (int) $_POST['toggle_visibility'];
            if ($id > 0) {
                $current = $media->find($id);
                $media->toggleVisibility($id);
                if ($current && (int) $current['is_public'] === 0 && $current['status'] === 'published') {
                    $notifService = new \App\Services\NotificationService();
                    $notifService->notifyMediaPublished($id, (string) $current['title'], $adminId);
                }
                flash('info', 'La visibilité du média a été modifiée.');
            }
            redirect('admin/mediatheque?filter=' . rawurlencode($filter));
        }

        if (isset($_POST['delete_media'])) {
            $id = (int) $_POST['delete_media'];
            if ($id > 0) {
                $row = $media->findFull($id);
                if (!$row) {
                    flash('warning', 'Média introuvable.');
                    redirect('admin/mediatheque?filter=' . rawurlencode($filter));
                }
                $filePath = (string) ($row['file_path'] ?? '');
                $posterPath = (string) ($row['poster_path'] ?? '');
                if (!$media->delete($id)) {
                    flash('danger', 'La suppression a échoué.');
                    redirect('admin/mediatheque?filter=' . rawurlencode($filter));
                }
                $this->deleteLocalFile($media, $filePath);
                $this->deleteLocalFile($media, $posterPath);
                log_audit(Database::pdo(), $adminId, 'media_deleted', 'media', $id, $filePath);
                flash('info', 'Le média a été supprimé définitivement.');
            }
            redirect('admin/mediatheque?filter=' . rawurlencode($filter));
        }

        if (isset($_POST['upload_media'])) {
            $this->handleQuickUpload($media, $filter, $adminId);
            return;
        }

        redirect('admin/mediatheque?filter=' . rawurlencode($filter));
    }

    private function handleQuickUpload(MediaAdminRepository $media, string $filter, int $adminId): void
    {
        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $categoryRaw = trim((string) ($_POST['category'] ?? ''));
        $type = strtolower(trim((string) ($_POST['type'] ?? 'image')));
        $isPublic = isset($_POST['is_public']) ? 1 : 0;
        $status = strtolower(trim((string) ($_POST['status'] ?? 'published')));
        $mode = strtolower(trim((string) ($_POST['upload_mode'] ?? 'file')));
        $externalPath = trim((string) ($_POST['external_path'] ?? ''));

        $allowedStatus = $media->allowedStatuses();
        if ($title === '') {
            flash('danger', 'Veuillez renseigner un titre pour ce média.');
            redirect('admin/mediatheque?filter=' . rawurlencode($filter));
        }
        if (!in_array($type, ['image', 'video', 'lien'], true)) {
            flash('danger', 'Choisissez un type valide (image, vidéo ou lien).');
            redirect('admin/mediatheque?filter=' . rawurlencode($filter));
        }
        if (!in_array($status, $allowedStatus, true)) {
            $status = $allowedStatus[0] ?? 'published';
        }

        $filePath = null;
        $uploadedLocalPath = null;

        if ($type === 'lien' || $mode === 'link') {
            if ($externalPath === '') {
                flash('danger', 'Ajoutez un lien ou un chemin pour ce média.');
                redirect('admin/mediatheque?filter=' . rawurlencode($filter));
            }
            $filePath = $externalPath;
            $type = 'lien';
        } else {
            $uploadResult = $this->handleMediaUpload($type, '');
            if ($uploadResult['error'] !== '') {
                flash('danger', $uploadResult['error']);
                redirect('admin/mediatheque?filter=' . rawurlencode($filter));
            }
            $filePath = $uploadResult['path'];
            $uploadedLocalPath = $uploadResult['local'];
        }

        $result = $media->create([
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'type' => $type,
            'file_path' => $filePath,
            'poster_path' => null,
            'category_id' => $categoryId,
            'category_raw' => $categoryRaw,
            'is_public' => $isPublic,
            'status' => $status,
            'display_order' => 0,
            'created_by' => $adminId,
        ]);

        if (!$result['ok']) {
            if ($uploadedLocalPath && is_file($uploadedLocalPath)) {
                @unlink($uploadedLocalPath);
            }
            flash('danger', "Impossible d'enregistrer le média." . (!empty($result['error']) ? ' ' . $result['error'] : ''));
            redirect('admin/mediatheque?filter=' . rawurlencode($filter));
        }

        if ($isPublic === 1 && $status === 'published') {
            $notifService = new \App\Services\NotificationService();
            $notifService->notifyMediaPublished((int) $result['id'], $title, $adminId);
        }

        flash('success', 'Le média a été ajouté avec succès.');
        redirect('admin/mediatheque?filter=' . rawurlencode($filter));
    }

    /** @return array{path: string, local: ?string, error: string} */
    private function handleMediaUpload(string $type, string $reusePath): array
    {
        $uploadErr = $_FILES['media_file']['error'] ?? UPLOAD_ERR_NO_FILE;
        $uploadName = (string) ($_FILES['media_file']['name'] ?? '');

        if ($uploadErr !== UPLOAD_ERR_OK || $uploadName === '') {
            if ($reusePath !== '') {
                return ['path' => $reusePath, 'local' => null, 'error' => ''];
            }
            return ['path' => '', 'local' => null, 'error' => "Veuillez sélectionner un fichier média avant d'envoyer."];
        }

        $tmpName = (string) $_FILES['media_file']['tmp_name'];
        $fileSize = (int) ($_FILES['media_file']['size'] ?? 0);
        $mime = emsp_detect_mime($tmpName);
        $extLower = strtolower(pathinfo($uploadName, PATHINFO_EXTENSION));

        if (!isset(self::MIME_MAP[$mime])) {
            $extFallback = [
                'mp4' => 'video/mp4',
                'mov' => 'video/quicktime',
                'webm' => 'video/webm',
                'avi' => 'video/x-msvideo',
                'mkv' => 'video/x-matroska',
                'ogv' => 'video/ogg',
            ];
            if (isset($extFallback[$extLower])) {
                $mime = $extFallback[$extLower];
            }
        }

        if (!isset(self::MIME_MAP[$mime])) {
            return ['path' => '', 'local' => null, 'error' => 'Formats acceptés : JPG, PNG, WEBP, GIF pour les images ; MP4, WEBM, OGG, MOV, AVI et MKV pour les vidéos.'];
        }

        $detected = self::MIME_MAP[$mime];
        $typeMatches = $type === $detected['type'] || ($type === 'video' && $detected['type'] === 'video');
        if (!$typeMatches) {
            return ['path' => '', 'local' => null, 'error' => 'Le type choisi ne correspond pas au fichier fourni.'];
        }

        $serverMaxUpload = emsp_max_upload_size(70 * 1024 * 1024);
        $limit = min($detected['max'], $serverMaxUpload);
        if ($fileSize <= 0 || $fileSize > $limit) {
            $limitMb = round($limit / 1024 / 1024, 1);
            return ['path' => '', 'local' => null, 'error' => ($detected['type'] === 'image' ? 'Image trop lourde' : 'Vidéo trop lourde') . ' (max ' . $limitMb . ' Mo).'];
        }

        $subdir = $detected['type'] === 'video' ? 'videos' : 'images';
        $targetDir = dirname(__DIR__, 3) . '/uploads/media/' . $subdir;
        if (!is_dir($targetDir) && !@mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            return ['path' => '', 'local' => null, 'error' => "Impossible de créer le dossier uploads/media/{$subdir}/."];
        }

        $generated = bin2hex(random_bytes(16)) . '.' . $detected['ext'];
        $targetPath = $targetDir . '/' . $generated;
        if (!move_uploaded_file($tmpName, $targetPath)) {
            return ['path' => '', 'local' => null, 'error' => "Impossible d'enregistrer le fichier média."];
        }

        return [
            'path' => 'uploads/media/' . $subdir . '/' . $generated,
            'local' => $targetPath,
            'error' => '',
        ];
    }

    /** @return array{path: string, error: string} */
    private function handlePosterUpload(bool $required = false): array
    {
        if (empty($_FILES['poster_file']) || !is_array($_FILES['poster_file'])) {
            if ($required) {
                return ['path' => '', 'error' => 'Fichier miniature manquant.'];
            }
            return ['path' => '', 'error' => ''];
        }

        $uploadErr = $_FILES['poster_file']['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($uploadErr === UPLOAD_ERR_NO_FILE) {
            if ($required) {
                return ['path' => '', 'error' => 'Fichier miniature manquant.'];
            }
            return ['path' => '', 'error' => ''];
        }
        if ($uploadErr !== UPLOAD_ERR_OK) {
            return ['path' => '', 'error' => 'Erreur lors du téléversement de la miniature.'];
        }

        $tmp = (string) ($_FILES['poster_file']['tmp_name'] ?? '');
        $size = (int) ($_FILES['poster_file']['size'] ?? 0);
        if ($tmp === '' || $size <= 0) {
            return ['path' => '', 'error' => 'Fichier miniature invalide.'];
        }

        $mime = emsp_detect_mime($tmp);
        if (!isset(self::POSTER_MIME[$mime])) {
            return ['path' => '', 'error' => 'Format de miniature non supporté (JPG, PNG, WEBP).'];
        }
        if (@getimagesize($tmp) === false) {
            return ['path' => '', 'error' => 'La miniature image est invalide.'];
        }
        if ($size > 2 * 1024 * 1024) {
            return ['path' => '', 'error' => 'Miniature trop lourde (max 2 Mo).'];
        }

        $targetDir = dirname(__DIR__, 3) . '/uploads/media/posters';
        if (!is_dir($targetDir) && !@mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            return ['path' => '', 'error' => 'Impossible de créer le dossier posters.'];
        }

        $filename = bin2hex(random_bytes(16)) . '.' . self::POSTER_MIME[$mime];
        $dest = $targetDir . '/' . $filename;
        if (!move_uploaded_file($tmp, $dest)) {
            return ['path' => '', 'error' => 'Impossible de sauvegarder la miniature.'];
        }

        return ['path' => 'uploads/media/posters/' . $filename, 'error' => ''];
    }

    private function deleteLocalFile(MediaAdminRepository $media, string $path): void
    {
        $local = $media->resolveLocalPath($path);
        if ($local !== null) {
            @unlink($local);
        }
    }
}
