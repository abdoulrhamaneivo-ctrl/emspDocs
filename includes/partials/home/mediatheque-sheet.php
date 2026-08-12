<?php
/** @var array $mediathequeDiscovery */
$discoveryCategories = (array) ($mediathequeDiscovery['categories'] ?? []);
$discoveryItems = (array) ($mediathequeDiscovery['items'] ?? []);
$hasDiscoveryItems = !empty($discoveryItems);
$mediathequeFullUrl = function_exists('url') ? url('mediatheque') : 'mediatheque';
?>
<?php if ($hasDiscoveryItems): ?>
<?php if (!function_exists('emsp_should_show_mobile_mediatheque_fab') || emsp_should_show_mobile_mediatheque_fab()): ?>
<button
    type="button"
    class="emsp-mediatheque-fab d-md-none"
    data-emsp-mediatheque-open="1"
    data-bs-toggle="modal"
    data-bs-target="#emspMediathequeSheet"
    aria-label="Découvrir la médiathèque EMSP"
>
    <i class="bi bi-collection-play-fill" aria-hidden="true"></i>
    <span class="emsp-mediatheque-fab__label">Médias</span>
</button>
<?php endif; ?>

<div
    class="modal fade emsp-mediatheque-sheet"
    id="emspMediathequeSheet"
    tabindex="-1"
    aria-labelledby="emspMediathequeSheetTitle"
    aria-hidden="true"
    data-bs-backdrop="true"
    data-bs-keyboard="true"
    data-emsp-mediatheque-sheet="1"
>
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="emsp-mediatheque-sheet__handle" aria-hidden="true"></div>
            <div class="modal-header emsp-mediatheque-sheet__head">
                <div class="emsp-mediatheque-sheet__head-copy">
                    <span class="emsp-mediatheque-sheet__kicker">Espace multimédia</span>
                    <h2 class="modal-title h5 mb-0" id="emspMediathequeSheetTitle">Découvrir la médiathèque</h2>
                </div>
                <button type="button" class="btn-close emsp-modal-close-touch" data-bs-dismiss="modal" aria-label="Fermer la médiathèque"></button>
            </div>

            <div class="emsp-mediatheque-sheet__filters" role="tablist" aria-label="Filtrer les médias">
                <div class="emsp-mediatheque-sheet__filters-track">
                    <?php foreach ($discoveryCategories as $index => $category): ?>
                        <?php
                        $catKey = (string) ($category['key'] ?? '');
                        $catLabel = (string) ($category['label'] ?? '');
                        ?>
                        <button
                            type="button"
                            class="emsp-mediatheque-sheet__filter<?= $index === 0 ? ' is-active' : '' ?>"
                            data-emsp-mediatheque-filter="<?= htmlspecialchars($catKey, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                            role="tab"
                            aria-selected="<?= $index === 0 ? 'true' : 'false' ?>"
                        ><?= htmlspecialchars($catLabel, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="modal-body emsp-mediatheque-sheet__body">
                <ul class="emsp-mediatheque-sheet__list" role="list">
                    <?php foreach ($discoveryItems as $item): ?>
                        <?php
                        $kind = (string) ($item['kind'] ?? 'image');
                        $filterKey = (string) ($item['filter_key'] ?? 'all');
                        $itemTitle = (string) ($item['title'] ?? 'Media EMSP');
                        $itemDate = (string) ($item['date_label'] ?? '');
                        $categoryLabel = (string) ($item['category_label'] ?? '');
                        ?>
                        <li
                            class="emsp-mediatheque-sheet__item"
                            data-emsp-mediatheque-item="1"
                            data-kind="<?= htmlspecialchars($kind, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                            data-filter="<?= htmlspecialchars($filterKey, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                            data-title="<?= htmlspecialchars($itemTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                            <?php if ($kind === 'image'): ?>
                                data-src="<?= htmlspecialchars((string) ($item['src'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                            <?php else: ?>
                                data-embed="<?= htmlspecialchars((string) ($item['embed'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                                data-is-youtube="<?= !empty($item['is_youtube']) ? '1' : '0' ?>"
                                data-thumb="<?= htmlspecialchars((string) ($item['thumb'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                                data-initial="<?= htmlspecialchars((string) ($item['initial'] ?? 'V'), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                            <?php endif; ?>
                        >
                            <button type="button" class="emsp-mediatheque-sheet__row" data-emsp-mediatheque-open-item="1">
                                <span class="emsp-mediatheque-sheet__thumb" aria-hidden="true">
                                    <?php if ($kind === 'image'): ?>
                                        <img
                                            src="<?= htmlspecialchars((string) ($item['src'] ?? 'assets/images/media-thumb-3.jpg'), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                                            alt=""
                                            loading="lazy"
                                            data-fallback-src="assets/images/media-thumb-3.jpg"
                                        >
                                    <?php else: ?>
                                        <?php if (!empty($item['thumb'])): ?>
                                            <img
                                                src="<?= htmlspecialchars((string) $item['thumb'], ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                                                alt=""
                                                loading="lazy"
                                                data-fallback-src="assets/images/media-thumb-3.jpg"
                                            >
                                        <?php else: ?>
                                            <span class="emsp-mediatheque-sheet__thumb-fallback"><?= htmlspecialchars((string) ($item['initial'] ?? 'V'), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                        <span class="emsp-mediatheque-sheet__play"><i class="bi bi-play-fill"></i></span>
                                    <?php endif; ?>
                                </span>
                                <span class="emsp-mediatheque-sheet__copy">
                                    <span class="emsp-mediatheque-sheet__type"><?= $kind === 'image' ? 'Photo' : 'Vidéo' ?></span>
                                    <span class="emsp-mediatheque-sheet__title"><?= htmlspecialchars($itemTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></span>
                                    <span class="emsp-mediatheque-sheet__meta">
                                        <?php if ($categoryLabel !== '' && $kind === 'image'): ?>
                                            <span><?= htmlspecialchars($categoryLabel, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                        <?php if ($itemDate !== ''): ?>
                                            <span><?= htmlspecialchars($itemDate, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </span>
                                </span>
                                <i class="bi bi-chevron-right emsp-mediatheque-sheet__chevron" aria-hidden="true"></i>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p class="emsp-mediatheque-sheet__empty text-muted" data-emsp-mediatheque-empty hidden>Aucun média dans cette catégorie.</p>
            </div>

            <div class="modal-footer emsp-mediatheque-sheet__footer">
                <a href="<?= htmlspecialchars($mediathequeFullUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>" class="btn btn-emsp w-100 emsp-mediatheque-sheet__cta">
                    Ouvrir la médiathèque complète
                    <i class="bi bi-arrow-right-short ms-1" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<div
    class="modal fade emsp-mediatheque-viewer"
    id="emspMediathequeViewer"
    tabindex="-1"
    aria-labelledby="emspMediathequeViewerTitle"
    aria-hidden="true"
    data-bs-backdrop="true"
    data-bs-keyboard="true"
    data-emsp-mediatheque-viewer="1"
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header emsp-mediatheque-viewer__head">
                <h3 class="modal-title h6 mb-0" id="emspMediathequeViewerTitle">Media EMSP</h3>
                <button type="button" class="btn-close btn-close-white emsp-modal-close-touch" data-bs-dismiss="modal" aria-label="Fermer le média"></button>
            </div>
            <div class="modal-body emsp-mediatheque-viewer__body">
                <div class="emsp-mediatheque-viewer__stage" data-emsp-mediatheque-stage="1"></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
