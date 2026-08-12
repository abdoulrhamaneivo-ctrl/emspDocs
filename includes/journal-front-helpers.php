<?php
include_once __DIR__ . '/content-helpers.php';

if (!function_exists('emsp_clean_journal_html')) {
    function emsp_clean_journal_html(string $html): string
    {
        $html = emsp_fix_mojibake($html);
        $decoded = htmlspecialchars_decode($html, ENT_QUOTES);
        $decoded = str_replace(
            ['../uploads/', '..\\uploads\\', '../assets/', '..\\assets\\'],
            ['uploads/', 'uploads/', 'assets/', 'assets/'],
            $decoded
        );
        $allowed = '<p><br><h1><h2><h3><h4><h5><h6><ul><ol><li><strong><em><b><i><u><a><img><blockquote><pre><code><table><thead><tbody><tr><th><td><div><span><figure><figcaption><hr><sup><sub><small><mark><del><ins>';
        $clean = strip_tags($decoded, $allowed);
        $clean = preg_replace_callback('~<a\b[^>]*>~i', function ($m) {
            $tag = $m[0];
            if (preg_match('/\bhref\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $tag, $hm)) {
                $href = $hm[1] !== '' ? $hm[1] : ($hm[2] !== '' ? $hm[2] : ($hm[3] ?? ''));
                $href = html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if (preg_match('/^\s*(javascript:|data:)/i', $href)) {
                    $tag = preg_replace('/\s*href\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $tag);
                }
            }
            return $tag;
        }, $clean);
        $clean = preg_replace_callback('~<img\b[^>]*>~i', function ($m) {
            $tag = $m[0];
            if (preg_match('/\bsrc\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $tag, $hm)) {
                $src = $hm[1] !== '' ? $hm[1] : ($hm[2] !== '' ? $hm[2] : ($hm[3] ?? ''));
                $src = html_entity_decode($src, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if (preg_match('/^\s*data:/i', $src)) {
                    if (strlen($src) > (1024 * 100)) {
                        $tag = preg_replace('/\s*src\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $tag);
                    }
                }
            }
            return $tag;
        }, $clean);
        return $clean;
    }
}

if (!function_exists('emsp_relative_date')) {
    function emsp_relative_date(?string $date): string
    {
        if (!$date) {
            return '';
        }
        try {
            $dt = new DateTimeImmutable($date, new DateTimeZone('UTC'));
        } catch (Exception $e) {
            return '';
        }
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $diff = $now->getTimestamp() - $dt->getTimestamp();
        if ($diff < 60) return 'il y a moins d\'une minute';
        if ($diff < 3600) return 'il y a ' . intval($diff / 60) . ' min';
        if ($diff < 86400) return 'il y a ' . intval($diff / 3600) . ' h';
        if ($diff < 7 * 86400) return 'il y a ' . intval($diff / 86400) . ' jours';
        return 'le ' . $dt->format('d/m/Y');
    }
}

if (!function_exists('emsp_newsblog_excerpt')) {
    function emsp_newsblog_excerpt(string $text, int $max = 160): string
    {
        $clean = trim(strip_tags(emsp_fix_mojibake($text)));
        if (mb_strlen($clean) <= $max) return $clean;
        return rtrim(mb_substr($clean, 0, $max - 1)) . '...';
    }
}

if (!function_exists('emsp_stmt_bind_params')) {
    function emsp_stmt_bind_params(mysqli_stmt $stmt, string $types, array $values): void
    {
        $bind = [];
        $bind[] = $types;
        foreach ($values as $k => $val) {
            $bind[] = &$values[$k];
        }
        mysqli_stmt_bind_param($stmt, ...$bind);
    }
}

if (!function_exists('emsp_newsblog_journal_type_meta')) {
    function emsp_newsblog_journal_type_meta(string $type): array
    {
        $map = [
            'annonce' => ['label' => 'Annonce', 'color' => '#004D2A', 'class' => 'journal-card-annonce'],
            'defi' => ['label' => 'Défi', 'color' => '#D4900A', 'class' => 'journal-card-defi'],
            'sondage' => ['label' => 'Sondage', 'color' => '#006B3C', 'class' => 'journal-card-sondage'],
        ];

        $type = strtolower(trim($type));
        return $map[$type] ?? ['label' => ucfirst($type !== '' ? $type : 'Article'), 'color' => '#004D2A', 'class' => 'journal-card-annonce'];
    }
}

if (!function_exists('emsp_newsblog_type_key')) {
    function emsp_newsblog_type_key(string $type): string
    {
        $type = strtolower(trim($type));
        if (!in_array($type, ['annonce', 'defi', 'sondage'], true)) {
            return 'annonce';
        }
        return $type;
    }
}

if (!function_exists('emsp_newsblog_journal_cover_src')) {
    function emsp_newsblog_journal_cover_src(string $html): string
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

if (!function_exists('emsp_newsblog_journal_cover_html')) {
    function emsp_newsblog_journal_cover_html(array $item, string $variant = 'feature'): string
    {
        $title = trim((string) ($item['title'] ?? 'Article'));
        $meta = emsp_newsblog_journal_type_meta((string) ($item['type'] ?? ''));
        $typeKey = emsp_newsblog_type_key((string) ($item['type'] ?? ''));
        $cover = emsp_newsblog_journal_cover_src((string) ($item['content'] ?? ''));
        $classes = 'journal-cover journal-cover-' . $variant;

        if ($cover !== '') {
            return '<div class="' . $classes . '">'
                . '<img src="' . htmlspecialchars($cover) . '" alt="' . htmlspecialchars($title) . '" class="journal-cover-img">'
                . '</div>';
        }

        $seed = $title !== '' ? $title : $meta['label'];
        $initial = mb_strtoupper(mb_substr($seed, 0, 1, 'UTF-8'), 'UTF-8');

        return '<div class="' . $classes . ' journal-cover-placeholder journal-cover-' . htmlspecialchars($typeKey, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '">'
            . '<span class="journal-cover-type">' . htmlspecialchars($meta['label']) . '</span>'
            . '<span class="journal-cover-initial">' . htmlspecialchars($initial) . '</span>'
            . '</div>';
    }
}
