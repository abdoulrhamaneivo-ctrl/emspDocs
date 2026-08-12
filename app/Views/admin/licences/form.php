<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0"><i class="bi bi-mortarboard text-primary me-2"></i><?= $id > 0 ? 'Modifier le niveau' : 'Ajouter un niveau' ?></h1>
    <a href="<?= url('admin/licences') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
</div>

<div class="card" style="max-width:560px;">
    <div class="card-body">
        <form method="post" action="<?= url('admin/licences/form') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $id ?>">

            <div class="mb-3">
                <label class="form-label fw-semibold">Nom <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name" required value="<?= h((string) ($item['name'] ?? '')) ?>" placeholder="Ex : Licence 1, Master 2...">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Statut</label>
                <select class="form-select" name="status">
                    <option value="active" <?= ($item['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Actif</option>
                    <option value="inactive" <?= ($item['status'] ?? 'active') === 'inactive' ? 'selected' : '' ?>>Inactif</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Filières associées</label>
                <select class="form-select" name="filiere_ids[]" multiple size="7">
                    <?php foreach ($filieres as $f): ?>
                        <option value="<?= (int) $f['id'] ?>" <?= in_array((int) $f['id'], $selectedFilieres, true) ? 'selected' : '' ?>>
                            <?= h((string) $f['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Sélectionne une ou plusieurs filières concernées (Ctrl/Cmd + clic).</div>
            </div>

            <div class="emsp-admin-sticky-actions d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Enregistrer</button>
                <a href="<?= url('admin/licences') ?>" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
