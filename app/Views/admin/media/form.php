<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h4 mb-0">
        <i class="bi bi-images text-primary me-2"></i>
        <?= $id > 0 ? 'Modifier le média' : 'Ajouter un média' ?>
    </h1>
    <a href="<?= url('admin/mediatheque') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Retour à la liste
    </a>
</div>

<div class="card shadow-sm" style="max-width:820px;">
    <div class="card-body">
        <form method="post" action="<?= url('admin/mediatheque/form') ?>" enctype="multipart/form-data" id="mediaForm">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $id ?>">
            <input type="hidden" name="current_poster_path" value="<?= h((string) ($item['poster_path'] ?? '')) ?>">

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label fw-semibold" for="mediaTitle">Titre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="mediaTitle" name="title" maxlength="200" required
                           value="<?= h((string) ($item['title'] ?? '')) ?>"
                           placeholder="Ex : Cérémonie de rentrée académique">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="mediaType">Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="mediaType" name="type" required>
                        <?php foreach (['image' => 'Image', 'video' => 'Vidéo', 'lien' => 'Lien externe'] as $val => $label): ?>
                            <option value="<?= $val ?>" <?= ($item['type'] ?? 'image') === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold d-block">Mode de fichier</label>
                    <div class="d-flex flex-wrap gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="file_mode" id="modeUpload" value="upload"
                                   <?= $isUploadMode ? 'checked' : '' ?>>
                            <label class="form-check-label" for="modeUpload">Téléverser un fichier</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="file_mode" id="modeLink" value="link"
                                   <?= !$isUploadMode ? 'checked' : '' ?>>
                            <label class="form-check-label" for="modeLink">Lien / chemin existant</label>
                        </div>
                    </div>
                </div>

                <div class="col-12" id="uploadBlock">
                    <label class="form-label fw-semibold" for="mediaFile">Fichier</label>
                    <input type="file" class="form-control" id="mediaFile" name="media_file"
                           accept=".jpg,.jpeg,.png,.webp,.gif,.mp4,.webm,.ogv,.mov,.avi,.mkv">
                    <div class="form-text">Images max 8 Mo · Vidéos max 70 Mo (MP4, WEBM, MOV…).</div>
                    <?php if ($id > 0 && $isUploadMode && !empty($item['file_path'])): ?>
                        <p class="small text-muted mt-2 mb-0">
                            Fichier actuel : <?= h((string) $item['file_path']) ?>
                            <?php if ($fileSrc !== ''): ?>
                                · <a href="<?= h(str_starts_with($fileSrc, 'http') ? $fileSrc : url($fileSrc)) ?>" target="_blank" rel="noopener">Voir</a>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="col-12 d-none" id="linkBlock">
                    <label class="form-label fw-semibold" for="filePath">Lien ou chemin</label>
                    <input type="text" class="form-control" id="filePath" name="file_path"
                           value="<?= h((string) ($item['file_path'] ?? '')) ?>"
                           placeholder="assets/images/campus.jpg ou https://youtube.com/…">
                    <div class="form-text">URL YouTube, chemin assets/ ou uploads/.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="categoryId">Catégorie</label>
                    <select class="form-select" id="categoryId" name="category_id">
                        <option value="0">— Sans catégorie —</option>
                        <?php
                        $selectedCat = (int) ($item['category_id'] ?? 0);
                        foreach ($categories as $cat):
                        ?>
                            <option value="<?= (int) $cat['id'] ?>" <?= $selectedCat === (int) $cat['id'] ? 'selected' : '' ?>>
                                <?= h($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" class="form-control mt-2" name="category" maxlength="100"
                           value="<?= h($selectedCat > 0 ? '' : (string) ($item['category'] ?? '')) ?>"
                           placeholder="Nouvelle catégorie (optionnel)">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold" for="mediaStatus">Statut</label>
                    <select class="form-select" id="mediaStatus" name="status" required>
                        <?php foreach ($allowedStatuses as $st): ?>
                            <option value="<?= h($st) ?>" <?= ($item['status'] ?? 'published') === $st ? 'selected' : '' ?>>
                                <?= h(ucfirst($st)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold" for="displayOrder">Ordre d'affichage</label>
                    <input type="number" class="form-control" id="displayOrder" name="display_order" min="0" step="1"
                           value="<?= (int) ($item['display_order'] ?? 0) ?>">
                    <div class="form-text">Plus élevé = affiché en premier.</div>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_public" value="1" id="isPublicMedia"
                               <?= (int) ($item['is_public'] ?? 1) === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isPublicMedia">Visible sur la médiathèque publique</label>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold" for="mediaDescription">Description</label>
                    <textarea class="form-control" id="mediaDescription" name="description" rows="4"
                              placeholder="Description courte du média…"><?= h((string) ($item['description'] ?? '')) ?></textarea>
                </div>

                <div class="col-12" id="posterBlock">
                    <label class="form-label fw-semibold" for="posterFile">Miniature / poster (vidéos)</label>
                    <?php if ($posterSrc !== ''): ?>
                        <div class="mb-2">
                            <img src="<?= h(str_starts_with($posterSrc, 'http') ? $posterSrc : url($posterSrc)) ?>" alt=""
                                 class="rounded border d-block mb-2" style="max-width:240px;aspect-ratio:16/9;object-fit:cover;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remove_poster" value="1" id="removePoster">
                                <label class="form-check-label small" for="removePoster">Supprimer la miniature actuelle</label>
                            </div>
                        </div>
                    <?php endif; ?>
                    <input type="file" class="form-control" id="posterFile" name="poster_file" accept=".jpg,.jpeg,.png,.webp">
                    <div class="form-text">JPG, PNG ou WEBP — max 2 Mo. Recommandé pour les vidéos locales.</div>
                </div>
            </div>

            <div class="emsp-admin-sticky-actions d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Enregistrer
                </button>
                <a href="<?= url('admin/mediatheque') ?>" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modeUpload = document.getElementById('modeUpload');
    var modeLink = document.getElementById('modeLink');
    var uploadBlock = document.getElementById('uploadBlock');
    var linkBlock = document.getElementById('linkBlock');
    var typeSelect = document.getElementById('mediaType');
    var filePathInput = document.getElementById('filePath');
    var titleInput = document.getElementById('mediaTitle');
    var posterBlock = document.getElementById('posterBlock');

    function toggleMode() {
        var isLink = modeLink.checked || (typeSelect && typeSelect.value === 'lien');
        uploadBlock.classList.toggle('d-none', isLink);
        linkBlock.classList.toggle('d-none', !isLink);
        if (posterBlock) {
            posterBlock.classList.toggle('d-none', typeSelect && typeSelect.value === 'image');
        }
    }

    modeUpload.addEventListener('change', toggleMode);
    modeLink.addEventListener('change', toggleMode);
    if (typeSelect) {
        typeSelect.addEventListener('change', function () {
            if (this.value === 'lien') {
                modeLink.checked = true;
            } else if (modeLink.checked && this.value !== 'lien') {
                modeUpload.checked = true;
            }
            toggleMode();
        });
    }

    function isYoutubeUrl(value) {
        return /(?:youtube\.com|youtu\.be)/i.test(value || '');
    }

    async function autofillYoutubeTitle() {
        if (!filePathInput || !titleInput || !typeSelect) return;
        var url = filePathInput.value.trim();
        var canAutofill = titleInput.value.trim() === '' || titleInput.dataset.autofilled === '1';
        if (typeSelect.value !== 'lien' || !modeLink.checked || !isYoutubeUrl(url) || !canAutofill) return;
        try {
            var response = await fetch('<?= url('admin/mediatheque/youtube-title') ?>?url=' + encodeURIComponent(url), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) return;
            var data = await response.json();
            if (data && data.title && (titleInput.value.trim() === '' || titleInput.dataset.autofilled === '1')) {
                titleInput.value = data.title;
                titleInput.dataset.autofilled = '1';
            }
        } catch (e) { /* silencieux */ }
    }

    if (titleInput) {
        titleInput.addEventListener('input', function () { this.dataset.autofilled = '0'; });
    }
    if (filePathInput) {
        filePathInput.addEventListener('blur', autofillYoutubeTitle);
        filePathInput.addEventListener('change', autofillYoutubeTitle);
    }
    toggleMode();
});
</script>
