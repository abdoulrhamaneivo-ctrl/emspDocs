(function (window, document) {
    'use strict';

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
            return;
        }
        fn();
    }

    ready(function () {
        var body = document.body;
        var wrapper = document.getElementById('admin-wrapper');
        var sidebar = document.getElementById('miniSidebar');
        var overlay = document.getElementById('sidebar-overlay');
        if (!body || !sidebar || !wrapper) return;

        var mobileToggles = document.querySelectorAll('[data-sidebar-toggle="mobile"]');
        var mobileQuery = window.matchMedia('(max-width: 991.98px)');
        var sidebarLinks = sidebar.querySelectorAll('.nav-link');
        var installBtn = document.getElementById('emspInstallAppBtn');
        var installNavItem = document.querySelector('.emsp-install-nav-item');

        function applySidebarWidth() {
            if (mobileQuery.matches) {
                wrapper.style.setProperty('--sidebar-width', '0px');
                document.documentElement.style.setProperty('--sidebar-width', '0px');
                return;
            }
            var collapsed = wrapper.classList.contains('collapsed');
            var width = collapsed ? '65px' : '260px';
            wrapper.style.setProperty('--sidebar-width', width);
            document.documentElement.style.setProperty('--sidebar-width', width);
        }

        function closeMobile() {
            body.classList.remove('sidebar-open');
            body.style.overflow = '';
            if (overlay) overlay.classList.remove('show');
            mobileToggles.forEach(function (btn) {
                btn.setAttribute('aria-expanded', 'false');
            });
            updateToggleButtons(false);
        }

        function toggleMobile() {
            var isOpen = !body.classList.contains('sidebar-open');
            if (isOpen) {
                body.classList.add('sidebar-open');
                body.style.overflow = mobileQuery.matches ? 'hidden' : '';
                if (overlay) overlay.classList.add('show');
            } else {
                body.classList.remove('sidebar-open');
                body.style.overflow = '';
                if (overlay) overlay.classList.remove('show');
            }
            var expanded = isOpen ? 'true' : 'false';
            mobileToggles.forEach(function (btn) {
                btn.setAttribute('aria-expanded', expanded);
            });
            updateToggleButtons(isOpen);
            if (window.navigator && typeof window.navigator.vibrate === 'function') {
                window.navigator.vibrate(20);
            }
        }

        function updateToggleButtons(isOpen) {
            var iconHtml = isOpen
                ? '<i class="bi bi-x-lg fs-2"></i>'
                : '<i class="bi bi-grid-fill fs-2"></i>';
            mobileToggles.forEach(function (btn) {
                if (!btn) return;
                btn.innerHTML = iconHtml;
                btn.style.background = isOpen ? '#dc2626' : '#006B3C';
                btn.style.color = '#ffffff';
                btn.setAttribute('aria-label', isOpen ? 'Fermer le menu' : 'Ouvrir le menu');
                btn.setAttribute('title', isOpen ? 'Fermer le menu' : 'Ouvrir le menu');
            });
        }

        function syncBodyLockState() {
            if (!mobileQuery.matches) {
                body.style.overflow = '';
                return;
            }
            body.style.overflow = body.classList.contains('sidebar-open') ? 'hidden' : '';
        }

        function syncSidebarLayoutMode() {
            if (mobileQuery.matches) {
                wrapper.classList.remove('collapsed');
                sidebar.classList.remove('collapsed');
                wrapper.style.setProperty('--sidebar-width', '0px');
                document.documentElement.style.setProperty('--sidebar-width', '0px');
                return;
            }
            if (!wrapper.classList.contains('collapsed')) {
                wrapper.classList.add('collapsed');
            }
        }

        function hydrateMobileTableCards() {
            var tables = document.querySelectorAll('#main-content table');
            var isMobileCards = window.matchMedia('(max-width: 767.98px)').matches;
            tables.forEach(function (table) {
                if (!table.querySelector('thead th')) return;
                if (!isMobileCards) {
                    table.classList.remove('table-mobile');
                    var resetCells = table.querySelectorAll('tbody td[data-label]');
                    resetCells.forEach(function (cell) {
                        cell.removeAttribute('data-label');
                    });
                    return;
                }
                var headers = [];
                var headerCells = table.querySelectorAll('thead th');
                headerCells.forEach(function (th) {
                    headers.push((th.textContent || '').trim().replace(/\s+/g, ' '));
                });
                if (!headers.length) return;
                table.classList.add('table-mobile');
                var rows = table.querySelectorAll('tbody tr');
                rows.forEach(function (row) {
                    var cells = row.querySelectorAll('td');
                    cells.forEach(function (cell, idx) {
                        if (!cell.getAttribute('data-label')) {
                            cell.setAttribute('data-label', headers[idx] || '');
                        }
                    });
                });
            });
        }

        function syncPreviewIframesHeight() {
            var isMobile = window.matchMedia('(max-width: 767.98px)').matches;
            var frames = document.querySelectorAll('.emsp-preview-modal-frame, iframe[data-emsp-preview]');
            frames.forEach(function (frame) {
                if (isMobile) {
                    frame.style.minHeight = '60vh';
                    frame.style.touchAction = 'manipulation';
                } else {
                    frame.style.minHeight = '';
                    frame.style.touchAction = '';
                }
            });
        }

        function initAjaxLoadingFeedback() {
            if (!window.jQuery || !window.matchMedia('(max-width: 991.98px)').matches) return;
            var $ = window.jQuery;
            $(document).on('ajaxStart', function () {
                body.classList.add('emsp-ajax-loading');
            });
            $(document).on('ajaxStop', function () {
                body.classList.remove('emsp-ajax-loading');
            });
        }

        function initHeaderScrollEffect() {
            var header = document.querySelector('.header-sticky');
            if (!header) return;
            function applyHeaderState() {
                if (window.scrollY > 20) {
                    header.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.05)';
                    header.style.background = 'rgba(255, 255, 255, 0.95)';
                } else {
                    header.style.boxShadow = 'none';
                    header.style.background = 'rgba(255, 255, 255, 0.8)';
                }
            }
            applyHeaderState();
            window.addEventListener('scroll', applyHeaderState, { passive: true });
        }

        function initPwaInstallHook() {
            var deferredPrompt = null;
            window.addEventListener('beforeinstallprompt', function (event) {
                // Keep the browser's native install route available. Cancelling this
                // event without prompting was the source of the visible PWA warning.
                deferredPrompt = event;
                window.__emspDeferredPwaPrompt = deferredPrompt;
            });

            if (installBtn) {
                installBtn.addEventListener('click', function () {
                    var promptEvent = deferredPrompt || window.__emspDeferredPwaPrompt;
                    if (!promptEvent) return;
                    promptEvent.prompt();
                    Promise.resolve(promptEvent.userChoice).finally(function () {
                        deferredPrompt = null;
                        window.__emspDeferredPwaPrompt = null;
                        if (installNavItem) {
                            installNavItem.classList.add('d-none');
                        }
                    });
                    if (mobileQuery.matches) {
                        closeMobile();
                    }
                });
            }
        }

        mobileToggles.forEach(function (btn) {
            btn.addEventListener('click', function () {
                toggleMobile();
            });
            btn.setAttribute('aria-expanded', 'false');
        });

        if (overlay) {
            overlay.addEventListener('click', closeMobile);
        }

        sidebarLinks.forEach(function (link) {
            link.addEventListener('click', function () {
                if (mobileQuery.matches) {
                    closeMobile();
                }
            });
        });

        window.addEventListener('resize', function () {
            if (!mobileQuery.matches) {
                body.classList.remove('sidebar-open');
            }
            syncSidebarLayoutMode();
            syncBodyLockState();
            applySidebarWidth();
            hydrateMobileTableCards();
            syncPreviewIframesHeight();
        });

        syncSidebarLayoutMode();

        document.addEventListener('click', function (event) {
            if (!mobileQuery.matches || !body.classList.contains('sidebar-open')) return;
            var target = event.target;
            if (!target) return;
            if (sidebar.contains(target)) return;
            var clickedToggle = false;
            mobileToggles.forEach(function (btn) {
                if (btn.contains(target)) clickedToggle = true;
            });
            if (clickedToggle) return;
            closeMobile();
        });

        applySidebarWidth();
        syncBodyLockState();
        updateToggleButtons(body.classList.contains('sidebar-open'));
        hydrateMobileTableCards();
        syncPreviewIframesHeight();
        initAjaxLoadingFeedback();
        initHeaderScrollEffect();
        initPwaInstallHook();
    });
})(window, document);
