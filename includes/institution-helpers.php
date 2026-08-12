<?php
include_once __DIR__ . '/content-helpers.php';

if (!function_exists('emsp_institution_fix_text')) {
    function emsp_institution_fix_text(string $text): string
    {
        return function_exists('emsp_fix_mojibake') ? emsp_fix_mojibake($text) : $text;
    }
}

if (!function_exists('emsp_institution_media_src')) {
    function emsp_institution_media_src(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if (emsp_is_external_url($path) || str_starts_with($path, 'data:') || str_starts_with($path, 'blob:')) {
            return $path;
        }

        $src = ltrim(str_replace('\\', '/', $path), '/');
        $localPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $src);

        return is_file($localPath) ? $src : '';
    }
}

if (!function_exists('emsp_sanitize_html')) {
    function emsp_sanitize_html(string $html): string
    {
        return function_exists('emsp_sanitize_rich_html')
            ? emsp_sanitize_rich_html($html)
            : htmlspecialchars((string) $html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('emsp_section_text')) {
    function emsp_section_text(array $rows): string
    {
        $parts = [];
        foreach ($rows as $row) {
            $val = trim(emsp_institution_fix_text((string) ($row['valeur'] ?? '')));
            if ($val !== '') {
                $parts[] = $val;
            }
        }
        return implode("\n\n", $parts);
    }
}

if (!function_exists('emsp_value_by_keys')) {
    function emsp_value_by_keys(array $byKey, array $keys): string
    {
        foreach ($keys as $k) {
            if (!isset($byKey[$k])) {
                continue;
            }
            $val = trim(emsp_institution_fix_text((string) ($byKey[$k]['valeur'] ?? '')));
            if ($val !== '') {
                return $val;
            }
        }
        return '';
    }
}
