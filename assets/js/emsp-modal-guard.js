(function (window, document) {
    'use strict';

    function resetScrollLockState() {
        if (document.querySelector('.modal.show, .offcanvas.show')) {
            return;
        }
        document.querySelectorAll('.modal-backdrop, .offcanvas-backdrop').forEach(function (node) {
            node.remove();
        });
        document.body.classList.remove('modal-open', 'offcanvas-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
        document.body.style.removeProperty('position');
        document.body.style.removeProperty('top');
        document.body.style.removeProperty('width');
        document.documentElement.style.removeProperty('overflow');
        document.documentElement.classList.remove('emsp-modal-scroll-lock');
    }

    function resetModalBodyState() {
        resetScrollLockState();
    }

    function scheduleReset(delayMs) {
        window.setTimeout(function () {
            if (!document.querySelector('.modal.show')) {
                resetModalBodyState();
            }
        }, delayMs || 0);
    }

    function dedupeBackdrops() {
        var backdrops = document.querySelectorAll('.modal-backdrop');
        for (var i = 1; i < backdrops.length; i += 1) {
            backdrops[i].remove();
        }
    }

    function teleportModalToBody(modal) {
        if (!modal || modal.parentElement === document.body) {
            return;
        }
        document.body.appendChild(modal);
    }

    function blurFocusedDescendant(modal) {
        var active = document.activeElement;
        if (active && modal.contains(active)) {
            active.blur();
        }
    }

    function dismissModal(modal) {
        if (!modal || !window.bootstrap || !window.bootstrap.Modal) {
            modal.classList.remove('show');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            resetModalBodyState();
            scheduleReset(120);
            return;
        }
        blurFocusedDescendant(modal);
        window.bootstrap.Modal.getOrCreateInstance(modal).hide();
        scheduleReset(280);
    }

    function bindModal(modal) {
        if (!modal || modal.dataset.emspModalGuard === '1') {
            return;
        }
        modal.dataset.emspModalGuard = '1';
        teleportModalToBody(modal);

        if (!modal.getAttribute('data-bs-backdrop')) {
            modal.setAttribute('data-bs-backdrop', 'true');
        }
        if (!modal.getAttribute('data-bs-keyboard')) {
            modal.setAttribute('data-bs-keyboard', 'true');
        }

        modal.addEventListener('show.bs.modal', function () {
            document.documentElement.classList.add('emsp-modal-scroll-lock');
            dedupeBackdrops();
        });

        modal.addEventListener('shown.bs.modal', function () {
            dedupeBackdrops();
        });

        modal.addEventListener('hide.bs.modal', function () {
            blurFocusedDescendant(modal);
        });

        modal.addEventListener('hidden.bs.modal', function () {
            blurFocusedDescendant(modal);
            if (!document.querySelector('.modal.show')) {
                resetModalBodyState();
            }
            scheduleReset(80);
            scheduleReset(320);
        });

        modal.addEventListener('click', function (event) {
            if (event.target !== modal) {
                return;
            }
            dismissModal(modal);
        });
    }

    function init() {
        document.querySelectorAll('.modal').forEach(bindModal);

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }
            var openModal = document.querySelector('.modal.show');
            if (!openModal) {
                return;
            }
            dismissModal(openModal);
        });

        if ('MutationObserver' in window) {
            var bodyObserver = new MutationObserver(function () {
                if (!document.querySelector('.modal.show, .offcanvas.show') &&
                    (document.body.classList.contains('modal-open') ||
                     document.body.classList.contains('offcanvas-open') ||
                     document.documentElement.classList.contains('emsp-modal-scroll-lock'))) {
                    resetScrollLockState();
                }
            });
            bodyObserver.observe(document.body, { attributes: true, attributeFilter: ['class'] });

            var mo = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    mutation.addedNodes.forEach(function (node) {
                        if (!node || node.nodeType !== 1) {
                            return;
                        }
                        if (node.classList && node.classList.contains('modal')) {
                            bindModal(node);
                        }
                        if (node.querySelectorAll) {
                            node.querySelectorAll('.modal').forEach(bindModal);
                        }
                    });
                });
            });
            mo.observe(document.body, { childList: true, subtree: true });
        }

        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                resetModalBodyState();
            }
        });
    }

    window.emspModalGuard = {
        reset: resetModalBodyState,
        bind: bindModal
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window, document);
