<?php

declare(strict_types=1);

// Chaque ligne : [méthode HTTP, motif d'URL, [Contrôleur::class, 'action']]
//
// Une route ne s'active que pour une URL qui ne correspond à aucun
// fichier/dossier réel existant (cf. .htaccess) — donc, tant qu'un ancien
// fichier .php n'a pas été transformé en stub de redirection (voir Phase 1
// ci-dessous), il continue de répondre normalement, inchangé.

use App\Controllers\AuthController;
use App\Controllers\PageController;
use App\Controllers\DocumentController;
use App\Controllers\HistoryController;
use App\Controllers\ProfileController;
use App\Controllers\Admin\PendingUsersController;
use App\Controllers\Admin\PendingDocumentsController;
use App\Controllers\Admin\UsersController;
use App\Controllers\Admin\TaxonomyController;
use App\Controllers\Admin\LicencesController;
use App\Controllers\Admin\FilieresController;
use App\Controllers\Admin\CommentModerationController;
use App\Controllers\Admin\JournalController;
use App\Controllers\Admin\MediaController;
use App\Controllers\Admin\StatsController;
use App\Controllers\Admin\SchoolDomainsController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\FormationsController;
use App\Controllers\InstitutionController;
use App\Controllers\JournalController as FrontJournalController;
use App\Controllers\MediaController as FrontMediaController;
use App\Controllers\DashboardController;
use App\Controllers\PendingStatusController;
use App\Controllers\NotificationController;
use App\Controllers\PushController;
use App\Controllers\DownloadController;
use App\Controllers\HomeController;

return [
    // --- Phase 9 : accueil (front controller pour '/' desormais que
    // index.php n'existe plus physiquement — voir .htaccess) ---
    ['GET',  '/', [HomeController::class, 'index']],
    ['GET',  '/index.php', [HomeController::class, 'index']],

    // --- Phase 1 : Authentification ---
    ['GET',  '/login', [AuthController::class, 'showLogin']],
    ['POST', '/login', [AuthController::class, 'login']],
    ['GET',  '/register', [AuthController::class, 'showRegister']],
    ['POST', '/register', [AuthController::class, 'register']],
    // Une déconnexion modifie l'état de session : POST + CSRF uniquement.
    ['POST', '/logout', [AuthController::class, 'logout']],

    // --- Phase 1b : vérification d'email, mot de passe oublié ---
    ['GET',  '/verify-email', [AuthController::class, 'verifyEmail']],
    ['POST', '/resend-verification', [AuthController::class, 'resendVerification']],
    ['GET',  '/forgot-password', [AuthController::class, 'showForgotPassword']],
    ['POST', '/forgot-password', [AuthController::class, 'forgotPassword']],
    ['GET',  '/reset-password', [AuthController::class, 'showResetPassword']],
    ['POST', '/reset-password', [AuthController::class, 'resetPassword']],

    // --- Phase 2a : bibliotheque de documents ---
    ['GET',  '/documents', [DocumentController::class, 'index']],

    // --- Phase 2b : fiche document (favoris, likes, commentaires) ---
    ['GET',  '/document', [DocumentController::class, 'show']],
    ['POST', '/document', [DocumentController::class, 'show']],

    // --- Phase 2c : depot de document ---
    ['GET',  '/upload', [DocumentController::class, 'showUpload']],
    ['POST', '/upload', [DocumentController::class, 'upload']],

    // --- Phase 4a : mes favoris ---
    ['GET',  '/favoris', [DocumentController::class, 'favorites']],
    ['POST', '/favoris', [DocumentController::class, 'favorites']],

    // --- Phase 4b : historique ---
    ['GET',  '/historique', [HistoryController::class, 'index']],

    // --- Phase 4c : mon profil ---
    ['GET',  '/mon-profil', [ProfileController::class, 'show']],
    ['POST', '/mon-profil', [ProfileController::class, 'show']],

    // --- Phase 5a : admin - validation des inscriptions ---
    ['GET',  '/admin/validation-comptes', [PendingUsersController::class, 'index']],
    ['POST', '/admin/validation-comptes', [PendingUsersController::class, 'index']],

    // --- Phase 5b : admin - validation des documents ---
    ['GET',  '/admin/validation-documents', [PendingDocumentsController::class, 'index']],
    ['POST', '/admin/validation-documents', [PendingDocumentsController::class, 'index']],

    // --- Phase 5c : admin - gestion des utilisateurs ---
    ['GET',  '/admin/utilisateurs', [UsersController::class, 'index']],
    ['POST', '/admin/utilisateurs', [UsersController::class, 'index']],
    ['GET',  '/admin/utilisateurs/nouveau', [UsersController::class, 'create']],
    ['POST', '/admin/utilisateurs/nouveau', [UsersController::class, 'store']],
    ['GET',  '/admin/utilisateurs/{id}/modifier', [UsersController::class, 'edit']],
    ['POST', '/admin/utilisateurs/{id}/modifier', [UsersController::class, 'update']],

    // --- Phase 5e : admin - licences (relation many-to-many filieres) ---
    // IMPORTANT : declarees AVANT la route generique /admin/{type} plus bas.
    ['GET',  '/admin/licences', [LicencesController::class, 'index']],
    ['POST', '/admin/licences', [LicencesController::class, 'index']],
    ['GET',  '/admin/licences/form', [LicencesController::class, 'form']],
    ['POST', '/admin/licences/form', [LicencesController::class, 'save']],

    // --- Phase 5f : admin - filieres (presentation editoriale + image) ---
    ['GET',  '/admin/filieres', [FilieresController::class, 'index']],
    ['POST', '/admin/filieres', [FilieresController::class, 'index']],
    ['GET',  '/admin/filieres/form', [FilieresController::class, 'form']],
    ['POST', '/admin/filieres/form', [FilieresController::class, 'save']],

    // --- Phase 5g : admin - moderation des commentaires ---
    ['GET',  '/admin/moderation-commentaires', [CommentModerationController::class, 'index']],
    ['POST', '/admin/moderation-commentaires', [CommentModerationController::class, 'index']],

    // --- Phase 5h : admin - journal (annonces, defis, sondages) ---
    ['GET',  '/admin/journal', [JournalController::class, 'index']],
    ['POST', '/admin/journal', [JournalController::class, 'index']],
    ['GET',  '/admin/journal/form', [JournalController::class, 'form']],
    ['POST', '/admin/journal/form', [JournalController::class, 'save']],

    // --- Phase 5i : admin - mediatheque ---
    ['GET',  '/admin/mediatheque', [MediaController::class, 'index']],
    ['POST', '/admin/mediatheque', [MediaController::class, 'index']],
    ['GET',  '/admin/mediatheque/form', [MediaController::class, 'form']],
    ['POST', '/admin/mediatheque/form', [MediaController::class, 'save']],
    ['GET',  '/admin/mediatheque/categories', [MediaController::class, 'categories']],
    ['POST', '/admin/mediatheque/categories', [MediaController::class, 'categories']],
    ['POST', '/admin/mediatheque/poster', [MediaController::class, 'poster']],
    ['GET',  '/admin/mediatheque/youtube-title', [MediaController::class, 'youtubeTitle']],

    // --- Phase 5j : admin - stats, domaines email, parametres (derniers modules) ---
    ['GET',  '/admin/statistiques', [StatsController::class, 'index']],
    ['GET',  '/admin/domaines-email', [SchoolDomainsController::class, 'index']],
    ['POST', '/admin/domaines-email', [SchoolDomainsController::class, 'index']],
    ['GET',  '/admin/parametres', [SettingsController::class, 'index']],
    ['POST', '/admin/parametres', [SettingsController::class, 'index']],

    // --- Phase 6a : pages statiques du front (FAQ) ---
    ['GET',  '/faq', [PageController::class, 'faq']],
    ['GET',  '/concours', [PageController::class, 'concours']],

    // --- Phase 7a : formations (filieres, licences) ---
    ['GET',  '/formations', [FormationsController::class, 'index']],

    // --- Phase 7b : institution (a propos, mission, equipe) ---
    ['GET',  '/institution', [InstitutionController::class, 'index']],

    // --- Phase 7c : journal public (annonces, defis, sondages) ---
    ['GET',  '/journal', [FrontJournalController::class, 'index']],
    ['GET',  '/journal/article', [FrontJournalController::class, 'show']],
    ['GET',  '/journal/action', [FrontJournalController::class, 'action']],
    ['POST', '/journal/action', [FrontJournalController::class, 'action']],

    // --- Phase 7d : mediatheque publique (photos, videos) ---
    ['GET',  '/mediatheque', [FrontMediaController::class, 'index']],

    // --- Phase 8 : espace personnel, notifications, telechargements ---
    ['GET',  '/dashboard', [DashboardController::class, 'index']],
    ['GET',  '/pending-status', [PendingStatusController::class, 'index']],
    ['GET',  '/notifications/unread-count', [NotificationController::class, 'unreadCount']],
    ['GET',  '/notifications/recent', [NotificationController::class, 'recent']],
    ['POST', '/notifications/mark-read', [NotificationController::class, 'markRead']],
    ['POST', '/push-subscribe', [PushController::class, 'subscribe']],
    ['POST', '/push-unsubscribe', [PushController::class, 'unsubscribe']],
    ['GET',  '/telecharger', [DownloadController::class, 'stream']],
    ['POST', '/telecharger', [DownloadController::class, 'stream']],
    ['HEAD', '/telecharger', [DownloadController::class, 'stream']],
    ['GET',  '/document-thumb', [DownloadController::class, 'thumb']],
    ['GET',  '/profil-public', [ProfileController::class, 'showPublic']],

    // --- Phase 5d : admin - referentiels simples (matieres, modules) ---
    // {type} = 'matieres' ou 'modules'
    ['GET',  '/admin/{type}', [TaxonomyController::class, 'index']],
    ['POST', '/admin/{type}', [TaxonomyController::class, 'index']],
    ['GET',  '/admin/{type}/form', [TaxonomyController::class, 'form']],
    ['POST', '/admin/{type}/form', [TaxonomyController::class, 'save']],
];

