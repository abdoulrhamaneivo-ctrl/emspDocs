<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-bold"><i class="bi bi-gear me-2 text-primary"></i>Paramètres de la plateforme</h5>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <form method="post" action="<?= url('admin/parametres') ?>">
            <?= csrf_field() ?>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">Médiathèque</div>
                <div class="card-body">
                    <p class="text-muted mb-3">Gérez les images et vidéos affichées sur la page publique <strong>/mediatheque</strong>.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?= url('admin/mediatheque') ?>" class="btn btn-outline-primary">
                            <i class="bi bi-collection-play me-1"></i>Gérer les médias
                        </a>
                        <a href="<?= url('admin/mediatheque/categories') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-tags me-1"></i>Catégories
                        </a>
                        <a href="<?= url('admin/mediatheque/form') ?>" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-1"></i>Ajouter un média
                        </a>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">Badge Or</div>
                <div class="card-body">
                    <label class="form-label">Nombre minimum de documents approuvés</label>
                    <input type="number" min="1" class="form-control" name="or_badge_min_approved_docs" value="<?= (int) $seuilActuel ?>">
                    <div class="form-text">Un étudiant obtient le badge Or à partir de ce nombre de documents approuvés.</div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">Bannière d'information</div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="banner_active" value="1" id="bannerActive" <?= $bannerActive === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="bannerActive">Afficher la bannière sur le site</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="banner_type">
                            <?php foreach (['info' => 'Information', 'warning' => 'Avertissement', 'danger' => 'Urgent'] as $val => $label): ?>
                                <option value="<?= $val ?>" <?= $bannerType === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Message</label>
                        <textarea class="form-control" name="banner_message" rows="2"><?= h($bannerMessage) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">Notifications push (VAPID)</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Clé publique</label>
                        <input type="text" class="form-control" name="push_vapid_public" value="<?= h($pushVapidPublic) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Clé privée</label>
                        <input type="text" class="form-control" name="push_vapid_private" value="<?= h($pushVapidPrivate) ?>">
                    </div>
                    <button type="submit" name="action" value="generate_push_keys" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-key me-1"></i>Générer de nouvelles clés
                    </button>
                </div>
            </div>

            <div class="emsp-admin-sticky-actions">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Enregistrer les paramètres</button>
            </div>
        </form>

        <div class="card shadow-sm mt-4">
            <div class="card-header bg-white fw-semibold">Aperçu rapide</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($statsRows as $row): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= h($row['label']) ?></span>
                        <strong><?= h($row['value']) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
