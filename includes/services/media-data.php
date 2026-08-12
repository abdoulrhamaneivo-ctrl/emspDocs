<?php

function emsp_is_youtube(string $src): bool
{
    return stripos($src, 'youtube.com') !== false || stripos($src, 'youtu.be') !== false;
}

function emsp_youtube_embed(string $url): string
{
    if (!preg_match('#(?:v=|youtu\.be/|embed/)([a-zA-Z0-9_-]{11})#', $url, $m)) return '';
    return 'https://www.youtube.com/embed/' . $m[1] . '?rel=0&autoplay=0';
}

function emsp_youtube_thumb(string $url): string
{
    if (!preg_match('#(?:v=|youtu\.be/|embed/)([a-zA-Z0-9_-]{11})#', $url, $m)) return 'assets/images/media-thumb-3.jpg';
    return 'https://img.youtube.com/vi/' . $m[1] . '/mqdefault.jpg';
}

function emsp_video_initial(string $title): string
{
    $title = trim(strip_tags($title));
    if ($title === '') {
        return 'V';
    }

    return mb_strtoupper(mb_substr($title, 0, 1, 'UTF-8'), 'UTF-8');
}

function emsp_video_cache_key(array $video): string
{
    $id = (int) ($video['id'] ?? 0);
    $createdAt = trim((string) ($video['created_at'] ?? ''));
    if ($id > 0 && $createdAt !== '') {
        return 'video_' . $id . '_' . strtotime($createdAt);
    }
    if ($id > 0) {
        return 'video_' . $id;
    }
    return 'video_' . md5((string) ($video['file_path'] ?? ''));
}

function emsp_local_asset_exists(string $src, string $projectRoot): bool
{
    $src = trim($src);
    if ($src === '' || emsp_is_external_url($src) || str_starts_with($src, 'data:') || str_starts_with($src, 'blob:')) {
        return $src !== '';
    }

    $path = strtok($src, '?') ?: $src;
    $path = ltrim(str_replace('\\', '/', $path), '/');
    $fullPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);

    return is_file($fullPath);
}

function emsp_existing_media_src(?string $path, string $projectRoot, string $fallback = 'assets/images/media-thumb-3.jpg'): string
{
    $src = emsp_media_src((string) $path);
    if ($src === '' || !emsp_local_asset_exists($src, $projectRoot)) {
        return $fallback;
    }

    return $src;
}

function emsp_video_poster_src(array $video, string $projectRoot): string
{
    $poster = trim((string) ($video['poster'] ?? ''));
    if ($poster !== '' && emsp_local_asset_exists($poster, $projectRoot)) {
        return $poster;
    }

    return 'assets/images/media-thumb-3.jpg';
}

function emsp_video_card_thumb(array $video, string $projectRoot): string
{
    if (!empty($video['is_youtube'])) {
        return '<img src="' . htmlspecialchars((string) ($video['thumb'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"'
            . ' alt="' . htmlspecialchars((string) ($video['title'] ?? 'Video EMSP'), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"'
            . ' class="video-thumb-img"'
            . ' data-fallback-src="assets/images/media-thumb-3.jpg"'
            . ' loading="lazy">';
    }

    $title = htmlspecialchars((string) ($video['title'] ?? 'Video EMSP'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $initiale = htmlspecialchars((string) ($video['initial'] ?? emsp_video_initial((string) ($video['title'] ?? 'Video EMSP'))), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $src = htmlspecialchars((string) ($video['embed'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $cacheKey = htmlspecialchars((string) ($video['cache_key'] ?? emsp_video_cache_key($video)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $poster = htmlspecialchars(emsp_video_poster_src($video, $projectRoot), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    return '
    <div class="video-thumb-local" data-local-thumb data-video-src="' . $src . '" data-video-title="' . $title . '" data-video-initial="' . $initiale . '" data-video-cache-key="' . $cacheKey . '" data-video-poster="' . $poster . '">
        <img class="video-thumb-poster" alt="' . $title . '" loading="lazy" src="' . $poster . '">
        <div class="video-thumb-bg">
            <span class="video-initiale">' . $initiale . '</span>
            <div class="video-play-btn">
                <i class="bi bi-play-circle-fill"></i>
            </div>
        </div>
        <div class="video-thumb-title">' . $title . '</div>
    </div>';
}

function emsp_video_player_placeholder(array $video, string $projectRoot): string
{
    $title = htmlspecialchars((string) ($video['title'] ?? 'Video EMSP'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $initiale = htmlspecialchars((string) ($video['initial'] ?? emsp_video_initial((string) ($video['title'] ?? 'Video EMSP'))), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $src = htmlspecialchars((string) ($video['embed'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $cacheKey = htmlspecialchars((string) ($video['cache_key'] ?? emsp_video_cache_key($video)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $poster = htmlspecialchars(emsp_video_poster_src($video, $projectRoot), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    return '
    <button type="button" class="video-player-local-card has-poster" data-local-player-trigger data-video-src="' . $src . '" data-video-title="' . $title . '" data-video-initial="' . $initiale . '" data-video-cache-key="' . $cacheKey . '" data-video-poster="' . $poster . '">
        <img class="video-player-poster" alt="' . $title . '" src="' . $poster . '">
        <span class="video-player-type">Video EMSP</span>
        <span class="video-player-initial">' . $initiale . '</span>
        <div class="video-player-play"><i class="bi bi-play-circle-fill"></i></div>
        <div class="video-player-title">' . $title . '</div>
    </button>';
}

function emsp_date_fr(string $dateStr): string
{
    if (!$dateStr) return '';
    $ts = strtotime($dateStr);
    if ($ts === false) return '';
    $mois = ['janvier', 'fevrier', 'mars', 'avril', 'mai', 'juin', 'juillet', 'aout', 'septembre', 'octobre', 'novembre', 'decembre'];
    return date('j', $ts) . ' ' . $mois[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
}

function emsp_mediatheque_fix_text(string $text): string
{
    return function_exists('emsp_fix_mojibake') ? emsp_fix_mojibake($text) : $text;
}

function emsp_mediatheque_normalize_category(string $text): string
{
    $text = str_replace(["\xC2\xA0", "\t"], ' ', $text);

    return trim($text);
}

/** @return list<array{id:int, title:string, src:string}> */
function emsp_mediatheque_fetch_album_photos(mysqli $con, string $category, int $categoryId = 0): array
{
    $cat = emsp_mediatheque_normalize_category($category);
    $photosAjax = [];
    $qAjax = mysqli_prepare(
        $con,
        "SELECT id, title, file_path
         FROM media
         WHERE type='image'
           AND is_public=1
           AND status='published'
           AND (
                ( (TRIM(REPLACE(REPLACE(category, CHAR(160), ' '), '\t', ' ')) = TRIM(REPLACE(REPLACE(?, CHAR(160), ' '), '\t', ' ')))
                  OR (category IS NULL AND TRIM(REPLACE(REPLACE(?, CHAR(160), ' '), '\t', ' ')) = '') )
                OR (? > 0 AND category_id = ?)
           )
         ORDER BY display_order DESC, created_at DESC
         LIMIT 100"
    );
    if ($qAjax) {
        mysqli_stmt_bind_param($qAjax, 'ssii', $cat, $cat, $categoryId, $categoryId);
        mysqli_stmt_execute($qAjax);
        $rAjax = emsp_stmt_fetch_all($qAjax);
        foreach ($rAjax as $row) {
            $photosAjax[] = [
                'id' => (int) $row['id'],
                'title' => emsp_mediatheque_fix_text((string) ($row['title'] ?? '')),
                'src' => emsp_media_src((string) $row['file_path']),
            ];
        }
        mysqli_stmt_close($qAjax);
    }

    return $photosAjax;
}

function emsp_mediatheque_album_ajax(mysqli $con, int $albumId, string $category, int $categoryIdHint = 0): array
{
    $passedCat = emsp_mediatheque_normalize_category($category);
    $categoryId = $categoryIdHint > 0 ? $categoryIdHint : 0;
    $resolvedCat = '';

    if ($albumId > 0) {
        $sCat = mysqli_prepare(
            $con,
            'SELECT m.category, m.category_id, mc.name AS category_name
             FROM media m
             LEFT JOIN media_categories mc ON mc.id = m.category_id
             WHERE m.id = ? LIMIT 1'
        );
        if (!$sCat) {
            $sCat = mysqli_prepare($con, 'SELECT category, category_id FROM media WHERE id = ? LIMIT 1');
        }
        if ($sCat) {
            mysqli_stmt_bind_param($sCat, 'i', $albumId);
            mysqli_stmt_execute($sCat);
            $rows = emsp_stmt_fetch_all($sCat);
            mysqli_stmt_close($sCat);
            if (!empty($rows[0])) {
                $row = $rows[0];
                if ($categoryId <= 0) {
                    $categoryId = (int) ($row['category_id'] ?? 0);
                }
                $nameFromTable = emsp_mediatheque_normalize_category((string) ($row['category_name'] ?? ''));
                $nameFromRow = emsp_mediatheque_normalize_category((string) ($row['category'] ?? ''));
                $resolvedCat = $nameFromTable !== '' ? $nameFromTable : $nameFromRow;
            }
        }
    }

    // La carte album et l'URL ?album= passent deja la bonne cle ; ne pas l'ecraser
    // avec une categorie vide ou obsolete resolue via cover_id.
    $cat = $passedCat !== '' ? $passedCat : $resolvedCat;

    $photosAjax = emsp_mediatheque_fetch_album_photos($con, $cat, $categoryId);

    if ($photosAjax === [] && $resolvedCat !== '' && $resolvedCat !== $cat) {
        $photosAjax = emsp_mediatheque_fetch_album_photos($con, $resolvedCat, $categoryId);
    }

    if ($photosAjax === [] && $categoryId > 0) {
        $photosAjax = emsp_mediatheque_fetch_album_photos($con, '', $categoryId);
    }

    return $photosAjax;
}

function emsp_mediatheque_build_view_model(mysqli $con, array $query, string $projectRoot): array
{
    $album_view = trim((string) ($query['album'] ?? ''));
    $album_view_label = emsp_mediatheque_fix_text($album_view);
    $page_album = 1;
    $per_page_albums = 0;
    $video_page = 1;
    $per_page_videos = 0;

    $total_albums = 0;
    $cnt = mysqli_query($con, "SELECT COUNT(*) AS total FROM (
         SELECT COALESCE(TRIM(REPLACE(REPLACE(category, CHAR(160), ' '), '\t', ' ')),'') AS cat
         FROM media
         WHERE type='image' AND is_public=1 AND status='published'
         GROUP BY COALESCE(TRIM(REPLACE(REPLACE(category, CHAR(160), ' '), '\t', ' ')),'')
     ) t");
    if ($cnt && $row = mysqli_fetch_assoc($cnt)) {
        $total_albums = (int) $row['total'];
    }

    $albums = [];
    $sAlbums = mysqli_prepare(
        $con,
        "SELECT
            COALESCE(TRIM(REPLACE(REPLACE(category, CHAR(160), ' '), '\t', ' ')),'') AS category,
            COUNT(*) AS nb_photos,
            MAX(created_at) AS last_date,
            MIN(id) AS cover_id,
            (SELECT m4.category_id FROM media m4
             WHERE COALESCE(TRIM(REPLACE(REPLACE(m4.category, CHAR(160), ' '), '\t', ' ')),'')
                   = COALESCE(TRIM(REPLACE(REPLACE(m.category, CHAR(160), ' '), '\t', ' ')),'')
               AND m4.type = 'image'
               AND m4.is_public = 1
               AND m4.status = 'published'
             ORDER BY m4.id ASC LIMIT 1) AS category_id,
            (SELECT file_path FROM media m2
             WHERE COALESCE(TRIM(REPLACE(REPLACE(m2.category, CHAR(160), ' '), '\t', ' ')),'')
                   = COALESCE(TRIM(REPLACE(REPLACE(m.category, CHAR(160), ' '), '\t', ' ')),'')
               AND m2.type = 'image'
               AND m2.is_public = 1
               AND m2.status = 'published'
             ORDER BY m2.created_at DESC LIMIT 1) AS cover_path
         FROM media m
         WHERE m.type = 'image'
           AND m.is_public = 1
           AND m.status = 'published'
         GROUP BY COALESCE(TRIM(REPLACE(REPLACE(category, CHAR(160), ' '), '\t', ' ')),'')
         ORDER BY last_date DESC"
    );
    if ($sAlbums) {
        mysqli_stmt_execute($sAlbums);
        $rAlbums = emsp_stmt_fetch_all($sAlbums);
        foreach ($rAlbums as $row) {
            $row['category_raw'] = (string) ($row['category'] ?? '');
            $row['category'] = emsp_mediatheque_fix_text($row['category_raw']);
            $row['cover_src'] = $row['cover_path'] ? emsp_existing_media_src((string) $row['cover_path'], $projectRoot) : 'assets/images/media-thumb-3.jpg';
            $row['category_label'] = $row['category'] !== '' ? $row['category'] : 'Sans titre';
            $albums[] = $row;
        }
        mysqli_stmt_close($sAlbums);
    }

    $album_photos = [];
    $album_total = 0;
    $album_page = 1;
    $per_page_photos = 0;
    if ($album_view !== '') {
        $sCount = mysqli_prepare(
            $con,
            "SELECT COUNT(*) FROM media
             WHERE ( (TRIM(REPLACE(REPLACE(category, CHAR(160), ' '), '\t', ' ')) = TRIM(REPLACE(REPLACE(?, CHAR(160), ' '), '\t', ' ')))
                     OR (category IS NULL AND TRIM(REPLACE(REPLACE(?, CHAR(160), ' '), '\t', ' ')) = '') )
               AND type='image' AND is_public=1 AND status='published'"
        );
        if ($sCount) {
            mysqli_stmt_bind_param($sCount, 'ss', $album_view, $album_view);
            mysqli_stmt_execute($sCount);
            mysqli_stmt_bind_result($sCount, $album_total);
            mysqli_stmt_fetch($sCount);
            mysqli_stmt_close($sCount);
        }

        $sPhotos = mysqli_prepare(
            $con,
            "SELECT id, title, description, file_path, created_at
             FROM media
             WHERE ( (TRIM(REPLACE(REPLACE(category, CHAR(160), ' '), '\t', ' ')) = TRIM(REPLACE(REPLACE(?, CHAR(160), ' '), '\t', ' ')))
                     OR (category IS NULL AND TRIM(REPLACE(REPLACE(?, CHAR(160), ' '), '\t', ' ')) = '') )
               AND type='image'
               AND is_public=1
               AND status='published'
             ORDER BY display_order DESC, created_at DESC"
        );
        if ($sPhotos) {
            mysqli_stmt_bind_param($sPhotos, 'ss', $album_view, $album_view);
            mysqli_stmt_execute($sPhotos);
            $rPhotos = emsp_stmt_fetch_all($sPhotos);
            foreach ($rPhotos as $row) {
                $row['title'] = emsp_mediatheque_fix_text((string) ($row['title'] ?? ''));
                $row['src'] = emsp_existing_media_src((string) $row['file_path'], $projectRoot);
                $album_photos[] = $row;
            }
            mysqli_stmt_close($sPhotos);
        }
    }

    $videos = [];
    $total_videos = 0;
    $sVidCount = mysqli_prepare(
        $con,
        "SELECT COUNT(*) FROM media
         WHERE type IN ('video','lien')
           AND is_public = 1
           AND status = 'published'"
    );
    if ($sVidCount) {
        mysqli_stmt_execute($sVidCount);
        mysqli_stmt_bind_result($sVidCount, $total_videos);
        mysqli_stmt_fetch($sVidCount);
        mysqli_stmt_close($sVidCount);
    }
    $sVid = mysqli_prepare(
        $con,
        "SELECT id, title, description, file_path, poster_path, type, category, created_at
         FROM media
         WHERE type IN ('video','lien')
           AND is_public = 1
           AND status = 'published'
         ORDER BY display_order DESC, created_at DESC"
    );
    if ($sVid) {
        mysqli_stmt_execute($sVid);
        $rVid = emsp_stmt_fetch_all($sVid);
        foreach ($rVid as $row) {
            $row['title'] = emsp_mediatheque_fix_text((string) ($row['title'] ?? ''));
            $row['description'] = emsp_mediatheque_fix_text((string) ($row['description'] ?? ''));
            $row['category'] = emsp_mediatheque_fix_text((string) ($row['category'] ?? ''));
            $src = emsp_media_src((string) $row['file_path']);
            $row['is_youtube'] = emsp_is_youtube($src);
            $row['embed'] = $row['is_youtube'] ? emsp_youtube_embed($src) : $src;
            $row['thumb'] = $row['is_youtube'] ? emsp_youtube_thumb($src) : '';
            $row['poster'] = '';
            if (!$row['is_youtube'] && !empty($row['poster_path'])) {
                $posterSrc = emsp_media_src((string) $row['poster_path']);
                $row['poster'] = emsp_local_asset_exists($posterSrc, $projectRoot) ? $posterSrc : '';
            }
            $row['initial'] = emsp_video_initial((string) $row['title']);
            $row['cache_key'] = emsp_video_cache_key($row);
            $videos[] = $row;
        }
        mysqli_stmt_close($sVid);
    }

    return [
        'album_view' => $album_view,
        'album_view_label' => $album_view_label,
        'page_album' => $page_album,
        'per_page_albums' => $per_page_albums,
        'video_page' => $video_page,
        'per_page_videos' => $per_page_videos,
        'total_albums' => $total_albums,
        'albums' => $albums,
        'album_photos' => $album_photos,
        'album_total' => $album_total,
        'album_page' => $album_page,
        'per_page_photos' => $per_page_photos,
        'videos' => $videos,
        'total_videos' => $total_videos,
        'page_title' => 'Espace Multimedia',
    ];
}
