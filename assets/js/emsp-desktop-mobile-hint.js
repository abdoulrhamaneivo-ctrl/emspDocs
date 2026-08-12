(function (window, document) {
    'use strict';

    var STORAGE_KEY = 'emsp-desktop-mobile-hint-dismissed';
    var LEGACY_STORAGE_KEY = 'emsp_desktop_mobile_hint_dismissed';
    var DISMISS_TTL_MS = 7 * 24 * 60 * 60 * 1000;
    var MIN_WIDTH = 768;
    var AUTH_ROUTES = {
        login: true,
        register: true,
        'forgot-password': true,
        'reset-password': true
    };

    function isStandalonePwa() {
        try {
            if (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) {
                return true;
            }
            if (window.navigator && window.navigator.standalone === true) {
                return true;
            }
        } catch (err) {
            // Ignore detection errors.
        }
        return false;
    }

    function readDismissState() {
        try {
            var raw = window.localStorage.getItem(STORAGE_KEY);
            if (raw) {
                var parsed = JSON.parse(raw);
                if (parsed && parsed.permanent === true) {
                    return { dismissed: true };
                }
                if (parsed && typeof parsed.until === 'number') {
                    if (Date.now() < parsed.until) {
                        return { dismissed: true };
                    }
                    window.localStorage.removeItem(STORAGE_KEY);
                    return { dismissed: false };
                }
            }

            if (window.localStorage.getItem(LEGACY_STORAGE_KEY) === '1') {
                persistDismiss();
                window.localStorage.removeItem(LEGACY_STORAGE_KEY);
                return { dismissed: true };
            }
        } catch (err) {
            // Ignore storage failures.
        }
        return { dismissed: false };
    }

    function isDismissed() {
        return readDismissState().dismissed;
    }

    function persistDismiss() {
        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify({
                until: Date.now() + DISMISS_TTL_MS
            }));
        } catch (err) {
            // Ignore storage failures.
        }
    }

    function currentRoute() {
        return (document.body && document.body.getAttribute('data-route')) || '';
    }

    function isAdminRoute() {
        return /^admin/i.test(currentRoute());
    }

    function isAuthRoute() {
        return Object.prototype.hasOwnProperty.call(AUTH_ROUTES, currentRoute());
    }

    function shouldShow() {
        if (window.innerWidth < MIN_WIDTH) return false;
        if (isStandalonePwa()) return false;
        if (isDismissed()) return false;
        if (isAdminRoute()) return false;
        if (isAuthRoute()) return false;
        return true;
    }

    function syncHint() {
        var hint = document.getElementById('emspDesktopMobileHint');
        if (!hint) return;

        var visible = shouldShow();
        hint.classList.toggle('is-visible', visible);
        hint.setAttribute('aria-hidden', visible ? 'false' : 'true');
        hint.removeAttribute('hidden');

        if (document.body) {
            document.body.classList.toggle('emsp-desktop-hint-visible', visible);
        }
    }

    window.__emspSyncDesktopMobileHint = syncHint;

    function init() {
        var hint = document.getElementById('emspDesktopMobileHint');
        var dismiss = document.getElementById('emspDesktopMobileHintDismiss');
        if (!hint) return;

        syncHint();

        if (dismiss) {
            dismiss.addEventListener('click', function () {
                persistDismiss();
                hint.classList.remove('is-visible');
                hint.setAttribute('aria-hidden', 'true');
                if (document.body) {
                    document.body.classList.remove('emsp-desktop-hint-visible');
                }
            });
        }

        window.addEventListener('resize', syncHint, { passive: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window, document);
