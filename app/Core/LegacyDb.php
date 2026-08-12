<?php

declare(strict_types=1);

namespace App\Core;

use mysqli;

/**
 * Pont temporaire vers la couche mysqli existante (admin/config/dbcon.php et
 * les fonctions métier legacy : rate_limit.php, brevo.php, notif-helper.php).
 *
 * Pourquoi ne pas utiliser App\Core\Database (PDO) ici ?
 * Ces fonctions métier (limitation des tentatives de connexion, envoi
 * d'emails Brevo, compteurs de notifications) sont déjà écrites, testées et
 * utilisées ailleurs dans l'application. Les dupliquer en PDO maintenant
 * ferait courir un risque de divergence de comportement pour un bénéfice nul.
 *
 * Elles seront réécrites en classes (Repository/Service, PDO) une fois que
 * 100% de la logique métier aura été extraite des anciens fichiers .php à
 * plat — à ce moment-là, un seul changement ici suffira.
 */
final class LegacyDb
{
    private static ?mysqli $con = null;
    private static bool $documentHelpersLoaded = false;

    public static function mysqli(): mysqli
    {
        if (self::$con instanceof mysqli) {
            return self::$con;
        }

        $rootDir = dirname(__DIR__, 2);

        // Charge $con + toutes les fonctions utilitaires legacy (emsp_*),
        // exactement comme le faisait chaque ancien fichier .php à plat.
        // includes/bootstrap.php ne redémarre pas la session : elle est déjà
        // active (démarrée par notre config/bootstrap.php), il ne fait
        // qu'ajouter les fonctions emsp_session_* utilisées par le login.
        require_once $rootDir . '/includes/bootstrap.php';
        require_once $rootDir . '/admin/config/dbcon.php';
        require_once $rootDir . '/includes/csrf.php';
        require_once $rootDir . '/includes/flash.php';
        require_once $rootDir . '/includes/rate_limit.php';
        require_once $rootDir . '/includes/notif-helper.php';
        require_once $rootDir . '/includes/brevo.php';

        /** @var mysqli $con défini par dbcon.php */
        self::$con = $con;

        return self::$con;
    }

    /**
     * Helpers additionnels utilisés uniquement par la bibliothèque / la
     * fiche document (taxonomie, miniatures, service document-data.php).
     * Chargés à la demande pour ne pas alourdir les pages qui n'en ont pas
     * besoin (login, register...).
     */
    public static function documentHelpers(): mysqli
    {
        $con = self::mysqli();

        if (!self::$documentHelpersLoaded) {
            $rootDir = dirname(__DIR__, 2);
            require_once $rootDir . '/includes/content-helpers.php';
            require_once $rootDir . '/includes/document-taxonomy.php';
            require_once $rootDir . '/includes/docs-workspace.php'; // charge aussi generate_thumb.php
            require_once $rootDir . '/includes/services/document-data.php';
            self::$documentHelpersLoaded = true;
        }

        return $con;
    }

    /**
     * Helpers pour les filières (présentation éditoriale, upload d'image
     * de couverture). Chargés à la demande.
     */
    public static function formationsHelpers(): mysqli
    {
        $con = self::mysqli();
        $rootDir = dirname(__DIR__, 2);
        require_once $rootDir . '/includes/formations-helpers.php';
        require_once $rootDir . '/includes/services/admin-image-upload.php';
        return $con;
    }

    /**
     * Helpers pour le journal (annonces/défis/sondages). Chargés à la demande.
     */
    public static function journalHelpers(): mysqli
    {
        $con = self::mysqli();
        $rootDir = dirname(__DIR__, 2);
        require_once $rootDir . '/includes/journal_helpers.php';
        return $con;
    }

    /**
     * Helpers pour le Journal public (liste, fiche article, actions
     * like/vote/défi/commentaire). Chargés à la demande.
     */
    public static function journalFrontHelpers(): mysqli
    {
        $con = self::journalHelpers();
        $rootDir = dirname(__DIR__, 2);
        require_once $rootDir . '/includes/journal-front-helpers.php';
        return $con;
    }

    /**
     * Helpers pour la page Institution (blocs dynamiques + fallback
     * contenu legacy). Chargés à la demande.
     */
    public static function institutionHelpers(): mysqli
    {
        $con = self::mysqli();
        $rootDir = dirname(__DIR__, 2);
        require_once $rootDir . '/includes/institution-helpers.php';
        return $con;
    }

    /**
     * Helpers pour la médiathèque publique. Chargés à la demande.
     */
    public static function mediaHelpers(): mysqli
    {
        $con = self::mysqli();
        $rootDir = dirname(__DIR__, 2);
        require_once $rootDir . '/includes/content-helpers.php';
        require_once $rootDir . '/includes/services/media-data.php';
        return $con;
    }
}
