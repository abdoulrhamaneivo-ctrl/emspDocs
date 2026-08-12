<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0"><i class="bi bi-person-plus text-primary me-2"></i>Ajouter un utilisateur</h1>
    <a href="<?= url('admin/utilisateurs') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
</div>

<div class="card" style="max-width:640px;">
    <div class="card-body">
        <form method="post" action="<?= url('admin/utilisateurs/nouveau') ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Prénom</label>
                    <input type="text" class="form-control" name="first_name" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nom</label>
                    <input type="text" class="form-control" name="last_name" required>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Mot de passe</label>
                    <input type="password" class="form-control" name="password" minlength="8" required>
                </div>
                <?php if ($canAssignRoles): ?>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Rôle</label>
                        <select class="form-select" name="role">
                            <option value="etudiant">Étudiant</option>
                            <option value="moderateur">Modérateur</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                <?php endif; ?>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Statut</label>
                    <select class="form-select" name="status">
                        <option value="active">Actif</option>
                        <option value="pending">En attente</option>
                        <option value="suspended">Suspendu</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Filière</label>
                    <select class="form-select" name="filiere_id">
                        <option value="">-- --</option>
                        <?php foreach ($filieres as $f): ?>
                            <option value="<?= (int) $f['id'] ?>"><?= h($f['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Niveau</label>
                    <select class="form-select" name="licence_id">
                        <option value="">-- --</option>
                        <?php foreach ($licences as $l): ?>
                            <option value="<?= (int) $l['id'] ?>"><?= h($l['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="emsp-admin-sticky-actions">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-lg me-1"></i>Créer le compte</button>
            </div>
        </form>
    </div>
</div>
