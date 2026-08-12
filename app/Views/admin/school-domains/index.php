<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-envelope-at me-2 text-primary"></i>Domaines email école</h5>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="post" action="<?= url('admin/domaines-email') ?>" class="row g-2 align-items-end">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <div class="col-md-6">
                <label class="form-label">Nouveau domaine</label>
                <input type="text" name="domain" class="form-control" placeholder="@emsp.int" required>
                <div class="form-text">Exemple : @emsp.int, @emsp.edu</div>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-lg me-1"></i>Ajouter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Domaine</th><th>Statut</th><th class="text-center">Actions</th></tr></thead>
            <tbody>
            <?php if (empty($domains)): ?>
                <tr><td colspan="3" class="text-center text-muted py-4">Aucun domaine enregistré.</td></tr>
            <?php else: foreach ($domains as $d): ?>
                <tr>
                    <td>
                        <form method="post" action="<?= url('admin/domaines-email') ?>" class="d-flex gap-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                            <input type="text" name="domain" class="form-control form-control-sm" value="<?= h((string) $d['domain']) ?>">
                            <button type="submit" class="btn btn-sm btn-outline-primary">Enregistrer</button>
                        </form>
                    </td>
                    <td><span class="badge <?= $d['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>"><?= $d['status'] === 'active' ? 'Actif' : 'Inactif' ?></span></td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            <form method="post" action="<?= url('admin/domaines-email') ?>">
                                <?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi <?= $d['status'] === 'active' ? 'bi-toggle-on text-warning' : 'bi-toggle-off text-success' ?>"></i></button>
                            </form>
                            <form method="post" action="<?= url('admin/domaines-email') ?>" onsubmit="return confirm('Supprimer ce domaine ?');">
                                <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
