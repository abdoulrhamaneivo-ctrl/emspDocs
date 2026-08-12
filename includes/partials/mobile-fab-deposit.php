<?php
if (!function_exists('emsp_should_show_mobile_deposit_fab') || !emsp_should_show_mobile_deposit_fab()) {
    return;
}

$fabBase = function_exists('url') ? rtrim((string) url(''), '/') : rtrim((string) ($base ?? ''), '/');
$fabUploadUrl = $fabBase . '/upload';
$fabScanUrl = $fabUploadUrl . '?entry=scan';
$hasScanner = is_file(dirname(__DIR__, 2) . '/assets/js/emsp-scanner.js');
?>
<div
    class="emsp-mobile-fab"
    id="emspMobileFab"
    data-emsp-mobile-fab="deposit"
    data-upload-url="<?= h($fabUploadUrl) ?>"
    data-scan-url="<?= h($fabScanUrl) ?>"
    data-has-scanner="<?= $hasScanner ? '1' : '0' ?>"
    aria-hidden="true"
    hidden
>
    <div class="emsp-mobile-fab__scrim" data-emsp-fab-scrim hidden aria-hidden="true"></div>
    <div class="emsp-mobile-fab__menu" id="emspMobileFabMenu" hidden>
        <?php if ($hasScanner): ?>
            <button type="button" class="emsp-mobile-fab__action" data-emsp-fab-action="scan">
                <i class="bi bi-camera-fill" aria-hidden="true"></i>
                <span>Scanner</span>
            </button>
        <?php endif; ?>
        <a class="emsp-mobile-fab__action" href="<?= h($fabUploadUrl) ?>" data-emsp-fab-action="deposit">
            <i class="bi bi-cloud-arrow-up-fill" aria-hidden="true"></i>
            <span>Déposer</span>
        </a>
    </div>
    <button
        type="button"
        class="emsp-mobile-fab__toggle"
        id="emspMobileFabToggle"
        aria-expanded="false"
        aria-controls="emspMobileFabMenu"
        aria-label="Actions de dépôt"
    >
        <i class="bi bi-plus-lg emsp-mobile-fab__icon emsp-mobile-fab__icon--open" aria-hidden="true"></i>
        <i class="bi bi-x-lg emsp-mobile-fab__icon emsp-mobile-fab__icon--close" aria-hidden="true"></i>
    </button>
</div>
