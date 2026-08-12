<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-chat-dots me-2 text-primary"></i>Modération des commentaires
        <span class="badge bg-secondary ms-2"><?= (int) $total ?></span>
    </h5>
</div>

<div class="d-flex flex-wrap gap-2 mb-2">
    <?php foreach (['visible' => 'Visibles', 'hidden' => 'Masqués', 'pending' => 'En attente', 'deleted' => 'Supprimés', '' => 'Tous'] as $value => $label): ?>
        <a href="<?= url('admin/moderation-commentaires?' . http_build_query(['status' => $value, 'source' => $fSource])) ?>"
           class="btn btn-sm <?= $fStatus === $value ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="<?= url('admin/moderation-commentaires?' . http_build_query(['status' => $fStatus, 'source' => ''])) ?>"
       class="btn btn-sm <?= $fSource === '' ? 'btn-dark' : 'btn-outline-dark' ?>">Toutes sources</a>
    <?php foreach ($sourceLabels as $key => $label): ?>
        <a href="<?= url('admin/moderation-commentaires?' . http_build_query(['status' => $fStatus, 'source' => $key])) ?>"
           class="btn btn-sm <?= $fSource === $key ? 'btn-dark' : 'btn-outline-dark' ?>">
            <?= h($label) ?> <span class="badge bg-light text-dark"><?= (int) ($sourceCounts[$key] ?? 0) ?></span>
        </a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>Source</th><th>Auteur</th><th>Contenu</th><th>Cible</th><th>Statut</th><th>Date</th><th class="text-center">Actions</th></tr>
            </thead>
            <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Aucun élément à modérer.</td></tr>
            <?php else: foreach ($items as $item):
                $statusClasses = ['visible' => 'bg-success', 'hidden' => 'bg-warning text-dark', 'pending' => 'bg-secondary', 'deleted' => 'bg-danger'];
                $statusLabels = ['visible' => 'Visible', 'hidden' => 'Masqué', 'pending' => 'En attente', 'deleted' => 'Supprimé'];
                $content = (string) $item['content'];
                $short = mb_strlen($content) > 180 ? (mb_substr($content, 0, 180) . '…') : $content;
            ?>
                <tr>
                    <td class="small"><span class="badge bg-light text-dark border"><?= h((string) $item['source_label']) ?></span></td>
                    <td class="small fw-medium"><?= h((string) $item['author_name']) ?></td>
                    <td style="max-width:320px;"><span class="d-block small" title="<?= h($content) ?>"><?= h($short) ?></span></td>
                    <td style="max-width:220px;">
                        <a href="<?= h((string) ($item['target_url'] ?? '#')) ?>" class="text-truncate d-block small" target="_blank" rel="noopener">
                            <?= h((string) ($item['target_title'] ?? 'Élément cible')) ?>
                        </a>
                    </td>
                    <td><span class="badge <?= $statusClasses[(string) $item['status']] ?? 'bg-secondary' ?>"><?= h($statusLabels[(string) $item['status']] ?? (string) $item['status']) ?></span></td>
                    <td class="text-muted small"><?= h(date('d/m/Y H:i', strtotime((string) ($item['created_at'] ?? 'now')))) ?></td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            <?php if ($item['status'] !== 'visible'): ?>
                                <form method="post" action="<?= url('admin/moderation-commentaires?' . http_build_query(['status' => $fStatus, 'source' => $fSource, 'page' => $pageNum])) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                    <input type="hidden" name="source_type" value="<?= h((string) $item['source_type']) ?>">
                                    <input type="hidden" name="action" value="visible">
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Rendre visible"><i class="bi bi-eye"></i></button>
                                </form>
                            <?php endif; ?>
                            <?php if ($item['status'] === 'visible'): ?>
                                <form method="post" action="<?= url('admin/moderation-commentaires?' . http_build_query(['status' => $fStatus, 'source' => $fSource, 'page' => $pageNum])) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                    <input type="hidden" name="source_type" value="<?= h((string) $item['source_type']) ?>">
                                    <input type="hidden" name="action" value="hidden">
                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Masquer"><i class="bi bi-eye-slash"></i></button>
                                </form>
                            <?php endif; ?>
                            <?php if ($item['status'] !== 'deleted'): ?>
                                <form method="post" action="<?= url('admin/moderation-commentaires?' . http_build_query(['status' => $fStatus, 'source' => $fSource, 'page' => $pageNum])) ?>"
                                      onsubmit="return confirm('Supprimer cet élément ? Cette action est irréversible.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                    <input type="hidden" name="source_type" value="<?= h((string) $item['source_type']) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination justify-content-center">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= $p === $pageNum ? 'active' : '' ?>">
                    <a class="page-link" href="<?= url('admin/moderation-commentaires?' . http_build_query(['status' => $fStatus, 'source' => $fSource, 'page' => $p])) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
