<?php

declare(strict_types=1);

if (!function_exists('emsp_thumb_column_exists')) {
    function emsp_thumb_column_exists(mysqli $con): bool {
        $res = mysqli_query($con, "SHOW COLUMNS FROM documents LIKE 'thumb_path'");
        if (!$res) return false;
        $ok = mysqli_num_rows($res) > 0;
        mysqli_free_result($res);
        return $ok;
    }
}

if (!function_exists('emsp_ensure_thumb_column')) {
    function emsp_ensure_thumb_column(mysqli $con): void {
        if (emsp_thumb_column_exists($con)) return;
        mysqli_query($con, "ALTER TABLE documents ADD COLUMN thumb_path VARCHAR(255) NULL AFTER file_path");
    }
}

if (!function_exists('emsp_is_shared_hosting')) {
    function emsp_is_shared_hosting(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if (
            str_contains($host, '.unaux.com')
            || str_contains($host, '.ezyro.com')
            || str_contains($host, '.profreehost.com')
        ) {
            $cached = true;
            return true;
        }

        $disabled = array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));
        if (in_array('exec', $disabled, true) || in_array('shell_exec', $disabled, true)) {
            $cached = true;
            return true;
        }

        $cached = false;
        return false;
    }
}

if (!function_exists('emsp_doc_thumb_public_url')) {
    function emsp_doc_thumb_public_url(string $relativePath): string
    {
        $path = trim($relativePath);
        if ($path === '') {
            return '';
        }
        if (preg_match('#^(https?:)?//#i', $path)) {
            return $path;
        }
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return url(ltrim(str_replace('\\', '/', $path), '/'));
    }
}

if (!function_exists('emsp_doc_thumb_lazy_url')) {
    function emsp_doc_thumb_lazy_url(int $docId): string
    {
        return $docId > 0 ? url('document-thumb?id=' . $docId) : '';
    }
}

if (!function_exists('emsp_doc_thumb_relative_path')) {
    function emsp_doc_thumb_relative_path(array $doc): string
    {
        $mime = strtolower((string) ($doc['mime_type'] ?? ''));
        $file = trim((string) ($doc['file_path'] ?? ''));
        $thumb = trim((string) ($doc['thumb_path'] ?? ''));
        $isLegacyPdfPlaceholder = (str_contains($mime, 'pdf') || strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'pdf')
            && str_ends_with(strtolower($thumb), '.svg');

        if ($isLegacyPdfPlaceholder && $file !== '') {
            $pdfThumb = 'thumbs/' . pathinfo($file, PATHINFO_FILENAME) . '.jpg';
            $pdfThumbPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'documents'
                . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $pdfThumb);
            if (is_file($pdfThumbPath)) {
                return 'uploads/documents/' . $pdfThumb;
            }
        }

        if ($thumb !== '' && !$isLegacyPdfPlaceholder) {
            if (preg_match('#^(https?:)?//#i', $thumb) || str_starts_with($thumb, 'uploads/')) {
                return $thumb;
            }

            return 'uploads/documents/' . ltrim(str_replace('\\', '/', $thumb), '/');
        }

        if ($file !== '') {
            $documentsDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'documents';
            $baseName = pathinfo($file, PATHINFO_FILENAME);
            foreach (['.jpg', '.jpeg', '.png', '.webp', '.svg'] as $suffix) {
                $candidate = $documentsDir . DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR . $baseName . $suffix;
                if (is_file($candidate)) {
                    return 'uploads/documents/thumbs/' . $baseName . $suffix;
                }
            }
        }

        if (str_starts_with($mime, 'image/') && $file !== '') {
            return 'uploads/documents/' . ltrim(str_replace('\\', '/', $file), '/');
        }

        return '';
    }
}

if (!function_exists('emsp_doc_thumb_resolve')) {
    function emsp_doc_thumb_resolve(array $doc): string
    {
        return emsp_doc_thumb_relative_path($doc);
    }
}

if (!function_exists('emsp_doc_thumb_src')) {
    function emsp_doc_thumb_src(array $doc, bool $allowGenerate = true): string
    {
        $resolved = emsp_doc_thumb_relative_path($doc);
        if ($resolved !== '') {
            return $resolved;
        }

        if (!$allowGenerate) {
            return '';
        }

        $mime = strtolower((string) ($doc['mime_type'] ?? ''));
        $file = trim((string) ($doc['file_path'] ?? ''));
        if ($file === '') {
            return '';
        }

        $documentsDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'documents';
        $sourcePath = $documentsDir . DIRECTORY_SEPARATOR . ltrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $file), DIRECTORY_SEPARATOR);
        if (!is_file($sourcePath) || !is_readable($sourcePath)) {
            return '';
        }

        $generated = (string) emsp_generate_document_thumbnail(
            $sourcePath,
            basename($file),
            $mime,
            (string) ($doc['title'] ?? ''),
            $documentsDir
        );
        if ($generated === '') {
            return '';
        }

        return 'uploads/documents/' . ltrim(str_replace('\\', '/', $generated), '/');
    }
}

if (!function_exists('emsp_thumb_kind_from_file')) {
    function emsp_thumb_kind_from_file(string $filePath, string $mimeType = ''): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mime = strtolower($mimeType);

        if ($extension === 'pdf' || str_contains($mime, 'pdf')) {
            return 'pdf';
        }
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) || str_starts_with($mime, 'image/')) {
            return 'image';
        }
        if (in_array($extension, ['doc', 'docx'], true)) {
            return 'word';
        }
        if (in_array($extension, ['xls', 'xlsx', 'csv'], true)) {
            return 'sheet';
        }
        if (in_array($extension, ['ppt', 'pptx'], true)) {
            return 'slides';
        }
        if (in_array($extension, ['zip', 'rar', '7z', 'tar', 'gz'], true)) {
            return 'archive';
        }
        if (in_array($extension, ['txt', 'md'], true) || str_starts_with($mime, 'text/')) {
            return 'text';
        }

        return $extension !== '' ? $extension : 'file';
    }
}

if (!function_exists('emsp_thumb_palette')) {
    function emsp_thumb_palette(string $kind): array
    {
        $map = [
            'pdf' => ['#991b1b', '#fee2e2', '#ffffff'],
            'image' => ['#0f766e', '#ccfbf1', '#ffffff'],
            'word' => ['#1d4ed8', '#dbeafe', '#ffffff'],
            'sheet' => ['#166534', '#dcfce7', '#ffffff'],
            'slides' => ['#9a3412', '#ffedd5', '#ffffff'],
            'archive' => ['#7c2d12', '#fed7aa', '#ffffff'],
            'text' => ['#334155', '#e2e8f0', '#ffffff'],
            'file' => ['#475569', '#e2e8f0', '#ffffff'],
        ];

        return $map[$kind] ?? $map['file'];
    }
}

if (!function_exists('emsp_thumb_placeholder_svg')) {
    function emsp_thumb_placeholder_svg(string $title, string $extension, string $kind): string
    {
        [$primary, $soft, $paper] = emsp_thumb_palette($kind);
        $safeTitle = htmlspecialchars(trim($title) !== '' ? $title : 'Document EMSP', ENT_QUOTES | ENT_XML1, 'UTF-8');
        $safeExt = htmlspecialchars(strtoupper($extension !== '' ? $extension : $kind), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $safeKind = htmlspecialchars(strtoupper($kind), ENT_QUOTES | ENT_XML1, 'UTF-8');

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="720" height="1020" viewBox="0 0 720 1020" fill="none">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="720" y2="1020" gradientUnits="userSpaceOnUse">
      <stop stop-color="{$soft}"/>
      <stop offset="1" stop-color="#F8FAFC"/>
    </linearGradient>
  </defs>
  <rect width="720" height="1020" rx="42" fill="url(#bg)"/>
  <rect x="58" y="58" width="604" height="904" rx="30" fill="{$paper}" fill-opacity="0.98"/>
  <rect x="58" y="58" width="604" height="170" rx="30" fill="{$primary}"/>
  <rect x="94" y="102" width="150" height="46" rx="23" fill="white" fill-opacity="0.18"/>
  <text x="169" y="132" text-anchor="middle" font-family="Inter,Segoe UI,sans-serif" font-size="22" font-weight="700" fill="white">EMSP DOCS</text>
  <text x="94" y="308" font-family="Inter,Segoe UI,sans-serif" font-size="34" font-weight="800" fill="#0F172A">{$safeTitle}</text>
  <text x="94" y="380" font-family="Inter,Segoe UI,sans-serif" font-size="120" font-weight="900" fill="{$primary}">{$safeExt}</text>
  <text x="94" y="438" font-family="Inter,Segoe UI,sans-serif" font-size="24" font-weight="700" fill="#475569">{$safeKind}</text>
  <rect x="94" y="504" width="492" height="18" rx="9" fill="#E2E8F0"/>
  <rect x="94" y="548" width="448" height="18" rx="9" fill="#E2E8F0"/>
  <rect x="94" y="592" width="516" height="18" rx="9" fill="#E2E8F0"/>
  <rect x="94" y="636" width="384" height="18" rx="9" fill="#E2E8F0"/>
  <rect x="94" y="842" width="190" height="52" rx="26" fill="{$soft}"/>
  <text x="189" y="875" text-anchor="middle" font-family="Inter,Segoe UI,sans-serif" font-size="24" font-weight="800" fill="{$primary}">PREVIEW</text>
</svg>
SVG;
    }
}

if (!function_exists('emsp_thumb_make_dirs')) {
    function emsp_thumb_make_dirs(string $documentsDir): string
    {
        $thumbDir = rtrim($documentsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'thumbs';
        if (!is_dir($thumbDir)) {
            @mkdir($thumbDir, 0755, true);
        }
        return $thumbDir;
    }
}

if (!function_exists('emsp_thumb_write_placeholder')) {
    function emsp_thumb_write_placeholder(string $documentsDir, string $storedFileName, string $title, string $kind): string
    {
        $thumbDir = emsp_thumb_make_dirs($documentsDir);
        $baseName = pathinfo($storedFileName, PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($storedFileName, PATHINFO_EXTENSION));
        $relative = 'thumbs/' . $baseName . '.svg';
        $absolute = $thumbDir . DIRECTORY_SEPARATOR . $baseName . '.svg';
        @file_put_contents($absolute, emsp_thumb_placeholder_svg($title, $extension, $kind));
        return $relative;
    }
}

if (!function_exists('emsp_thumb_generate_image')) {
    function emsp_thumb_generate_image(string $sourcePath, string $absoluteThumbPath): bool
    {
        if (!function_exists('imagecreatefromstring')) {
            return false;
        }

        $binary = @file_get_contents($sourcePath);
        if ($binary === false) {
            return false;
        }

        $source = @imagecreatefromstring($binary);
        if (!$source) {
            return false;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        if ($width <= 0 || $height <= 0) {
            imagedestroy($source);
            return false;
        }

        $targetWidth = 720;
        $targetHeight = 1020;
        $sourceRatio = $width / $height;
        $targetRatio = $targetWidth / $targetHeight;

        if ($sourceRatio > $targetRatio) {
            $cropHeight = $height;
            $cropWidth = (int) round($height * $targetRatio);
            $srcX = (int) round(($width - $cropWidth) / 2);
            $srcY = 0;
        } else {
            $cropWidth = $width;
            $cropHeight = (int) round($width / $targetRatio);
            $srcX = 0;
            $srcY = (int) round(($height - $cropHeight) / 2);
        }

        $thumb = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($thumb, true);
        imagesavealpha($thumb, true);
        $white = imagecolorallocate($thumb, 255, 255, 255);
        imagefill($thumb, 0, 0, $white);
        imagecopyresampled($thumb, $source, 0, 0, $srcX, $srcY, $targetWidth, $targetHeight, $cropWidth, $cropHeight);

        $written = imagejpeg($thumb, $absoluteThumbPath, 86);
        imagedestroy($thumb);
        imagedestroy($source);

        return $written;
    }
}

if (!function_exists('emsp_thumb_generate_pdf_imagick')) {
    function emsp_thumb_generate_pdf_imagick(string $sourcePath, string $absoluteThumbPath): bool
    {
        if (!extension_loaded('imagick')) {
            return false;
        }

        try {
            $imagick = new Imagick();
            $imagick->setResolution(160, 160);
            $imagick->readImage($sourcePath . '[0]');
            $imagick->setImageFormat('jpeg');
            $imagick->setImageBackgroundColor('white');
            if (method_exists($imagick, 'setImageAlphaChannel')) {
                $imagick->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
            }
            $imagick = $imagick->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
            $imagick->cropThumbnailImage(720, 1020);
            $ok = $imagick->writeImage($absoluteThumbPath);
            $imagick->clear();
            $imagick->destroy();
            return (bool) $ok;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('emsp_thumb_generate_pdf_poppler')) {
    function emsp_thumb_generate_pdf_poppler(string $sourcePath, string $absoluteThumbPath): bool
    {
        if (emsp_is_shared_hosting() || !function_exists('exec')) {
            return false;
        }

        // Local XAMPP does not always ship Imagick. Poppler renders page 1 so
        // PDF cards show the document itself instead of a generic placeholder.
        $outputBase = preg_replace('/\\.jpg$/i', '', $absoluteThumbPath) ?: $absoluteThumbPath;
        $localPoppler = 'C:\\Users\\abdoul ivo\\.cache\\codex-runtimes\\codex-primary-runtime\\dependencies\\native\\poppler\\Library\\bin\\pdftoppm.exe';
        $binary = is_file($localPoppler) ? '"' . $localPoppler . '"' : 'pdftoppm';
        $command = $binary . ' -f 1 -l 1 -jpeg -jpegopt quality=86 -scale-to-x 720 -scale-to-y -1 '
            . escapeshellarg($sourcePath) . ' ' . escapeshellarg($outputBase);
        @exec($command . ' 2>NUL', $lines, $exitCode);
        $candidates = glob($outputBase . '-*.jpg') ?: [];
        $generated = $candidates[0] ?? $outputBase . '-1.jpg';
        if ($exitCode === 0 && is_file($generated)) {
            if ($generated !== $absoluteThumbPath) {
                @rename($generated, $absoluteThumbPath);
            }
            return is_file($absoluteThumbPath);
        }
        return false;
    }
}

if (!function_exists('emsp_generate_document_thumbnail')) {
    function emsp_generate_document_thumbnail(
        string $sourcePath,
        string $storedFileName,
        string $mimeType = '',
        string $title = '',
        string $documentsDir = ''
    ): string {
        $documentsDir = $documentsDir !== '' ? $documentsDir : dirname($sourcePath);
        $kind = emsp_thumb_kind_from_file($storedFileName, $mimeType);
        $thumbDir = emsp_thumb_make_dirs($documentsDir);
        $baseName = pathinfo($storedFileName, PATHINFO_FILENAME);

        if ($kind === 'image') {
            $relative = 'thumbs/' . $baseName . '.jpg';
            $absolute = $thumbDir . DIRECTORY_SEPARATOR . $baseName . '.jpg';
            if (emsp_thumb_generate_image($sourcePath, $absolute)) {
                return $relative;
            }
            return ltrim($storedFileName, '/');
        }

        if ($kind === 'pdf') {
            $relative = 'thumbs/' . $baseName . '.jpg';
            $absolute = $thumbDir . DIRECTORY_SEPARATOR . $baseName . '.jpg';
            if (!emsp_is_shared_hosting()
                && (
                    emsp_thumb_generate_pdf_imagick($sourcePath, $absolute)
                    || emsp_thumb_generate_pdf_poppler($sourcePath, $absolute)
                )) {
                return $relative;
            }
        }

        return emsp_thumb_write_placeholder($documentsDir, $storedFileName, $title, $kind);
    }
}

if (!function_exists('emsp_generate_document_thumb')) {
    /**
     * Alias legacy utilise par UploadService (signature historique).
     */
    function emsp_generate_document_thumb(string $sourcePath, string $absoluteThumbPath): bool
    {
        if (!is_file($sourcePath) || !is_readable($sourcePath)) {
            return false;
        }

        $documentsDir = dirname($sourcePath);
        if (basename(dirname($absoluteThumbPath)) === 'thumbs') {
            $documentsDir = dirname(dirname($absoluteThumbPath));
        }

        $relative = emsp_generate_document_thumbnail(
            $sourcePath,
            basename($sourcePath),
            '',
            '',
            $documentsDir
        );
        if ($relative === '') {
            return false;
        }

        $generatedAbsolute = rtrim($documentsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (!is_file($generatedAbsolute)) {
            return false;
        }

        if ($generatedAbsolute !== $absoluteThumbPath) {
            @copy($generatedAbsolute, $absoluteThumbPath);
        }

        return is_file($absoluteThumbPath);
    }
}

if (!function_exists('emsp_doc_thumb_stream_file')) {
    function emsp_doc_thumb_stream_file(string $absolutePath): void
    {
        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Miniature introuvable';
            exit;
        }

        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        $mimeMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
        ];
        $mime = $mimeMap[$ext] ?? 'application/octet-stream';

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: ' . $mime);
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: public, max-age=86400');
        readfile($absolutePath);
        exit;
    }
}
