<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\Admin\SettingsRepository;

final class SettingsController extends AdminController
{
    public function index(): void
    {
        [$con, $adminUser] = $this->guard();

        if (strtolower((string) ($adminUser['role'] ?? '')) !== 'admin') {
            redirect('admin/journal');
        }

        $settings = new SettingsRepository($con);
        $settings->ensureTableExists();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handlePost($settings);
            return;
        }

        $current = $settings->all();

        $statQueries = [
            'Utilisateurs actifs' => "SELECT COUNT(*) FROM users WHERE status='active'",
            'Documents approuvés' => "SELECT COUNT(*) FROM documents WHERE status='approved'",
            'Documents en attente' => "SELECT COUNT(*) FROM documents WHERE status='pending'",
            'Total téléchargements' => 'SELECT SUM(download_count) FROM documents',
            'Commentaires visibles' => "SELECT COUNT(*) FROM comments WHERE status='visible'",
        ];
        $statsRows = [];
        foreach ($statQueries as $label => $query) {
            $value = (float) ($con->query($query)->fetchColumn() ?: 0);
            $statsRows[] = ['label' => $label, 'value' => number_format($value, 0, ',', ' ')];
        }

        $this->view('admin/settings/index', [
            'seuilActuel' => (int) ($current['or_badge_min_approved_docs'] ?? 20),
            'bannerActive' => $current['banner_active'] ?? '0',
            'bannerType' => $current['banner_type'] ?? 'info',
            'bannerMessage' => $current['banner_message'] ?? '',
            'pushVapidPublic' => $current['push_vapid_public'] ?? '',
            'pushVapidPrivate' => $current['push_vapid_private'] ?? '',
            'statsRows' => $statsRows,
        ]);
    }

    private function handlePost(SettingsRepository $settings): void
    {
        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            flash('danger', 'Token de sécurité invalide.');
            redirect('admin/parametres');
        }

        $seuil = max(1, (int) ($_POST['or_badge_min_approved_docs'] ?? 20));
        $bannerActive = isset($_POST['banner_active']) ? '1' : '0';
        $bannerType = trim((string) ($_POST['banner_type'] ?? 'info'));
        $bannerMessage = trim((string) ($_POST['banner_message'] ?? ''));
        $pushPublic = trim((string) ($_POST['push_vapid_public'] ?? ''));
        $pushPrivate = trim((string) ($_POST['push_vapid_private'] ?? ''));
        $message = 'Paramètres mis à jour.';

        if (($_POST['action'] ?? '') === 'generate_push_keys' && function_exists('emsp_push_generate_vapid_keys')) {
            $generated = emsp_push_generate_vapid_keys();
            if ($generated) {
                $pushPublic = trim((string) ($generated['publicKey'] ?? ''));
                $pushPrivate = trim((string) ($generated['privateKey'] ?? ''));
                $message = 'Clés push générées et enregistrées.';
            } else {
                $message = 'Impossible de générer les clés push sur ce serveur.';
            }
        }

        $settings->set('or_badge_min_approved_docs', (string) $seuil);
        $settings->set('banner_active', $bannerActive);
        $settings->set('banner_type', $bannerType);
        $settings->set('banner_message', $bannerMessage);
        $settings->set('push_vapid_public', $pushPublic);
        $settings->set('push_vapid_private', $pushPrivate);

        flash('info', $message);
        redirect('admin/parametres');
    }
}
