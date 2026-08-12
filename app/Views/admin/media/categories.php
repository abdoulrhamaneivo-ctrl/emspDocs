<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="mb-0 fw-bold"><i class="bi bi-collection me-2 text-primary"></i>Catégories médias</h5>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/mediatheque') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Médiathèque
        </a>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#formCat">
            <i class="bi bi-plus-lg me-1"></i>Ajouter
        </button>
    </div>
</div>

<div id="formCat" class="collapse mb-3">
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post" action="<?= url('admin/mediatheque/categories') ?>" class="row g-2">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <div class="col-md-4">
                    <label class="form-label fw-semibold visually-hidden" for="newCatName">Nom</label>
                    <input id="newCatName" name="name" class="form-control" placeholder="Nom de la catégorie" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold visually-hidden" for="newCatDesc">Description</label>
                    <input id="newCatDesc" name="description" class="form-control" placeholder="Description (optionnel)">
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-success" type="submit">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive d-none d-md-block">
        <table class="table table-admin table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Description</th>
                    <th>Créée le</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($categories)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">Aucune catégorie enregistrée.</td></tr>
            <?php else: foreach ($categories as $c): ?>
                <tr>
                    <td><?= h($c['name']) ?></td>
                    <td class="text-muted small"><?= ($c['description'] ?? '') !== '' ? h($c['description']) : '—' ?></td>
                    <td class="text-muted small"><?= h((string) ($c['created_at'] ?? '')) ?></td>
                    <td class="text-end">
                        <div class="d-flex gap-2 justify-content-end align-items-center">
                            <button class="btn btn-sm btn-outline-primary" type="button"
                                    onclick="emspEditCat(<?= (int) $c['id'] ?>, <?= json_encode((string) $c['name'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, <?= json_encode((string) ($c['description'] ?? ''), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)">
                                Modifier
                            </button>
                            <form method="post" class="m-0"
                                  onsubmit="return confirm('Supprimer cette catégorie ? Les médias associés seront détachés.');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card-body d-md-none">
        <?php if (empty($categories)): ?>
            <p class="text-muted text-center mb-0">Aucune catégorie enregistrée.</p>
        <?php else: foreach ($categories as $c): ?>
            <div class="border rounded p-3 mb-3">
                <h6 class="fw-bold mb-1"><?= h($c['name']) ?></h6>
                <p class="small text-muted mb-2"><?= ($c['description'] ?? '') !== '' ? h($c['description']) : 'Sans description' ?></p>
                <form method="post" class="row g-2 mb-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                    <div class="col-12"><input name="name" class="form-control form-control-sm" value="<?= h($c['name']) ?>" required></div>
                    <div class="col-12"><input name="description" class="form-control form-control-sm" value="<?= h((string) ($c['description'] ?? '')) ?>" placeholder="Description"></div>
                    <div class="col-12"><button class="btn btn-sm btn-outline-primary w-100" type="submit">Mettre à jour</button></div>
                </form>
                <form method="post" onsubmit="return confirm('Supprimer cette catégorie ?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger w-100" type="submit"><i class="bi bi-trash me-1"></i>Supprimer</button>
                </form>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<div class="modal fade" id="editCatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Modifier la catégorie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="<?= url('admin/mediatheque/categories') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editCatId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="editCatName">Nom <span class="text-danger">*</span></label>
                        <input name="name" id="editCatName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="editCatDesc">Description</label>
                        <input name="description" id="editCatDesc" class="form-control" placeholder="Optionnel">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function emspEditCat(id, name, desc) {
    document.getElementById('editCatId').value = id;
    document.getElementById('editCatName').value = name;
    document.getElementById('editCatDesc').value = desc || '';
    new bootstrap.Modal(document.getElementById('editCatModal')).show();
}
</script>
