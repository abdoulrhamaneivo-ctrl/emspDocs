<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-mortarboard me-2 text-primary"></i>Niveaux / Licences</h5>
    <a href="<?= url('admin/licences/form') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Ajouter</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>#</th><th>Nom</th><th>Filières associées</th><th>Statut</th><th class="text-center">Actions</th></tr></thead>
            <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Aucun niveau enregistré.</td></tr>
            <?php else: foreach ($items as $item): ?>
                <tr>
                    <td class="text-muted small"><?= (int) $item['id'] ?></td>
                    <td class="fw-medium"><?= h((string) $item['name']) ?></td>
                    <td class="text-muted small"><?= h((string) ($item['filiere_names'] ?? '') ?: 'Aucune') ?></td>
                    <td>
                        <span class="badge <?= $item['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                            <?= $item['status'] === 'active' ? 'Actif' : 'Inactif' ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            <a href="<?= url('admin/licences/form?id=' . (int) $item['id']) ?>" class="btn btn-sm btn-outline-primary" title="Modifier"><i class="bi bi-pencil"></i></a>
                            <form method="post" action="<?= url('admin/licences') ?>" onsubmit="return confirm('Changer le statut de ce niveau ?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Activer/désactiver">
                                    <i class="bi <?= $item['status'] === 'active' ? 'bi-toggle-on text-warning' : 'bi-toggle-off text-success' ?>"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
