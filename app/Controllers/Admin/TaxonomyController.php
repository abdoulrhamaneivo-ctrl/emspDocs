<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\Admin\TaxonomyRepository;

final class TaxonomyController extends AdminController
{
    /** Config des types de référentiel supportés par ce contrôleur générique. */
    private const TYPES = [
        'matieres' => [
            'table' => 'matieres', 'label' => 'Matières', 'singular' => 'matière',
            'icon' => 'book', 'parent_table' => 'modules', 'parent_column' => 'module_id',
            'parent_label' => 'Module associé', 'route' => 'admin/matieres',
        ],
        'modules' => [
            'table' => 'modules', 'label' => 'Modules', 'singular' => 'module',
            'icon' => 'grid', 'parent_table' => 'licences', 'parent_column' => 'licence_id',
            'parent_label' => 'Niveau associé', 'route' => 'admin/modules',
        ],
    ];

    public function index(array $params): void
    {
        [$con] = $this->guard();
        $config = $this->resolveConfig($params);
        $repo = $this->repository($con, $config);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
            $this->handleToggle($repo, $config);
            return;
        }

        $perPage = emsp_per_page_from_request(25);
        $pageNum = max(1, (int) ($_GET['page'] ?? 1));
        [, $total] = $repo->paginated(1, 0);
        $pagination = emsp_paginate($total, $pageNum, $perPage);
        [$items] = $repo->paginated($pagination['perPage'], $pagination['offset']);

        $this->view('admin/taxonomy/index', [
            'config' => $config,
            'items' => $items,
            'pagination' => $pagination,
        ]);
    }

    public function form(array $params): void
    {
        [$con] = $this->guard();
        $config = $this->resolveConfig($params);
        $repo = $this->repository($con, $config);

        $id = (int) ($_GET['id'] ?? 0);
        $item = $id > 0 ? $repo->find($id) : null;

        $this->view('admin/taxonomy/form', [
            'config' => $config,
            'id' => $id,
            'item' => $item,
            'parentOptions' => $repo->parentOptions(),
        ]);
    }

    public function save(array $params): void
    {
        [$con] = $this->guard();
        $config = $this->resolveConfig($params);
        $repo = $this->repository($con, $config);

        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            flash('danger', 'Token de sécurité invalide.');
            redirect($config['route']);
        }

        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $parentId = (int) ($_POST['parent_id'] ?? 0) ?: null;
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

        if ($name === '') {
            flash('danger', 'Le nom est obligatoire.');
            redirect($config['route'] . '/form' . ($id > 0 ? '?id=' . $id : ''));
        }

        $repo->save($id, $name, $parentId, $status);
        flash('success', ucfirst($config['singular']) . ($id > 0 ? ' modifiée avec succès.' : ' ajoutée avec succès.'));
        redirect($config['route']);
    }

    private function handleToggle(TaxonomyRepository $repo, array $config): void
    {
        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            flash('danger', 'Token de sécurité invalide.');
            redirect($config['route']);
        }
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $repo->toggleStatus($id);
        }
        flash('info', 'Statut mis à jour.');
        redirect($config['route']);
    }

    private function resolveConfig(array $params): array
    {
        $type = (string) ($params['type'] ?? '');
        if (!isset(self::TYPES[$type])) {
            redirect('admin/journal');
        }
        return self::TYPES[$type];
    }

    private function repository(\PDO $pdo, array $config): TaxonomyRepository
    {
        return new TaxonomyRepository($pdo, $config['table'], $config['parent_table'], $config['parent_column']);
    }
}
