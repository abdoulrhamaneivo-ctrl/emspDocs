(function (window, document) {
    'use strict';

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
            return;
        }
        fn();
    }

    function isMobileViewport() {
        return window.matchMedia('(max-width: 767.98px)').matches;
    }

    function prefersReducedMotion() {
        return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }

    function shouldHideDepositFab(root) {
        if (!root) {
            return true;
        }

        var route = (document.body && document.body.getAttribute('data-route')) || '';
        if (route === 'upload') {
            return true;
        }

        if (document.querySelector('#uploadForm, #documentInput, #emsp-scan-overlay, .emsp-scan-overlay.show')) {
            return true;
        }

        if (document.body && document.body.classList.contains('emsp-admin-body')) {
            return true;
        }

        return false;
    }

    function shouldHideGuestFab() {
        if (document.body && document.body.classList.contains('emsp-admin-body')) {
            return true;
        }

        var route = (document.body && document.body.getAttribute('data-route')) || '';
        var hidden = ['login', 'register', 'forgot-password', 'reset-password', 'pending-status', 'verify-email', 'upload'];
        return hidden.indexOf(route) >= 0;
    }

    function initFab(root) {
        if (!root || root.dataset.emspFabBound === '1') {
            return;
        }
        root.dataset.emspFabBound = '1';

        var mode = root.getAttribute('data-emsp-mobile-fab') || 'deposit';
        var isGuest = mode === 'guest';
        var toggle = root.querySelector('.emsp-mobile-fab__toggle');
        var menu = root.querySelector('.emsp-mobile-fab__menu');
        var scrim = root.querySelector('[data-emsp-fab-scrim]');
        var uploadUrl = root.getAttribute('data-upload-url') || '';
        var scanUrl = root.getAttribute('data-scan-url') || uploadUrl + '?entry=scan';
        var hasScanner = root.getAttribute('data-has-scanner') === '1';

        function setOpen(isOpen) {
            if (!toggle || !menu) {
                return;
            }
            root.classList.toggle('is-open', isOpen);
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            menu.hidden = !isOpen;
            if (scrim) {
                scrim.hidden = !isOpen;
            }
            document.body.classList.toggle('emsp-mobile-fab-open', isOpen);
            if (isOpen && !prefersReducedMotion() && navigator.vibrate) {
                try {
                    navigator.vibrate(10);
                } catch (err) {
                    /* ignore */
                }
            }
        }

        function syncVisibility() {
            var visible = isMobileViewport();
            if (isGuest) {
                visible = visible && !shouldHideGuestFab();
            } else {
                visible = visible && !shouldHideDepositFab(root);
            }
            root.hidden = !visible;
            root.setAttribute('aria-hidden', visible ? 'false' : 'true');
            if (!visible) {
                setOpen(false);
            }
        }

        if (toggle) {
            toggle.addEventListener('click', function () {
                setOpen(!root.classList.contains('is-open'));
            });
        }

        if (scrim) {
            scrim.addEventListener('click', function () {
                setOpen(false);
            });
        }

        if (!isGuest) {
            root.querySelectorAll('[data-emsp-fab-action="scan"]').forEach(function (button) {
                button.addEventListener('click', function () {
                    setOpen(false);
                    if (window.EMSPScan && typeof window.EMSPScan.open === 'function') {
                        if ((document.body.getAttribute('data-route') || '') === 'upload') {
                            window.EMSPScan.open();
                            return;
                        }
                    }
                    window.location.href = scanUrl;
                });
            });

            root.querySelectorAll('[data-emsp-fab-action="deposit"]').forEach(function (link) {
                link.addEventListener('click', function () {
                    setOpen(false);
                });
            });

            if (!hasScanner && menu) {
                menu.classList.add('emsp-mobile-fab__menu--deposit-only');
            }
        } else {
            root.querySelectorAll('[data-emsp-fab-action="login"]').forEach(function (button) {
                button.addEventListener('click', function () {
                    setOpen(false);
                    var modal = document.getElementById('emspQuickLoginModal');
                    if (modal && window.bootstrap && window.bootstrap.Modal) {
                        window.bootstrap.Modal.getOrCreateInstance(modal).show();
                        return;
                    }
                    var loginLink = document.querySelector('.emsp-open-login-modal');
                    if (loginLink) {
                        loginLink.click();
                    }
                });
            });

            root.querySelectorAll('[data-emsp-fab-action="register"], [data-emsp-fab-action="browse"]').forEach(function (link) {
                link.addEventListener('click', function () {
                    setOpen(false);
                });
            });
        }

        document.addEventListener('click', function (event) {
            if (!root.classList.contains('is-open')) {
                return;
            }
            if (root.contains(event.target)) {
                return;
            }
            setOpen(false);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        });

        window.addEventListener('resize', syncVisibility);
        window.addEventListener('pageshow', syncVisibility);
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') {
                syncVisibility();
            }
        });

        syncVisibility();
    }

    ready(function () {
        document.querySelectorAll('[data-emsp-mobile-fab]').forEach(initFab);
    });
})(window, document);
