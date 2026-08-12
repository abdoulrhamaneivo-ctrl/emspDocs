<?php

if (!function_exists('emsp_home_local_asset_exists')) {
    function emsp_home_local_asset_exists(string $src, string $projectRoot): bool
    {
        $src = trim($src);
        if ($src === '' || emsp_is_external_url($src) || str_starts_with($src, 'data:') || str_starts_with($src, 'blob:')) {
            return $src !== '';
        }

        $path = strtok($src, '?') ?: $src;
        $path = ltrim(str_replace('\\', '/', $path), '/');

        return is_file($projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path));
    }
}

if (!function_exists('emsp_home_media_cover_src')) {
    function emsp_home_media_cover_src(string $path, string $projectRoot): string
    {
        $src = emsp_media_src($path);

        return emsp_home_local_asset_exists($src, $projectRoot) ? $src : 'assets/images/media-thumb-3.jpg';
    }
}

if (!function_exists('emsp_home_excerpt')) {
    function emsp_home_excerpt(string $value, int $max = 160): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');
        if ($value === '') {
            return 'Document academique partage par la communaute EMSP.';
        }
        if (mb_strlen($value) <= $max) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $max - 1)) . '...';
    }
}

if (!function_exists('emsp_home_journal_cover_src')) {
    function emsp_home_journal_cover_src(string $html): string
    {
        $decoded = htmlspecialchars_decode(emsp_fix_mojibake($html), ENT_QUOTES | ENT_HTML5);
        $decoded = str_replace(
            ['../uploads/', '..\\uploads\\', '../assets/', '..\\assets\\'],
            ['uploads/', 'uploads/', 'assets/', 'assets/'],
            $decoded
        );

        if (!preg_match('/<img\b[^>]*\bsrc\s*=\s*(?:"([^"]+)"|\'([^\']+)\'|([^\s>]+))/i', $decoded, $match)) {
            return '';
        }

        $src = trim((string) ($match[1] ?: ($match[2] ?: ($match[3] ?? ''))));
        if ($src === '' || preg_match('#^(?:javascript:|data:)#i', $src)) {
            return '';
        }

        if (preg_match('#^https?://#i', $src)) {
            return $src;
        }

        $src = ltrim(str_replace('\\', '/', $src), '/');
        if (strpos($src, 'uploads/') === 0 || strpos($src, 'assets/') === 0) {
            return $src;
        }

        return '';
    }
}

if (!function_exists('emsp_home_mediatheque_preview_photos')) {
    /**
     * Photos récentes publiées pour le rail horizontal accueil (mobile-first).
     *
     * @return list<array{id: int, title: string, src: string, category_raw: string, category_label: string}>
     */
    function emsp_home_mediatheque_preview_photos(mysqli $con, string $projectRoot, int $limit = 10): array
    {
        $limit = max(1, min(10, $limit));
        $photos = [];
        $stmt = mysqli_prepare(
            $con,
            "SELECT id, title, file_path, category, created_at
             FROM media
             WHERE type = 'image'
               AND is_public = 1
               AND status = 'published'
             ORDER BY display_order DESC, created_at DESC
             LIMIT ?"
        );

        if (!$stmt) {
            return [];
        }

        mysqli_stmt_bind_param($stmt, 'i', $limit);
        mysqli_stmt_execute($stmt);
        $rows = emsp_stmt_fetch_all($stmt);
        mysqli_stmt_close($stmt);

        foreach ($rows as $row) {
            $categoryRaw = (string) ($row['category'] ?? '');
            $categoryLabel = emsp_fix_mojibake(trim($categoryRaw));
            if ($categoryLabel === '') {
                $categoryLabel = 'Album EMSP';
            }

            $title = emsp_fix_mojibake(trim((string) ($row['title'] ?? '')));
            if ($title === '') {
                $title = 'Photo EMSP';
            }

            $photos[] = [
                'id' => (int) ($row['id'] ?? 0),
                'title' => $title,
                'src' => emsp_home_media_cover_src((string) ($row['file_path'] ?? ''), $projectRoot),
                'category_raw' => $categoryRaw,
                'category_label' => $categoryLabel,
            ];
        }

        return $photos;
    }
}

if (!function_exists('emsp_home_mediatheque_discovery')) {
    /**
     * Jeu de donnees leger pour la bottom sheet mobile « Decouvrir » (accueil).
     *
     * @return array{categories: list<array{key: string, label: string}>, items: list<array<string, mixed>>}
     */
    function emsp_home_mediatheque_discovery(mysqli $con, array $mediaHighlights, string $projectRoot): array
    {
        if (!function_exists('emsp_is_youtube')) {
            require_once dirname(__DIR__, 2) . '/includes/services/media-data.php';
        }

        $categories = [
            ['key' => 'all', 'label' => 'Tout'],
            ['key' => 'video', 'label' => 'Vidéos'],
        ];

        foreach ($mediaHighlights as $album) {
            $raw = (string) ($album['category_raw'] ?? '');
            $label = trim((string) ($album['category_label'] ?? ''));
            if ($label === '') {
                $label = 'Album EMSP';
            }
            $categories[] = [
                'key' => 'album:' . $raw,
                'label' => $label,
            ];
        }

        $items = [];
        $stmt = mysqli_prepare(
            $con,
            "SELECT id, title, description, file_path, poster_path, type, category, created_at
             FROM media
             WHERE is_public = 1
               AND status = 'published'
               AND (
                    type = 'image'
                    OR type IN ('video', 'lien')
               )
             ORDER BY display_order DESC, created_at DESC
             LIMIT 24"
        );

        if ($stmt) {
            mysqli_stmt_execute($stmt);
            $rows = emsp_stmt_fetch_all($stmt);
            mysqli_stmt_close($stmt);

            foreach ($rows as $row) {
                $type = (string) ($row['type'] ?? '');
                $categoryRaw = (string) ($row['category'] ?? '');
                $categoryLabel = emsp_fix_mojibake(trim($categoryRaw));
                if ($categoryLabel === '') {
                    $categoryLabel = 'Sans titre';
                }

                $title = emsp_fix_mojibake(trim((string) ($row['title'] ?? '')));
                if ($title === '') {
                    $title = $type === 'image' ? 'Photo EMSP' : 'Video EMSP';
                }

                $item = [
                    'id' => (int) ($row['id'] ?? 0),
                    'kind' => $type === 'image' ? 'image' : 'video',
                    'title' => $title,
                    'description' => emsp_fix_mojibake(trim((string) ($row['description'] ?? ''))),
                    'category_raw' => $categoryRaw,
                    'category_label' => $categoryLabel,
                    'created_at' => (string) ($row['created_at'] ?? ''),
                    'date_label' => function_exists('emsp_date_fr') ? emsp_date_fr((string) ($row['created_at'] ?? '')) : '',
                ];

                if ($item['kind'] === 'image') {
                    $item['src'] = emsp_home_media_cover_src((string) ($row['file_path'] ?? ''), $projectRoot);
                    $item['filter_key'] = 'album:' . $categoryRaw;
                } else {
                    $src = emsp_media_src((string) ($row['file_path'] ?? ''));
                    $isYoutube = emsp_is_youtube($src);
                    $item['is_youtube'] = $isYoutube;
                    $item['embed'] = $isYoutube ? emsp_youtube_embed($src) : $src;
                    $item['thumb'] = $isYoutube
                        ? emsp_youtube_thumb($src)
                        : emsp_video_poster_src([
                            'poster_path' => $row['poster_path'] ?? '',
                            'file_path' => $row['file_path'] ?? '',
                        ], $projectRoot);
                    if (!$isYoutube && ($item['thumb'] ?? '') === 'assets/images/media-thumb-3.jpg') {
                        $item['thumb'] = '';
                    }
                    $item['initial'] = emsp_video_initial($title);
                    $item['filter_key'] = 'video';
                }

                $items[] = $item;
            }
        }

        return [
            'categories' => $categories,
            'items' => $items,
        ];
    }
}

if (!function_exists('emsp_home_build_view_model')) {
    function emsp_home_build_view_model(mysqli $con, array $session, string $projectRoot): array
    {
        $authUser = $session['auth_user'] ?? [];
        $isAuth = !empty($session['auth']) || !empty($authUser['id']);
        $firstName = emsp_fix_mojibake((string) ($authUser['first_name'] ?? ''));
        $uid = (int) ($authUser['id'] ?? 0);

        $totalApprovedDocs = 0;
        $activeFiliereCount = 0;
        $activeLicenceCount = 0;
        $downloadsCount = 0;
        $favoritesCount = 0;
        $notificationsCount = 0;
        $journalHighlights = [];
        $mediaHighlights = [];
        $concoursHighlights = [];
        $featuredDocs = [];
        $guestCountFilter = $isAuth ? '' : " AND is_public=1";
        $guestConcoursFilter = $isAuth ? '' : " AND d.is_public=1";

        $countResult = mysqli_query($con, "SELECT COUNT(*) AS total FROM documents WHERE status='approved'" . $guestCountFilter);
        if ($countResult) {
            $countRow = mysqli_fetch_assoc($countResult);
            $totalApprovedDocs = (int) ($countRow['total'] ?? 0);
        }

        $filiereCountResult = mysqli_query($con, "SELECT COUNT(*) AS total FROM filieres WHERE status='active'");
        if ($filiereCountResult) {
            $filiereCountRow = mysqli_fetch_assoc($filiereCountResult);
            $activeFiliereCount = (int) ($filiereCountRow['total'] ?? 0);
        }

        $licenceCountResult = mysqli_query($con, "SELECT COUNT(*) AS total FROM licences WHERE status='active'");
        if ($licenceCountResult) {
            $licenceCountRow = mysqli_fetch_assoc($licenceCountResult);
            $activeLicenceCount = (int) ($licenceCountRow['total'] ?? 0);
        }

        $journalStmt = mysqli_prepare(
            $con,
            "SELECT id, type, title, content, created_at
             FROM journal
             WHERE status='published'
             ORDER BY created_at DESC
             LIMIT 8"
        );
        if ($journalStmt) {
            mysqli_stmt_execute($journalStmt);
            $journalHighlights = emsp_stmt_fetch_all($journalStmt);
            mysqli_stmt_close($journalStmt);
        }

        foreach ($journalHighlights as &$journalItem) {
            foreach (['type', 'title', 'content'] as $field) {
                if (isset($journalItem[$field]) && is_string($journalItem[$field])) {
                    $journalItem[$field] = emsp_fix_mojibake($journalItem[$field]);
                }
            }
        }
        unset($journalItem);

        $mediaStmt = mysqli_prepare(
            $con,
            "SELECT
                COALESCE(TRIM(REPLACE(REPLACE(category, CHAR(160), ' '), '\t', ' ')),'') AS category,
                COUNT(*) AS media_count,
                MAX(created_at) AS last_date,
                (SELECT file_path
                 FROM media m2
                 WHERE COALESCE(TRIM(REPLACE(REPLACE(m2.category, CHAR(160), ' '), '\t', ' ')),'')
                       = COALESCE(TRIM(REPLACE(REPLACE(m.category, CHAR(160), ' '), '\t', ' ')),'')
                   AND m2.type = 'image'
                   AND m2.is_public = 1
                   AND m2.status = 'published'
                 ORDER BY m2.created_at DESC
                 LIMIT 1) AS cover_path
             FROM media m
             WHERE m.type='image'
               AND m.is_public=1
               AND m.status='published'
             GROUP BY COALESCE(TRIM(REPLACE(REPLACE(category, CHAR(160), ' '), '\t', ' ')),'')
             ORDER BY last_date DESC
             LIMIT 8"
        );
        if ($mediaStmt) {
            mysqli_stmt_execute($mediaStmt);
            $mediaHighlights = emsp_stmt_fetch_all($mediaStmt);
            mysqli_stmt_close($mediaStmt);
        }

        foreach ($mediaHighlights as &$mediaItem) {
            $categoryRaw = (string) ($mediaItem['category'] ?? '');
            $mediaItem['category_raw'] = $categoryRaw;
            $mediaItem['category'] = emsp_fix_mojibake($categoryRaw);
            $mediaItem['category_label'] = trim($mediaItem['category']) !== '' ? $mediaItem['category'] : 'Album EMSP';
            $coverPath = trim((string) ($mediaItem['cover_path'] ?? ''));
            $mediaItem['cover_src'] = $coverPath !== '' ? emsp_home_media_cover_src($coverPath, $projectRoot) : 'assets/images/media-thumb-3.jpg';
        }
        unset($mediaItem);

        $concoursStmt = mysqli_prepare(
            $con,
            "SELECT d.id, d.title, d.description, d.created_at, d.download_count, d.file_path, d.thumb_path, d.mime_type,
                    u.first_name, u.last_name
             FROM documents d
             JOIN users u ON u.id = d.uploader_id
             WHERE d.status='approved'
               AND d.doc_type='concours'" . $guestConcoursFilter . "
             ORDER BY COALESCE(d.approved_at, d.created_at) DESC, d.created_at DESC
             LIMIT 6"
        );
        if ($concoursStmt) {
            mysqli_stmt_execute($concoursStmt);
            $concoursHighlights = emsp_stmt_fetch_all($concoursStmt);
            mysqli_stmt_close($concoursStmt);
        }

        foreach ($concoursHighlights as &$concoursItem) {
            foreach (['title', 'description', 'first_name', 'last_name'] as $field) {
                if (isset($concoursItem[$field]) && is_string($concoursItem[$field])) {
                    $concoursItem[$field] = emsp_fix_mojibake($concoursItem[$field]);
                }
            }
        }
        unset($concoursItem);

        $featuredDocsStmt = mysqli_prepare(
            $con,
            "SELECT d.id, d.title, d.doc_type, d.download_count,
                    d.like_count, d.file_size_bytes, d.created_at,
                    u.first_name, u.last_name,
                    f.name AS filiere_name
             FROM documents d
             JOIN users u ON u.id = d.uploader_id
             LEFT JOIN filieres f ON f.id = d.filiere_id
             WHERE d.status='approved'" . $guestCountFilter . "
             ORDER BY d.download_count DESC
             LIMIT 4"
        );
        if ($featuredDocsStmt) {
            mysqli_stmt_execute($featuredDocsStmt);
            $featuredDocs = emsp_stmt_fetch_all($featuredDocsStmt);
            mysqli_stmt_close($featuredDocsStmt);
        }

        foreach ($featuredDocs as &$featuredDoc) {
            foreach (['title', 'doc_type', 'first_name', 'last_name', 'filiere_name'] as $field) {
                if (isset($featuredDoc[$field]) && is_string($featuredDoc[$field])) {
                    $featuredDoc[$field] = emsp_fix_mojibake($featuredDoc[$field]);
                }
            }
        }
        unset($featuredDoc);

        if ($isAuth && $uid > 0) {
            $downloadsStmt = mysqli_prepare($con, "SELECT COUNT(*) FROM history WHERE user_id=? AND action='download'");
            if ($downloadsStmt) {
                mysqli_stmt_bind_param($downloadsStmt, 'i', $uid);
                mysqli_stmt_execute($downloadsStmt);
                mysqli_stmt_bind_result($downloadsStmt, $downloadsCount);
                mysqli_stmt_fetch($downloadsStmt);
                mysqli_stmt_close($downloadsStmt);
            }

            $favoritesStmt = mysqli_prepare($con, "SELECT COUNT(*) FROM favorites WHERE user_id=?");
            if ($favoritesStmt) {
                mysqli_stmt_bind_param($favoritesStmt, 'i', $uid);
                mysqli_stmt_execute($favoritesStmt);
                mysqli_stmt_bind_result($favoritesStmt, $favoritesCount);
                mysqli_stmt_fetch($favoritesStmt);
                mysqli_stmt_close($favoritesStmt);
            }

            $notificationsStmt = mysqli_prepare($con, "SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
            if ($notificationsStmt) {
                mysqli_stmt_bind_param($notificationsStmt, 'i', $uid);
                mysqli_stmt_execute($notificationsStmt);
                mysqli_stmt_bind_result($notificationsStmt, $notificationsCount);
                mysqli_stmt_fetch($notificationsStmt);
                mysqli_stmt_close($notificationsStmt);
            }
        }

        $featuredJournalData = null;
        $journalCardItems = array_slice($journalHighlights, 0, 6);
        $journalVisitorItems = array_slice($journalHighlights, 0, 6);

        if (!empty($journalHighlights)) {
            $featured = $journalHighlights[0];
            $featuredType = strtolower(trim((string) ($featured['type'] ?? 'annonce')));
            if (!in_array($featuredType, ['annonce', 'defi', 'sondage'], true)) {
                $featuredType = 'annonce';
            }

            $featuredCover = emsp_home_journal_cover_src((string) ($featured['content'] ?? ''));
            if ($featuredCover === '') {
                $featuredCover = 'assets/images/emsp-ivoire-tech-forum-2025.jpg';
            }

            $featuredJournalData = [
                'id' => (int) ($featured['id'] ?? 0),
                'title' => trim((string) ($featured['title'] ?? 'Actualite EMSP')),
                'excerpt' => emsp_home_excerpt(strip_tags((string) ($featured['content'] ?? '')), 220),
                'type' => $featuredType,
                'type_label' => ucfirst($featuredType),
                'cover' => $featuredCover,
                'date' => (string) ($featured['created_at'] ?? 'now'),
            ];

            if (count($journalHighlights) > 1) {
                $journalCardItems = array_slice($journalHighlights, 1, 6);
            }
        }

        $mediathequeDiscovery = emsp_home_mediatheque_discovery($con, $mediaHighlights, $projectRoot);
        $mediathequePreviewPhotos = emsp_home_mediatheque_preview_photos($con, $projectRoot, 10);

        return [
            'authUser' => $authUser,
            'isAuth' => $isAuth,
            'firstName' => $firstName,
            'uid' => $uid,
            'totalApprovedDocs' => $totalApprovedDocs,
            'activeFiliereCount' => $activeFiliereCount,
            'activeLicenceCount' => $activeLicenceCount,
            'downloadsCount' => $downloadsCount,
            'favoritesCount' => $favoritesCount,
            'notificationsCount' => $notificationsCount,
            'journalHighlights' => $journalHighlights,
            'mediaHighlights' => $mediaHighlights,
            'mediathequeDiscovery' => $mediathequeDiscovery,
            'mediathequePreviewPhotos' => $mediathequePreviewPhotos,
            'concoursHighlights' => $concoursHighlights,
            'featuredDocs' => $featuredDocs,
            'heroDocumentCount' => number_format($totalApprovedDocs, 0, ',', ' '),
            'heroFiliereCount' => number_format($activeFiliereCount, 0, ',', ' '),
            'heroLicenceCount' => number_format($activeLicenceCount, 0, ',', ' '),
            'documentTypeMeta' => [
                'cours' => ['label' => 'Cours', 'badge' => 'bg-success'],
                'td' => ['label' => 'TD', 'badge' => 'bg-warning text-dark'],
                'correction' => ['label' => 'Correction', 'badge' => 'bg-primary'],
                'concours' => ['label' => 'Concours', 'badge' => 'bg-dark'],
                'examen' => ['label' => 'Examen', 'badge' => 'bg-secondary'],
            ],
            'featuredJournalData' => $featuredJournalData,
            'journalCardItems' => $journalCardItems,
            'journalVisitorItems' => $journalVisitorItems,
        ];
    }
}
