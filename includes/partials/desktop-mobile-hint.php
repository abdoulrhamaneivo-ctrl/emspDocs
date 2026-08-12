<?php
/**
 * Bandeau desktop invitant à l'expérience mobile.
 * Visibilité : script inline anti-flicker + assets/js/emsp-desktop-mobile-hint.js.
 */
?>
<div class="emsp-desktop-mobile-hint" id="emspDesktopMobileHint" aria-hidden="true" role="region" aria-label="Conseil mobile" aria-live="polite">
    <div class="emsp-desktop-mobile-hint__inner">
        <span class="emsp-desktop-mobile-hint__icon" aria-hidden="true"><i class="bi bi-phone"></i></span>
        <p class="emsp-desktop-mobile-hint__text">
            <strong>L'expérience EMSP Docs est pensée pour le mobile</strong>
            — ouvrez le site sur votre téléphone pour déposer, scanner et consulter vos documents.
        </p>
        <button type="button" class="emsp-desktop-mobile-hint__dismiss" id="emspDesktopMobileHintDismiss" aria-label="Masquer ce conseil">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>
</div>
<script>
(function () {
    var hint = document.getElementById('emspDesktopMobileHint');
    if (!hint || window.innerWidth < 768) return;

    try {
        if (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) return;
        if (window.navigator && window.navigator.standalone === true) return;
    } catch (e) {}

    var route = (document.body && document.body.getAttribute('data-route')) || '';
    if (/^admin/i.test(route)) return;
    if (route === 'login' || route === 'register' || route === 'forgot-password' || route === 'reset-password') return;

    try {
        var raw = window.localStorage.getItem('emsp-desktop-mobile-hint-dismissed');
        if (raw) {
            var parsed = JSON.parse(raw);
            if (parsed && (parsed.permanent === true || (typeof parsed.until === 'number' && Date.now() < parsed.until))) return;
        } else if (window.localStorage.getItem('emsp_desktop_mobile_hint_dismissed') === '1') {
            return;
        }
    } catch (e) {}

    hint.classList.add('is-visible');
    hint.setAttribute('aria-hidden', 'false');
    if (document.body) {
        document.body.classList.add('emsp-desktop-hint-visible');
    }
})();
</script>
