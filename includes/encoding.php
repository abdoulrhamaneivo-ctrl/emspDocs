<?php

if (!function_exists('mb_strlen')) {
    function mb_strlen($string, $encoding = null)
    {
        return strlen($string);
    }
}

if (!function_exists('mb_substr')) {
    function mb_substr($string, $start, $length = null, $encoding = null)
    {
        return $length === null ? substr($string, $start) : substr($string, $start, $length);
    }
}

if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper($string, $encoding = null)
    {
        return strtoupper($string);
    }
}

if (!function_exists('mb_strtolower')) {
    function mb_strtolower($string, $encoding = null)
    {
        return strtolower($string);
    }
}

if (!function_exists('emsp_mojibake_score')) {
    function emsp_mojibake_score(string $text): int
    {
        if ($text === '') {
            return 0;
        }

        if (preg_match('//u', $text) !== 1) {
            return 100;
        }

        $score = 0;

        foreach (['à', 'Â', '"', '"', 'ï¿½', '�'] as $needle ){
            if (strpos($text, $needle) !== false) {
                $score += 4 * substr_count($text, $needle);
            }
        }

        if (strpos($text, 'ïÂ¿Â½') !== false) {
            $score += 10 * substr_count($text, 'ïÂ¿Â½');
        }
        if (strpos($text, 'àƒÂ¯à‚Â¿à‚Â½') !== false) {
            $score += 10 * substr_count($text, 'àƒÂ¯à‚Â¿à‚Â½');
        }

        foreach (['àƒÆ’', 'àƒ"š', 'àƒ', 'à‚'] as $prefix) {
            $pattern = '/' . preg_quote($prefix, '/') . '[\x{0080}-\x{00BF}]/u';
            if (preg_match_all($pattern, $text, $matches)) {
                $score += count($matches[0]);
            }
        }

        foreach (['àƒÂ¢â"šÂ¬â"žÂ¢', 'àƒÂ¢â"šÂ¬à…"', 'àƒÂ¢â"šÂ¬à‚Â', 'àƒÂ¢â"šÂ¬ââ‚¬Å“', 'àƒÂ¢â"šÂ¬ââ‚¬Â', 'àƒÂ¢â"šÂ¬à‚Â¦', 'àƒÂ¢â"šÂ¬à‚Â¢', 'àƒÂ¢ââ‚¬Å¾à‚Â¢'] as $needle) {
            if (strpos($text, $needle) !== false) {
                $score += 2 * substr_count($text, $needle);
            }
        }

        if (strpos($text, 'àƒÆ’') !== false) {
            $score += substr_count($text, 'àƒÆ’');
        }
        if (strpos($text, 'àƒ"š') !== false) {
            $score += substr_count($text, 'àƒ"š');
        }

        if (preg_match_all('/(?:\x{00C3}[\x{0080}-\x{00FF}]|\x{00C2}[\x{00A0}-\x{00FF}]|\x{00E2}[\x{0080}-\x{00BF}]|\x{FFFD})/u', $text, $markers)) {
            $score += 2 * count($markers[0]);
        }

        return $score;
    }
}

if (!function_exists('emsp_fix_mojibake')) {
    /**
     * Shared encoding repair helper used across front/admin rendering.
     * Keep it outside dbcon.php so layout helpers and tests can rely on one source of truth.
     */
    function emsp_fix_mojibake(string $text): string
    {
        if ($text === '') {
            return $text;
        }

        $text = str_replace("\xEF\xBB\xBF", '', $text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text) ?? $text;

        $best = $text;
        $bestScore = emsp_mojibake_score($best);
        if ($bestScore === 0) {
            if (preg_match('/(?:\x{00C3}[\x{0080}-\x{00FF}]|\x{00C2}[\x{00A0}-\x{00FF}]|\x{00E2}[\x{0080}-\x{00BF}]|\x{FFFD})/u', $best) === 1) {
                $bestScore = 1;
            } else {
                return $best;
            }
        }

        for ($pass = 0; $pass < 4 && $bestScore > 0; $pass++) {
            $isValidUtf8 = (preg_match('//u', $best) === 1);
            $candidates = [];

            if ($isValidUtf8) {
                if (function_exists('mb_convert_encoding')) {
                    foreach (['Windows-1252', 'ISO-8859-1'] as $encoding) {
                        $candidate = @mb_convert_encoding($best, $encoding, 'UTF-8');
                        if ($candidate !== false && $candidate !== '') {
                            $candidates[] = $candidate;
                        }
                    }
                } elseif (function_exists('iconv')) {
                    foreach (['Windows-1252', 'ISO-8859-1'] as $encoding) {
                        $candidate = @iconv('UTF-8', $encoding . '//IGNORE', $best);
                        if ($candidate !== false && $candidate !== '') {
                            $candidates[] = $candidate;
                        }
                    }
                } else {
                    break;
                }
            } else {
                if (function_exists('mb_convert_encoding')) {
                    $c1 = @mb_convert_encoding($best, 'UTF-8', 'Windows-1252');
                    if ($c1 !== false && $c1 !== '') {
                        $candidates[] = $c1;
                    }
                    $c2 = @mb_convert_encoding($best, 'UTF-8', 'ISO-8859-1');
                    if ($c2 !== false && $c2 !== '') {
                        $candidates[] = $c2;
                    }
                } elseif (function_exists('iconv')) {
                    $c1 = @iconv('Windows-1252', 'UTF-8//IGNORE', $best);
                    if ($c1 !== false && $c1 !== '') {
                        $candidates[] = $c1;
                    }
                    $c2 = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $best);
                    if ($c2 !== false && $c2 !== '') {
                        $candidates[] = $c2;
                    }
                } else {
                    break;
                }
            }

            $improved = false;
            foreach ($candidates as $candidate) {
                if ($candidate === $best || preg_match('//u', $candidate) !== 1) {
                    continue;
                }

                $score = emsp_mojibake_score($candidate);
                if ($score < $bestScore) {
                    $best = $candidate;
                    $bestScore = $score;
                    $improved = true;
                    if ($bestScore === 0) {
                        break;
                    }
                }
            }

            if (!$improved) {
                break;
            }
        }

        $literalFixes = [
            'Ã©' => 'e',
            'Ã¨' => 'e',
            'Ãª' => 'e',
            'Ã«' => 'e',
            'Ã ' => 'a',
            'Ã¢' => 'a',
            'Ã®' => 'i',
            'Ã¯' => 'i',
            'Ã´' => 'o',
            'Ã¶' => 'o',
            'Ã¹' => 'u',
            'Ã»' => 'u',
            'Ã¼' => 'u',
            'Ã§' => 'c',
            'Ã‰' => 'E',
            'Ã€' => 'A',
            'Ã‡' => 'C',
            'â€™' => "'",
            'â€˜' => "'",
            'â€œ' => '"',
            'â€' => '"',
            'â€“' => '-',
            'â€”' => '-',
            'â€¦' => '...',
            'Â ' => ' ',
            'Â«' => '"',
            'Â»' => '"',
        ];

        $best = strtr($best, [
            'Ã©' => 'é',
            'Ã¨' => 'è',
            'Ãª' => 'ê',
            'Ã«' => 'ë',
            'Ã ' => 'à',
            'Ã¢' => 'â',
            'Ã®' => 'î',
            'Ã¯' => 'ï',
            'Ã´' => 'ô',
            'Ã¶' => 'ö',
            'Ã¹' => 'ù',
            'Ã»' => 'û',
            'Ã¼' => 'ü',
            'Ã§' => 'ç',
            'Ã‰' => 'É',
            'Ãˆ' => 'È',
            'ÃŠ' => 'Ê',
            'Ã‹' => 'Ë',
            'Ã€' => 'À',
            'Ã‚' => 'Â',
            'ÃŽ' => 'Î',
            'Ã�' => 'Ï',
            'Ã”' => 'Ô',
            'Ã–' => 'Ö',
            'Ã™' => 'Ù',
            'Ã›' => 'Û',
            'Ãœ' => 'Ü',
            'Ã‡' => 'Ç',
            'â€™' => "'",
            'â€˜' => "'",
            'â€œ' => '"',
            'â€' => '"',
            'â€“' => '-',
            'â€”' => '-',
            'â€¦' => '...',
            'Â·' => '·',
            'Â ' => ' ',
            'Â«' => '"',
            'Â»' => '"',
        ]);

        $best = str_replace([
            hex2bin('c383c2a9'),
            hex2bin('c383c2a8'),
            hex2bin('c383c2aa'),
            hex2bin('c383c2ab'),
            hex2bin('c383c2a0'),
            hex2bin('c383c2a2'),
            hex2bin('c383c2ae'),
            hex2bin('c383c2af'),
            hex2bin('c383c2b4'),
            hex2bin('c383c2b6'),
            hex2bin('c383c2b9'),
            hex2bin('c383c2bb'),
            hex2bin('c383c2bc'),
            hex2bin('c383c2a7'),
            hex2bin('c3a2e282ace284a2'),
            hex2bin('c3a2e282ace28098'),
            hex2bin('c3a2e282ace593'),
            hex2bin('c3a2e282acc29d'),
            hex2bin('c382c2b7'),
            hex2bin('c38220'),
        ], [
            '&#233;',
            '&#232;',
            '&#234;',
            '&#235;',
            '&#224;',
            '&#226;',
            '&#238;',
            '&#239;',
            '&#244;',
            '&#246;',
            '&#249;',
            '&#251;',
            '&#252;',
            '&#231;',
            "'",
            "'",
            '"',
            '"',
            ' - ',
            ' ',
        ], $best);

        return strtr($best, $literalFixes);
    }
}
