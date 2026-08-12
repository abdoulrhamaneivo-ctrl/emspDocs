<?php

if (!function_exists('emsp_uuid_v4')) {
    function emsp_uuid_v4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        $hex = bin2hex($data);
        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }
}

if (!function_exists('emsp_upload_image_asset')) {
    /**
     * Upload a single image with MIME validation, UUID naming and optional resize.
     *
     * @param array<string,mixed> $file      A single $_FILES[field] payload.
     * @param string              $targetDir Absolute target directory.
     * @param string              $publicPrefix Public relative prefix, e.g. "uploads/formations/covers/".
     * @param int                 $maxBytes Maximum file size in bytes.
     * @param int                 $maxWidth Max output width (0 = keep original).
     * @param int                 $maxHeight Max output height (0 = keep original).
     * @return array{ok:bool,path:string,error:string,mime:string}
     */
    function emsp_upload_image_asset(
        array $file,
        string $targetDir,
        string $publicPrefix,
        int $maxBytes = 6291456,
        int $maxWidth = 1920,
        int $maxHeight = 1920
    ): array {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'path' => '', 'error' => '', 'mime' => ''];
        }
        if ($error !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'path' => '', 'error' => 'Erreur lors de l upload du fichier image.', 'mime' => ''];
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > $maxBytes) {
            return [
                'ok' => false,
                'path' => '',
                'error' => 'Image trop lourde (max ' . round($maxBytes / 1024 / 1024, 1) . ' Mo).',
                'mime' => '',
            ];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => false, 'path' => '', 'error' => 'Fichier temporaire invalide.', 'mime' => ''];
        }

        $mime = function_exists('emsp_detect_mime') ? emsp_detect_mime($tmp) : '';
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];
        if (!isset($allowed[$mime])) {
            return ['ok' => false, 'path' => '', 'error' => 'Format non autorise (JPG, PNG, WEBP, GIF).', 'mime' => $mime];
        }

        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
            return ['ok' => false, 'path' => '', 'error' => 'Impossible de creer le dossier cible.', 'mime' => $mime];
        }

        $realTarget = realpath($targetDir);
        if ($realTarget === false || !is_writable($realTarget)) {
            return ['ok' => false, 'path' => '', 'error' => 'Le dossier cible n est pas accessible en ecriture.', 'mime' => $mime];
        }

        $ext = $allowed[$mime];
        $filename = emsp_uuid_v4() . '.' . $ext;
        $absolute = $realTarget . DIRECTORY_SEPARATOR . $filename;

        $resized = false;
        if (
            function_exists('imagecreatetruecolor')
            && function_exists('imagecopyresampled')
            && function_exists('getimagesize')
            && $maxWidth > 0
            && $maxHeight > 0
        ) {
            $imgInfo = @getimagesize($tmp);
            if (is_array($imgInfo) && isset($imgInfo[0], $imgInfo[1])) {
                $srcW = (int) $imgInfo[0];
                $srcH = (int) $imgInfo[1];
                if ($srcW > 0 && $srcH > 0) {
                    $ratio = min($maxWidth / $srcW, $maxHeight / $srcH, 1);
                    $dstW = max(1, (int) round($srcW * $ratio));
                    $dstH = max(1, (int) round($srcH * $ratio));

                    $src = null;
                    if ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg')) {
                        $src = @imagecreatefromjpeg($tmp);
                    } elseif ($mime === 'image/png' && function_exists('imagecreatefrompng')) {
                        $src = @imagecreatefrompng($tmp);
                    } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
                        $src = @imagecreatefromwebp($tmp);
                    } elseif ($mime === 'image/gif' && function_exists('imagecreatefromgif')) {
                        $src = @imagecreatefromgif($tmp);
                    }

                    if ($src !== false && $src !== null) {
                        $dst = imagecreatetruecolor($dstW, $dstH);
                        if ($mime === 'image/png' || $mime === 'image/gif' || $mime === 'image/webp') {
                            imagealphablending($dst, false);
                            imagesavealpha($dst, true);
                            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                            imagefilledrectangle($dst, 0, 0, $dstW, $dstH, $transparent);
                        }
                        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

                        if ($mime === 'image/jpeg' && function_exists('imagejpeg')) {
                            $resized = imagejpeg($dst, $absolute, 85);
                        } elseif ($mime === 'image/png' && function_exists('imagepng')) {
                            $resized = imagepng($dst, $absolute, 6);
                        } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
                            $resized = imagewebp($dst, $absolute, 85);
                        } elseif ($mime === 'image/gif' && function_exists('imagegif')) {
                            $resized = imagegif($dst, $absolute);
                        }

                        imagedestroy($dst);
                        imagedestroy($src);
                    }
                }
            }
        }

        if (!$resized && !move_uploaded_file($tmp, $absolute)) {
            return ['ok' => false, 'path' => '', 'error' => 'Impossible d enregistrer l image.', 'mime' => $mime];
        }

        $publicPrefix = trim(str_replace('\\', '/', $publicPrefix), '/');
        $publicPath = $publicPrefix . '/' . $filename;

        return ['ok' => true, 'path' => $publicPath, 'error' => '', 'mime' => $mime];
    }
}
