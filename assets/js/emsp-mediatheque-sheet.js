(function (window, document) {
    'use strict';

    var MOBILE_MQ = window.matchMedia('(max-width: 767.98px)');

    function prefersReducedMotion() {
        return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }

    function onReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
            return;
        }
        callback();
    }

    function isMobile() {
        return MOBILE_MQ.matches;
    }

    function getBootstrapModal(el) {
        if (!el || typeof window.bootstrap === 'undefined' || !window.bootstrap.Modal) {
            return null;
        }
        return window.bootstrap.Modal.getOrCreateInstance(el, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function initMediathequeSheet() {
        var sheetEl = document.getElementById('emspMediathequeSheet');
        if (!sheetEl) {
            return;
        }

        var viewerEl = document.getElementById('emspMediathequeViewer');
        var filterButtons = sheetEl.querySelectorAll('[data-emsp-mediatheque-filter]');
        var items = sheetEl.querySelectorAll('[data-emsp-mediatheque-item]');
        var emptyState = sheetEl.querySelector('[data-emsp-mediatheque-empty]');
        var stage = viewerEl ? viewerEl.querySelector('[data-emsp-mediatheque-stage]') : null;
        var viewerTitle = viewerEl ? viewerEl.querySelector('#emspMediathequeViewerTitle') : null;
        var sheetModal = getBootstrapModal(sheetEl);
        var viewerModal = viewerEl ? getBootstrapModal(viewerEl) : null;
        var activeFilter = 'all';

        function applyFilter(filterKey) {
            activeFilter = filterKey || 'all';
            var visibleCount = 0;

            filterButtons.forEach(function (button) {
                var isActive = (button.getAttribute('data-emsp-mediatheque-filter') || '') === activeFilter;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            items.forEach(function (item) {
                var itemFilter = item.getAttribute('data-filter') || '';
                var show = activeFilter === 'all'
                    || activeFilter === itemFilter
                    || (activeFilter === 'video' && item.getAttribute('data-kind') === 'video');
                item.hidden = !show;
                if (show) {
                    visibleCount += 1;
                }
            });

            if (emptyState) {
                emptyState.hidden = visibleCount > 0;
            }
        }

        filterButtons.forEach(function (button) {
            if (button.dataset.bound === '1') {
                return;
            }
            button.dataset.bound = '1';
            button.addEventListener('click', function () {
                applyFilter(button.getAttribute('data-emsp-mediatheque-filter') || 'all');
            });
        });

        function clearViewerStage() {
            if (!stage) {
                return;
            }
            var iframe = stage.querySelector('iframe');
            if (iframe) {
                iframe.src = '';
            }
            var video = stage.querySelector('video');
            if (video) {
                try {
                    video.pause();
                } catch (error) {
                    // no-op
                }
                video.removeAttribute('src');
                video.load();
            }
            stage.innerHTML = '';
        }

        function openViewerFromItem(item) {
            if (!viewerEl || !stage || !viewerModal) {
                return;
            }

            var kind = item.getAttribute('data-kind') || 'image';
            var title = item.getAttribute('data-title') || 'Media EMSP';
            if (viewerTitle) {
                viewerTitle.textContent = title;
            }

            clearViewerStage();

            if (kind === 'image') {
                var src = item.getAttribute('data-src') || '';
                stage.innerHTML = '<img class="emsp-mediatheque-viewer__image" src="' + escapeHtml(src) + '" alt="' + escapeHtml(title) + '">';
            } else {
                var embed = item.getAttribute('data-embed') || '';
                var isYoutube = item.getAttribute('data-is-youtube') === '1';
                if (isYoutube && embed) {
                    stage.innerHTML = '<iframe class="emsp-mediatheque-viewer__iframe" src="' + escapeHtml(embed) + '" title="' + escapeHtml(title) + '" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                } else if (embed) {
                    stage.innerHTML = '<video class="emsp-mediatheque-viewer__video" controls playsinline preload="metadata" poster="' + escapeHtml(item.getAttribute('data-thumb') || '') + '"><source src="' + escapeHtml(embed) + '"></video>';
                } else {
                    stage.innerHTML = '<p class="text-muted mb-0">Lecture indisponible pour ce media.</p>';
                }
            }

            viewerModal.show();
        }

        sheetEl.querySelectorAll('[data-emsp-mediatheque-open-item]').forEach(function (button) {
            if (button.dataset.bound === '1') {
                return;
            }
            button.dataset.bound = '1';
            button.addEventListener('click', function () {
                var item = button.closest('[data-emsp-mediatheque-item]');
                if (!item) {
                    return;
                }
                openViewerFromItem(item);
            });
        });

        if (viewerEl) {
            viewerEl.addEventListener('hidden.bs.modal', clearViewerStage);
        }

        document.querySelectorAll('[data-emsp-mediatheque-open]').forEach(function (trigger) {
            if (trigger.dataset.sheetBound === '1') {
                return;
            }
            trigger.dataset.sheetBound = '1';
            trigger.addEventListener('click', function (event) {
                if (!isMobile()) {
                    return;
                }
                if (!sheetModal) {
                    return;
                }
                if (trigger.getAttribute('data-bs-toggle') === 'modal') {
                    return;
                }
                event.preventDefault();
                sheetModal.show();
            });
        });

        sheetEl.addEventListener('shown.bs.modal', function () {
            applyFilter(activeFilter);
            document.body.classList.add('emsp-mediatheque-sheet-open');
        });

        sheetEl.addEventListener('hidden.bs.modal', function () {
            document.body.classList.remove('emsp-mediatheque-sheet-open');
        });

        applyFilter('all');
    }

    onReady(initMediathequeSheet);
})(window, document);
