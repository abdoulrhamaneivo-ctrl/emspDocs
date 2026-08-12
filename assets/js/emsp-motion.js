(function (window, document) {
    'use strict';

    function prefersReducedMotion() {
        return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }

    function isMobileViewport() {
        return !!(window.matchMedia && window.matchMedia('(max-width: 767.98px)').matches);
    }

    function shouldSkipEntranceMotion() {
        return prefersReducedMotion() || isMobileViewport();
    }

    function onReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
            return;
        }
        fn();
    }

    function initPageEnter() {
        var main = document.querySelector('.site-main');
        if (!main || main.dataset.emspMotionPage === '1') {
            return;
        }
        main.dataset.emspMotionPage = '1';
        if (!shouldSkipEntranceMotion()) {
            main.classList.add('emsp-page-entered');
        }
    }

    function initStaggerItems(scope) {
        var skipMotion = shouldSkipEntranceMotion();
        var items = (scope || document).querySelectorAll('.emsp-motion-item:not([data-emsp-motion-bound])');
        if (!items.length) {
            return;
        }

        if (skipMotion || !('IntersectionObserver' in window)) {
            items.forEach(function (el) {
                el.dataset.emspMotionBound = '1';
                el.classList.add('is-motion-visible');
            });
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }
                var el = entry.target;
                if (el.classList.contains('is-motion-visible')) {
                    observer.unobserve(el);
                    return;
                }
                var siblings = el.parentElement
                    ? Array.prototype.filter.call(el.parentElement.children, function (node) {
                        return node.classList && node.classList.contains('emsp-motion-item');
                    })
                    : [];
                var index = siblings.indexOf(el);
                var delay = Math.min(index, 10) * 55;
                window.setTimeout(function () {
                    el.classList.add('is-motion-visible');
                }, delay);
                observer.unobserve(el);
            });
        }, {
            rootMargin: '0px 0px -8% 0px',
            threshold: 0.08
        });

        items.forEach(function (el) {
            el.dataset.emspMotionBound = '1';
            observer.observe(el);
        });
    }

    function initScrollReveals(scope) {
        var skipMotion = shouldSkipEntranceMotion();
        var nodes = (scope || document).querySelectorAll('[data-emsp-scroll-reveal]:not([data-emsp-reveal-bound])');
        if (!nodes.length) {
            return;
        }

        if (skipMotion || !('IntersectionObserver' in window)) {
            nodes.forEach(function (el) {
                el.dataset.emspRevealBound = '1';
                el.classList.add('is-scroll-revealed');
            });
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }
                entry.target.classList.add('is-scroll-revealed');
                observer.unobserve(entry.target);
            });
        }, {
            rootMargin: '0px 0px -10% 0px',
            threshold: 0.1
        });

        nodes.forEach(function (el) {
            el.dataset.emspRevealBound = '1';
            observer.observe(el);
        });
    }

    function initModalTransitions() {
        document.querySelectorAll('.modal.emsp-doc-preview-modal, .modal.docs-quickview-modal, .modal[data-emsp-motion-modal="1"]').forEach(function (modal) {
            if (modal.dataset.emspMotionModalBound === '1') {
                return;
            }
            modal.dataset.emspMotionModalBound = '1';

            modal.addEventListener('show.bs.modal', function () {
                if (prefersReducedMotion()) {
                    return;
                }
                modal.classList.remove('is-motion-closing');
                modal.classList.add('is-motion-opening');
            });

            modal.addEventListener('shown.bs.modal', function () {
                modal.classList.remove('is-motion-opening');
            });

            modal.addEventListener('hide.bs.modal', function () {
                if (prefersReducedMotion()) {
                    return;
                }
                modal.classList.add('is-motion-closing');
            });

            modal.addEventListener('hidden.bs.modal', function () {
                modal.classList.remove('is-motion-opening', 'is-motion-closing');
            });
        });
    }

    function initNativeTouchFeedback(scope) {
        var reduce = prefersReducedMotion();
        var touchNodes = (scope || document).querySelectorAll(
            '.emsp-mobile-bottom-nav__item:not([data-emsp-native-touch]), ' +
            '.emsp-mobile-fab__toggle:not([data-emsp-native-touch]), ' +
            '.emsp-mobile-fab__action:not([data-emsp-native-touch]), ' +
            '.docs-workspace-item:not([data-emsp-native-touch]), ' +
            '.docs-workspace-native-thumb-btn:not([data-emsp-native-touch]), ' +
            '.docs-workspace-row-preview-btn:not([data-emsp-native-touch]), ' +
            'a.emsp-nav-link:not([data-emsp-native-touch]), ' +
            '.home-vitrine-card:not([data-emsp-native-touch]), ' +
            '.formation-card:not([data-emsp-native-touch]), ' +
            '.dropdown-item:not([data-emsp-native-touch])'
        );

        touchNodes.forEach(function (node) {
            node.dataset.emspNativeTouch = '1';
            node.addEventListener('touchstart', function () {
                node.classList.add('is-native-press', 'is-touch-active');
                if (!reduce && navigator.vibrate) {
                    try {
                        navigator.vibrate(8);
                    } catch (err) {
                        /* ignore */
                    }
                }
            }, { passive: true });
            var clear = function () {
                node.classList.remove('is-native-press', 'is-touch-active');
            };
            node.addEventListener('touchend', clear);
            node.addEventListener('touchcancel', clear);
        });
    }

    function initInteractiveHover(scope) {
        if (prefersReducedMotion()) {
            return;
        }

        var selectors = [
            '.emsp-btn:not([data-emsp-hover-bound])',
            '.docs-workspace-action:not([data-emsp-hover-bound])',
            '.docs-workspace-iconbtn:not([data-emsp-hover-bound])',
            '.docs-workspace-item:not([data-emsp-hover-bound])',
            '.docs-workspace-card:not([data-emsp-hover-bound])',
            '.docs-workspace-row--with-thumb:not([data-emsp-hover-bound])',
            '.docs-workspace-chip:not([data-emsp-hover-bound])',
            '.docs-workspace-view-btn:not([data-emsp-hover-bound])',
            '.site-header .nav-link:not([data-emsp-hover-bound])',
            '.emsp-navbar-actions-desktop .btn:not([data-emsp-hover-bound])',
            '.emsp-avatar-btn:not([data-emsp-hover-bound])',
            '.emsp-profile-doc-card:not([data-emsp-hover-bound])',
            '.emsp-profile-panel:not([data-emsp-hover-bound])',
            '.emsp-doc-sidebar:not([data-emsp-hover-bound])',
            '.home-doc-card:not([data-emsp-hover-bound])',
            '.home-step-card:not([data-emsp-hover-bound])',
            '.home-vitrine-card:not([data-emsp-hover-bound])',
            '.emsp-featured-card:not([data-emsp-hover-bound])',
            '.formation-card:not([data-emsp-hover-bound])',
            '.emsp-editorial-card:not([data-emsp-hover-bound])',
            '.site-main .card:not([data-emsp-hover-bound])',
            '.album-card:not([data-emsp-hover-bound])',
            '.journal-card:not([data-emsp-hover-bound])',
            '.concours-card:not([data-emsp-hover-bound])',
            '.home-hero-stat:not([data-emsp-hover-bound])',
            '.emsp-mini-card:not([data-emsp-hover-bound])',
            '.home-moment-card:not([data-emsp-hover-bound])',
            '.home-guest-headline-card:not([data-emsp-hover-bound])',
            '.playlist-item:not([data-emsp-hover-bound])',
            '.emsp-mediatheque-preview__card:not([data-emsp-hover-bound])',
            '.home-journal-feature-shell:not([data-emsp-hover-bound])',
            '.emsp-native-list-item:not([data-emsp-hover-bound])',
            '.institution-panel:not([data-emsp-hover-bound])',
            '.formations-panel:not([data-emsp-hover-bound])',
            '.emsp-float-card:not([data-emsp-hover-bound])',
            '.emsp-gate-card:not([data-emsp-hover-bound])',
            '.emsp-form-section:not([data-emsp-hover-bound])',
            '.journal-feature-card:not([data-emsp-hover-bound])',
            '.emsp-dashboard-hero-card:not([data-emsp-hover-bound])',
            '.emsp-profile-cta:not([data-emsp-hover-bound])',
            '.formations-tronc-link:not([data-emsp-hover-bound])',
            '.journal-interaction-card:not([data-emsp-hover-bound])',
            '.journal-state-card:not([data-emsp-hover-bound])',
            '.journal-social-card:not([data-emsp-hover-bound])',
            '.card-dg:not([data-emsp-hover-bound])',
            '.card-de:not([data-emsp-hover-bound])',
            '.emsp-error-card:not([data-emsp-hover-bound])'
        ].join(',');

        (scope || document).querySelectorAll(selectors).forEach(function (node) {
            node.dataset.emspHoverBound = '1';
            node.addEventListener('mouseenter', function () {
                node.classList.add('is-js-hover');
            });
            node.addEventListener('mouseleave', function () {
                node.classList.remove('is-js-hover');
            });
            node.addEventListener('mousedown', function () {
                node.classList.add('is-js-press');
            });
            node.addEventListener('mouseup', function () {
                node.classList.remove('is-js-press');
            });
            node.addEventListener('mouseleave', function () {
                node.classList.remove('is-js-press');
            });
            node.addEventListener('touchstart', function () {
                node.classList.add('is-js-press');
            }, { passive: true });
            node.addEventListener('touchend', function () {
                node.classList.remove('is-js-press');
            });
            node.addEventListener('touchcancel', function () {
                node.classList.remove('is-js-press');
            });
        });
    }

    function initSmoothAnchors() {
        if (prefersReducedMotion()) {
            return;
        }
        document.querySelectorAll('a[href^="#"]:not([data-emsp-no-smooth])').forEach(function (link) {
            if (link.dataset.emspSmoothBound === '1') {
                return;
            }
            link.dataset.emspSmoothBound = '1';
            link.addEventListener('click', function (event) {
                var hash = link.getAttribute('href');
                if (!hash || hash.length < 2) {
                    return;
                }
                var target = document.querySelector(hash);
                if (!target) {
                    return;
                }
                event.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    }

    function initPageTransitions() {
        if (shouldSkipEntranceMotion()) {
            return;
        }
        document.querySelectorAll('a[href]:not([target="_blank"]):not([download]):not([href^="#"]):not([href^="mailto:"]):not([href^="tel:"])').forEach(function (link) {
            if (link.dataset.emspNavBound === '1') {
                return;
            }
            if (link.closest('.modal, .dropdown-menu, .pagination, form, [data-emsp-no-transition]')) {
                return;
            }
            var url = link.href;
            if (!url || url.indexOf(window.location.origin) !== 0) {
                return;
            }
            link.dataset.emspNavBound = '1';
            link.addEventListener('click', function (event) {
                if (event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }
                document.body.classList.add('emsp-page-leaving');
            });
        });
    }

    function releaseScrollLock() {
        if (window.emspModalGuard && typeof window.emspModalGuard.reset === 'function') {
            window.emspModalGuard.reset();
            return;
        }
        document.body.classList.remove('modal-open', 'offcanvas-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    }

    function bindOffcanvasScrollGuard() {
        document.querySelectorAll('.offcanvas').forEach(function (panel) {
            if (panel.dataset.emspOffcanvasGuard === '1') {
                return;
            }
            panel.dataset.emspOffcanvasGuard = '1';
            panel.addEventListener('hidden.bs.offcanvas', function () {
                window.setTimeout(releaseScrollLock, 80);
                window.setTimeout(releaseScrollLock, 280);
            });
        });
    }

    onReady(function () {
        initPageEnter();
        initStaggerItems(document);
        initScrollReveals(document);
        initModalTransitions();
        initInteractiveHover(document);
        initNativeTouchFeedback(document);
        initSmoothAnchors();
        initPageTransitions();
        bindOffcanvasScrollGuard();

        if (prefersReducedMotion() || isMobileViewport()) {
            document.body.classList.remove('emsp-page-leaving');
            document.querySelectorAll('.emsp-motion-item, [data-emsp-scroll-reveal]').forEach(function (el) {
                el.classList.add('is-motion-visible', 'is-scroll-revealed');
            });
        } else if (window.matchMedia) {
            var motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
            var onMotionPrefChange = function () {
                if (motionQuery.matches) {
                    document.body.classList.remove('emsp-page-leaving');
                    document.querySelectorAll('.emsp-motion-item, [data-emsp-scroll-reveal]').forEach(function (el) {
                        el.classList.add('is-motion-visible', 'is-scroll-revealed');
                    });
                }
            };
            if (typeof motionQuery.addEventListener === 'function') {
                motionQuery.addEventListener('change', onMotionPrefChange);
            } else if (typeof motionQuery.addListener === 'function') {
                motionQuery.addListener(onMotionPrefChange);
            }
        }

        if ('MutationObserver' in window) {
            var scheduled = false;
            var mo = new MutationObserver(function () {
                if (scheduled) {
                    return;
                }
                scheduled = true;
                window.requestAnimationFrame(function () {
                    scheduled = false;
                    initStaggerItems(document);
                    initScrollReveals(document);
                    initModalTransitions();
                    initInteractiveHover(document);
                    bindOffcanvasScrollGuard();
                });
            });
            mo.observe(document.body, { childList: true, subtree: true });
        }
    });

    window.emspMotion = {
        refresh: function (scope) {
            initStaggerItems(scope || document);
            initScrollReveals(scope || document);
            initModalTransitions();
            initInteractiveHover(scope || document);
            initNativeTouchFeedback(scope || document);
        }
    };
})(window, document);
