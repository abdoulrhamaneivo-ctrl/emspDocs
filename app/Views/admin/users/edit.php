<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0"><i class="bi bi-pencil text-primary me-2"></i>Modifier <?= h($user['first_name'] ?? '') ?></h1>
    <a href="<?= url('admin/utilisateurs') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Informations</h6>
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted small">Nom</td><td><?= h(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?></td></tr>
                    <tr><td class="text-muted small">Email</td><td><?= h($user['email'] ?? '') ?></td></tr>
                    <tr><td class="text-muted small">Filière</td><td><?= h($user['filiere_name'] ?? '-') ?></td></tr>
                    <tr><td class="text-muted small">Niveau</td><td><?= h($user['licence_name'] ?? '-') ?></td></tr>
                    <tr><td class="text-muted small">Inscrit le</td><td><?= h(!empty($user['created_at']) ? date('d/m/Y', strtotime((string) $user['created_at'])) : '-') ?></td></tr>
                    <?php if (!empty($user['student_card_path'])): ?>
                        <tr><td class="text-muted small">Carte étudiante</td>
                            <td><button type="button" class="btn btn-link btn-sm p-0" data-bs-toggle="modal" data-bs-target="#userCardModal">Voir la carte</button></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Rôle et statut</h6>
                <form method="post" action="<?= url('admin/utilisateurs/' . (int) $user['id'] . '/modifier') ?>">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Rôle</label>
                            <?php if ($canAssignRoles): ?>
                                <select class="form-select" name="role">
                                    <?php foreach (['etudiant' => 'Étudiant', 'moderateur' => 'Modérateur', 'admin' => 'Admin'] as $val => $label): ?>
                                        <option value="<?= $val ?>" <?= $user['role'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="text" class="form-control" value="<?= h(ucfirst((string) $user['role'])) ?>" disabled>
                                <div class="form-text">Seul un administrateur peut changer le rôle.</div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Statut</label>
                            <select class="form-select" name="status">
                                <?php foreach (['active' => 'Actif', 'pending' => 'En attente', 'suspended' => 'Suspendu', 'rejected' => 'Rejeté'] as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= $user['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="emsp-admin-sticky-actions">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($user['student_card_path'])): ?>
<div class="modal fade" id="userCardModal" tabindex="-1" aria-labelledby="userCardModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content emsp-user-card-modal">
            <div class="modal-header">
                <div>
                    <span class="emsp-doc-preview-kicker">Dossier étudiant</span>
                    <h2 class="modal-title h5 mb-0" id="userCardModalTitle">Carte de <?= h(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?></h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4 align-items-start">
                    <div class="col-md-7">
                        <div class="emsp-user-card-frame">
                            <img src="<?= url('admin/voir-carte.php?user_id=' . (int) $user['id']) ?>" alt="Carte étudiante" class="img-fluid" onerror="this.closest('.emsp-user-card-frame').classList.add('is-pdf')">
                            <a class="emsp-user-card-file" href="<?= url('admin/voir-carte.php?user_id=' . (int) $user['id']) ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> Ouvrir le fichier</a>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <dl class="emsp-user-card-details mb-0">
                            <div><dt>Email</dt><dd><?= h($user['email'] ?? '') ?></dd></div>
                            <div><dt>Filière</dt><dd><?= h($user['filiere_name'] ?? '-') ?></dd></div>
                            <div><dt>Niveau</dt><dd><?= h($user['licence_name'] ?? '-') ?></dd></div>
                            <div><dt>Inscription</dt><dd><?= h(!empty($user['created_at']) ? date('d/m/Y', strtotime((string) $user['created_at'])) : '-') ?></dd></div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
