<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseHelper;
use App\Core\LegacyDb;

final class FormationsController extends Controller
{
    public function index(): void
    {
        DatabaseHelper::ensureFiliereEditorialColumns(Database::pdo());
        $con = LegacyDb::formationsHelpers();

        $editorialEnabled = emsp_formations_editorial_columns_present($con);
        $programColumnEnabled = emsp_formations_program_column_present($con);

        $columns = ['id', 'name'];
        if ($editorialEnabled) {
            $columns = array_merge($columns, ['summary', 'description_html', 'cover_image_path']);
        }
        if ($programColumnEnabled) {
            $columns[] = 'formation_program';
        }

        $query = 'SELECT ' . implode(', ', $columns) . " FROM filieres WHERE status='active' ORDER BY name";

        $filieres = [];
        if ($stmt = mysqli_prepare($con, $query)) {
            mysqli_stmt_execute($stmt);
            $filieres = emsp_stmt_fetch_all($stmt);
            mysqli_stmt_close($stmt);
        }

        $troncCommuns = [];
        $filieresWithoutTronc = [];
        foreach ($filieres as $filiere) {
            $normalized = self::formationKey((string) ($filiere['name'] ?? ''));
            if (preg_match('/\btronc?\s+commun\b/u', $normalized)) {
                $troncCommuns[] = $filiere;
            } else {
                $filieresWithoutTronc[] = $filiere;
            }
        }

        $partition = emsp_partition_filieres_by_program($filieresWithoutTronc);

        $licences = [];
        if ($stmt = mysqli_prepare($con, "SELECT id, name FROM licences WHERE status='active' ORDER BY name")) {
            mysqli_stmt_execute($stmt);
            $licences = emsp_stmt_fetch_all($stmt);
            mysqli_stmt_close($stmt);
        }

        $licenceMap = [];
        if ($stmt = mysqli_prepare(
            $con,
            "SELECT lf.filiere_id, l.name
             FROM licence_filieres lf
             JOIN licences l ON l.id = lf.licence_id
             WHERE l.status='active'
             ORDER BY l.name"
        )) {
            mysqli_stmt_execute($stmt);
            foreach (emsp_stmt_fetch_all($stmt) as $row) {
                $filiereId = (int) ($row['filiere_id'] ?? 0);
                $licenceName = trim((string) ($row['name'] ?? ''));
                if ($filiereId > 0 && $licenceName !== '') {
                    $licenceMap[$filiereId][] = $licenceName;
                }
            }
            mysqli_stmt_close($stmt);
        }

        $this->view('formations/index', [
            'fsMenumProgram' => emsp_fs_menum_program_meta(),
            'fsMenumFilieres' => $partition['fsMenum'],
            'filieresMain' => $partition['other'],
            'troncCommuns' => $troncCommuns,
            'licences' => $licences,
            'licenceMap' => $licenceMap,
        ]);
    }

    private static function formationKey(string $name): string
    {
        $value = mb_strtolower(trim(function_exists('emsp_fix_mojibake') ? emsp_fix_mojibake($name) : $name));
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($converted !== false) {
                $value = $converted;
            }
        }
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', (string) $value);
        return trim((string) $value);
    }
}
