<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\Admin\UserAdminRepository;

final class UsersController extends AdminController
{
    public function index(): void
    {
        [$con, $adminUser] = $this->guard();
        $users = new UserAdminRepository($con);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action'], $_POST['user_id'])) {
            $this->handleQuickAction($users, $adminUser);
            return;
        }

        $role = trim((string) ($_GET['role'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $search = trim((string) ($_GET['q'] ?? ''));
        $perPage = emsp_per_page_from_request(25);
        $pageNum = max(1, (int) ($_GET['page'] ?? 1));

        [, $total] = $users->filtered($role, $status, $search, 1, 0);
        $pagination = emsp_paginate($total, $pageNum, $perPage);
        [$rows] = $users->filtered($role, $status, $search, $pagination['perPage'], $pagination['offset']);

        $this->view('admin/users/index', [
            'users' => $rows,
            'total' => $total,
            'filterRole' => $role,
            'filterStatus' => $status,
            'search' => $search,
            'pageNum' => $pagination['page'],
            'totalPages' => $pagination['totalPages'],
            'pagination' => $pagination,
            'adminUser' => $adminUser,
        ]);
    }

    private function handleQuickAction(UserAdminRepository $users, array $adminUser): void
    {
        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            flash('danger', 'Token de sécurité invalide.');
            redirect('admin/utilisateurs');
        }

        $uid = (int) $_POST['user_id'];
        $action = (string) $_POST['action'];

        if ($uid === (int) $adminUser['id']) {
            flash('warning', 'Vous ne pouvez pas modifier votre propre compte ici.');
            redirect('admin/utilisateurs');
        }

        $targetRole = $users->findRoleById($uid);
        if ($targetRole === null || !emsp_can_manage_user_account($adminUser, ['role' => $targetRole])) {
            flash('warning', "Seul un administrateur peut modifier un compte admin ou toucher aux rôles.");
            redirect('admin/utilisateurs');
        }

        $map = ['activate' => 'active', 'suspend' => 'suspended', 'reject' => 'rejected'];
        if (isset($map[$action])) {
            $users->updateStatus($uid, $map[$action]);
            $auditActions = ['activate' => 'account_activated', 'suspend' => 'account_suspended', 'reject' => 'account_rejected'];
            log_audit($con, (int) $adminUser['id'], $auditActions[$action] ?? 'account_updated', 'user', $uid, $map[$action]);
            flash('info', "Le statut de l'utilisateur a été modifié.");
        }

        redirect('admin/utilisateurs');
    }

    public function create(): void
    {
        [$con, $adminUser] = $this->guard();

        $academic = new \App\Repositories\AcademicRepository($con);

        $this->view('admin/users/create', [
            'filieres' => $academic->activeFilieres(),
            'licences' => $academic->activeLicences(),
            'canAssignRoles' => emsp_can_assign_user_roles($adminUser),
        ]);
    }

    public function store(): void
    {
        [$con, $adminUser] = $this->guard();

        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            flash('danger', 'Token de sécurité invalide.');
            redirect('admin/utilisateurs/nouveau');
        }

        $canAssignRoles = emsp_can_assign_user_roles($adminUser);
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = $canAssignRoles ? trim((string) ($_POST['role'] ?? 'etudiant')) : 'etudiant';
        $status = trim((string) ($_POST['status'] ?? 'active'));
        $filiereId = (int) ($_POST['filiere_id'] ?? 0) ?: null;
        $licenceId = (int) ($_POST['licence_id'] ?? 0) ?: null;

        if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
            flash('danger', 'Tous les champs obligatoires doivent être remplis.');
            redirect('admin/utilisateurs/nouveau');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('danger', 'Adresse email invalide.');
            redirect('admin/utilisateurs/nouveau');
        }
        if (strlen($password) < 8) {
            flash('danger', 'Le mot de passe doit contenir au moins 8 caractères.');
            redirect('admin/utilisateurs/nouveau');
        }
        if (!in_array($role, ['etudiant', 'moderateur', 'admin'], true)) {
            flash('danger', 'Rôle invalide.');
            redirect('admin/utilisateurs/nouveau');
        }
        if (!in_array($status, ['active', 'pending', 'suspended'], true)) {
            flash('danger', 'Statut invalide.');
            redirect('admin/utilisateurs/nouveau');
        }

        $users = new UserAdminRepository($con);
        if ($users->emailExists($email)) {
            flash('danger', 'Cette adresse email est déjà utilisée.');
            redirect('admin/utilisateurs/nouveau');
        }

        try {
            $users->create($firstName, $lastName, $email, $password, $role, $status, $filiereId, $licenceId);
            flash('success', 'Utilisateur créé avec succès.');
            redirect('admin/utilisateurs');
        } catch (\Throwable $e) {
            error_log('EMSP admin add-user failed: ' . $e->getMessage());
            flash('danger', 'Une erreur est survenue lors de la création du compte.');
            redirect('admin/utilisateurs/nouveau');
        }
    }

    public function edit(array $params): void
    {
        [$con, $adminUser] = $this->guard();
        $id = (int) ($params['id'] ?? ($_GET['id'] ?? 0));
        if ($id <= 0) {
            redirect('admin/utilisateurs');
        }

        $users = new UserAdminRepository($con);
        $targetRole = $users->findRoleById($id);
        if ($targetRole === null) {
            redirect('admin/utilisateurs');
        }
        if (!emsp_can_manage_user_account($adminUser, ['role' => $targetRole])) {
            flash('warning', "Seul un administrateur peut modifier un compte admin ou changer les rôles.");
            redirect('admin/utilisateurs');
        }

        $user = $users->findForEdit($id);
        if (!$user) {
            redirect('admin/utilisateurs');
        }

        $this->view('admin/users/edit', [
            'user' => $user,
            'canAssignRoles' => emsp_can_assign_user_roles($adminUser),
        ]);
    }

    public function update(array $params): void
    {
        [$con, $adminUser] = $this->guard();
        $id = (int) ($params['id'] ?? ($_POST['id'] ?? 0));
        if ($id <= 0) {
            redirect('admin/utilisateurs');
        }

        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            flash('danger', 'Token de sécurité invalide.');
            redirect('admin/utilisateurs/' . $id . '/modifier');
        }

        $users = new UserAdminRepository($con);
        $currentTargetRole = $users->findRoleById($id);
        if ($currentTargetRole === null || !emsp_can_manage_user_account($adminUser, ['role' => $currentTargetRole])) {
            flash('warning', "Seul un administrateur peut modifier un compte admin ou changer les rôles.");
            redirect('admin/utilisateurs');
        }

        $canAssignRoles = emsp_can_assign_user_roles($adminUser);
        $role = $canAssignRoles ? trim((string) ($_POST['role'] ?? '')) : $currentTargetRole;
        $status = trim((string) ($_POST['status'] ?? ''));

        if (!in_array($role, ['etudiant', 'moderateur', 'admin'], true) || !in_array($status, ['active', 'pending', 'suspended', 'rejected'], true)) {
            flash('danger', 'Valeurs invalides.');
            redirect('admin/utilisateurs/' . $id . '/modifier');
        }

        $users->updateRoleStatus($id, $role, $status);
        flash('success', 'Utilisateur mis à jour.');
        redirect('admin/utilisateurs');
    }
}
