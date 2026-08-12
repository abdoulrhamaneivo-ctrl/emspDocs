<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0"><i class="bi bi-<?= h($config['icon']) ?> text-primary me-2"></i>
        <?= $id > 0 ? 'Modifier ' . h($config['singular']) : 'Ajouter ' . ($config['singular'] === 'module' ? 'un ' : 'une ') . h($config['singular']) ?>
    </h1>
    <a href="<?= url($config['route']) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
</div>

<div class="card" style="max-width:560px;">
    <div class="card-body">
        <form method="post" action="<?= url($config['route'] . '/form') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $id ?>">

            <div class="mb-3">
                <label class="form-label fw-semibold">Nom <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name" required value="<?= h((string) ($item['name'] ?? '')) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold"><?= h($config['parent_label']) ?></label>
                <select class="form-select" name="parent_id">
                    <option value="">-- --</option>
                    <?php foreach ($parentOptions as $opt): ?>
                        <option value="<?= (int) $opt['id'] ?>" <?= (int) ($item[$config['parent_column']] ?? 0) === (int) $opt['id'] ? 'selected' : '' ?>>
                            <?= h((string) $opt['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Statut</label>
                <select class="form-select" name="status">
                    <option value="active" <?= ($item['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Actif</option>
                    <option value="inactive" <?= ($item['status'] ?? 'active') === 'inactive' ? 'selected' : '' ?>>Inactif</option>
                </select>
            </div>

            <div class="emsp-admin-sticky-actions d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Enregistrer</button>
                <a href="<?= url($config['route']) ?>" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
