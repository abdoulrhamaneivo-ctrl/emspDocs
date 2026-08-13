<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Controller;
use App\Core\LegacyDb;
use App\Repositories\AcademicRepository;
use App\Repositories\UserRepository;

final class ProfileController extends Controller
{
    public function show(): void
    {
        require_auth();
        $con = LegacyDb::mysqli();
        $uid = (int) (current_user()['id'] ?? 0);
        $users = new UserRepository(Database::pdo());

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handlePost($users, $uid);
            return;
        }

        $user = $users->findFullProfile($uid);
        // Recharge la session si le badge a changé pendant la visite (même
        // logique que l'original : emsp_session_sync_auth_user tient la
        // session à jour avec les dernières infos du compte).
        if (function_exists('emsp_session_sync_auth_user')) {
            emsp_session_sync_auth_user($user);
        }

        $academic = new AcademicRepository(Database::pdo());

        $this->view('profile/show', [
            'user' => $user,
            'stats' => $users->profileStats($uid),
            'filieres' => $academic->activeFilieres(),
            'licences' => $academic->activeLicences(),
            'badgeLabels' => ['or' => 'Badge Or', 'argent' => 'Badge Argent', 'bronze' => 'Badge Bronze', 'none' => 'Aucun badge'],
        ]);
    }

    public function showPublic(): void
    {
        $con = LegacyDb::mysqli();

        $profil_id = (int) ($_GET['id'] ?? 0);
        if ($profil_id <= 0) {
            redirect('documents');
        }

        $stmt = mysqli_prepare($con,
            "SELECT u.id, u.first_name, u.last_name, u.photo_path,
                    u.badge_level, u.upload_count, u.created_at,
                    f.name AS filiere_name, l.name AS licence_name
             FROM users u
             LEFT JOIN filieres f ON f.id = u.filiere_id
             LEFT JOIN licences l ON l.id = u.licence_id
             WHERE u.id=? AND u.status='active' LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $profil_id);
        mysqli_stmt_execute($stmt);
        $profil = emsp_stmt_fetch_assoc($stmt);
        mysqli_stmt_close($stmt);

        if (!$profil) {
            http_response_code(404);
            echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">'
                . '<title>Page introuvable - EMSP</title>'
                . '<link rel="stylesheet" href="' . asset('css/bootstrap5.min.css') . '">'
                . '</head><body class="bg-light d-flex align-items-center justify-content-center min-vh-100">'
                . '<div class="text-center p-5"><h1 class="display-1 fw-bold text-muted">404</h1>'
                . '<h2 class="mb-3">Page introuvable</h2>'
                . '<p class="text-muted mb-4">La page que vous cherchez n\'existe pas ou a ete deplacee.</p>'
                . '<a href="' . url('index.php') . '" class="btn btn-primary">Retour a l\'accueil</a></div></body></html>';
            exit;
        }

        $docs = mysqli_prepare($con,
            "SELECT d.id, d.title, d.doc_type, d.semester, d.download_count, d.created_at,
                    fi.name AS filiere_name
             FROM documents d
             LEFT JOIN filieres fi ON fi.id = d.filiere_id
             WHERE d.uploader_id=? AND d.status='approved'
             ORDER BY d.created_at DESC LIMIT 12");
        mysqli_stmt_bind_param($docs, 'i', $profil_id);
        mysqli_stmt_execute($docs);
        $docs_result = emsp_stmt_fetch_all($docs);
        mysqli_stmt_close($docs);
        $docs_count = count($docs_result);

        $badge_labels = [
            'or' => 'Badge Or',
            'argent' => 'Badge Argent',
            'bronze' => 'Badge Bronze',
            'none' => '',
        ];
        $badge_class_map = [
            'or' => 'emsp-profile-badge-level-or',
            'argent' => 'emsp-profile-badge-level-argent',
            'bronze' => 'emsp-profile-badge-level-bronze',
            'none' => 'emsp-profile-badge-level-none',
        ];
        $type_colors = [
            'cours' => 'bg-primary',
            'td' => 'bg-success',
            'correction' => 'bg-info text-dark',
            'concours' => 'bg-warning text-dark',
            'examen' => 'bg-danger',
        ];

        $this->view('profile/public', [
            'profil' => $profil,
            'docs_result' => $docs_result,
            'docs_count' => $docs_count,
            'badge_labels' => $badge_labels,
            'badge_class_map' => $badge_class_map,
            'type_colors' => $type_colors,
            'isAuthViewer' => !empty($_SESSION['auth']),
        ]);
    }

    private function handlePost(UserRepository $users, int $uid): void
    {
        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            flash('danger', 'Token de sécurité invalide.');
            redirect('mon-profil');
        }

        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'update_academic') {
            $filiereRaw = (string) ($_POST['filiere_id'] ?? '');
            $isTroncCommun = $filiereRaw === (string) emsp_tronc_commun_filiere_value();
            $filiereId = $isTroncCommun ? null : (((int) $filiereRaw) ?: null);
            $licenceId = (int) ($_POST['licence_id'] ?? 0) ?: null;

            if (!$isTroncCommun && ($filiereId === null || $filiereId <= 0)) {
                flash('danger', 'Veuillez choisir une filière ou le tronc commun.');
                redirect('mon-profil');
            }
            if ($licenceId === null || $licenceId <= 0) {
                flash('danger', 'Veuillez choisir un niveau.');
                redirect('mon-profil');
            }

            $academic = new AcademicRepository(Database::pdo());
            if (!$isTroncCommun && !$academic->filiereIsActive((int) $filiereId)) {
                flash('danger', "Cette filière n'est pas disponible.");
                redirect('mon-profil');
            }
            if (!$academic->licenceIsActive((int) $licenceId)) {
                flash('danger', "Ce niveau n'est pas disponible.");
                redirect('mon-profil');
            }

            $users->updateAcademic($uid, $filiereId, $licenceId);
            flash('success', 'Vos informations académiques ont été mises à jour.');
            redirect('mon-profil');
        }

        if ($action === 'update_profile') {
            $firstName = trim((string) ($_POST['first_name'] ?? ''));
            $lastName = trim((string) ($_POST['last_name'] ?? ''));

            if ($firstName === '' || $lastName === '') {
                flash('danger', 'Prénom et nom sont obligatoires.');
                redirect('mon-profil');
            }

            $photoUpdated = false;
            if (!empty($_FILES['photo']['name'])) {
                $uploadService = new \App\Services\UploadService();
                $res = $uploadService->processProfilePhoto($_FILES['photo'], $uid);
                if (!$res['ok']) {
                    flash('danger', $res['error'] ?? 'Échec de l\'envoi de la photo.');
                    redirect('mon-profil');
                }

                $photoPath = (string) $res['path'];
                $users->updatePhotoPath($uid, $photoPath);
                $_SESSION['auth_user']['photo_path'] = $photoPath;
                $photoUpdated = true;
            }

            $users->updateNames($uid, $firstName, $lastName);
            $_SESSION['auth_user']['first_name'] = $firstName;
            $_SESSION['auth_user']['last_name'] = $lastName;

            flash('success', $photoUpdated
                ? 'Votre nouvelle photo de profil est visible immédiatement.'
                : 'Vos informations ont été enregistrées avec succès.');
            redirect('mon-profil');
        }

        if ($action === 'change_password') {
            $current = (string) ($_POST['current_password'] ?? '');
            $new = (string) ($_POST['new_password'] ?? '');
            $confirm = (string) ($_POST['confirm_password'] ?? '');

            if (strlen($new) < 8) {
                flash('danger', 'Le nouveau mot de passe doit contenir au moins 8 caractères.');
                redirect('mon-profil');
            }
            if ($new !== $confirm) {
                flash('danger', 'Les mots de passe ne correspondent pas. Vérifiez votre saisie puis réessayez.');
                redirect('mon-profil');
            }

            $result = $users->changePassword($uid, $current, $new);
            if ($result !== true) {
                flash('danger', (string) $result);
                redirect('mon-profil');
            }

            flash('success', 'Votre mot de passe a été mis à jour avec succès.');
            redirect('mon-profil');
        }

        redirect('mon-profil');
    }
}
