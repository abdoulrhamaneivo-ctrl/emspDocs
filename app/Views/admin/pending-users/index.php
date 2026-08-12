<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-person-check me-2 text-warning"></i> Comptes en attente (email vérifié)</h5>
</div>

<?php if (empty($pendingVerifiedUsers)): ?>
    <div class="card shadow-sm mb-4"><div class="card-body text-center text-muted py-4">
        <i class="bi bi-check-circle text-success fs-2 d-block mb-2"></i>
        Aucun compte en attente après vérification email.
    </div></div>
<?php else: ?>
    <div class="table-responsive card shadow-sm mb-4">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Nom</th><th>Email</th><th>Filière / Niveau</th><th>Inscrit le</th><th>Email vérifié</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingVerifiedUsers as $u): ?>
                    <tr>
                        <td><?= h($u['first_name'] . ' ' . $u['last_name']) ?></td>
                        <td><?= h($u['email']) ?></td>
                        <td><?= h($u['filiere_name'] ?? '-') ?> / <?= h($u['licence_name'] ?? '-') ?></td>
                        <td><?= h((string) $u['created_at']) ?></td>
                        <td><span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle-fill me-1"></i><?= h($u['verified_label']) ?></span></td>
                        <td>
                            <div class="d-flex gap-2 align-items-center flex-wrap emsp-admin-validation-actions">
                                <form method="post" action="<?= url('admin/validation-comptes') ?>" class="m-0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button class="btn btn-sm btn-success" type="submit">Approuver</button>
                                </form>
                                <button type="button" class="btn btn-sm btn-outline-danger js-open-reject-modal"
                                        data-user-id="<?= (int) $u['id'] ?>"
                                        data-user-name="<?= h(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))) ?>">
                                    Rejeter
                                </button>
                                <?php if (($u['registration_method'] ?? '') === 'manual_card' && !empty($u['student_card_path'])): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary js-carte-btn"
                                            data-user-id="<?= (int) $u['id'] ?>"
                                            data-prenom="<?= h($u['first_name'] ?? '') ?>"
                                            data-nom="<?= h($u['last_name'] ?? '') ?>"
                                            data-email="<?= h($u['email'] ?? '') ?>"
                                            data-filiere="<?= h($u['filiere_name'] ?? '-') ?>"
                                            data-licence="<?= h($u['licence_name'] ?? '-') ?>"
                                            data-date="<?= h($u['created_label'] !== '' ? $u['created_label'] : '-') ?>">
                                        Voir carte
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted small">Pas de carte</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-envelope-paper me-2 text-secondary"></i> Emails non vérifiés</h5>
</div>

<?php if (empty($pendingUnverifiedUsers)): ?>
    <div class="card shadow-sm mb-4"><div class="card-body text-center text-muted py-4">
        <i class="bi bi-inbox text-secondary fs-2 d-block mb-2"></i>
        Aucun compte en attente de vérification email.
    </div></div>
<?php else: ?>
    <div class="table-responsive card shadow-sm">
        <table class="table table-hover mb-0">
            <thead><tr><th>Nom</th><th>Email</th><th>Méthode</th><th>Inscrit le</th></tr></thead>
            <tbody>
                <?php foreach ($pendingUnverifiedUsers as $u): ?>
                    <tr>
                        <td><?= h($u['first_name'] . ' ' . $u['last_name']) ?></td>
                        <td><?= h($u['email']) ?></td>
                        <td><?= h((string) $u['registration_method']) ?></td>
                        <td><?= h((string) $u['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Modale carte étudiante -->
<div class="modal fade emsp-user-card-modal emsp-admin-sheet-modal" id="modalCarte" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold"><i class="bi bi-card-image me-2 text-primary"></i>Carte étudiante — vérification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4 emsp-user-card-modal-grid">
                    <div class="col-lg-7">
                        <p class="text-muted fw-semibold small text-uppercase mb-2">Carte fournie par l'étudiant</p>
                        <div class="emsp-user-card-frame border rounded bg-light p-2" id="carte-preview-frame">
                            <img id="modal-carte-img" src="" alt="Carte étudiante" class="img-fluid rounded shadow-sm">
                            <iframe id="modal-carte-pdf" class="emsp-user-card-pdf d-none" title="Carte étudiante PDF" data-emsp-preview="1"></iframe>
                            <div id="carte-fallback" class="d-none flex-column align-items-center gap-2 py-4">
                                <i class="bi bi-file-earmark-pdf text-danger" style="font-size:3rem;"></i>
                                <p class="text-muted small mb-0">Chargement de la carte…</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 d-flex flex-column">
                        <p class="text-muted fw-semibold small text-uppercase mb-2">Informations déclarées</p>
                        <table class="table table-sm table-bordered mb-3">
                            <tbody>
                                <tr><td class="fw-semibold text-muted">Prénom</td><td id="info-prenom">-</td></tr>
                                <tr><td class="fw-semibold text-muted">Nom</td><td id="info-nom">-</td></tr>
                                <tr><td class="fw-semibold text-muted">Email</td><td id="info-email">-</td></tr>
                                <tr><td class="fw-semibold text-muted">Filière</td><td id="info-filiere">-</td></tr>
                                <tr><td class="fw-semibold text-muted">Niveau</td><td id="info-licence">-</td></tr>
                                <tr><td class="fw-semibold text-muted">Inscrit le</td><td id="info-date">-</td></tr>
                            </tbody>
                        </table>
                        <div class="alert alert-info small py-2">
                            Vérifiez que le <strong>nom</strong>, le <strong>prénom</strong> et la <strong>filière</strong> correspondent à la carte.
                        </div>
                        <form method="post" action="<?= url('admin/validation-comptes') ?>" id="modal-approve-form" class="mt-auto d-flex gap-2 flex-wrap">
                            <?= csrf_field() ?>
                            <input type="hidden" name="user_id" id="modal-user-id" value="">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Approuver</button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de rejet -->
<div class="modal fade emsp-admin-sheet-modal" id="rejectUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="post" action="<?= url('admin/validation-comptes') ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-x-octagon me-2 text-danger"></i>Refuser l'inscription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="user_id" id="reject-user-id" value="">
                    <p class="text-muted mb-2 small">Compte concerné : <strong id="reject-user-name">-</strong></p>
                    <label for="reject-motif" class="form-label">Motif du refus</label>
                    <textarea class="form-control" id="reject-motif" name="motif" rows="3" required
                              placeholder="Expliquez clairement la raison du refus..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-sm btn-danger">Confirmer le rejet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function ouvrirCarte(userId, prenom, nom, email, filiere, licence, date) {
    document.getElementById('info-prenom').textContent = prenom;
    document.getElementById('info-nom').textContent = nom;
    document.getElementById('info-email').textContent = email;
    document.getElementById('info-filiere').textContent = filiere;
    document.getElementById('info-licence').textContent = licence;
    document.getElementById('info-date').textContent = date;
    document.getElementById('modal-user-id').value = userId;

    const url = '<?= url('admin/voir-carte.php') ?>?user_id=' + userId;
    const img = document.getElementById('modal-carte-img');
    const pdfFrame = document.getElementById('modal-carte-pdf');
    const fallback = document.getElementById('carte-fallback');
    const frame = document.getElementById('carte-preview-frame');

    img.style.display = 'none';
    pdfFrame.classList.add('d-none');
    fallback.classList.remove('d-none');
    fallback.style.display = 'flex';
    frame.classList.remove('is-pdf');

    img.onload = function () {
        img.style.display = 'block';
        pdfFrame.classList.add('d-none');
        fallback.classList.add('d-none');
        fallback.style.display = 'none';
        frame.classList.remove('is-pdf');
    };
    img.onerror = function () {
        img.style.display = 'none';
        pdfFrame.src = '<?= url('assets/js/pdfjs/web/viewer.html') ?>?file=' + encodeURIComponent(url);
        pdfFrame.classList.remove('d-none');
        fallback.classList.add('d-none');
        fallback.style.display = 'none';
        frame.classList.add('is-pdf');
    };
    img.src = url + '&_=' + Date.now();

    if (!window.bootstrap || !bootstrap.Modal) {
        window.open(url, '_blank');
        return;
    }
    new bootstrap.Modal(document.getElementById('modalCarte')).show();
}

document.querySelectorAll('.js-carte-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        ouvrirCarte(
            parseInt(btn.getAttribute('data-user-id') || '0', 10) || 0,
            btn.getAttribute('data-prenom') || '',
            btn.getAttribute('data-nom') || '',
            btn.getAttribute('data-email') || '',
            btn.getAttribute('data-filiere') || '-',
            btn.getAttribute('data-licence') || '-',
            btn.getAttribute('data-date') || '-'
        );
    });
});

var rejectModalEl = document.getElementById('rejectUserModal');
if (rejectModalEl && window.bootstrap && bootstrap.Modal) {
    var rejectModal = new bootstrap.Modal(rejectModalEl);
    document.querySelectorAll('.js-open-reject-modal').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('reject-user-id').value = btn.getAttribute('data-user-id') || '';
            document.getElementById('reject-user-name').textContent = btn.getAttribute('data-user-name') || '-';
            document.getElementById('reject-motif').value = '';
            rejectModal.show();
        });
    });
}
</script>
