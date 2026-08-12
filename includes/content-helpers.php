<?php
include_once __DIR__ . '/helpers.php';


if (!function_exists('emsp_sanitize_rich_html')) {
    function emsp_sanitize_rich_html(string $html): string
    {
        $html = function_exists('emsp_fix_mojibake') ? emsp_fix_mojibake($html) : $html;
        $decoded = htmlspecialchars_decode($html, ENT_QUOTES | ENT_HTML5);

        $allowedTags = [
            'p','br','h1','h2','h3','h4','h5','h6','ul','ol','li','strong','em','b','i','u',
            'a','img','div','span','figure','figcaption','blockquote','hr','pre','code',
            'table','thead','tbody','tr','th','td','sup','sub','small','mark','del','ins'
        ];

        if (!class_exists('DOMDocument')) {
            $clean = strip_tags($decoded, '<p><br><h1><h2><h3><h4><h5><h6><ul><ol><li><strong><em><b><i><u><a><img><div><span><figure><figcaption><blockquote><hr><pre><code><table><thead><tbody><tr><th><td><sup><sub><small><mark><del><ins>');
            $clean = preg_replace('/<([a-z0-9]+)\b[^>]*>/i', '<$1>', $clean) ?? $clean;
            return $clean;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $wrapped = '<?xml encoding="utf-8" ?><div id="emsp-safe-root">' . $decoded . '</div>';
        $loaded = $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (!$loaded) {
            return '';
        }

        $root = $dom->getElementById('emsp-safe-root');
        if (!$root) {
            return '';
        }

        $sanitize = function (DOMNode $node) use (&$sanitize, $allowedTags): void {
            $children = [];
            foreach ($node->childNodes as $child) {
                $children[] = $child;
            }
            foreach ($children as $child) {
                $sanitize($child);
            }

            if ($node->nodeType !== XML_ELEMENT_NODE) {
                return;
            }

            $el = $node;
            $tag = strtolower($el->tagName);
            if (!in_array($tag, $allowedTags, true)) {
                $parent = $el->parentNode;
                if ($parent) {
                    while ($el->firstChild) {
                        $parent->insertBefore($el->firstChild, $el);
                    }
                    $parent->removeChild($el);
                }
                return;
            }

            $allowedAttrs = ['class','style','title'];
            if ($tag === 'a') {
                $allowedAttrs = ['class','style','title','href','target','rel'];
            } elseif ($tag === 'img') {
                $allowedAttrs = ['class','style','title','src','alt','width','height'];
            }

            $attrs = [];
            foreach ($el->attributes as $attr) {
                $attrs[] = strtolower($attr->name);
            }
            foreach ($attrs as $name) {
                $value = $el->getAttribute($name);
                if (!in_array($name, $allowedAttrs, true) || str_starts_with($name, 'on')) {
                    $el->removeAttribute($name);
                    continue;
                }

                if ($name === 'href') {
                    $v = trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    if ($v === '' || preg_match('/^(?:javascript|data|vbscript|file):/i', $v)) {
                        $el->removeAttribute($name);
                        continue;
                    }
                    if (preg_match('/^https?:\/\//i', $v) || str_starts_with($v, '//')) {
                        $el->setAttribute('rel', 'noopener noreferrer');
                    }
                    $el->setAttribute($name, $v);
                    continue;
                }

                if ($name === 'src') {
                    $v = trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    $safe = preg_match('#^https?://#i', $v)
                        || str_starts_with(ltrim(str_replace('\\','/',$v), '/'), 'uploads/')
                        || str_starts_with(ltrim(str_replace('\\','/',$v), '/'), 'assets/');
                    if (!$safe || preg_match('/^(?:javascript|data|vbscript):/i', $v)) {
                        $parent = $el->parentNode;
                        if ($parent) { $parent->removeChild($el); }
                        return;
                    }
                    $el->setAttribute($name, $v);
                    continue;
                }

                if ($name === 'style') {
                    $v = trim($value);
                    if ($v === '' || preg_match('/(?:expression\s*\(|javascript\s*:|vbscript\s*:|url\s*\(|@import|behavior\s*:|-moz-binding)/i', $v)) {
                        $el->removeAttribute($name);
                    } else {
                        $el->setAttribute($name, $v);
                    }
                }
            }
        };

        $sanitize($root);
        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }
        return $out;
    }
}

if (!function_exists('emsp_excerpt')) {
    function emsp_excerpt(?string $text, int $max = 140): string
    {
        $clean = trim(strip_tags((string) $text));
        if ($clean === '') {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($clean, 'UTF-8') <= $max) {
                return $clean;
            }
            return rtrim(mb_substr($clean, 0, $max - 1, 'UTF-8')) . '...';
        }

        if (strlen($clean) <= $max) {
            return $clean;
        }
        return rtrim(substr($clean, 0, $max - 1)) . '...';
    }
}

if (!function_exists('emsp_format_date')) {
    function emsp_format_date(?string $date): string
    {
        if (empty($date)) {
            return '';
        }
        $ts = strtotime($date);
        if ($ts === false) {
            return '';
        }
        return date('d/m/Y', $ts);
    }
}

if (!function_exists('emsp_youtube_title')) {
    function emsp_youtube_title(string $url): string
    {
        $url = trim($url);
        if ($url === '' || !preg_match('#(?:youtube\.com|youtu\.be)#i', $url)) {
            return '';
        }

        $api = 'https://www.youtube.com/oembed?url=' . urlencode($url) . '&format=json';
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 3,
                'ignore_errors' => true,
                'user_agent' => 'EMSPDocs/1.0',
            ],
        ]);

        $json = @file_get_contents($api, false, $ctx);
        if (!is_string($json) || trim($json) === '') {
            return '';
        }

        $data = json_decode($json, true);
        $title = trim((string) ($data['title'] ?? ''));

        return function_exists('emsp_fix_mojibake') ? emsp_fix_mojibake($title) : $title;
    }
}


