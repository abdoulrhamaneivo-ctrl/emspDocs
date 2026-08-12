<?php
/** @var list<array<string, mixed>> $mediathequePreviewPhotos */
$previewPhotos = array_slice((array) ($mediathequePreviewPhotos ?? []), 0, 8);
$hasPhotos = $previewPhotos !== [];

$mediathequeFullUrl = function_exists('url') ? url('mediatheque') : 'mediatheque';
$fallbackThumb = function_exists('url') ? url('assets/images/media-thumb-3.jpg') : 'assets/images/media-thumb-3.jpg';

$resolvePhotoSrc = static function (array $photo) use ($fallbackThumb): string {
    $src = trim((string) ($photo['src'] ?? ''));
    if ($src === '') {
        return $fallbackThumb;
    }
    if (preg_match('#^(https?:)?//#i', $src)) {
        return $src;
    }

    return function_exists('url') ? url(ltrim(str_replace('\\', '/', $src), '/')) : $src;
};
?>
<section
    class="emsp-mediatheque-preview is-motion-visible<?= $hasPhotos ? '' : ' emsp-mediatheque-preview--empty' ?>"
    aria-labelledby="emspMediathequePreviewTitle"
>
    <div class="emsp-mediatheque-preview__head">
        <div>
            <span class="emsp-mediatheque-preview__kicker">Espace multimédia</span>
            <h2 class="emsp-mediatheque-preview__title" id="emspMediathequePreviewTitle">Médiathèque</h2>
            <p class="emsp-mediatheque-preview__lede">Photos et reportages des activités EMSP</p>
        </div>
        <a href="<?= htmlspecialchars($mediathequeFullUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>" class="emsp-mediatheque-preview__cta">
            Voir plus
            <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
        </a>
    </div>

    <?php if ($hasPhotos): ?>
        <div
            class="emsp-mediatheque-preview__carousel"
            data-emsp-mediatheque-carousel="1"
            aria-roledescription="carousel"
            aria-label="Photos récentes de la médiathèque EMSP"
        >
            <div class="emsp-mediatheque-preview__viewport">
                <div class="emsp-mediatheque-preview__track" data-emsp-carousel-track="1">
                    <?php foreach ($previewPhotos as $index => $photo): ?>
                        <?php
                        $photoTitle = trim((string) ($photo['title'] ?? 'Photo EMSP'));
                        $albumLabel = trim((string) ($photo['category_label'] ?? 'Album EMSP'));
                        $categoryRaw = (string) ($photo['category_raw'] ?? '');
                        $photoSrc = $resolvePhotoSrc($photo);
                        $albumHref = 'mediatheque?album=' . rawurlencode($categoryRaw) . '#phototheque';
                        $slideId = 'emspMediathequeSlide' . ($index + 1);
                        ?>
                        <article
                            class="emsp-mediatheque-preview__slide<?= $index === 0 ? ' is-active' : '' ?>"
                            id="<?= htmlspecialchars($slideId, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                            data-emsp-carousel-slide="1"
                            role="group"
                            aria-roledescription="slide"
                            aria-label="<?= htmlspecialchars(($index + 1) . ' sur ' . count($previewPhotos), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                            aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>"
                        >
                            <a
                                href="<?= htmlspecialchars($albumHref, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                                class="emsp-mediatheque-preview__card"
                                aria-label="<?= htmlspecialchars($photoTitle . ' — album ' . $albumLabel, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                            >
                                <div class="emsp-mediatheque-preview__visual">
                                    <img
                                        src="<?= htmlspecialchars($photoSrc, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                                        alt=""
                                        loading="<?= $index === 0 ? 'eager' : 'lazy' ?>"
                                        decoding="async"
                                        data-fallback-src="<?= htmlspecialchars($fallbackThumb, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                                    >
                                    <div class="emsp-mediatheque-preview__overlay">
                                        <span class="emsp-mediatheque-preview__album"><?= htmlspecialchars($albumLabel, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></span>
                                        <span class="emsp-mediatheque-preview__caption"><?= htmlspecialchars($photoTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></span>
                                    </div>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="emsp-mediatheque-preview__dots" data-emsp-carousel-dots="1" role="tablist" aria-label="Choisir une photo">
                <?php foreach ($previewPhotos as $index => $photo): ?>
                    <button
                        type="button"
                        class="emsp-mediatheque-preview__dot<?= $index === 0 ? ' is-active' : '' ?>"
                        data-emsp-carousel-dot="<?= (int) $index ?>"
                        role="tab"
                        aria-selected="<?= $index === 0 ? 'true' : 'false' ?>"
                        aria-controls="emspMediathequeSlide<?= (int) ($index + 1) ?>"
                        aria-label="Photo <?= (int) ($index + 1) ?>"
                    ></button>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="emsp-mediatheque-preview__empty" role="status">
            <p>Les albums photo seront bientôt disponibles.</p>
            <a href="<?= htmlspecialchars($mediathequeFullUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>" class="emsp-mediatheque-preview__cta emsp-mediatheque-preview__cta--inline">
                Explorer la médiathèque
                <i class="bi bi-images" aria-hidden="true"></i>
            </a>
        </div>
    <?php endif; ?>
</section>
