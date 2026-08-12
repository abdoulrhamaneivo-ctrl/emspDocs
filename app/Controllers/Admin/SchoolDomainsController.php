<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\Admin\SchoolDomainRepository;

final class SchoolDomainsController extends AdminController
{
    public function index(): void
    {
        [$con, $adminUser] = $this->guard();

        if (!emsp_can_assign_user_roles($adminUser)) {
            flash('warning', 'Seul un administrateur peut gérer les domaines email.');
            redirect('admin/journal');
        }

        $domains = new SchoolDomainRepository($con);
        $domains->ensureTableExists();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action'])) {
            $this->handlePost($domains);
            return;
        }

        $this->view('admin/school-domains/index', ['domains' => $domains->all()]);
    }

    private function handlePost(SchoolDomainRepository $domains): void
    {
        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            flash('danger', 'Token de sécurité invalide.');
            redirect('admin/domaines-email');
        }

        $action = (string) $_POST['action'];

        if ($action === 'add') {
            $domain = SchoolDomainRepository::normalize((string) ($_POST['domain'] ?? ''));
            if ($domain === '' || !SchoolDomainRepository::isValid($domain)) {
                flash('warning', 'Domaine invalide. Exemple attendu : @emsp.int');
                redirect('admin/domaines-email');
            }
            $domains->add($domain);
            flash('success', "Le domaine est maintenant autorisé.");
            redirect('admin/domaines-email');
        }

        if ($action === 'update' && isset($_POST['id'])) {
            $id = (int) $_POST['id'];
            $domain = SchoolDomainRepository::normalize((string) ($_POST['domain'] ?? ''));
            if ($id <= 0 || $domain === '' || !SchoolDomainRepository::isValid($domain)) {
                flash('warning', 'Domaine invalide. Exemple attendu : @emsp.int');
                redirect('admin/domaines-email');
            }
            $domains->update($id, $domain);
            flash('success', 'Le domaine a été mis à jour.');
            redirect('admin/domaines-email');
        }

        if ($action === 'toggle' && isset($_POST['id'])) {
            $id = (int) $_POST['id'];
            if ($id > 0) {
                $domains->toggle($id);
            }
            redirect('admin/domaines-email');
        }

        if ($action === 'delete' && isset($_POST['id'])) {
            $id = (int) $_POST['id'];
            if ($id > 0) {
                $domains->delete($id);
            }
            flash('info', 'Le domaine a été retiré.');
            redirect('admin/domaines-email');
        }

        redirect('admin/domaines-email');
    }
}
