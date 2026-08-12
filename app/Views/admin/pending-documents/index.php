<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-hourglass-split me-2 text-warning"></i>Documents en attente
        <span class="badge bg-warning text-dark ms-2"><?= count($docs) ?></span>
    </h5>
</div>

<?php if (empty($docs)): ?>
    <div class="card shadow-sm"><div class="card-body text-center py-5 text-muted">
        <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>
        Aucun document en attente. Tout est à jour !
    </div></div>
<?php else: ?>
    <div class="table-responsive card shadow-sm">
        <table class="table table-hover table-mobile mb-0">
            <thead>
                <tr>
                    <th>Titre</th><th>Auteur</th><th>Filière(s)</th><th>Matière</th><th>Taille</th><th>Déposé le</th><th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($docs as $d): ?>
                    <tr>
                        <td class="fw-medium"><?= h($d['title']) ?></td>
                        <td class="text-muted small"><?= h(trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? ''))) ?></td>
                        <td class="text-muted small"><?= h($d['filiere_label']) ?></td>
                        <td class="text-muted small"><?= h($d['matiere_display']) ?></td>
                        <td class="text-muted small"><?= max(1, (int) round(((int) ($d['file_size_bytes'] ?? 0)) / 1024)) ?> Ko</td>
                        <td class="text-muted small"><?= h(date('d/m/Y H:i', strtotime((string) ($d['created_at'] ?? 'now')))) ?></td>
                        <td>
                            <button type="button" class="btn btn-outline-primary review-doc-btn w-100"
                                    data-bs-toggle="modal" data-bs-target="#reviewDocModal"
                                    data-doc-id="<?= (int) $d['id'] ?>"
                                    data-doc-title="<?= h($d['title']) ?>"
                                    data-doc-author="<?= h(trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? ''))) ?>"
                                    data-doc-filiere="<?= h($d['filiere_label']) ?>"
                                    data-doc-licence="<?= h((string) ($d['licence_name'] ?? 'Non renseignée')) ?>"
                                    data-doc-matiere="<?= h((string) ($d['matiere_name'] ?? '')) ?>"
                                    data-doc-matiere-pending="<?= h((string) ($d['matiere_label_pending'] ?? '')) ?>"
                                    data-doc-preview="<?= h(url('admin/preview-doc.php?id=' . (int) $d['id'])) ?>">
                                <i class="bi bi-search me-1"></i>Examiner
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<div class="modal fade emsp-admin-sheet-modal" id="reviewDocModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="reviewDocTitle">Examiner le document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-lg-7">
                        <iframe id="reviewDocFrame" title="Aperçu document" class="w-100 border rounded"
                                style="min-height:420px;background:#fff;"></iframe>
                    </div>
                    <div class="col-lg-5">
                        <div class="border rounded p-3 bg-light mb-3 small">
                            <div class="mb-1"><strong>Auteur :</strong> <span id="reviewDocAuthor"></span></div>
                            <div class="mb-1"><strong>Filière(s) :</strong> <span id="reviewDocFiliere"></span></div>
                            <div class="mb-1"><strong>Niveau :</strong> <span id="reviewDocLicence"></span></div>
                            <div class="mb-0"><strong>Matière actuelle :</strong> <span id="reviewDocMatiere"></span></div>
                        </div>

                        <form method="post" id="reviewDocForm" action="<?= url('admin/validation-documents') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="doc_id" id="reviewDocId">
                            <input type="hidden" name="action" id="reviewDocAction">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Matière existante</label>
                                <select class="form-select" name="review_matiere_id" id="review_matiere_id">
                                    <option value="">-- Choisir une matière existante --</option>
                                    <?php foreach ($availableMatieres as $matiere): ?>
                                        <option value="<?= (int) $matiere['id'] ?>"><?= h($matiere['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Ou corriger / créer la matière</label>
                                <input type="text" class="form-control" name="review_matiere_label" id="review_matiere_label" maxlength="120">
                                <div class="form-text">Si la matière n'existe pas, le libellé corrigé sera créé lors de l'approbation.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Motif de rejet</label>
                                <textarea class="form-control" name="motif" id="reviewMotif" rows="3"
                                          placeholder="Obligatoire uniquement si vous refusez le document."></textarea>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-success" id="approveDocBtn">Approuver</button>
                                <button type="button" class="btn btn-outline-danger" id="rejectDocBtn">Rejeter</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var frame = document.getElementById('reviewDocFrame');
    var form = document.getElementById('reviewDocForm');
    var actionInput = document.getElementById('reviewDocAction');
    var docIdInput = document.getElementById('reviewDocId');
    var motifInput = document.getElementById('reviewMotif');

    document.querySelectorAll('.review-doc-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('reviewDocTitle').textContent = 'Examiner : ' + (btn.getAttribute('data-doc-title') || '');
            document.getElementById('reviewDocAuthor').textContent = btn.getAttribute('data-doc-author') || '';
            document.getElementById('reviewDocFiliere').textContent = btn.getAttribute('data-doc-filiere') || '';
            document.getElementById('reviewDocLicence').textContent = btn.getAttribute('data-doc-licence') || '';
            document.getElementById('reviewDocMatiere').textContent = btn.getAttribute('data-doc-matiere') || btn.getAttribute('data-doc-matiere-pending') || '—';
            docIdInput.value = btn.getAttribute('data-doc-id') || '';
            motifInput.value = '';
            document.getElementById('review_matiere_id').value = '';
            document.getElementById('review_matiere_label').value = '';
            frame.src = btn.getAttribute('data-doc-preview') || '';
        });
    });

    document.getElementById('approveDocBtn').addEventListener('click', function () {
        actionInput.value = 'approve';
        form.submit();
    });
    document.getElementById('rejectDocBtn').addEventListener('click', function () {
        if (motifInput.value.trim() === '') {
            motifInput.focus();
            motifInput.classList.add('is-invalid');
            return;
        }
        actionInput.value = 'reject';
        form.submit();
    });
})();
</script>
