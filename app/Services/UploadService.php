<?php

declare(strict_types=1);

namespace App\Services;

final class UploadService
{
    private string $rootDir;

    public function __construct(?string $rootDir = null)
    {
        $this->rootDir = $rootDir ?? dirname(__DIR__, 2);
    }

    /**
     * Valider et traiter l'upload d'un document d'étude (PDF, Office, Zip).
     *
     * @return array{ok: bool, path?: string, thumb?: string, hash?: string, original_name?: string, mime?: string, size?: int, error?: string}
     */
    public function processDocument(array $file, int $userId): array
    {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['ok' => false, 'error' => 'Aucun fichier n\'a été téléversé.'];
        }

        $realMime = function_exists('emsp_detect_mime')
            ? emsp_detect_mime($file['tmp_name'])
            : ($file['type'] ?? 'application/octet-stream');

        $allowedMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'application/zip',
            'application/x-zip-compressed',
        ];

        if (!in_array($realMime, $allowedMimes, true)) {
            return ['ok' => false, 'error' => 'Format de fichier non autorisé. Formats acceptés : PDF, Word, PowerPoint, Excel, Texte, ZIP.'];
        }

        $maxSize = function_exists('emsp_max_upload_size')
            ? emsp_max_upload_size(defined('MAX_UPLOAD_SIZE') ? MAX_UPLOAD_SIZE : (20 * 1024 * 1024))
            : (20 * 1024 * 1024);

        $fileSize = (int) ($file['size'] ?? 0);
        if ($fileSize > $maxSize) {
            $maxMb = round($maxSize / 1024 / 1024, 1);
            return ['ok' => false, 'error' => "Le fichier est trop volumineux. La taille maximale autorisée est de {$maxMb} Mo."];
        }

        $fileHash = hash_file('sha256', $file['tmp_name']);
        if ($fileHash === false) {
            return ['ok' => false, 'error' => 'Impossible de calculer l\'empreinte du fichier.'];
        }

        $docsDir = $this->rootDir . '/uploads/documents';
        if (!is_dir($docsDir) && !@mkdir($docsDir, 0755, true)) {
            return ['ok' => false, 'error' => 'Impossible de préparer le dossier d\'envoi. Contactez l\'administration.'];
        }
        if (!is_writable($docsDir) && !@chmod($docsDir, 0755)) {
            return ['ok' => false, 'error' => 'Le serveur ne peut pas enregistrer le fichier. Contactez l\'administration.'];
        }

        $mimeToExt = [
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'text/plain' => 'txt',
            'application/zip' => 'zip',
            'application/x-zip-compressed' => 'zip',
        ];
        $ext = $mimeToExt[$realMime] ?? 'bin';

        $fileName = bin2hex(random_bytes(16)) . '.' . $ext;
        $destPath = $docsDir . '/' . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['ok' => false, 'error' => 'Échec de l\'enregistrement du fichier sur le serveur.'];
        }
        @chmod($destPath, 0644);

        $thumbPath = '';
        $thumbsDir = $docsDir . '/thumbs';
        if (!is_dir($thumbsDir)) {
            @mkdir($thumbsDir, 0755, true);
        }

        if (function_exists('emsp_generate_document_thumbnail')) {
            require_once $this->rootDir . '/includes/generate_thumb.php';
            $relativeThumb = emsp_generate_document_thumbnail(
                $destPath,
                $fileName,
                $realMime,
                (string) ($file['name'] ?? ''),
                $docsDir
            );
            if ($relativeThumb !== '') {
                $thumbPath = 'uploads/documents/' . ltrim(str_replace('\\', '/', $relativeThumb), '/');
            }
        }

        return [
            'ok' => true,
            'path' => 'uploads/documents/' . $fileName,
            'thumb' => $thumbPath,
            'hash' => $fileHash,
            'original_name' => (string) ($file['name'] ?? ''),
            'mime' => $realMime,
            'size' => $fileSize,
        ];
    }

    /**
     * Valider et traiter l'upload d'une photo de profil.
     *
     * @return array{ok: bool, path?: string, error?: string}
     */
    public function processProfilePhoto(array $file, int $userId): array
    {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['ok' => false, 'error' => 'Aucune photo n\'a été téléversée.'];
        }

        $realMime = function_exists('emsp_detect_mime')
            ? emsp_detect_mime($file['tmp_name'])
            : ($file['type'] ?? '');

        if (!in_array($realMime, ['image/jpeg', 'image/png'], true)) {
            return ['ok' => false, 'error' => 'Seuls les fichiers JPG ou PNG sont acceptés pour la photo de profil.'];
        }
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false || empty($imageInfo[0]) || empty($imageInfo[1])) {
            return ['ok' => false, 'error' => "La photo fournie n'est pas une image valide."];
        }

        $maxUpload = function_exists('emsp_max_upload_size')
            ? emsp_max_upload_size(defined('MAX_UPLOAD_SIZE') ? MAX_UPLOAD_SIZE : (5 * 1024 * 1024))
            : (5 * 1024 * 1024);
        $limit = min($maxUpload, 2 * 1024 * 1024);

        if (($file['size'] ?? 0) > $limit) {
            return ['ok' => false, 'error' => 'La photo ne doit pas dépasser ' . round($limit / 1024 / 1024, 1) . ' Mo.'];
        }

        $profilesDir = $this->rootDir . '/uploads/profiles';
        if (!is_dir($profilesDir) && !@mkdir($profilesDir, 0755, true)) {
            return ['ok' => false, 'error' => 'Impossible de préparer le dossier photo. Contactez l\'administration.'];
        }
        if (!is_writable($profilesDir) && !@chmod($profilesDir, 0755)) {
            return ['ok' => false, 'error' => 'Le serveur ne peut pas enregistrer la photo. Contactez l\'administration.'];
        }

        $mimeToExt = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        $ext = $mimeToExt[$realMime] ?? 'jpg';
        $photoName = bin2hex(random_bytes(16)) . '.' . $ext;
        $destPath = $profilesDir . '/' . $photoName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['ok' => false, 'error' => 'Impossible d\'enregistrer la photo sur le serveur.'];
        }
        @chmod($destPath, 0644);

        return ['ok' => true, 'path' => $photoName];
    }

    /**
     * Supprimer proprement un fichier principal et sa miniature éventuelle.
     */
    public function cleanup(?string $filePath, ?string $thumbPath = null): void
    {
        if ($filePath !== null && $filePath !== '') {
            $absFile = strpos($filePath, '/') === 0 ? $filePath : $this->rootDir . '/' . ltrim($filePath, '/');
            if (is_file($absFile)) {
                @unlink($absFile);
            }
        }

        if ($thumbPath !== null && $thumbPath !== '') {
            $absThumb = strpos($thumbPath, '/') === 0 ? $thumbPath : $this->rootDir . '/' . ltrim($thumbPath, '/');
            if (is_file($absThumb)) {
                @unlink($absThumb);
            }
        }
    }
}
