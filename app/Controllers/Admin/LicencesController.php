<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\Admin\LicenceAdminRepository;

final class LicencesController extends AdminController
{
    public function index(): void
    {
        [$con] = $this->guard();
        $licences = new LicenceAdminRepository($con);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
            if (!verify_csrf($_POST['csrf_token'] ?? null)) {
                flash('danger', 'Token de sécurité invalide.');
                redirect('admin/licences');
            }
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                $licences->toggleStatus($id);
            }
            flash('info', 'Statut mis à jour.');
            redirect('admin/licences');
        }

        $this->view('admin/licences/index', ['items' => $licences->all()]);
    }

    public function form(): void
    {
        [$con] = $this->guard();
        $licences = new LicenceAdminRepository($con);

        $id = (int) ($_GET['id'] ?? 0);
        $item = $id > 0 ? $licences->find($id) : null;
        $selected = $id > 0 ? $licences->selectedFiliereIds($id) : [];

        $this->view('admin/licences/form', [
            'id' => $id,
            'item' => $item,
            'selectedFilieres' => $selected,
            'filieres' => $licences->activeFilieres(),
        ]);
    }

    public function save(): void
    {
        [$con] = $this->guard();
        $licences = new LicenceAdminRepository($con);

        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            flash('danger', 'Token de sécurité invalide.');
            redirect('admin/licences');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        $filiereIds = array_map('intval', (array) ($_POST['filiere_ids'] ?? []));

        if ($name === '') {
            flash('danger', 'Le nom est obligatoire.');
            redirect('admin/licences/form' . ($id > 0 ? '?id=' . $id : ''));
        }

        if (!$licences->save($id, $name, $status, $filiereIds)) {
            flash('danger', 'Erreur lors de la sauvegarde. Réessayez.');
            redirect('admin/licences/form' . ($id > 0 ? '?id=' . $id : ''));
        }

        flash('success', $id > 0 ? 'Niveau modifié.' : 'Niveau ajouté.');
        redirect('admin/licences');
    }
}
