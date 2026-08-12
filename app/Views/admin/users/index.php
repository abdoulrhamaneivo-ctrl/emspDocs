<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-people text-primary me-2"></i>Utilisateurs
            <span class="badge rounded-pill bg-body-tertiary text-body-secondary border ms-1"><?= (int) $total ?></span>
        </h1>
        <p class="text-body-secondary small mb-0">Gérez les comptes étudiants, modérateurs et administrateurs.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/validation-comptes') ?>" class="btn btn-outline-warning btn-sm"><i class="bi bi-person-check me-1"></i>Validation</a>
        <a href="<?= url('admin/utilisateurs/nouveau') ?>" class="btn btn-primary btn-sm"><i class="bi bi-person-plus me-1"></i>Ajouter</a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('admin/utilisateurs') ?>" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label small text-body-secondary mb-1">Rechercher</label>
                <input type="text" class="form-control" name="q" placeholder="Nom, prénom ou email..." value="<?= h($search) ?>">
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label small text-body-secondary mb-1">Rôle</label>
                <select class="form-select" name="role">
                    <option value="">Tous</option>
                    <option value="etudiant" <?= $filterRole === 'etudiant' ? 'selected' : '' ?>>Étudiant</option>
                    <option value="moderateur" <?= $filterRole === 'moderateur' ? 'selected' : '' ?>>Modérateur</option>
                    <option value="admin" <?= $filterRole === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label small text-body-secondary mb-1">Statut</label>
                <select class="form-select" name="status">
                    <option value="">Tous</option>
                    <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Actif</option>
                    <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>En attente</option>
                    <option value="suspended" <?= $filterStatus === 'suspended' ? 'selected' : '' ?>>Suspendu</option>
                    <option value="rejected" <?= $filterStatus === 'rejected' ? 'selected' : '' ?>>Rejeté</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i>Filtrer</button>
                <a href="<?= url('admin/utilisateurs') ?>" class="btn btn-outline-secondary" title="Réinitialiser"><i class="bi bi-arrow-clockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>#</th><th>Nom</th><th>Email</th><th>Rôle</th><th>Filière</th><th>Badge</th><th>Docs</th><th>Statut</th><th>Inscrit le</th><th class="text-center">Actions</th></tr>
            </thead>
            <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">Aucun utilisateur trouvé.</td></tr>
            <?php else: ?>
                <?php foreach ($users as $u):
                    $canManageTarget = emsp_can_manage_user_account($adminUser, $u);
                    $roleBadge = ['admin' => 'bg-danger', 'moderateur' => 'bg-warning text-dark', 'etudiant' => 'bg-secondary'][$u['role']] ?? 'bg-secondary';
                    $badgeIcons = ['or' => '<span class="badge bg-warning text-dark">OR</span>', 'argent' => '<span class="badge bg-secondary">ARG</span>', 'bronze' => '<span class="badge bg-danger">BR</span>', 'none' => '-'];
                    $statusBadge = ['active' => 'bg-success', 'pending' => 'bg-warning text-dark', 'suspended' => 'bg-danger', 'rejected' => 'bg-danger'][$u['status']] ?? 'bg-secondary';
                    $statusLabel = ['active' => 'Actif', 'pending' => 'En attente', 'suspended' => 'Suspendu', 'rejected' => 'Rejeté'][$u['status']] ?? $u['status'];
                ?>
                    <tr>
                        <td class="text-muted small"><?= (int) $u['id'] ?></td>
                        <td class="fw-medium"><?= h($u['first_name'] . ' ' . $u['last_name']) ?></td>
                        <td class="text-muted small text-truncate" style="max-width:200px;"><?= h($u['email']) ?></td>
                        <td><span class="badge <?= $roleBadge ?>"><?= h(ucfirst((string) $u['role'])) ?></span></td>
                        <td class="text-muted small"><?= h($u['filiere_name'] ?? 'Non renseignée') ?></td>
                        <td><?= $badgeIcons[$u['badge_level']] ?? '-' ?></td>
                        <td class="text-center small"><?= (int) $u['upload_count'] ?></td>
                        <td><span class="badge <?= $statusBadge ?>"><?= h($statusLabel) ?></span></td>
                        <td class="text-muted small"><?= h(date('d/m/Y', strtotime((string) $u['created_at']))) ?></td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <?php if ($canManageTarget): ?>
                                    <a href="<?= url('admin/utilisateurs/' . (int) $u['id'] . '/modifier') ?>" class="btn btn-sm btn-outline-primary" title="Modifier"><i class="bi bi-pencil"></i></a>
                                <?php endif; ?>
                                <?php if ($canManageTarget && $u['status'] === 'suspended'): ?>
                                    <form method="post" action="<?= url('admin/utilisateurs') ?>" onsubmit="return confirm('Réactiver ce compte ?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                        <input type="hidden" name="action" value="activate">
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Réactiver"><i class="bi bi-check-lg"></i></button>
                                    </form>
                                <?php elseif ($canManageTarget && $u['status'] === 'active' && (int) $u['id'] !== (int) $adminUser['id']): ?>
                                    <form method="post" action="<?= url('admin/utilisateurs') ?>" onsubmit="return confirm('Suspendre ce compte ?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                        <input type="hidden" name="action" value="suspend">
                                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Suspendre"><i class="bi bi-pause-circle"></i></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination justify-content-center">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= $p === $pageNum ? 'active' : '' ?>">
                    <a class="page-link" href="<?= url('admin/utilisateurs?' . http_build_query(array_merge($_GET, ['page' => $p]))) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
