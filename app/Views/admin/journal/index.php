<?php
$journalStats = is_array($journalStats ?? null) ? $journalStats : ['filtered' => count($articles), 'published' => 0, 'draft' => 0, 'open' => 0];
$typeBadge = ['annonce' => 'bg-primary', 'defi' => 'bg-warning text-dark', 'sondage' => 'bg-success'];
$typeLabel = ['annonce' => 'Annonce', 'defi' => 'Défi', 'sondage' => 'Sondage'];
$stateBadge = ['draft' => 'bg-secondary', 'open' => 'bg-success', 'scheduled' => 'bg-info text-dark', 'closed' => 'bg-dark', 'expired' => 'bg-warning text-dark'];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-newspaper me-2 text-primary"></i>Journal</h5>
    <a href="<?= url('admin/journal/form') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nouveau contenu</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="card text-center shadow-sm"><div class="card-body py-3"><div class="fs-4 fw-bold"><?= (int) ($journalStats['filtered'] ?? 0) ?></div><div class="small text-muted">Filtrés</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card text-center shadow-sm"><div class="card-body py-3"><div class="fs-4 fw-bold text-success"><?= (int) ($journalStats['published'] ?? 0) ?></div><div class="small text-muted">Publiés</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card text-center shadow-sm"><div class="card-body py-3"><div class="fs-4 fw-bold text-secondary"><?= (int) ($journalStats['draft'] ?? 0) ?></div><div class="small text-muted">Brouillons</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card text-center shadow-sm"><div class="card-body py-3"><div class="fs-4 fw-bold text-info"><?= (int) ($journalStats['open'] ?? 0) ?></div><div class="small text-muted">Ouverts</div></div></div></div>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <?php foreach (['' => 'Tous types', 'annonce' => 'Annonces', 'defi' => 'Défis', 'sondage' => 'Sondages'] as $v => $l): ?>
        <a href="<?= url('admin/journal?' . http_build_query(['type' => $v, 'status' => $statusFilter, 'state' => $stateFilter])) ?>"
           class="btn btn-sm <?= $typeFilter === $v ? 'btn-dark' : 'btn-outline-dark' ?>"><?= $l ?></a>
    <?php endforeach; ?>
    <span class="vr mx-1"></span>
    <?php foreach (['' => 'Tous statuts', 'published' => 'Publiés', 'draft' => 'Brouillons'] as $v => $l): ?>
        <a href="<?= url('admin/journal?' . http_build_query(['type' => $typeFilter, 'status' => $v, 'state' => $stateFilter])) ?>"
           class="btn btn-sm <?= $statusFilter === $v ? 'btn-primary' : 'btn-outline-primary' ?>"><?= $l ?></a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-mobile align-middle mb-0">
            <thead><tr><th>Titre</th><th>Type</th><th>Auteur</th><th>Statut</th><th>État</th><th>Créé le</th><th class="text-center">Actions</th></tr></thead>
            <tbody>
            <?php if (empty($articles)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Aucun contenu.</td></tr>
            <?php else: foreach ($articles as $a): $stateCode = $a['state']['code'] ?? 'draft'; ?>
                <tr>
                    <td class="fw-medium" style="max-width:280px;" data-label="Titre">
                        <div class="text-truncate" title="<?= h((string) $a['title']) ?>"><?= h((string) $a['title']) ?></div>
                        <div class="small text-muted text-truncate"><?= h((string) $a['excerpt']) ?></div>
                    </td>
                    <td data-label="Type"><span class="badge <?= $typeBadge[$a['type']] ?? 'bg-secondary' ?>"><?= h($typeLabel[$a['type']] ?? $a['type']) ?></span></td>
                    <td class="small text-muted" data-label="Auteur"><?= h((string) ($a['author_label'] ?? '')) ?: '—' ?></td>
                    <td data-label="Statut"><span class="badge <?= $a['status'] === 'published' ? 'bg-success' : 'bg-secondary' ?>"><?= $a['status'] === 'published' ? 'Publié' : 'Brouillon' ?></span></td>
                    <td data-label="État"><span class="badge <?= $stateBadge[$stateCode] ?? 'bg-secondary' ?>"><?= h((string) ($a['state']['label'] ?? $stateCode)) ?></span></td>
                    <td class="small text-muted" data-label="Créé le"><?= h(date('d/m/Y', strtotime((string) $a['created_at']))) ?></td>
                    <td class="text-center" data-label="Actions">
                        <div class="d-flex gap-1 justify-content-center flex-wrap">
                            <a href="<?= url('admin/journal/form?id=' . (int) $a['id']) ?>" class="btn btn-sm btn-outline-primary" title="Modifier"><i class="bi bi-pencil"></i></a>
                            <a href="<?= url('admin/journal?details_id=' . (int) $a['id'] . '&format=json') ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Détails (JSON)"><i class="bi bi-info-circle"></i></a>
                            <?php if ($a['type'] === 'defi'): ?>
                                <a href="<?= url('admin/journal?export_defi_id=' . (int) $a['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Export CSV"><i class="bi bi-download"></i></a>
                            <?php endif; ?>

                            <?php if ($a['status'] !== 'published'): ?>
                                <form method="post" action="<?= url('admin/journal?' . http_build_query(['type' => $typeFilter, 'status' => $statusFilter, 'state' => $stateFilter])) ?>">
                                    <?= csrf_field() ?><input type="hidden" name="toggle_id" value="<?= (int) $a['id'] ?>"><input type="hidden" name="new_status" value="published">
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Publier"><i class="bi bi-upload"></i></button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="<?= url('admin/journal?' . http_build_query(['type' => $typeFilter, 'status' => $statusFilter, 'state' => $stateFilter])) ?>">
                                    <?= csrf_field() ?><input type="hidden" name="toggle_id" value="<?= (int) $a['id'] ?>"><input type="hidden" name="new_status" value="draft">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="Repasser en brouillon"><i class="bi bi-file-earmark"></i></button>
                                </form>
                            <?php endif; ?>

                            <?php if ($hasClosedAt && $hasClosedBy && in_array($a['type'], ['sondage', 'defi'], true)): ?>
                                <?php if (empty($a['closed_at'])): ?>
                                    <form method="post" action="<?= url('admin/journal?' . http_build_query(['type' => $typeFilter, 'status' => $statusFilter, 'state' => $stateFilter])) ?>" onsubmit="return confirm('Fermer ce contenu ?');">
                                        <?= csrf_field() ?><input type="hidden" name="close_id" value="<?= (int) $a['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-dark" title="Fermer"><i class="bi bi-lock"></i></button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="<?= url('admin/journal?' . http_build_query(['type' => $typeFilter, 'status' => $statusFilter, 'state' => $stateFilter])) ?>">
                                        <?= csrf_field() ?><input type="hidden" name="reopen_id" value="<?= (int) $a['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-dark" title="Rouvrir"><i class="bi bi-unlock"></i></button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>

                            <form method="post" action="<?= url('admin/journal?' . http_build_query(['type' => $typeFilter, 'status' => $statusFilter, 'state' => $stateFilter])) ?>" onsubmit="return confirm('Supprimer définitivement ce contenu ?');">
                                <?= csrf_field() ?><input type="hidden" name="delete_id" value="<?= (int) $a['id'] ?>">
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

<?php
emsp_include_pagination($pagination ?? [], 'admin/journal', [
    'type' => $typeFilter,
    'status' => $statusFilter,
    'state' => $stateFilter,
]);
?>
