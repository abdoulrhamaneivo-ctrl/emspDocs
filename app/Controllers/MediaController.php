<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\LegacyDb;

final class MediaController extends Controller
{
    public function index(): void
    {
        $con = LegacyDb::mediaHelpers();
        $projectRoot = dirname(__DIR__, 2);

        // Endpoint AJAX pour charger les photos d'un album en lightbox.
        if (isset($_GET['ajax']) && $_GET['ajax'] === 'album') {
            $cat = trim((string) ($_GET['category'] ?? ''));
            $albumId = (int) ($_GET['album_id'] ?? 0);
            $categoryId = (int) ($_GET['category_id'] ?? 0);
            $photosAjax = emsp_mediatheque_album_ajax($con, $albumId, $cat, $categoryId);
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(
                ['photos' => $photosAjax],
                JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
            );
            exit;
        }

        if (!empty($_SESSION['auth_user']['id'])) {
            emsp_mark_notifications_seen_for_section($con, (int) $_SESSION['auth_user']['id'], 'media');
        }

        $mediaViewModel = emsp_mediatheque_build_view_model($con, $_GET, $projectRoot);

        $this->view('media/index', $mediaViewModel);
    }
}
