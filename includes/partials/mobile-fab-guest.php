<?php
if (!function_exists('emsp_should_show_mobile_guest_fab') || !emsp_should_show_mobile_guest_fab()) {
    return;
}

$fabRegisterUrl = function_exists('url') ? url('register') : 'register';
$fabDocumentsUrl = function_exists('url') ? url('documents') : 'documents';
?>
<div
    class="emsp-mobile-fab emsp-mobile-fab--guest"
    id="emspMobileGuestFab"
    data-emsp-mobile-fab="guest"
    aria-hidden="true"
    hidden
>
    <div class="emsp-mobile-fab__scrim" data-emsp-fab-scrim hidden aria-hidden="true"></div>
    <div class="emsp-mobile-fab__menu" id="emspMobileGuestFabMenu" hidden>
        <a class="emsp-mobile-fab__action emsp-mobile-fab__action--accent" href="<?= h($fabRegisterUrl) ?>" data-emsp-fab-action="register">
            <i class="bi bi-person-plus-fill" aria-hidden="true"></i>
            <span>S'inscrire</span>
        </a>
        <button type="button" class="emsp-mobile-fab__action" data-emsp-fab-action="login">
            <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
            <span>Se connecter</span>
        </button>
        <a class="emsp-mobile-fab__action" href="<?= h($fabDocumentsUrl) ?>" data-emsp-fab-action="browse">
            <i class="bi bi-collection" aria-hidden="true"></i>
            <span>Bibliothèque</span>
        </a>
    </div>
    <button
        type="button"
        class="emsp-mobile-fab__toggle emsp-mobile-fab__toggle--guest"
        id="emspMobileGuestFabToggle"
        aria-expanded="false"
        aria-controls="emspMobileGuestFabMenu"
        aria-label="Actions visiteur"
    >
        <i class="bi bi-person-fill emsp-mobile-fab__icon emsp-mobile-fab__icon--open" aria-hidden="true"></i>
        <i class="bi bi-x-lg emsp-mobile-fab__icon emsp-mobile-fab__icon--close" aria-hidden="true"></i>
    </button>
</div>
