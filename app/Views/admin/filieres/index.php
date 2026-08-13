<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-diagram-3 me-2 text-primary"></i>Filières — photos & présentation</h5>
    <a href="<?= url('admin/filieres/form') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Ajouter</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nom</th>
                    <?php if (!empty($programColumnEnabled ?? false)): ?><th>Programme</th><?php endif; ?>
                    <?php if ($editorialEnabled): ?><th>Photo</th><th>Description</th><?php endif; ?>
                    <th>Statut</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="<?= ($editorialEnabled ? 6 : 4) + (!empty($programColumnEnabled) ? 1 : 0) ?>" class="text-center text-muted py-4">Aucune filière enregistrée.</td></tr>
            <?php else: foreach ($items as $i): ?>
                <?php
                $coverSrc = function_exists('emsp_formation_image_src')
                    ? emsp_formation_image_src((string) ($i['cover_image_path'] ?? ''))
                    : '';
                ?>
                <tr>
                    <td class="text-muted small"><?= (int) $i['id'] ?></td>
                    <td class="fw-medium"><?= h((string) $i['name']) ?></td>
                    <?php if (!empty($programColumnEnabled)): ?>
                        <td>
                            <?php if (($i['formation_program'] ?? '') === 'fs-menum'): ?>
                                <span class="badge bg-warning text-dark">FS-MENUM</span>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                    <?php if ($editorialEnabled): ?>
                        <td>
                            <?php if ($coverSrc !== ''): ?>
                                <img src="<?= h($coverSrc) ?>" alt="" class="rounded border" width="72" height="48" style="object-fit:cover;">
                            <?php else: ?>
                                <span class="badge bg-secondary">Manquante</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small" style="max-width:16rem;"><?= h(function_exists('emsp_formation_summary') ? emsp_formation_summary($i, 90) : '') ?></td>
                    <?php endif; ?>
                    <td>
                        <span class="badge <?= $i['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                            <?= $i['status'] === 'active' ? 'Actif' : 'Inactif' ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center flex-wrap">
                            <a href="<?= url('admin/filieres/form?id=' . (int) $i['id']) ?>" class="btn btn-sm btn-outline-primary" title="Modifier"><i class="bi bi-pencil"></i></a>
                            <form method="post" action="<?= url('admin/filieres') ?>" onsubmit="return confirm('Changer le statut de cette filière ?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= (int) $i['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Activer/désactiver">
                                    <i class="bi <?= $i['status'] === 'active' ? 'bi-toggle-on text-warning' : 'bi-toggle-off text-success' ?>"></i>
                                </button>
                            </form>
                            <form method="post" action="<?= url('admin/filieres') ?>" onsubmit="return confirm('Supprimer définitivement cette filière ?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $i['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php emsp_include_pagination($pagination ?? [], 'admin/filieres'); ?>
