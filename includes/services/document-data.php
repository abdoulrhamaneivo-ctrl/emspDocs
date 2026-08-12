<?php

function fmt_size(int $bytes): string
{
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' Mo';
    }
    if ($bytes >= 1024) {
        return round($bytes / 1024) . ' Ko';
    }
    return $bytes . ' o';
}

function time_ago(string $dt): string
{
    $ts = strtotime($dt);
    if (!$ts) {
        return '';
    }
    $diff = time() - $ts;
    if ($diff < 60) {
        return "A l'instant";
    }
    if ($diff < 3600) {
        return (int) ($diff / 60) . ' min';
    }
    if ($diff < 86400) {
        return (int) ($diff / 3600) . 'h';
    }
    if ($diff < 604800) {
        return (int) ($diff / 86400) . 'j';
    }
    return date('d/m/Y', $ts);
}

function emsp_strim(string $text, int $max, string $suffix = '...'): string
{
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($text, 0, $max, $suffix, 'UTF-8');
    }
    if (function_exists('mb_substr') && function_exists('mb_strlen')) {
        if (mb_strlen($text, 'UTF-8') > $max) {
            return mb_substr($text, 0, max(0, $max - strlen($suffix)), 'UTF-8') . $suffix;
        }
        return $text;
    }
    if (strlen($text) > $max) {
        return substr($text, 0, max(0, $max - strlen($suffix))) . $suffix;
    }
    return $text;
}

function emsp_document_load_base(mysqli $con, int $doc_id, array $session): array
{
    $isAuth = !empty($session['auth']);
    $uid = $isAuth ? (int) ($session['auth_user']['id'] ?? 0) : 0;
    $role = $isAuth ? (string) ($session['auth_role'] ?? ($session['auth_user']['role'] ?? 'etudiant')) : 'etudiant';
    $is_staff = in_array($role, ['admin', 'moderateur'], true);

    $stmt = mysqli_prepare($con,
        "SELECT d.*,
                u.first_name, u.last_name, u.badge_level, u.photo_path,
                f.name  AS filiere_name,
                l.name  AS licence_name,
                ma.name AS matiere_name,"
                . (emsp_pending_matiere_enabled($con) ? " d.matiere_label_pending," : " NULL AS matiere_label_pending,") . "
                ab.first_name AS approver_first, ab.last_name AS approver_last
         FROM documents d
         JOIN users u  ON u.id  = d.uploader_id
         LEFT JOIN filieres f  ON f.id  = d.filiere_id
         LEFT JOIN licences l  ON l.id  = d.licence_id
         LEFT JOIN matieres ma ON ma.id = d.matiere_id
         LEFT JOIN users ab    ON ab.id = d.approved_by
         WHERE d.id = ? LIMIT 1");
    if (!$stmt) {
        return ['found' => false];
    }
    mysqli_stmt_bind_param($stmt, 'i', $doc_id);
    mysqli_stmt_execute($stmt);
    $doc = emsp_stmt_fetch_assoc($stmt);
    mysqli_stmt_close($stmt);
    if (!$doc) {
        return ['found' => false];
    }

    $fix_fields = [
        'title', 'description', 'first_name', 'last_name', 'badge_level',
        'photo_path', 'filiere_name', 'licence_name',
        'matiere_name', 'approver_first', 'approver_last',
        'exam_session', 'rejection_reason', 'matiere_label_pending',
    ];
    foreach ($fix_fields as $f) {
        if (isset($doc[$f]) && is_string($doc[$f])) {
            $doc[$f] = emsp_fix_mojibake($doc[$f]);
        }
    }

    $docFiliereLabels = emsp_fetch_document_filiere_labels($con, [$doc_id]);
    $doc['filiere_labels'] = $docFiliereLabels[$doc_id] ?? [];
    $doc['filiere_label_display'] = !empty($doc['filiere_labels'])
        ? implode(', ', $doc['filiere_labels'])
        : trim((string) ($doc['filiere_name'] ?? ''));
    $doc['matiere_display'] = trim((string) ($doc['matiere_name'] ?? '')) !== ''
        ? trim((string) $doc['matiere_name'])
        : trim((string) ($doc['matiere_label_pending'] ?? ''));

    $is_owner = ($uid > 0 && $uid === intval($doc['uploader_id']));
    $is_public_document = ((int) ($doc['is_public'] ?? 0) === 1);
    $is_approved = ((string) ($doc['status'] ?? '') === 'approved');

    return [
        'found' => true,
        'doc' => $doc,
        'isAuth' => $isAuth,
        'uid' => $uid,
        'role' => $role,
        'is_staff' => $is_staff,
        'is_owner' => $is_owner,
        'is_public_document' => $is_public_document,
        'is_approved' => $is_approved,
    ];
}

function emsp_document_load_discussion(mysqli $con, int $doc_id, int $uid): array
{
    $cmt_s = mysqli_prepare($con,
        "SELECT c.id, c.content, c.created_at,
                u.first_name, u.last_name, u.badge_level, u.photo_path, u.id AS author_id
         FROM comments c
         JOIN users u ON u.id = c.user_id
         WHERE c.document_id=? AND c.status='visible'
         ORDER BY c.created_at ASC");
    $comments = [];
    if ($cmt_s) {
        mysqli_stmt_bind_param($cmt_s, 'i', $doc_id);
        mysqli_stmt_execute($cmt_s);
        $comments = emsp_stmt_fetch_all($cmt_s);
        mysqli_stmt_close($cmt_s);
    }
    foreach ($comments as &$c) {
        foreach (['first_name', 'last_name', 'content', 'photo_path'] as $f) {
            if (isset($c[$f]) && is_string($c[$f])) {
                $c[$f] = emsp_fix_mojibake($c[$f]);
            }
        }
    }
    unset($c);

    $rep_s = mysqli_prepare($con,
        "SELECT r.*, u.id AS author_id, u.first_name, u.last_name, u.badge_level, u.photo_path
         FROM comment_replies r
         JOIN users u ON u.id = r.user_id
         JOIN comments c ON c.id = r.comment_id
         WHERE c.document_id=? AND r.status='visible'
         ORDER BY r.created_at ASC");
    $replies = [];
    if ($rep_s) {
        mysqli_stmt_bind_param($rep_s, 'i', $doc_id);
        mysqli_stmt_execute($rep_s);
        $replies = emsp_stmt_fetch_all($rep_s);
        mysqli_stmt_close($rep_s);
    }
    foreach ($replies as &$r) {
        foreach (['first_name', 'last_name', 'content', 'photo_path'] as $f) {
            if (isset($r[$f]) && is_string($r[$f])) {
                $r[$f] = emsp_fix_mojibake($r[$f]);
            }
        }
    }
    unset($r);

    $replies_by_cmt = [];
    foreach ($replies as $r) {
        $cid = intval($r['comment_id'] ?? 0);
        if ($cid > 0) {
            $replies_by_cmt[$cid][] = $r;
        }
    }

    $reaction_labels = [
        'like' => 'Like',
        'love' => 'Love',
        'haha' => 'Haha',
        'wow' => 'Wow',
        'sad' => 'Sad',
        'angry' => 'Angry',
    ];
    $reaction_counts = [];
    $my_reactions = [];
    $comment_ids = [];
    foreach ($comments as $c) {
        $comment_ids[] = intval($c['id'] ?? 0);
    }
    $comment_ids = array_values(array_filter($comment_ids, function ($v) { return $v > 0; }));
    if (!empty($comment_ids)) {
        $ids = implode(',', $comment_ids);
        $qr = mysqli_query($con,
            "SELECT comment_id, reaction, COUNT(*) AS nb
             FROM comment_reactions
             WHERE comment_id IN ($ids)
             GROUP BY comment_id, reaction");
        if ($qr) {
            while ($row = mysqli_fetch_assoc($qr)) {
                $cid = intval($row['comment_id'] ?? 0);
                $rk = (string) ($row['reaction'] ?? '');
                if ($cid > 0 && isset($reaction_labels[$rk])) {
                    $reaction_counts[$cid][$rk] = intval($row['nb'] ?? 0);
                }
            }
        }
        if ($uid > 0) {
            $qr = mysqli_query($con,
                "SELECT comment_id, reaction
                 FROM comment_reactions
                 WHERE user_id=" . $uid . " AND comment_id IN ($ids)");
            if ($qr) {
                while ($row = mysqli_fetch_assoc($qr)) {
                    $cid = intval($row['comment_id'] ?? 0);
                    $rk = (string) ($row['reaction'] ?? '');
                    if ($cid > 0 && isset($reaction_labels[$rk])) {
                        $my_reactions[$cid] = $rk;
                    }
                }
            }
        }
    }

    return [
        'comments' => $comments,
        'cmt_count' => count($comments),
        'replies_by_cmt' => $replies_by_cmt,
        'reaction_labels' => $reaction_labels,
        'reaction_counts' => $reaction_counts,
        'my_reactions' => $my_reactions,
    ];
}

function emsp_document_build_view_data(array $doc, int $doc_id, bool $is_owner, bool $is_staff): array
{
    $badge_icons = [
        'or' => '&#x1F947;',
        'argent' => '&#x1F948;',
        'bronze' => '&#x1F949;',
        'none' => '',
    ];
    $type_labels = [
        'cours' => 'Cours',
        'td' => 'TD',
        'correction' => 'Correction',
        'concours' => 'Concours',
        'examen' => 'Examen',
    ];
    $type_colors = [
        'cours' => '#004D2A',
        'td' => '#16783a',
        'correction' => '#0d9488',
        'concours' => '#d97706',
        'examen' => '#C0392B',
    ];

    $tc = $type_colors[$doc['doc_type']] ?? '#6B6B6B';
    $tl = $type_labels[$doc['doc_type']] ?? ucfirst((string) ($doc['doc_type'] ?? ''));
    $doc_type_key = strtolower(trim((string) ($doc['doc_type'] ?? '')));
    if (!array_key_exists($doc_type_key, $type_labels)) {
        $doc_type_key = 'other';
    }

    $mime = strtolower((string) ($doc['mime_type'] ?? ''));
    $ext = strtolower(pathinfo((string) ($doc['file_path'] ?? ''), PATHINFO_EXTENSION));
    $is_pdf = ($mime === 'application/pdf');
    $is_image = (str_starts_with($mime, 'image/') || in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true));
    $is_docx = ($mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' || $ext === 'docx');
    $is_xlsx = ($mime === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' || $ext === 'xlsx');
    $is_text = (
        str_starts_with($mime, 'text/')
        || in_array($ext, ['txt', 'csv', 'md', 'log', 'json', 'xml'], true)
        || in_array($mime, ['application/json', 'application/xml'], true)
    );
    $can_view = ($doc['status'] === 'approved' || $is_owner || $is_staff);

    $hero_summary = trim((string) ($doc['description'] ?? ''));
    if ($hero_summary === '') {
        $hero_bits = array_values(array_filter([
            trim((string) ($doc['filiere_label_display'] ?? '')),
            trim((string) ($doc['licence_name'] ?? '')),
            trim((string) ($doc['matiere_display'] ?? '')),
        ], static function ($value): bool {
            return $value !== '';
        }));
        if (!empty($hero_bits)) {
            $hero_summary = 'Ressource academique partagee par la communaute EMSP pour ' . implode(' - ', $hero_bits) . '.';
        } else {
            $hero_summary = 'Ressource academique partagee sur EMSP Docs.';
        }
    }
    $hero_summary = trim((string) preg_replace('/\s+/', ' ', $hero_summary));
    $hero_summary = emsp_strim($hero_summary, 170, '...');

    $doc_icon = 'bi-file-earmark-text';
    $doc_icon_color = '#004D2A';
    $doc_icon_kind = 'default';
    if ($is_pdf) {
        $doc_icon = 'bi-file-earmark-pdf-fill';
        $doc_icon_color = '#C0392B';
        $doc_icon_kind = 'pdf';
    } elseif ($is_image) {
        $doc_icon = 'bi-image-fill';
        $doc_icon_color = '#D4900A';
        $doc_icon_kind = 'image';
    } elseif ($is_docx) {
        $doc_icon = 'bi-file-earmark-word-fill';
        $doc_icon_color = '#004D2A';
        $doc_icon_kind = 'word';
    } elseif ($is_xlsx) {
        $doc_icon = 'bi-file-earmark-excel-fill';
        $doc_icon_color = '#006B3C';
        $doc_icon_kind = 'excel';
    } elseif ($is_text) {
        $doc_icon = 'bi-file-earmark-text-fill';
        $doc_icon_color = '#006B3C';
        $doc_icon_kind = 'text';
    }

    $status_label = '';
    $status_color = '#6B6B6B';
    $status_key = 'other';
    if (($doc['status'] ?? '') === 'approved') {
        $status_label = 'Approuve';
        $status_color = '#006B3C';
        $status_key = 'approved';
    } elseif (($doc['status'] ?? '') === 'pending') {
        $status_label = 'En attente';
        $status_color = '#D4900A';
        $status_key = 'pending';
    } elseif (($doc['status'] ?? '') === 'rejected') {
        $status_label = 'Rejete';
        $status_color = '#C0392B';
        $status_key = 'rejected';
    }

    $preview_label = 'Document';
    if ($is_pdf) {
        $preview_label = 'Apercu PDF';
    } elseif ($is_image) {
        $preview_label = 'Apercu image';
    } elseif ($is_docx) {
        $preview_label = 'Apercu DOCX';
    } elseif ($is_xlsx) {
        $preview_label = 'Apercu Excel';
    } elseif ($is_text) {
        $preview_label = 'Apercu texte';
    }

    $share_url = '';
    if (defined('APP_URL') && trim((string) APP_URL) !== '') {
        $share_url = rtrim((string) APP_URL, '/') . '/document.php?id=' . $doc_id;
    } else {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if ($host !== '') {
            $share_url = $scheme . '://' . $host . '/document.php?id=' . $doc_id;
        } else {
            $share_url = 'document.php?id=' . $doc_id;
        }
    }

    return [
        'badge_icons' => $badge_icons,
        'type_labels' => $type_labels,
        'type_colors' => $type_colors,
        'tc' => $tc,
        'tl' => $tl,
        'doc_type_key' => $doc_type_key,
        'mime' => $mime,
        'ext' => $ext,
        'is_pdf' => $is_pdf,
        'is_image' => $is_image,
        'is_docx' => $is_docx,
        'is_xlsx' => $is_xlsx,
        'is_text' => $is_text,
        'can_view' => $can_view,
        'hero_summary' => $hero_summary,
        'doc_icon' => $doc_icon,
        'doc_icon_color' => $doc_icon_color,
        'doc_icon_kind' => $doc_icon_kind,
        'status_label' => $status_label,
        'status_color' => $status_color,
        'status_key' => $status_key,
        'preview_label' => $preview_label,
        'share_url' => $share_url,
    ];
}
