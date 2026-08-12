<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0"><i class="bi bi-diagram-3 text-primary me-2"></i><?= $id > 0 ? 'Modifier la filière' : 'Ajouter une filière' ?></h1>
    <a href="<?= url('admin/filieres') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
</div>

<div class="card" style="max-width:720px;">
    <div class="card-body">
        <form method="post" action="<?= url('admin/filieres/form') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $id ?>">
            <input type="hidden" name="current_cover_image_path" value="<?= h((string) ($item['cover_image_path'] ?? '')) ?>">

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Titre (nom) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" required value="<?= h((string) ($item['name'] ?? '')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-uppercase" name="code" required maxlength="20" value="<?= h((string) ($item['code'] ?? '')) ?>" placeholder="Ex : GLSI">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Statut</label>
                    <select class="form-select" name="status">
                        <option value="active" <?= ($item['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Actif</option>
                        <option value="inactive" <?= ($item['status'] ?? 'active') === 'inactive' ? 'selected' : '' ?>>Inactif</option>
                    </select>
                </div>

                <?php if (!empty($programColumnEnabled)): ?>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Programme de formation</label>
                        <select class="form-select" name="formation_program">
                            <option value="" <?= ($item['formation_program'] ?? '') === '' ? 'selected' : '' ?>>Aucun (filière autonome)</option>
                            <option value="fs-menum" <?= ($item['formation_program'] ?? '') === 'fs-menum' ? 'selected' : '' ?>>FS-MENUM — Formation Supérieure en Management de l'Économie Numérique</option>
                        </select>
                        <div class="form-text">Rattachez la filière au programme FS-MENUM (5 filières de spécialisation). Ne pas créer « FS-MENUM » comme filière.</div>
                    </div>
                <?php endif; ?>

                <?php if ($editorialEnabled): ?>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Description courte</label>
                        <input type="text" class="form-control" name="summary" maxlength="200" value="<?= h((string) ($item['summary'] ?? '')) ?>" placeholder="Résumé affiché sur la page Formations">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Description détaillée</label>
                        <textarea class="form-control" name="description_html" rows="8" placeholder="Présentation complète (HTML simple accepté)"><?= h((string) ($item['description_html'] ?? '')) ?></textarea>
                        <div class="form-text">Balises HTML simples acceptées (&lt;p&gt;, &lt;strong&gt;, &lt;ul&gt;…).</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Photo filière</label>
                        <?php if (!empty($coverSrc)): ?>
                            <div class="mb-2">
                                <img src="<?= h($coverSrc) ?>" alt="" style="max-width:280px;aspect-ratio:16/9;object-fit:cover;" class="rounded border d-block mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remove_cover" value="1" id="removeCover">
                                    <label class="form-check-label small" for="removeCover">Supprimer l'image actuelle</label>
                                </div>
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" name="cover_image" accept=".jpg,.jpeg,.png,.webp,.gif">
                        <div class="form-text">JPG, PNG, WEBP ou GIF — stockée dans uploads/formations/covers/.</div>
                    </div>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-warning mb-0">Les colonnes photo/description ne sont pas disponibles. Contactez l'administrateur base de données.</div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="emsp-admin-sticky-actions">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Enregistrer</button>
            </div>
        </form>
    </div>
</div>
