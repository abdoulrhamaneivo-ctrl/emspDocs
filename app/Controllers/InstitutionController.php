<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\LegacyDb;

final class InstitutionController extends Controller
{
    public function index(): void
    {
        $con = LegacyDb::institutionHelpers();

        // 1) Blocs dynamiques
        $blocs = [];
        $useLegacy = true;
        $tableExists = false;
        $check = mysqli_query($con, "SHOW TABLES LIKE 'institution_blocs'");
        if ($check && mysqli_num_rows($check) > 0) {
            $tableExists = true;
        }

        if ($tableExists) {
            $r = mysqli_query($con, "SELECT * FROM institution_blocs WHERE visible=1 ORDER BY ordre ASC, id ASC");
            if ($r instanceof \mysqli_result) {
                while ($row = mysqli_fetch_assoc($r)) {
                    $blocs[] = $row;
                }
            }
            if (!empty($blocs)) {
                $useLegacy = false;
            }
        }

        // 2) Fallback legacy
        $bySection = [];
        $byKey = [];
        if ($useLegacy) {
            $s = mysqli_prepare($con, "SELECT cle, section, label, valeur, ordre FROM institution_content ORDER BY section, ordre, cle");
            if ($s) {
                mysqli_stmt_execute($s);
                $contentRows = emsp_stmt_fetch_all($s);
                foreach ($contentRows as $row) {
                    $section = trim((string) ($row['section'] ?? ''));
                    $key = trim((string) ($row['cle'] ?? ''));
                    if ($section !== '') {
                        $bySection[$section][] = $row;
                    }
                    if ($key !== '') {
                        $byKey[$key] = $row;
                    }
                }
                mysqli_stmt_close($s);
            }
        }

        $aboutText = emsp_section_text($bySection['a-propos'] ?? $bySection['a_propos'] ?? []);
        $missionText = emsp_section_text($bySection['mission'] ?? []);
        $visionText = emsp_section_text($bySection['vision'] ?? []);
        $valuesText = emsp_section_text($bySection['valeurs'] ?? $bySection['valeur'] ?? []);
        $perspectivesText = emsp_section_text($bySection['perspectives'] ?? []);
        $motDGText = emsp_section_text($bySection['mot-dg'] ?? $bySection['mot_dg'] ?? []);
        $motDEText = emsp_section_text($bySection['mot-de'] ?? $bySection['mot_de'] ?? []);

        $motDGName = emsp_value_by_keys($byKey, ['mot_dg_nom', 'mot-dg-nom', 'directeur_general_nom']);
        $motDEName = emsp_value_by_keys($byKey, ['mot_de_nom', 'mot-de-nom', 'directeur_etudes_nom']);
        $motDGPhoto = emsp_value_by_keys($byKey, ['mot_dg_photo', 'directeur_photo', 'dg_photo']);
        $motDEPhoto = emsp_value_by_keys($byKey, ['mot_de_photo', 'directeur_etudes_photo', 'de_photo']);
        $motDGTitre = emsp_value_by_keys($byKey, ['mot_dg_titre', 'dg_titre']) ?: 'Directeur Général';
        $motDETitre = emsp_value_by_keys($byKey, ['mot_de_titre', 'de_titre']) ?: 'Directeur des Études';

        $activeStudents = 0;
        $approvedDocs = 0;
        $countries = null;

        $s = mysqli_prepare($con, "SELECT COUNT(*) FROM users WHERE role='etudiant' AND status='active'");
        if ($s) {
            mysqli_stmt_execute($s);
            mysqli_stmt_bind_result($s, $activeStudents);
            mysqli_stmt_fetch($s);
            mysqli_stmt_close($s);
        }

        $s = mysqli_prepare($con, "SELECT COUNT(*) FROM documents WHERE status='approved'");
        if ($s) {
            mysqli_stmt_execute($s);
            mysqli_stmt_bind_result($s, $approvedDocs);
            mysqli_stmt_fetch($s);
            mysqli_stmt_close($s);
        }

        $countriesRaw = emsp_value_by_keys($byKey, ['stats_pays', 'nombre_pays', 'pays_membres']);
        if ($countriesRaw !== '' && is_numeric($countriesRaw)) {
            $countries = (int) $countriesRaw;
        }

        $this->view('institution/index', [
            'blocs' => $blocs,
            'use_legacy' => $useLegacy,
            'aboutText' => $aboutText,
            'missionText' => $missionText,
            'visionText' => $visionText,
            'valuesText' => $valuesText,
            'perspectivesText' => $perspectivesText,
            'motDGText' => $motDGText,
            'motDEText' => $motDEText,
            'motDGName' => $motDGName,
            'motDEName' => $motDEName,
            'motDGPhoto' => $motDGPhoto,
            'motDEPhoto' => $motDEPhoto,
            'motDGTitre' => $motDGTitre,
            'motDETitre' => $motDETitre,
            'activeStudents' => $activeStudents,
            'approvedDocs' => $approvedDocs,
            'countries' => $countries,
        ]);
    }
}
