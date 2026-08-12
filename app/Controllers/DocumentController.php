<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Controller;
use App\Core\LegacyDb;
use App\Repositories\AcademicRepository;
use App\Repositories\DocumentRepository;

final class DocumentController extends Controller
{
    public function index(): void
    {
        $user = current_user();

        if (!$user) {
            // Comportement identique à l'ancien bibliotheque.php : une page
            // d'accueil de la bibliothèque, pas une redirection sèche.
            $_SESSION['redirect_after_login'] = url('documents');
            $this->view('documents/gate', []);
            return;
        }

        try {
            LegacyDb::documentHelpers();
            $role = strtolower((string) ($user['role'] ?? ''));
            $isStaff = in_array($role, ['admin', 'moderateur'], true);
            $userId = (int) ($user['id'] ?? 0);

            $documents = new DocumentRepository(Database::pdo());
            $rawDocs = $documents->workspaceDocuments($isStaff, $userId);

            $csrf = csrf_token();
            $workspaceDocs = array_map(
                static fn(array $doc): array => emsp_docs_workspace_payload($doc, ['source' => 'library', 'csrf_token' => $csrf]),
                $rawDocs
            );

            $filterOptions = [
                'filiere' => $documents->activeNames('filieres'),
                'licence' => $documents->activeNames('licences'),
                'matiere' => $documents->activeNames('matieres'),
                'semester' => $documents->distinctSemesters(),
            ];

            $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
            if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
                $jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
            }

            $workspacePayloadJson = json_encode(['documents' => $workspaceDocs], $jsonFlags);
            $workspaceConfigJson = json_encode([
                'page' => 'library',
                'title' => 'Bibliothèque',
                'subtitle' => 'Recherche instantanée, vue grille et vue liste sans rechargement.',
                'emptyTitle' => 'Aucun document trouvé',
                'emptyText' => 'La bibliothèque ne contient pas encore de ressource pour cette combinaison de filtres.',
                'defaultView' => 'grid',
                'filterOptions' => $filterOptions,
            ], $jsonFlags);

            if ($workspacePayloadJson === false || $workspaceConfigJson === false) {
                throw new \RuntimeException('json_encode failed for documents workspace payload');
            }

            $this->view('documents/index', [
                'workspacePayloadJson' => $workspacePayloadJson,
                'workspaceConfigJson' => $workspaceConfigJson,
            ]);
        } catch (\Throwable $e) {
            error_log('EMSP documents index failed: ' . $e->getMessage());
            flash('danger', 'La bibliothèque est temporairement indisponible. Réessayez dans quelques instants.');
            $this->view('documents/gate', []);
        }
    }

    public function show(array $params): void
    {
        $docId = (int) ($params['id'] ?? ($_GET['id'] ?? 0));
        if ($docId <= 0) {
            redirect('documents');
        }

        try {
            $con = LegacyDb::documentHelpers();
            $user = current_user();
            $isAuth = $user !== null;
            $uid = (int) ($user['id'] ?? 0);

            $base = emsp_document_load_base($con, $docId, $_SESSION);
            if (empty($base['found'])) {
                redirect('documents');
            }

            $doc = $base['doc'];
            $role = (string) $base['role'];
            $isStaff = (bool) $base['is_staff'];
            $isOwner = (bool) $base['is_owner'];
            $isPublicDocument = (bool) $base['is_public_document'];
            $isApproved = (bool) $base['is_approved'];

            // Même règle d'accès que l'ancien document.php : les visiteurs non
            // connectés ne peuvent voir que les documents "concours".
            if (!$isAuth && ($doc['doc_type'] ?? '') !== 'concours') {
                redirect('concours');
            }
            if ((!$isApproved || !$isPublicDocument) && !$isOwner && !$isStaff) {
                if (!$isAuth) {
                    $_SESSION['redirect_after_login'] = url('document?id=' . $docId);
                    redirect('login');
                }
                redirect('documents');
            }

            if ($user) {
                try {
                    $notifService = new \App\Services\NotificationService();
                    $notifService->markDocumentNotificationsSeen((int) $user['id'], $docId);
                } catch (\Throwable $e) {
                    error_log('EMSP document notifications failed: ' . $e->getMessage());
                }
            }

            $documents = new DocumentRepository(Database::pdo());

            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                if (!$isAuth) {
                    $_SESSION['redirect_after_login'] = url('document?id=' . $docId);
                    redirect('login');
                }
                if (!verify_csrf($_POST['csrf_token'] ?? null)) {
                    flash('danger', 'Token de sécurité invalide.');
                    redirect('document?id=' . $docId);
                }

                if (isset($_POST['fav_action'])) {
                    $documents->setFavorite($uid, $docId, $_POST['fav_action'] === 'add');
                    redirect('document?id=' . $docId);
                }

                if (isset($_POST['like_action'])) {
                    $documents->setLike($uid, $docId, $_POST['like_action'] === 'add');
                    redirect('document?id=' . $docId);
                }

                if (isset($_POST['comment_content'])) {
                    $content = trim((string) $_POST['comment_content']);
                    if ($content !== '' && $doc['status'] === 'approved') {
                        $documents->addComment($uid, $docId, $content, (int) $doc['uploader_id'], (string) $doc['title'], (string) ($user['first_name'] ?? ''), (string) ($user['last_name'] ?? ''));
                    }
                    header('Location: ' . url('document?id=' . $docId . '#commentaires'));
                    exit;
                }

                if (isset($_POST['reply_content'], $_POST['reply_comment_id'])) {
                    $parentId = (int) $_POST['reply_comment_id'];
                    $content = trim((string) $_POST['reply_content']);
                    if ($content !== '' && $parentId > 0 && $doc['status'] === 'approved') {
                        $documents->addReply($uid, $parentId, $content, $docId, (string) $doc['title'], (string) ($user['first_name'] ?? ''), (string) ($user['last_name'] ?? ''));
                    }
                    header('Location: ' . url('document?id=' . $docId . '#commentaires'));
                    exit;
                }

                redirect('document?id=' . $docId);
            }

            $isFav = $isAuth ? $documents->isFavorite($uid, $docId) : false;
            $isLiked = $isAuth ? $documents->isLiked($uid, $docId) : false;
            $discussion = emsp_document_load_discussion($con, $docId, $uid);
            $viewData = emsp_document_build_view_data($doc, $docId, $isOwner, $isStaff);
            $previewAvailable = emsp_document_file_available($doc['file_path'] ?? '');
            $previewImageRelative = emsp_doc_thumb_resolve($doc);
            $previewImageUrl = $previewImageRelative !== ''
                ? emsp_doc_thumb_public_url($previewImageRelative)
                : ($previewAvailable ? emsp_doc_thumb_lazy_url($docId) : '');

            $this->view('documents/show', array_merge($viewData, $discussion, [
                'doc' => $doc,
                'doc_id' => $docId,
                'isAuth' => $isAuth,
                'is_owner' => $isOwner,
                'is_staff' => $isStaff,
                'is_fav' => $isFav,
                'is_liked' => $isLiked,
                'csrf_field' => csrf_field(),
                'preview_available' => $previewAvailable,
                'preview_image_url' => $previewImageUrl,
            ]));
        } catch (\Throwable $e) {
            error_log('EMSP document show failed: ' . $e->getMessage());
            flash('danger', 'Impossible d\'afficher ce document pour le moment. Réessayez dans quelques instants.');
            redirect('documents');
        }
    }

    // ---------------------------------------------------------------
    // DÉPÔT DE DOCUMENT (upload)
    // ---------------------------------------------------------------

    public function showUpload(): void
    {
        require_auth();
        $con = LegacyDb::documentHelpers();
        $academic = new AcademicRepository(Database::pdo());

        $this->view('documents/upload', [
            'filieres' => $academic->activeFilieres(),
            'licences' => $academic->activeLicences(),
            'matieres' => $academic->activeMatieres(),
        ]);
    }

    public function upload(): void
    {
        require_auth();
        $con = LegacyDb::documentHelpers();

        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            flash('danger', 'Token de sécurité invalide.');
            redirect('upload');
        }

        $uid = (int) (current_user()['id'] ?? 0);
        if ($uid <= 0) {
            flash('danger', 'Votre session a expiré. Merci de vous reconnecter.');
            redirect('login');
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $description = $description === '' ? null : emsp_fix_mojibake($description);
        if ($title !== '') {
            $title = emsp_fix_mojibake($title);
        }
        $docType = trim((string) ($_POST['doc_type'] ?? ''));
        $semester = trim((string) ($_POST['semester'] ?? ''));
        $semester = $semester === '' ? null : $semester;

        $licenceId = (int) ($_POST['licence_id'] ?? 0);
        $matiereId = (int) ($_POST['matiere_id'] ?? 0);
        $matiereFreeText = trim((string) ($_POST['matiere_free_text'] ?? ''));
        $filiereIds = emsp_collect_int_ids($_POST['filiere_ids'] ?? []);

        $filiereId = !empty($filiereIds) ? (int) $filiereIds[0] : null;
        $licenceId = $licenceId > 0 ? $licenceId : null;
        $matiereId = $matiereId > 0 ? $matiereId : null;
        $isPublic = isset($_POST['is_public']) ? 1 : 0;

        $examSession = null;
        $examSection = null;
        $examYear = null;

        if ($title === '') {
            flash('danger', 'Le titre est obligatoire pour soumettre un document.');
            redirect('upload');
        }
        if (empty($filiereIds)) {
            flash('danger', 'Sélectionnez au moins une filière avant d\'envoyer le document.');
            redirect('upload');
        }
        if ($matiereId === null && $matiereFreeText === '') {
            flash('danger', 'Choisissez une matière existante ou saisissez une nouvelle matière.');
            redirect('upload');
        }
        if ($matiereFreeText !== '') {
            $matiereId = null;
            if (!emsp_pending_matiere_enabled($con)) {
                flash('danger', 'La matière libre nécessite une migration SQL non appliquée sur ce serveur. Choisissez une matière existante.');
                redirect('upload');
            }
        }
        if (!in_array($docType, ['cours', 'td', 'correction', 'concours', 'examen'], true)) {
            flash('danger', 'Choisissez un type de document pour continuer.');
            redirect('upload');
        }
        if ($docType === 'examen') {
            $examSession = trim((string) ($_POST['exam_session'] ?? ''));
            $examSection = trim((string) ($_POST['exam_section'] ?? ''));
            $examYear = (int) ($_POST['exam_year'] ?? 0);
            if ($examSession === '' || $examSection === '' || $examYear < 2000) {
                flash('danger', 'Session, section et année sont obligatoires pour un examen.');
                redirect('upload');
            }
        }

        $uploadService = new \App\Services\UploadService();
        $uploadRes = $uploadService->processDocument($_FILES['document'], $uid);
        if (!$uploadRes['ok']) {
            flash('danger', $uploadRes['error'] ?? 'Échec du téléversement du fichier.');
            redirect('upload');
        }

        $documents = new DocumentRepository(Database::pdo());
        if ($documents->fileHashExists($uploadRes['hash'])) {
            $uploadService->cleanup($uploadRes['path'], $uploadRes['thumb']);
            flash('warning', 'Ce fichier exact existe déjà dans la bibliothèque. Si c\'est une mise à jour, ajoutez la version dans le titre (ex : "Cours_v2.pdf").');
            redirect('upload');
        }

        $result = $documents->createFromUpload([
            'uploader_id' => $uid,
            'title' => $title,
            'description' => $description,
            'doc_type' => $docType,
            'semester' => $semester,
            'filiere_id' => $filiereId,
            'licence_id' => $licenceId,
            'module_id' => null,
            'matiere_id' => $matiereId,
            'matiere_free_text' => $matiereFreeText,
            'thumb_path' => $uploadRes['thumb'],
            'exam_session' => $examSession,
            'exam_section' => $examSection,
            'exam_year' => $examYear,
            'file_name' => basename($uploadRes['path']),
            'mime_type' => $uploadRes['mime'],
            'size' => $uploadRes['size'],
            'is_public' => $isPublic,
            'file_hash' => $uploadRes['hash'],
            'filiere_ids' => $filiereIds,
        ]);

        if ($result === 'duplicate' || $result === 0) {
            $uploadService->cleanup($uploadRes['path'], $uploadRes['thumb']);
            if ($result === 'duplicate') {
                flash('warning', 'Ce fichier exact existe déjà dans la bibliothèque.');
            } else {
                flash('danger', "Une erreur est survenue lors de l'enregistrement du document.");
            }
            redirect('upload');
        }

        flash('success', 'Document soumis avec succès ! Il est en attente de validation par un modérateur. Tu seras notifié dès qu\'il sera approuvé.');
        redirect('documents');
    }

    // ---------------------------------------------------------------
    // MES FAVORIS
    // ---------------------------------------------------------------

    public function favorites(): void
    {
        require_auth();
        $con = LegacyDb::documentHelpers();
        $uid = (int) (current_user()['id'] ?? 0);
        $documents = new DocumentRepository(Database::pdo());

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['doc_id'])) {
            if (!verify_csrf($_POST['csrf_token'] ?? null)) {
                flash('danger', 'Token de sécurité invalide.');
                redirect('favoris');
            }
            $documents->removeFavorite($uid, (int) $_POST['doc_id']);
            flash('info', 'Document retiré de vos favoris.');
            redirect('favoris');
        }

        $csrf = csrf_token();
        $rawFavs = $documents->workspaceFavorites($uid);
        $workspaceDocs = array_map(
            static fn(array $doc): array => emsp_docs_workspace_payload($doc, ['source' => 'favorites', 'csrf_token' => $csrf]),
            $rawFavs
        );

        $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }

        $workspacePayloadJson = json_encode(
            ['documents' => $workspaceDocs],
            $jsonFlags
        );
        // Pas de filterOptions ici : comme dans l'original, le JS dérive les
        // filtres directement depuis le jeu de favoris (pas de liste
        // pré-calculée côté serveur pour cette page).
        $workspaceConfigJson = json_encode([
            'page' => 'favorites',
            'title' => 'Mes favoris',
            'subtitle' => 'Retrouve, filtre et consulte rapidement les documents que tu gardes sous la main.',
            'emptyTitle' => 'Aucun favori pour le moment',
            'emptyText' => 'Ajoute des documents à tes favoris depuis la bibliothèque.',
            'defaultView' => 'grid',
        ], $jsonFlags);

        $this->view('documents/favorites', [
            'workspacePayloadJson' => $workspacePayloadJson,
            'workspaceConfigJson' => $workspaceConfigJson,
        ]);
    }
}
