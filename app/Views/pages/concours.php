<?php include_once dirname(__DIR__, 3) . '/includes/generate_thumb.php'; ?>
<section class="emsp-section concours-page emsp-page-shell py-5">
    <div class="emsp-container">
        <header class="concours-page-hero emsp-editorial-bg concours-page-head">
            <span class="concours-page-kicker"><i class="bi bi-trophy"></i> Ressources publiques</span>
            <h1>Annales de concours</h1>
            <p>Retrouvez les concours et annales partagés par la communauté EMSP, accessibles sans connexion.</p>
        </header>

        <form method="get" action="<?= url('concours') ?>" class="concours-filter-bar">
            <div class="flex-grow-1">
                <label class="visually-hidden" for="concours-search">Rechercher un concours</label>
                <div class="concours-search-wrap">
                    <i class="bi bi-search"></i>
                    <input id="concours-search" type="search" name="q" class="emsp-input" value="<?= h($search) ?>" placeholder="Rechercher par titre, matière ou filière">
                </div>
            </div>
            <?php if (!empty($years)): ?>
                <div class="concours-year-wrap">
                    <label class="visually-hidden" for="concours-year">Année</label>
                    <select id="concours-year" name="annee" class="emsp-select">
                        <option value="0">Toutes les années</option>
                        <?php foreach ($years as $y): ?>
                            <option value="<?= (int) $y ?>" <?= $yearFilter === (int) $y ? 'selected' : '' ?>><?= (int) $y ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <button type="submit" class="emsp-btn emsp-btn-primary"><i class="bi bi-funnel"></i> Filtrer</button>
        </form>

        <?php if (empty($docs)): ?>
            <div class="concours-empty">
                <i class="bi bi-trophy"></i>
                <div><strong>Aucun concours trouvé</strong><span>Modifiez votre recherche ou revenez plus tard.</span></div>
            </div>
        <?php else: ?>
            <div class="concours-results-head">
                <p><strong><?= count($docs) ?></strong> ressource<?= count($docs) > 1 ? 's' : '' ?> disponible<?= count($docs) > 1 ? 's' : '' ?></p>
            </div>
            <div class="concours-card-grid">
                <?php foreach ($docs as $d): ?>
                    <?php
                    $id = (int) ($d['id'] ?? 0);
                    $title = trim((string) ($d['title'] ?? 'Concours EMSP'));
                    $author = trim((string) (($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? '')));
                    $context = implode(' · ', array_filter([
                        trim((string) ($d['filiere_name'] ?? '')),
                        trim((string) ($d['matiere_name'] ?? '')),
                        !empty($d['exam_year']) ? (string) $d['exam_year'] : '',
                    ]));
                    $modalId = 'concours-preview-' . $id;
                    $thumbUrl = function_exists('emsp_doc_thumb_src') ? emsp_doc_thumb_src($d) : '';
                    if ($thumbUrl === '') {
                        $thumbUrl = url('assets/images/actu-concours.png');
                    }
                    $pdfViewerUrl = url('telecharger?id=' . $id . '&preview=1');
                    $pdfDataUrl = url('telecharger?id=' . $id . '&pdfdata=1');
                    ?>
                    <article class="concours-card">
                        <div class="concours-card-media">
                            <img src="<?= h($thumbUrl) ?>" alt="Première page : <?= h($title) ?>" loading="lazy">
                            <span class="concours-card-media-label"><i class="bi bi-file-earmark-pdf"></i> Aperçu</span>
                        </div>
                        <div class="concours-card-top">
                            <span class="concours-card-type"><i class="bi bi-trophy"></i> Concours</span>
                            <?php if (!empty($d['exam_year'])): ?><span class="concours-card-year"><?= h((string) $d['exam_year']) ?></span><?php endif; ?>
                        </div>
                        <h2><?= h($title) ?></h2>
                        <p class="concours-card-context"><?= h($context !== '' ? $context : 'Ressource académique EMSP') ?></p>
                        <div class="concours-card-meta">
                            <span><i class="bi bi-download"></i> <?= (int) ($d['download_count'] ?? 0) ?></span>
                            <span><i class="bi bi-person"></i> <?= h($author !== '' ? $author : 'Équipe EMSP') ?></span>
                        </div>
                        <div class="concours-card-actions">
                            <button type="button" class="emsp-btn emsp-btn-outline btn-sm" data-bs-toggle="modal" data-bs-target="#<?= $modalId ?>"><i class="bi bi-eye"></i> Aperçu</button>
                            <a href="<?= url('document?id=' . $id) ?>" class="emsp-btn emsp-btn-primary btn-sm">Consulter <i class="bi bi-arrow-up-right"></i></a>
                        </div>
                    </article>

                    <div class="modal fade concours-preview-modal" id="<?= $modalId ?>" tabindex="-1" aria-labelledby="<?= $modalId ?>-title" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true" data-emsp-motion-modal="1">
                        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable concours-preview-dialog">
                            <div class="modal-content">
                                <div class="modal-header concours-preview-modal-head">
                                    <div class="concours-preview-modal-head-text">
                                        <span class="concours-page-kicker">Aperçu du document</span>
                                        <h2 class="modal-title fs-5 concours-preview-modal-title" id="<?= $modalId ?>-title"><?= h($title) ?></h2>
                                    </div>
                                    <button type="button" class="btn-close emsp-modal-close-touch" data-bs-dismiss="modal" aria-label="Fermer l'aperçu"></button>
                                </div>
                                <div class="modal-body concours-preview-body concours-preview-body--stack">
                                    <div class="emsp-pdf-preview-wrap">
                                        <div class="emsp-pdf-preview-loading" aria-live="polite">
                                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                            Chargement de l'aperçu…
                                        </div>
                                        <iframe
                                            title="Aperçu : <?= h($title) ?>"
                                            class="emsp-doc-preview-frame emsp-inline-pdf-frame"
                                            data-emsp-pdf-preview="1"
                                            data-pdf-data-url="<?= h($pdfDataUrl) ?>"
                                            data-preview-url="<?= h($pdfViewerUrl) ?>"
                                            data-fallback-thumb="<?= h($thumbUrl) ?>"
                                        ></iframe>
                                    </div>
                                    <p class="emsp-doc-preview-note">Défilez le document — aucun téléchargement automatique.</p>
                                </div>
                                <div class="modal-footer concours-preview-footer">
                                    <p class="concours-preview-footer-excerpt mb-0"><?= h($context !== '' ? $context : 'Ressource académique EMSP') ?></p>
                                    <a href="<?= url('document?id=' . $id) ?>" class="emsp-btn emsp-btn-primary btn-sm">Ouvrir la fiche</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
