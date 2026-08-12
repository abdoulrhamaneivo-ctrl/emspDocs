<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\LegacyDb;
use App\Repositories\Admin\FiliereAdminRepository;

final class FilieresController extends AdminController
{
    public function index(): void
    {
        [$con] = $this->guard();
        LegacyDb::formationsHelpers();
        $filieres = new FiliereAdminRepository($con);
        $filieres->ensureEditorialColumns();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $action = (string) ($_POST['action'] ?? '');
            if (!verify_csrf($_POST['csrf_token'] ?? null)) {
                flash('danger', 'Token de sécurité invalide.');
                redirect('admin/filieres');
            }

            $id = (int) ($_POST['id'] ?? 0);

            if ($action === 'toggle' && $id > 0) {
                $filieres->toggleStatus($id);
                flash('info', 'Statut mis à jour.');
                redirect('admin/filieres');
            }

            if ($action === 'delete' && $id > 0) {
                $item = $filieres->find($id);
                if (!$item) {
                    flash('warning', 'Filière introuvable.');
                    redirect('admin/filieres');
                }

                if ($filieres->usageCount($id) > 0) {
                    flash('warning', 'Impossible de supprimer : cette filière est liée à des utilisateurs ou des documents. Désactivez-la plutôt.');
                    redirect('admin/filieres');
                }

                $coverPath = trim((string) ($item['cover_image_path'] ?? ''));
                if ($filieres->delete($id)) {
                    $this->deleteLocalAsset($coverPath);
                    flash('success', 'Filière supprimée.');
                } else {
                    flash('danger', 'La suppression a échoué.');
                }
                redirect('admin/filieres');
            }
        }

        $this->view('admin/filieres/index', [
            'items' => $filieres->all(),
            'editorialEnabled' => $filieres->editorialEnabled(),
            'programColumnEnabled' => $filieres->programColumnEnabled(),
        ]);
    }

    public function form(): void
    {
        [$con] = $this->guard();
        LegacyDb::formationsHelpers();
        $filieres = new FiliereAdminRepository($con);
        $filieres->ensureEditorialColumns();

        $id = (int) ($_GET['id'] ?? 0);
        $defaults = [
            'name' => '', 'code' => '', 'status' => 'active',
            'summary' => '', 'description_html' => '', 'cover_image_path' => '',
            'formation_program' => '',
        ];
        $item = $defaults;
        if ($id > 0) {
            $found = $filieres->find($id);
            if (!$found) {
                flash('warning', 'Filière introuvable.');
                redirect('admin/filieres');
            }
            $item = array_merge($defaults, $found);
        }

        $this->view('admin/filieres/form', [
            'id' => $id,
            'item' => $item,
            'editorialEnabled' => $filieres->editorialEnabled(),
            'programColumnEnabled' => $filieres->programColumnEnabled(),
            'coverSrc' => function_exists('emsp_formation_image_src') ? emsp_formation_image_src((string) ($item['cover_image_path'] ?? '')) : '',
        ]);
    }

    public function save(): void
    {
        [$con] = $this->guard();
        LegacyDb::formationsHelpers();
        $filieres = new FiliereAdminRepository($con);
        $filieres->ensureEditorialColumns();

        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            flash('danger', 'Token de sécurité invalide.');
            redirect('admin/filieres');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $editing = $id > 0;
        $editorial = $filieres->editorialEnabled();

        $name = trim((string) ($_POST['name'] ?? ''));
        $code = strtoupper(trim((string) ($_POST['code'] ?? '')));
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : 'active';
        $summary = trim((string) ($_POST['summary'] ?? ''));
        $descriptionHtml = trim((string) ($_POST['description_html'] ?? ''));
        if (function_exists('emsp_sanitize_rich_html')) {
            $descriptionHtml = emsp_sanitize_rich_html($descriptionHtml);
        }
        $currentCoverPath = trim((string) ($_POST['current_cover_image_path'] ?? ''));
        $removeCover = !empty($_POST['remove_cover']);
        $coverImagePath = $currentCoverPath;
        $formationProgram = trim((string) ($_POST['formation_program'] ?? ''));
        if ($formationProgram !== emsp_fs_menum_program_slug()) {
            $formationProgram = '';
        }

        if ($editorial) {
            $upload = $this->handleCoverUpload();
            if ($upload['error'] !== '') {
                flash('danger', $upload['error']);
                redirect('admin/filieres/form' . ($editing ? '?id=' . $id : ''));
            }
            if ($upload['path'] !== '') {
                if ($currentCoverPath !== '' && $currentCoverPath !== $upload['path']) {
                    $this->deleteLocalAsset($currentCoverPath);
                }
                $coverImagePath = $upload['path'];
            } elseif ($removeCover) {
                $this->deleteLocalAsset($currentCoverPath);
                $coverImagePath = '';
            }
        }

        if ($name === '' || $code === '') {
            flash('danger', 'Nom et code obligatoires.');
            redirect('admin/filieres/form' . ($editing ? '?id=' . $id : ''));
        }

        if ($filieres->codeExists($code, $editing ? $id : null)) {
            flash('danger', 'Ce code existe déjà. Choisissez un code unique.');
            redirect('admin/filieres/form' . ($editing ? '?id=' . $id : ''));
        }

        $ok = $filieres->save($editing ? $id : null, [
            'name' => $name,
            'code' => $code,
            'status' => $status,
            'summary' => $summary,
            'description_html' => $descriptionHtml,
            'cover_image_path' => $coverImagePath,
            'formation_program' => $formationProgram,
        ]);

        if (!$ok) {
            flash('danger', "Une erreur est survenue lors de l'enregistrement. Veuillez réessayer.");
            redirect('admin/filieres/form' . ($editing ? '?id=' . $id : ''));
        }

        flash('success', $editing ? 'Filière modifiée.' : 'Filière ajoutée.');
        redirect('admin/filieres');
    }

    /** @return array{path: string, error: string} */
    private function handleCoverUpload(): array
    {
        if (empty($_FILES['cover_image']) || !is_array($_FILES['cover_image'])) {
            return ['path' => '', 'error' => ''];
        }

        $maxSize = emsp_max_upload_size(defined('MAX_UPLOAD_SIZE') ? MAX_UPLOAD_SIZE : (6 * 1024 * 1024));
        $rootDir = dirname(__DIR__, 3);
        $upload = emsp_upload_image_asset(
            $_FILES['cover_image'],
            $rootDir . '/uploads/formations/covers',
            'uploads/formations/covers',
            $maxSize,
            1600,
            900
        );

        return [
            'path' => (string) ($upload['path'] ?? ''),
            'error' => (string) ($upload['error'] ?? ''),
        ];
    }

    private function deleteLocalAsset(?string $path): void
    {
        $path = trim((string) $path);
        if ($path === '' || preg_match('#^https?://#i', $path)) {
            return;
        }
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if (strpos($path, 'uploads/formations/') !== 0) {
            return;
        }
        $rootDir = dirname(__DIR__, 3);
        $absolute = realpath($rootDir . '/' . $path);
        $base = realpath($rootDir . '/uploads/formations');
        if ($absolute !== false && $base !== false && strpos($absolute, $base) === 0 && is_file($absolute)) {
            @unlink($absolute);
        }
    }
}
