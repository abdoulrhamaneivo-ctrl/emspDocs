<?php
include_once __DIR__ . '/helpers.php';
include_once __DIR__ . '/content-helpers.php';

if (!function_exists('emsp_formations_editorial_columns_present')) {
    function emsp_formations_editorial_columns_present(mysqli $con): bool
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }

        $required = ['summary', 'description_html', 'cover_image_path'];
        foreach ($required as $column) {
            $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
            $result = @mysqli_query($con, "SHOW COLUMNS FROM `filieres` LIKE '" . mysqli_real_escape_string($con, $safe) . "'");
            if (!$result || mysqli_num_rows($result) === 0) {
                if ($result instanceof mysqli_result) {
                    mysqli_free_result($result);
                }
                $ready = false;
                return false;
            }
            mysqli_free_result($result);
        }

        $ready = true;
        return true;
    }
}

if (!function_exists('emsp_formation_image_src')) {
    function emsp_formation_image_src(?string $path): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        if (emsp_is_external_url($path)) {
            return $path;
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');
        $src = (strpos($path, 'assets/') === 0 || strpos($path, 'uploads/') === 0)
            ? $path
            : 'uploads/formations/' . basename($path);

        $localPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $src);

        return is_file($localPath) ? $src : '';
    }
}

if (!function_exists('emsp_formation_summary')) {
    function emsp_formation_summary(array $row, int $max = 180): string
    {
        $summary = trim((string) ($row['summary'] ?? ''));
        if ($summary !== '') {
            return function_exists('emsp_fix_mojibake') ? emsp_fix_mojibake($summary) : $summary;
        }

        $description = trim((string) ($row['description_html'] ?? ''));
        if ($description !== '') {
            return function_exists('emsp_excerpt') ? emsp_excerpt($description, $max) : trim(strip_tags($description));
        }

        return 'Découvre les ressources liées à cette filière dans la bibliothèque EMSP.';
    }
}

if (!function_exists('emsp_formation_has_details')) {
    function emsp_formation_has_details(array $row): bool
    {
        return trim((string) ($row['description_html'] ?? '')) !== '';
    }
}

if (!function_exists('emsp_fs_menum_program_slug')) {
    function emsp_fs_menum_program_slug(): string
    {
        return 'fs-menum';
    }
}

if (!function_exists('emsp_fs_menum_program_meta')) {
    /** @return array{slug: string, short: string, title: string, summary: string} */
    function emsp_fs_menum_program_meta(): array
    {
        return [
            'slug' => emsp_fs_menum_program_slug(),
            'short' => 'FS-MENUM',
            'title' => 'Formation Supérieure en Management de l\'Économie Numérique',
            'summary' => 'Programme de l\'École de la Poste (EMSP) regroupant plusieurs filières de spécialisation dans le numérique et le management.',
        ];
    }
}

if (!function_exists('emsp_formations_program_column_present')) {
    function emsp_formations_program_column_present(mysqli $con): bool
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }

        $result = @mysqli_query($con, "SHOW COLUMNS FROM `filieres` LIKE 'formation_program'");
        if (!$result || mysqli_num_rows($result) === 0) {
            if ($result instanceof mysqli_result) {
                mysqli_free_result($result);
            }
            $ready = false;
            return false;
        }
        mysqli_free_result($result);
        $ready = true;
        return true;
    }
}

if (!function_exists('emsp_normalize_program_token')) {
    function emsp_normalize_program_token(string $value): string
    {
        $value = mb_strtoupper(trim(function_exists('emsp_fix_mojibake') ? emsp_fix_mojibake($value) : $value));
        $value = preg_replace('/[\s\-_\.]+/u', '', $value);
        return (string) $value;
    }
}

if (!function_exists('emsp_filiere_is_fs_menum_program_entry')) {
    /**
     * Détecte une entrée filière créée par erreur pour le programme FS-MENUM lui-même.
     */
    function emsp_filiere_is_fs_menum_program_entry(array $row): bool
    {
        foreach (['name', 'code'] as $field) {
            $token = emsp_normalize_program_token((string) ($row[$field] ?? ''));
            if ($token === 'FSMENUM') {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('emsp_filiere_belongs_to_fs_menum')) {
    function emsp_filiere_belongs_to_fs_menum(array $row): bool
    {
        if (emsp_filiere_is_fs_menum_program_entry($row)) {
            return false;
        }

        $program = strtolower(trim((string) ($row['formation_program'] ?? '')));
        if ($program === emsp_fs_menum_program_slug()) {
            return true;
        }

        return false;
    }
}

if (!function_exists('emsp_partition_filieres_by_program')) {
    /**
     * @param array<int, array<string, mixed>> $filieres
     * @return array{fsMenum: array<int, array<string, mixed>>, other: array<int, array<string, mixed>>}
     */
    function emsp_partition_filieres_by_program(array $filieres): array
    {
        $fsMenum = [];
        $other = [];

        foreach ($filieres as $filiere) {
            if (emsp_filiere_is_fs_menum_program_entry($filiere)) {
                continue;
            }
            if (emsp_filiere_belongs_to_fs_menum($filiere)) {
                $fsMenum[] = $filiere;
            } else {
                $other[] = $filiere;
            }
        }

        $sortByName = static function (array $a, array $b): int {
            return strcasecmp(
                (string) ($a['name'] ?? ''),
                (string) ($b['name'] ?? '')
            );
        };
        usort($fsMenum, $sortByName);
        usort($other, $sortByName);

        return ['fsMenum' => $fsMenum, 'other' => $other];
    }
}
