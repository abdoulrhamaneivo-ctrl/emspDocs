<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-<?= h($config['icon']) ?> me-2 text-primary"></i><?= h($config['label']) ?></h5>
    <a href="<?= url($config['route'] . '/form') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Ajouter</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th><th>Nom</th><th><?= h($config['parent_label']) ?></th>
                    <?php if ($config['table'] === 'matieres'): ?><th>Niveau</th><?php endif; ?>
                    <th>Statut</th><th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Aucun élément enregistré.</td></tr>
            <?php else: foreach ($items as $item): ?>
                <tr>
                    <td class="text-muted small"><?= (int) $item['id'] ?></td>
                    <td class="fw-medium"><?= h((string) $item['name']) ?></td>
                    <td class="text-muted small"><?= h((string) ($item['parent_name'] ?? 'Non renseigné')) ?></td>
                    <?php if ($config['table'] === 'matieres'): ?>
                        <td class="text-muted small"><?= h((string) ($item['licence_name'] ?? 'Non renseigné')) ?></td>
                    <?php endif; ?>
                    <td>
                        <span class="badge <?= $item['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                            <?= $item['status'] === 'active' ? 'Actif' : 'Inactif' ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            <a href="<?= url($config['route'] . '/form?id=' . (int) $item['id']) ?>" class="btn btn-sm btn-outline-primary" title="Modifier"><i class="bi bi-pencil"></i></a>
                            <form method="post" action="<?= url($config['route']) ?>" onsubmit="return confirm('Changer le statut de cet élément ?');">
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
