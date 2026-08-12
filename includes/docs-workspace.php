<?php

include_once __DIR__ . '/generate_thumb.php';

if (!function_exists('emsp_docs_workspace_format_size')) {
    function emsp_docs_workspace_format_size($bytes): string
    {
        $size = max(0, (int) $bytes);
        if ($size >= 1073741824) {
            return number_format($size / 1073741824, 1, ',', ' ') . ' Go';
        }
        if ($size >= 1048576) {
            return number_format($size / 1048576, 1, ',', ' ') . ' Mo';
        }
        if ($size >= 1024) {
            return number_format($size / 1024, 0, ',', ' ') . ' Ko';
        }

        return $size . ' o';
    }
}

if (!function_exists('emsp_docs_workspace_time_ago')) {
    function emsp_docs_workspace_time_ago(?string $date): string
    {
        if (empty($date)) {
            return '';
        }

        $timestamp = strtotime($date);
        if (!$timestamp) {
            return (string) $date;
        }

        $delta = time() - $timestamp;
        if ($delta < 60) {
            return 'A l instant';
        }
        if ($delta < 3600) {
            return floor($delta / 60) . ' min';
        }
        if ($delta < 86400) {
            return floor($delta / 3600) . ' h';
        }
        if ($delta < 604800) {
            return floor($delta / 86400) . ' j';
        }

        return date('d/m/Y', $timestamp);
    }
}

if (!function_exists('emsp_docs_workspace_gradient')) {
    function emsp_docs_workspace_gradient(string $typeKey): string
    {
        $gradients = [
            'cours' => 'linear-gradient(135deg, #d1fae5 0%, #ecfdf5 100%)',
            'td' => 'linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%)',
            'correction' => 'linear-gradient(135deg, #e0e7ff 0%, #eef2ff 100%)',
            'examen' => 'linear-gradient(135deg, #fee2e2 0%, #fff1f2 100%)',
            'concours' => 'linear-gradient(135deg, #fef3c7 0%, #fffbeb 100%)',
        ];

        return $gradients[$typeKey] ?? 'linear-gradient(135deg, #e2e8f0 0%, #f8fafc 100%)';
    }
}

if (!function_exists('emsp_docs_workspace_type_meta')) {
    function emsp_docs_workspace_type_meta(?string $docType): array
    {
        $typeKey = strtolower(trim((string) $docType));
        $map = [
            'cours' => ['label' => 'Cours', 'icon' => 'bi-journal-text', 'tone' => '#1d4ed8', 'accent' => '#dbeafe'],
            'td' => ['label' => 'TD', 'icon' => 'bi-pencil-square', 'tone' => '#0f766e', 'accent' => '#ccfbf1'],
            'correction' => ['label' => 'Correction', 'icon' => 'bi-check2-square', 'tone' => '#7c3aed', 'accent' => '#ede9fe'],
            'examen' => ['label' => 'Examen', 'icon' => 'bi-patch-check', 'tone' => '#b91c1c', 'accent' => '#fee2e2'],
            'concours' => ['label' => 'Concours', 'icon' => 'bi-trophy', 'tone' => '#b45309', 'accent' => '#fef3c7'],
        ];

        $meta = $map[$typeKey] ?? ['label' => 'Document', 'icon' => 'bi-file-earmark-text', 'tone' => '#334155', 'accent' => '#e2e8f0'];
        $meta['key'] = $typeKey;
        $meta['gradient'] = emsp_docs_workspace_gradient($typeKey);

        return $meta;
    }
}

if (!function_exists('emsp_docs_workspace_file_kind')) {
    function emsp_docs_workspace_file_kind(?string $filePath, ?string $mimeType = ''): string
    {
        $extension = strtolower(pathinfo((string) $filePath, PATHINFO_EXTENSION));
        $mime = strtolower((string) $mimeType);

        if ($extension === 'pdf' || str_contains($mime, 'pdf')) {
            return 'pdf';
        }
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) || str_starts_with($mime, 'image/')) {
            return 'image';
        }
        if (in_array($extension, ['txt', 'md', 'csv'], true) || str_starts_with($mime, 'text/')) {
            return 'text';
        }
        if (in_array($extension, ['doc', 'docx'], true)) {
            return 'word';
        }
        if (in_array($extension, ['ppt', 'pptx'], true)) {
            return 'slides';
        }
        if (in_array($extension, ['xls', 'xlsx'], true)) {
            return 'sheet';
        }

        return $extension !== '' ? $extension : 'file';
    }
}

if (!function_exists('emsp_docs_workspace_kind_icon')) {
    function emsp_docs_workspace_kind_icon(string $kind): string
    {
        $map = [
            'pdf' => 'bi-file-earmark-pdf',
            'image' => 'bi-file-earmark-image',
            'text' => 'bi-file-earmark-text',
            'word' => 'bi-file-earmark-word',
            'slides' => 'bi-file-earmark-slides',
            'sheet' => 'bi-file-earmark-spreadsheet',
        ];

        return $map[$kind] ?? 'bi-file-earmark';
    }
}

if (!function_exists('emsp_docs_workspace_author_initials')) {
    function emsp_docs_workspace_author_initials(?string $firstName, ?string $lastName): string
    {
        $first = trim((string) $firstName);
        $last = trim((string) $lastName);
        $initials = '';

        if ($first !== '') {
            $initials .= strtoupper(substr($first, 0, 1));
        }
        if ($last !== '') {
            $initials .= strtoupper(substr($last, 0, 1));
        }

        return $initials !== '' ? $initials : 'EM';
    }
}

if (!function_exists('emsp_docs_workspace_payload')) {
    function emsp_docs_workspace_payload(array $doc, array $options = []): array
    {
        $id = (int) ($doc['id'] ?? 0);
        $firstName = trim((string) ($doc['first_name'] ?? ''));
        $lastName = trim((string) ($doc['last_name'] ?? ''));
        $author = trim($firstName . ' ' . $lastName);
        $authorCompact = trim($firstName . ' ' . ($lastName !== '' ? strtoupper(substr($lastName, 0, 1)) . '.' : ''));
        $filePath = (string) ($doc['file_path'] ?? '');
        $mimeType = (string) ($doc['mime_type'] ?? $doc['file_type'] ?? '');
        $typeMeta = emsp_docs_workspace_type_meta((string) ($doc['doc_type'] ?? ''));
        $fileKind = emsp_docs_workspace_file_kind($filePath, $mimeType);
        // La bibliothèque charge des dizaines de fiches d'un coup : ne jamais
        // générer de miniatures PDF/images de façon synchrone ici (Imagick,
        // poppler, GD) — trop lent sur un hébergement mutualisé et source de 500.
        $thumbRelative = function_exists('emsp_doc_thumb_resolve')
            ? emsp_doc_thumb_resolve($doc)
            : '';
        $thumbUrl = $thumbRelative !== '' && function_exists('emsp_doc_thumb_public_url')
            ? emsp_doc_thumb_public_url($thumbRelative)
            : '';
        $previewAvailable = function_exists('emsp_document_file_available')
            ? emsp_document_file_available($filePath)
            : false;
        $thumbLazyUrl = '';
        if ($thumbUrl === '' && $previewAvailable && in_array($fileKind, ['pdf', 'image'], true)) {
            $thumbLazyUrl = function_exists('emsp_doc_thumb_lazy_url')
                ? emsp_doc_thumb_lazy_url($id)
                : url('document-thumb?id=' . $id);
        }
        $quickViewSupported = $previewAvailable && in_array($fileKind, ['pdf', 'image', 'text'], true);
        $filiere = trim((string) ($doc['filiere_label_display'] ?? $doc['filiere_name'] ?? ''));
        $licence = trim((string) ($doc['licence_name'] ?? ''));
        $matiere = trim((string) ($doc['matiere_display'] ?? $doc['matiere_name'] ?? ''));
        $semester = trim((string) ($doc['semester'] ?? $doc['semestre'] ?? ''));
        $createdAt = trim((string) ($doc['created_at'] ?? ''));
        $favoriteDate = trim((string) ($doc['fav_date'] ?? ''));
        $sizeBytes = (int) ($doc['file_size_bytes'] ?? $doc['size'] ?? 0);
        $csrfToken = (string) ($options['csrf_token'] ?? '');
        $source = (string) ($options['source'] ?? 'library');
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $searchParts = [
            $doc['title'] ?? '',
            $doc['description'] ?? '',
            $author,
            $filiere,
            $licence,
            $matiere,
            $semester,
            $doc['doc_type'] ?? '',
        ];
        $searchText = strtolower(trim(implode(' ', array_map(static function ($value): string {
            return is_string($value) ? $value : '';
        }, $searchParts))));
        $badges = [];

        if ($createdAt !== '' && strtotime($createdAt) >= strtotime('-14 days')) {
            $badges[] = 'Nouveau';
        }
        if ((int) ($doc['download_count'] ?? 0) >= 15) {
            $badges[] = 'Populaire';
        }
        if (($doc['status'] ?? '') === 'approved') {
            $badges[] = 'Valide';
        }

        return [
            'id' => $id,
            'source' => $source,
            'title' => trim((string) ($doc['title'] ?? 'Document sans titre')),
            'description' => trim((string) ($doc['description'] ?? '')),
            'docType' => trim((string) ($doc['doc_type'] ?? '')),
            'typeKey' => $typeMeta['key'],
            'typeLabel' => $typeMeta['label'],
            'typeIcon' => $typeMeta['icon'],
            'typeTone' => $typeMeta['tone'],
            'typeAccent' => $typeMeta['accent'],
            'gradient' => $typeMeta['gradient'],
            'fileKind' => $fileKind,
            'fileExt' => $extension !== '' ? strtoupper($extension) : strtoupper($fileKind),
            'fileIcon' => emsp_docs_workspace_kind_icon($fileKind),
            'mimeType' => $mimeType,
            'author' => $author !== '' ? $author : 'Équipe EMSP',
            'authorCompact' => $authorCompact !== '' ? $authorCompact : 'Équipe EMSP',
            'authorInitials' => emsp_docs_workspace_author_initials($firstName, $lastName),
            'filiere' => $filiere,
            'licence' => $licence,
            'matiere' => $matiere,
            'semester' => $semester,
            'contextLabel' => trim(implode(' - ', array_filter([$filiere, $licence, $matiere]))),
            'sizeBytes' => $sizeBytes,
            'sizeLabel' => emsp_docs_workspace_format_size($sizeBytes),
            'createdAt' => $createdAt,
            'dateLabel' => emsp_docs_workspace_time_ago($createdAt),
            'downloadCount' => (int) ($doc['download_count'] ?? 0),
            'likeCount' => (int) ($doc['like_count'] ?? 0),
            'badges' => $badges,
            'isNew' => in_array('Nouveau', $badges, true),
            'isPopular' => in_array('Populaire', $badges, true),
            'isValidated' => in_array('Valide', $badges, true),
            'previewAvailable' => $previewAvailable,
            'thumbUrl' => $thumbUrl !== '' ? $thumbUrl : null,
            'thumbLazyUrl' => $thumbLazyUrl !== '' ? $thumbLazyUrl : null,
            // A PDF iframe can be captured by download managers despite an
            // inline header. Always use the rendered first-page image here.
            'previewImageUrl' => $thumbUrl !== '' ? $thumbUrl : ($thumbLazyUrl !== '' ? $thumbLazyUrl : null),
            'pdfViewerUrl' => $fileKind === 'pdf' && $previewAvailable
                ? url('telecharger?id=' . $id . '&preview=1')
                : null,
            'pdfDataUrl' => $fileKind === 'pdf' && $previewAvailable
                ? url('telecharger?id=' . $id . '&pdfdata=1')
                : null,
            'previewUrl' => $quickViewSupported
                ? url('telecharger?id=' . $id . ($fileKind === 'text' ? '&raw=1' : '&preview=1'))
                : null,
            'rawUrl' => $previewAvailable ? url('telecharger?id=' . $id . '&preview=1') : null,
            'documentUrl' => url('document?id=' . $id),
            'downloadUrl' => url('telecharger?id=' . $id . '&download=1'),
            'csrfToken' => $csrfToken,
            'favoriteDateLabel' => $favoriteDate !== '' ? date('d/m/Y', strtotime($favoriteDate)) : '',
            'favoriteActionUrl' => $source === 'favorites' ? 'mes-favoris.php' : null,
            'favoriteRemovable' => $source === 'favorites',
            'searchText' => $searchText,
        ];
    }
}
