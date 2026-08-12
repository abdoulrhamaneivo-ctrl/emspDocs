<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="mb-0 fw-bold"><i class="bi bi-collection-play me-2 text-primary"></i>Médiathèque</h5>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= url('admin/mediatheque/categories') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-tags me-1"></i>Catégories
        </a>
        <a href="<?= url('admin/mediatheque/form') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Ajouter un média
        </a>
        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMediaModal">
            <i class="bi bi-lightning me-1"></i>Ajout rapide
        </button>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="card text-center shadow-sm"><div class="card-body py-3"><div class="fs-4 fw-bold"><?= (int) $counters['total'] ?></div><div class="small text-muted">Total</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card text-center shadow-sm"><div class="card-body py-3"><div class="fs-4 fw-bold text-success"><?= (int) $counters['images'] ?></div><div class="small text-muted">Images</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card text-center shadow-sm"><div class="card-body py-3"><div class="fs-4 fw-bold text-primary"><?= (int) $counters['videos'] ?></div><div class="small text-muted">Vidéos</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card text-center shadow-sm"><div class="card-body py-3"><div class="fs-4 fw-bold text-info"><?= (int) $counters['publics'] ?></div><div class="small text-muted">Publics</div></div></div></div>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <?php foreach (['all' => 'Tous', 'image' => 'Images', 'video' => 'Vidéos', 'public' => 'Publics', 'private' => 'Privés'] as $v => $l): ?>
        <a href="<?= url('admin/mediatheque?filter=' . $v) ?>" class="btn btn-sm <?= $filter === $v ? 'btn-primary' : 'btn-outline-primary' ?>"><?= $l ?></a>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <?php if (empty($items)): ?>
        <div class="col-12"><div class="card"><div class="card-body text-center text-muted py-5">Aucun média pour ce filtre.</div></div></div>
    <?php else: foreach ($items as $item):
        $typeBadge = ['image' => 'bg-success', 'video' => 'bg-primary', 'lien' => 'bg-secondary'][$item['type']] ?? 'bg-secondary';
        $mediaSrc = function_exists('emsp_media_src') ? emsp_media_src((string) $item['file_path']) : '';
        $hrefSrc = (str_starts_with($mediaSrc, 'http')) ? $mediaSrc : url($mediaSrc);
        $posterSrc = !empty($item['poster_path']) && function_exists('emsp_media_src')
            ? emsp_media_src((string) $item['poster_path']) : '';
        $thumbSrc = '';
        if ($item['type'] === 'image') {
            $thumbSrc = $hrefSrc;
        } elseif ($posterSrc !== '') {
            $thumbSrc = str_starts_with($posterSrc, 'http') ? $posterSrc : url($posterSrc);
        } elseif ($item['type'] === 'lien' && function_exists('emsp_is_youtube') && emsp_is_youtube($mediaSrc) && function_exists('emsp_youtube_thumb')) {
            $thumbSrc = emsp_youtube_thumb($mediaSrc);
        }
        $statusLabel = ($item['status'] ?? 'published') === 'published' ? 'Publié' : 'Archivé';
    ?>
        <div class="col-sm-6 col-lg-4 col-xl-3">
            <div class="card h-100 shadow-sm">
                <div class="ratio ratio-16x9 bg-light d-flex align-items-center justify-content-center overflow-hidden">
                    <?php if ($thumbSrc !== ''): ?>
                        <img src="<?= h($thumbSrc) ?>" class="w-100 h-100" style="object-fit:cover;" alt="">
                    <?php elseif ($item['type'] === 'video' || $item['type'] === 'lien'): ?>
                        <i class="bi bi-play-circle-fill text-primary" style="font-size:2.5rem;"></i>
                    <?php else: ?>
                        <i class="bi bi-link-45deg text-secondary" style="font-size:2.5rem;"></i>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                        <h6 class="fw-bold mb-0 text-truncate" title="<?= h($item['title']) ?>"><?= h($item['title']) ?></h6>
                        <span class="badge <?= $typeBadge ?>"><?= h(ucfirst((string) $item['type'])) ?></span>
                    </div>
                    <p class="small text-muted text-truncate mb-1"><?= h((string) ($item['cat_name'] ?? $item['category'] ?? '')) ?: 'Sans catégorie' ?></p>
                    <p class="small text-muted mb-2">
                        <?= h($statusLabel) ?>
                        · <?= (int) ($item['is_public'] ?? 0) === 1 ? 'Public' : 'Privé' ?>
                        · <?= h(date('d/m/Y', strtotime((string) $item['created_at']))) ?>
                    </p>
                    <div class="d-flex gap-1 flex-wrap">
                        <a href="<?= url('admin/mediatheque/form?id=' . (int) $item['id']) ?>" class="btn btn-sm btn-primary flex-grow-1" title="Modifier">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="<?= h($hrefSrc) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary" title="Voir">
                            <i class="bi bi-eye"></i>
                        </a>
                        <form method="post" action="<?= url('admin/mediatheque?filter=' . $filter) ?>" class="flex-grow-1">
                            <?= csrf_field() ?>
                            <input type="hidden" name="toggle_visibility" value="<?= (int) $item['id'] ?>">
                            <button type="submit" class="btn btn-sm w-100 <?= (int) $item['is_public'] === 1 ? 'btn-outline-success' : 'btn-outline-warning' ?>" title="Visibilité">
                                <i class="bi <?= (int) $item['is_public'] === 1 ? 'bi-globe' : 'bi-lock' ?>"></i>
                            </button>
                        </form>
                        <form method="post" action="<?= url('admin/mediatheque?filter=' . $filter) ?>"
                              onsubmit="return confirm('Supprimer ce média définitivement ?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="delete_media" value="<?= (int) $item['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; endif; ?>
</div>

<div class="modal fade emsp-admin-sheet-modal" id="addMediaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="post" action="<?= url('admin/mediatheque?filter=' . $filter) ?>" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Ajout rapide</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="upload_media" value="1">
                    <p class="small text-muted">Pour poster, ordre d'affichage et champs avancés, utilisez le <a href="<?= url('admin/mediatheque/form') ?>">formulaire complet</a>.</p>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="uploadMode">Mode</label>
                        <select class="form-select" name="upload_mode" id="uploadMode">
                            <option value="file">Envoyer un fichier</option>
                            <option value="link">Lien externe</option>
                        </select>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold" for="quickTitle">Titre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quickTitle" name="title" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="quickType">Type</label>
                            <select class="form-select" id="quickType" name="type">
                                <option value="image">Image</option>
                                <option value="video">Vidéo</option>
                                <option value="lien">Lien</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="quickDesc">Description</label>
                            <textarea class="form-control" id="quickDesc" name="description" rows="2"></textarea>
                        </div>
                        <div class="col-md-6" id="fileField">
                            <label class="form-label fw-semibold" for="quickFile">Fichier</label>
                            <input type="file" class="form-control" id="quickFile" name="media_file" accept=".jpg,.jpeg,.png,.webp,.gif,.mp4,.webm,.ogv,.mov,.avi,.mkv">
                        </div>
                        <div class="col-md-6 d-none" id="linkField">
                            <label class="form-label fw-semibold" for="quickLink">Lien / chemin</label>
                            <input type="text" class="form-control" id="quickLink" name="external_path" placeholder="https://...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="quickCat">Catégorie</label>
                            <select class="form-select" id="quickCat" name="category_id">
                                <option value="0">— Sans catégorie —</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int) $cat['id'] ?>"><?= h($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="quickStatus">Statut</label>
                            <select class="form-select" id="quickStatus" name="status">
                                <option value="published">Publié</option>
                                <option value="archived">Archivé</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_public" value="1" id="isPublicMedia" checked>
                                <label class="form-check-label" for="isPublicMedia">Rendre public</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('uploadMode').addEventListener('change', function () {
    var isLink = this.value === 'link';
    document.getElementById('fileField').classList.toggle('d-none', isLink);
    document.getElementById('linkField').classList.toggle('d-none', !isLink);
    if (isLink) {
        document.getElementById('quickType').value = 'lien';
    }
});
</script>
