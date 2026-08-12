<?php
include_once dirname(__DIR__, 2) . '/generate_thumb.php';
$concoursModals = [];
?>

<div class="emsp-home-showcase-flow">
<section class="home-section home-vitrine-section home-vitrine-media emsp-section-band d-none d-md-block" data-emsp-parallax-bg="0.06">
    <div class="container">
        <div class="home-section-head emsp-motion-item">
            <div>
                <span class="home-section-kicker emsp-kicker">Médiathèque EMSP</span>
                <h2>Médiathèque en lumière</h2>
                <p>Albums et reportages visuels pour une expérience immersive des activités EMSP.</p>
            </div>
            <a href="mediatheque" class="btn btn-outline-primary d-none d-md-inline-flex">Explorer la médiathèque</a>
            <?php if (!empty($mediathequeDiscovery['items'])): ?>
                <button
                    type="button"
                    class="btn btn-outline-primary d-md-none"
                    data-emsp-mediatheque-open="1"
                    data-bs-toggle="modal"
                    data-bs-target="#emspMediathequeSheet"
                >
                    Découvrir
                </button>
            <?php else: ?>
                <a href="mediatheque" class="btn btn-outline-primary d-md-none">Explorer</a>
            <?php endif; ?>
        </div>

        <div class="row g-4">
            <?php if (empty($mediaHighlights)): ?>
                    <article class="card home-vitrine-card home-vitrine-card--empty emsp-motion-item">
                        <div class="card-body py-5 text-center text-muted">
                            <i class="bi bi-images fs-2 d-block mb-2"></i>
                            Aucun album n'est disponible pour le moment.
                        </div>
                    </article>
            <?php else: ?>
                <?php foreach (array_slice($mediaHighlights, 0, 6) as $mediaItem): ?>
                    <?php
                    $albumTitle = trim((string) ($mediaItem['category_label'] ?? 'Album EMSP'));
                    $albumHref = 'mediatheque?album=' . urlencode((string) ($mediaItem['category_raw'] ?? '')) . '#phototheque';
                    ?>
                    <div class="col-sm-6 col-xl-4 emsp-motion-item">
                        <article class="card home-vitrine-card h-100 emsp-editorial-card">
                            <a class="home-vitrine-media-link" href="<?= htmlspecialchars($albumHref, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>" aria-label="Voir l album <?= htmlspecialchars($albumTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>">
                                <div class="home-vitrine-media">
                                    <img src="<?= htmlspecialchars((string) ($mediaItem['cover_src'] ?? url('assets/images/media-thumb-3.jpg')), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>" alt="<?= htmlspecialchars($albumTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>" loading="lazy" data-fallback-src="<?= h(url('assets/images/media-thumb-3.jpg')) ?>">
                                </div>
                            </a>
                            <div class="card-body">
                                <span class="home-vitrine-kicker home-vitrine-kicker--media">Médiathèque</span>
                                <h3><?= htmlspecialchars($albumTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></h3>
                                <p><?= (int) ($mediaItem['media_count'] ?? 0) ?> élément(s) publié(s) dans cet album.</p>
                            </div>
                            <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                                <small><?= date('d/m/Y', strtotime((string) ($mediaItem['last_date'] ?? 'now'))) ?></small>
                                <a href="<?= htmlspecialchars($albumHref, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>" class="home-vitrine-link">
                                    Voir <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="home-section home-vitrine-section home-vitrine-concours emsp-section-band emsp-section-band--concours" id="concours" data-emsp-parallax-bg="0.04">
    <div class="container">
        <div class="home-section-head home-section-head--centered emsp-motion-item">
            <div class="home-section-head__copy">
                <span class="home-section-kicker emsp-kicker">Opportunités</span>
                <h2>Concours à la une</h2>
                <p>Les concours prioritaires pour orienter les étudiants vers les meilleures opportunités académiques et professionnelles.</p>
            </div>
            <a href="concours" class="btn btn-outline-primary home-section-head__cta">Voir les concours</a>
        </div>

        <div class="home-concours-grid" role="list">
            <?php if (empty($concoursHighlights)): ?>
                <article class="card home-vitrine-card home-vitrine-card--empty">
                    <div class="card-body py-4 text-center text-muted">
                        <i class="bi bi-trophy fs-2 d-block mb-2"></i>
                        Aucun concours public n'est disponible pour le moment.
                    </div>
                </article>
            <?php else: ?>
                <?php foreach ($concoursHighlights as $concoursItem): ?>
                    <?php
                    $concoursTitle = trim((string) ($concoursItem['title'] ?? 'Concours EMSP'));
                    $concoursExcerpt = emsp_home_excerpt((string) ($concoursItem['description'] ?? ''), 140);
                    $concoursAuthor = trim((string) ($concoursItem['first_name'] ?? '') . ' ' . (string) ($concoursItem['last_name'] ?? ''));
                    if ($concoursAuthor === '') {
                        $concoursAuthor = 'Équipe EMSP';
                    }
                    $concoursId = (int) ($concoursItem['id'] ?? 0);
                    $concoursModalId = 'home-concours-preview-' . $concoursId;
                    $concoursPreviewUrl = url('telecharger?id=' . $concoursId . '&preview=1');
                    $concoursThumbUrl = function_exists('emsp_doc_thumb_src') ? emsp_doc_thumb_src($concoursItem) : '';
                    if ($concoursThumbUrl === '') {
                        $concoursThumbUrl = url('assets/images/actu-concours.png');
                    } elseif (!preg_match('#^(https?:)?//#i', $concoursThumbUrl)) {
                        $concoursThumbUrl = url(ltrim(str_replace('\\', '/', $concoursThumbUrl), '/'));
                    }
                    $concoursThumbFallback = url('assets/images/actu-concours.png');
                    $concoursModals[] = [
                        'id' => $concoursModalId,
                        'title' => $concoursTitle,
                        'excerpt' => $concoursExcerpt,
                        'preview_url' => $concoursPreviewUrl,
                        'doc_id' => $concoursId,
                    ];
                    ?>
                    <article class="card home-vitrine-card home-vitrine-card--concours home-moment-card h-100 emsp-editorial-card emsp-motion-item" role="listitem">
                        <div class="home-concours-media">
                            <img
                                src="<?= htmlspecialchars($concoursThumbUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                                alt="Aperçu : <?= htmlspecialchars($concoursTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                                loading="lazy"
                                decoding="async"
                                data-fallback-src="<?= htmlspecialchars($concoursThumbFallback, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                            >
                            <button
                                type="button"
                                class="home-concours-preview-trigger"
                                data-bs-toggle="modal"
                                data-bs-target="#<?= $concoursModalId ?>"
                                data-emsp-concours-preview="<?= $concoursId ?>"
                                aria-label="Aperçu PDF : <?= htmlspecialchars($concoursTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                            >
                                <span class="home-concours-media-label"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Aperçu PDF</span>
                            </button>
                        </div>
                        <div class="card-body">
                            <span class="home-vitrine-kicker home-vitrine-kicker--concours">Concours</span>
                            <h3><?= htmlspecialchars($concoursTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></h3>
                            <p><?= htmlspecialchars($concoursExcerpt, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></p>
                        </div>
                        <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                            <small><?= date('d/m/Y', strtotime((string) ($concoursItem['created_at'] ?? 'now'))) ?></small>
                            <button type="button" class="home-vitrine-link home-vitrine-preview" data-bs-toggle="modal" data-bs-target="#<?= $concoursModalId ?>" data-emsp-concours-preview="<?= $concoursId ?>">Aperçu <i class="bi bi-eye"></i></button>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
</div>

<?php if (!empty($concoursModals)): ?>
<div class="home-concours-modals">
    <?php foreach ($concoursModals as $concoursModal): ?>
        <div class="modal fade concours-preview-modal" id="<?= htmlspecialchars($concoursModal['id'], ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>" tabindex="-1" aria-labelledby="<?= htmlspecialchars($concoursModal['id'], ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>-title" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true" data-emsp-motion-modal="1">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable concours-preview-dialog">
                <div class="modal-content">
                    <div class="modal-header concours-preview-modal-head">
                        <div class="concours-preview-modal-head-text">
                            <span class="concours-page-kicker">Aperçu du concours</span>
                            <h2 class="modal-title fs-5 concours-preview-modal-title" id="<?= htmlspecialchars($concoursModal['id'], ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>-title"><?= htmlspecialchars($concoursModal['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></h2>
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
                                title="Aperçu : <?= htmlspecialchars($concoursModal['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                                class="emsp-doc-preview-frame emsp-inline-pdf-frame"
                                data-emsp-pdf-preview="1"
                                data-pdf-data-url="<?= htmlspecialchars(url('telecharger?id=' . (int) $concoursModal['doc_id'] . '&pdfdata=1'), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                                data-preview-url="<?= htmlspecialchars($concoursModal['preview_url'], ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                            ></iframe>
                        </div>
                        <p class="emsp-doc-preview-note">Défilez le document dans la modale — aucun téléchargement automatique.</p>
                    </div>
                    <div class="modal-footer concours-preview-footer">
                        <p class="concours-preview-footer-excerpt mb-0"><?= htmlspecialchars($concoursModal['excerpt'], ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></p>
                        <a href="document?id=<?= (int) $concoursModal['doc_id'] ?>" class="emsp-btn emsp-btn-primary btn-sm">Voir la fiche</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
