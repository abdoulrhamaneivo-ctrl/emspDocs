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
        var sidebar = document.getElementById('emsp-admin-sidebar');
        var overlay = document.getElementById('emsp-admin-overlay');
        var toggle = document.getElementById('emsp-admin-menu-toggle');
        var bottomMenu = document.getElementById('emsp-admin-bottom-menu');
        var bottomNav = document.querySelector('[data-emsp-admin-bottom-nav="1"]');
        var mobileQuery = window.matchMedia('(max-width: 991.98px)');
        var tableQuery = window.matchMedia('(max-width: 767.98px)');

        if (!body || !sidebar || !toggle) {
            return;
        }

        if (bottomNav) {
            body.classList.add('emsp-admin-bottom-nav-enabled');
        }

        function setOpen(isOpen) {
            body.classList.toggle('emsp-admin-nav-open', isOpen);
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            toggle.setAttribute('aria-label', isOpen ? 'Fermer le menu' : 'Ouvrir le menu');
            if (bottomMenu) {
                bottomMenu.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                bottomMenu.setAttribute('aria-label', isOpen ? 'Fermer le menu' : 'Plus d\'options');
                bottomMenu.setAttribute('title', isOpen ? 'Fermer' : 'Plus');
                bottomMenu.classList.toggle('is-active', isOpen);
            }
            if (overlay) {
                overlay.hidden = !isOpen;
                overlay.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
            }
            if (mobileQuery.matches) {
                body.style.overflow = isOpen ? 'hidden' : '';
                body.style.touchAction = isOpen ? 'none' : '';
            } else {
                body.style.removeProperty('overflow');
                body.style.removeProperty('touch-action');
            }
        }

        function openSidebar() {
            setOpen(true);
        }

        function closeSidebar() {
            setOpen(false);
            if (!document.querySelector('.modal.show') && window.emspModalGuard && typeof window.emspModalGuard.reset === 'function') {
                window.emspModalGuard.reset();
            }
        }

        function toggleSidebar() {
            setOpen(!body.classList.contains('emsp-admin-nav-open'));
        }

        toggle.addEventListener('click', toggleSidebar);

        if (bottomMenu) {
            bottomMenu.addEventListener('click', toggleSidebar);
        }

        if (overlay) {
            overlay.addEventListener('click', closeSidebar);
            overlay.addEventListener('touchend', function (event) {
                event.preventDefault();
                closeSidebar();
            }, { passive: false });
        }

        sidebar.querySelectorAll('a.emsp-admin-nav-link, button.emsp-admin-logout').forEach(function (link) {
            link.addEventListener('click', function () {
                if (mobileQuery.matches) {
                    closeSidebar();
                }
            });
        });

        function wrapBareTables() {
            document.querySelectorAll('.emsp-admin-content table').forEach(function (table) {
                if (table.closest('.table-responsive')) {
                    return;
                }
                var parent = table.parentElement;
                if (!parent) {
                    return;
                }
                var wrap = document.createElement('div');
                wrap.className = 'table-responsive';
                parent.insertBefore(wrap, table);
                wrap.appendChild(table);
            });
        }

        function hydrateMobileTableCards() {
            var tables = document.querySelectorAll('.emsp-admin-content table');
            var isMobile = tableQuery.matches;
            tables.forEach(function (table) {
                if (!table.querySelector('thead th')) {
                    return;
                }
                if (!isMobile) {
                    table.classList.remove('table-mobile');
                    table.querySelectorAll('tbody td[data-label]').forEach(function (cell) {
                        cell.removeAttribute('data-label');
                    });
                    return;
                }
                var headers = [];
                table.querySelectorAll('thead th').forEach(function (th) {
                    headers.push((th.textContent || '').trim().replace(/\s+/g, ' '));
                });
                if (!headers.length) {
                    return;
                }
                table.classList.add('table-mobile');
                table.querySelectorAll('tbody tr').forEach(function (row) {
                    row.querySelectorAll('td').forEach(function (cell, idx) {
                        if (!cell.getAttribute('data-label')) {
                            cell.setAttribute('data-label', headers[idx] || '');
                        }
                    });
                });
            });
        }

        function wrapResponsiveCharts() {
            document.querySelectorAll('.emsp-admin-content canvas').forEach(function (canvas) {
                if (canvas.closest('.chart-responsive')) {
                    return;
                }
                var parent = canvas.parentElement;
                if (!parent) {
                    return;
                }
                var wrap = document.createElement('div');
                wrap.className = 'chart-responsive';
                parent.insertBefore(wrap, canvas);
                wrap.appendChild(canvas);
            });

            if (window.Chart && typeof window.Chart.getChart === 'function') {
                document.querySelectorAll('.emsp-admin-content canvas').forEach(function (canvas) {
                    var chart = window.Chart.getChart(canvas);
                    if (chart && typeof chart.resize === 'function') {
                        chart.resize();
                    }
                });
            }
        }

        function normalizeMobileForms() {
            if (!tableQuery.matches) {
                return;
            }
            document.querySelectorAll('.emsp-admin-content .row > [class*="col-"]').forEach(function (col) {
                col.classList.add('w-100');
            });
        }

        function enhanceFilterTracks() {
            if (!tableQuery.matches) {
                document.querySelectorAll('.emsp-admin-filter-track-wrap').forEach(function (wrap) {
                    var track = wrap.querySelector('.emsp-admin-filter-track');
                    if (!track) {
                        return;
                    }
                    while (track.firstChild) {
                        wrap.insertBefore(track.firstChild, track);
                    }
                    track.remove();
                    wrap.classList.remove('emsp-admin-filter-track-wrap');
                });
                return;
            }

            document.querySelectorAll('.emsp-admin-content .d-flex.flex-wrap.gap-2').forEach(function (row) {
                if (row.closest('.emsp-admin-filter-track-wrap') || row.closest('.modal')) {
                    return;
                }
                if (!row.querySelector('.btn-sm, .btn-outline-dark, .btn-outline-primary')) {
                    return;
                }
                if (row.querySelectorAll('.btn').length < 3) {
                    return;
                }
                var wrap = document.createElement('div');
                wrap.className = 'emsp-admin-filter-track-wrap';
                var track = document.createElement('div');
                track.className = 'emsp-admin-filter-track';
                row.parentNode.insertBefore(wrap, row);
                wrap.appendChild(track);
                track.appendChild(row);
            });
        }

        function enhanceAdminModals() {
            document.querySelectorAll('.emsp-admin-content .modal, .modal.emsp-admin-sheet-modal').forEach(function (modal) {
                if (modal.getAttribute('data-emsp-admin-sheet') === '1') {
                    return;
                }
                modal.classList.add('emsp-admin-sheet-modal');
                modal.setAttribute('data-emsp-admin-sheet', '1');

                modal.addEventListener('show.bs.modal', function () {
                    body.classList.add('emsp-admin-sheet-open');
                    closeSidebar();
                });
                modal.addEventListener('hidden.bs.modal', function () {
                    if (!document.querySelector('.modal.show')) {
                        body.classList.remove('emsp-admin-sheet-open');
                    }
                });
            });
        }

        function enhanceStickyTopbar() {
            var topbar = document.querySelector('.emsp-admin-topbar');
            var scrollRoot = document.querySelector('.emsp-admin-content');
            if (!topbar || !scrollRoot) {
                return;
            }
            var update = function () {
                topbar.classList.toggle('is-scrolled', scrollRoot.scrollTop > 8);
            };
            update();
            scrollRoot.addEventListener('scroll', update, { passive: true });
        }

        function enhancePageToolbars() {
            document.querySelectorAll('.emsp-admin-content > .d-flex.justify-content-between, .emsp-admin-content > .d-flex.flex-column.flex-md-row').forEach(function (toolbar) {
                if (toolbar.classList.contains('emsp-admin-page-toolbar')) {
                    return;
                }
                if (!toolbar.querySelector('h1, h5, .h4')) {
                    return;
                }
                toolbar.classList.add('emsp-admin-page-toolbar');
            });
        }

        function enhanceStickyFormActions() {
            if (!tableQuery.matches) {
                document.querySelectorAll('.emsp-admin-sticky-actions[data-emsp-auto-sticky="1"]').forEach(function (wrap) {
                    var parent = wrap.parentNode;
                    if (parent) {
                        while (wrap.firstChild) {
                            parent.insertBefore(wrap.firstChild, wrap);
                        }
                        wrap.remove();
                    }
                });
                return;
            }

            document.querySelectorAll('.emsp-admin-content form').forEach(function (form) {
                if (form.closest('.modal') || form.querySelector('.emsp-admin-sticky-actions')) {
                    return;
                }
                var submitRow = form.querySelector('.d-flex.gap-2 .btn-primary[type="submit"], .btn-primary[type="submit"]:not(.btn-sm)');
                if (!submitRow) {
                    submitRow = form.querySelector('button[type="submit"].btn-primary, input[type="submit"].btn-primary');
                }
                if (!submitRow) {
                    return;
                }
                var actionRow = submitRow.closest('.d-flex.gap-2') || submitRow.parentElement;
                if (!actionRow || actionRow.closest('.emsp-admin-sticky-actions')) {
                    return;
                }
                var sticky = document.createElement('div');
                sticky.className = 'emsp-admin-sticky-actions';
                sticky.setAttribute('data-emsp-auto-sticky', '1');
                actionRow.parentNode.insertBefore(sticky, actionRow.nextSibling);
                sticky.appendChild(actionRow);
            });
        }

        function enhancePendingValidation() {
            if (!tableQuery.matches) {
                return;
            }

            document.querySelectorAll('.review-doc-btn').forEach(function (btn) {
                btn.classList.add('w-100');
                var row = btn.closest('tr');
                if (!row || row.getAttribute('data-emsp-row-tap') === '1') {
                    return;
                }
                row.setAttribute('data-emsp-row-tap', '1');
                row.addEventListener('click', function (event) {
                    if (event.target.closest('button, a, input, select, textarea, label')) {
                        return;
                    }
                    btn.click();
                });
            });

            document.querySelectorAll('.emsp-admin-validation-actions').forEach(function (wrap) {
                wrap.querySelectorAll('.btn').forEach(function (btn) {
                    btn.classList.add('w-100');
                });
            });
        }

        function enhanceStatsCharts() {
            if (!window.Chart || typeof window.Chart.getChart !== 'function') {
                return;
            }
            document.querySelectorAll('.emsp-admin-content canvas').forEach(function (canvas) {
                var chart = window.Chart.getChart(canvas);
                if (!chart || !chart.options) {
                    return;
                }
                if (tableQuery.matches) {
                    chart.options.maintainAspectRatio = false;
                    chart.options.plugins = chart.options.plugins || {};
                    chart.options.plugins.legend = chart.options.plugins.legend || {};
                    chart.options.plugins.legend.labels = chart.options.plugins.legend.labels || {};
                    chart.options.plugins.legend.labels.boxWidth = 12;
                    chart.options.plugins.legend.position = 'bottom';
                }
                if (typeof chart.resize === 'function') {
                    chart.resize();
                }
            });
        }

        function enhanceQuillMobile() {
            if (!tableQuery.matches) {
                return;
            }
            document.querySelectorAll('.emsp-admin-content .ql-container').forEach(function (container) {
                container.style.fontSize = '16px';
            });
        }

        function refreshLayout() {
            if (!mobileQuery.matches) {
                closeSidebar();
            }
            wrapBareTables();
            hydrateMobileTableCards();
            wrapResponsiveCharts();
            normalizeMobileForms();
            enhanceFilterTracks();
            enhanceStickyFormActions();
            enhancePendingValidation();
            enhanceStatsCharts();
            enhanceQuillMobile();
        }

        window.addEventListener('resize', refreshLayout);
        window.addEventListener('load', refreshLayout);

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeSidebar();
            }
        });

        enhanceAdminModals();
        enhanceStickyTopbar();
        enhancePageToolbars();
        enhanceStickyFormActions();
        enhancePendingValidation();
        enhanceQuillMobile();
        refreshLayout();
    });
})(window, document);
