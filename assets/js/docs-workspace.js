(function () {
    function parseJsonScript(root, selector, fallback) {
        const node = root.querySelector(selector);
        if (!node) {
            return fallback;
        }
        try {
            return JSON.parse(node.textContent || '');
        } catch (error) {
            return fallback;
        }
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function buildTypeStyle(item) {
        const tone = item.typeTone || '#0f172a';
        const accent = item.typeAccent || '#e2e8f0';
        return 'color:' + tone + ';background:' + accent + ';';
    }

    function renderBadges(item) {
        if (!Array.isArray(item.badges) || item.badges.length === 0) {
            return '';
        }
        return '<div class="docs-workspace-statuses">' + item.badges.map(function (badge) {
            return '<span class="docs-workspace-status">' + escapeHtml(badge) + '</span>';
        }).join('') + '</div>';
    }

    function renderDownloadForm(item, compact, strip) {
        var btnClass = strip
            ? 'docs-workspace-iconbtn docs-workspace-strip-btn docs-workspace-iconbtn--accent'
            : (compact ? 'docs-workspace-iconbtn docs-workspace-iconbtn--accent' : 'docs-workspace-action docs-workspace-action--accent');
        return '' +
            '<form method="post" action="' + escapeHtml(item.downloadUrl) + '" class="m-0">' +
                '<input type="hidden" name="csrf_token" value="' + escapeHtml(item.csrfToken || '') + '">' +
                '<button type="submit" class="' + btnClass + '" title="Telecharger" aria-label="Telecharger">' +
                    '<i class="bi bi-download"></i>' + (compact || strip ? '' : '<span>Telecharger</span>') +
                '</button>' +
            '</form>';
    }

    function renderFavoriteRemoval(item) {
        if (!item.favoriteRemovable || !item.favoriteActionUrl) {
            return '';
        }
        return '' +
            '<form method="post" action="' + escapeHtml(item.favoriteActionUrl) + '" class="m-0">' +
                '<input type="hidden" name="csrf_token" value="' + escapeHtml(item.csrfToken || '') + '">' +
                '<input type="hidden" name="doc_id" value="' + escapeHtml(item.id) + '">' +
                '<button type="submit" class="docs-workspace-iconbtn" title="Retirer des favoris">' +
                    '<i class="bi bi-star-fill"></i>' +
                '</button>' +
            '</form>';
    }

    function resolvePreviewMedia(item) {
        if (item.thumbUrl) {
            return { url: item.thumbUrl, lazy: false };
        }
        if (item.thumbLazyUrl) {
            return { url: item.thumbLazyUrl, lazy: true };
        }
        if (item.previewAvailable && item.previewUrl && item.fileKind === 'image') {
            return { url: item.previewUrl, lazy: false };
        }
        return { url: '', lazy: false };
    }

    function coverBackgroundStyle(item) {
        var media = resolvePreviewMedia(item);
        if (media.url) {
            return 'background:#fff';
        }
        return 'background:' + (item.gradient || 'linear-gradient(135deg, #e2e8f0 0%, #f8fafc 100%)');
    }

    function renderPreviewSurface(item, variant) {
        const typeLabel = escapeHtml(item.typeLabel || 'Document');
        const extension = escapeHtml(item.fileExt || 'DOC');
        const title = escapeHtml(item.title || 'Document');
        const media = resolvePreviewMedia(item);
        const previewImage = media.url
            ? '<img src="' + (media.lazy ? '' : escapeHtml(media.url)) + '"' +
                (media.lazy ? ' data-lazy-thumb="' + escapeHtml(media.url) + '"' : '') +
                ' alt="' + title + '" class="docs-workspace-preview-media" loading="lazy">'
            : '';
        const previewClass = variant === 'mobile'
            ? 'docs-workspace-item-preview'
            : (variant === 'native-thumb'
                ? 'docs-workspace-native-preview'
                : 'docs-workspace-card-preview');

        const fallbackSheet = media.url ? '' :
            '<div class="docs-workspace-preview-sheet">' +
                '<span class="docs-workspace-preview-type">' + typeLabel + '</span>' +
                '<strong class="docs-workspace-preview-title">' + title + '</strong>' +
                '<span class="docs-workspace-preview-ext">' + extension + '</span>' +
            '</div>';

        return '' +
            '<div class="' + previewClass + (media.url ? ' has-preview-media' : '') + '">' +
                previewImage + fallbackSheet +
            '</div>';
    }

    function bindThumbFallbacks(container) {
        if (!container) {
            return;
        }

        container.querySelectorAll('.docs-workspace-preview-media').forEach(function (img) {
            if (img.getAttribute('data-thumb-bound') === '1') {
                return;
            }
            img.setAttribute('data-thumb-bound', '1');
            if (img.complete && img.naturalWidth > 0) {
                img.classList.add('is-thumb-loaded');
            }
            img.addEventListener('error', function () {
                img.classList.add('is-thumb-error');
                img.removeAttribute('src');
                var preview = img.closest('.docs-workspace-card-preview, .docs-workspace-item-preview, .docs-workspace-native-preview, .docs-workspace-row-preview');
                if (preview) {
                    preview.classList.remove('has-preview-media');
                }
                var cover = img.closest('.docs-workspace-card-cover, .docs-workspace-item-cover, .docs-workspace-native-thumb, .docs-workspace-row-thumb');
                if (cover) {
                    cover.classList.remove('has-doc-preview');
                }
            });
            img.addEventListener('load', function () {
                if (img.naturalWidth <= 0) {
                    return;
                }
                img.classList.add('is-thumb-loaded');
                var preview = img.closest('.docs-workspace-card-preview, .docs-workspace-item-preview, .docs-workspace-native-preview, .docs-workspace-row-preview');
                if (preview) {
                    preview.classList.add('has-preview-media');
                }
                var cover = img.closest('.docs-workspace-card-cover, .docs-workspace-item-cover, .docs-workspace-native-thumb, .docs-workspace-row-thumb');
                if (cover) {
                    cover.classList.add('has-doc-preview');
                }
            });
        });
    }

    function hydrateLazyThumbs(container) {
        if (!container) {
            return;
        }

        if (!('IntersectionObserver' in window)) {
            container.querySelectorAll('img[data-lazy-thumb]').forEach(function (img) {
                const src = img.getAttribute('data-lazy-thumb');
                if (src && !img.getAttribute('src')) {
                    img.setAttribute('src', src);
                }
            });
            bindThumbFallbacks(container);
            return;
        }

        const pending = container.querySelectorAll('img[data-lazy-thumb]:not([src])');
        if (!pending.length) {
            bindThumbFallbacks(container);
            return;
        }

        const observer = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }
                const img = entry.target;
                const src = img.getAttribute('data-lazy-thumb');
                if (src) {
                    img.setAttribute('src', src);
                }
                obs.unobserve(img);
            });
        }, { rootMargin: '120px 0px' });

        pending.forEach(function (img) {
            observer.observe(img);
        });

        bindThumbFallbacks(container);
    }

    function renderEditorialRow(item) {
        const subject = (item.matiere || item.typeLabel || '').toUpperCase();
        const metaParts = [item.contextLabel, item.licence, item.semester, item.fileExt]
            .filter(function (part) { return part && String(part).trim() !== ''; });
        const media = resolvePreviewMedia(item);
        const thumbHtml = media.url
            ? '<span class="docs-workspace-row-thumb' + (media.url ? ' has-doc-preview' : '') + '" style="' + coverBackgroundStyle(item) + '">' +
                renderPreviewSurface(item, 'native-thumb') +
                '<span class="docs-workspace-row-thumb-zoom" aria-hidden="true"><i class="bi bi-eye"></i></span>' +
              '</span>'
            : '';
        return '' +
            '<a href="' + escapeHtml(item.documentUrl) + '" class="docs-workspace-row docs-workspace-row--with-thumb emsp-motion-item" data-doc-id="' + escapeHtml(item.id) + '">' +
                thumbHtml +
                '<div class="docs-workspace-row-main">' +
                    (subject ? '<span class="docs-workspace-row-subject">' + escapeHtml(subject) + '</span>' : '') +
                    '<h3 class="docs-workspace-row-title">' + escapeHtml(item.title) + '</h3>' +
                    '<p class="docs-workspace-row-meta">' + escapeHtml(metaParts.join(' · ')) + '</p>' +
                '</div>' +
                '<span class="docs-workspace-row-actions">' +
                    '<button type="button" class="docs-workspace-row-preview-btn" data-action="quick-view" data-doc-id="' + escapeHtml(item.id) + '" title="Aperçu rapide" aria-label="Aperçu rapide">' +
                        '<i class="bi bi-eye"></i>' +
                    '</button>' +
                    '<span class="docs-workspace-row-arrow"><i class="bi bi-arrow-right"></i></span>' +
                '</span>' +
            '</a>';
    }

    function renderGridCard(item) {
        return '' +
            '<article class="docs-workspace-card emsp-motion-item" data-doc-id="' + escapeHtml(item.id) + '">' +
                '<div class="docs-workspace-card-cover' + (resolvePreviewMedia(item).url ? ' has-doc-preview' : '') + '" style="' + coverBackgroundStyle(item) + '">' +
                    renderPreviewSurface(item, 'grid') +
                    '<div class="docs-workspace-card-cover-top">' +
                        '<span class="docs-workspace-kind"><i class="bi ' + escapeHtml(item.fileIcon) + '"></i></span>' +
                        '<span class="docs-workspace-fileext">' + escapeHtml(item.fileExt || 'DOC') + '</span>' +
                    '</div>' +
                    '<div class="docs-workspace-card-overlay">' +
                        '<div class="docs-workspace-overlay-actions">' +
                            '<button type="button" class="docs-workspace-action" data-action="quick-view" data-doc-id="' + escapeHtml(item.id) + '">' +
                                '<i class="bi bi-eye"></i><span>Apercu rapide</span>' +
                            '</button>' +
                            '<a href="' + escapeHtml(item.documentUrl) + '" class="docs-workspace-action">' +
                                '<i class="bi bi-arrow-up-right"></i><span>Ouvrir</span>' +
                            '</a>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="docs-workspace-card-body">' +
                    '<div class="docs-workspace-card-head">' +
                        '<span class="docs-workspace-doc-type" style="' + buildTypeStyle(item) + '">' +
                            '<i class="bi ' + escapeHtml(item.typeIcon) + '"></i>' + escapeHtml(item.typeLabel) +
                        '</span>' +
                    '</div>' +
                    '<div>' +
                        '<h3 class="docs-workspace-titleline">' + escapeHtml(item.title) + '</h3>' +
                        '<p class="docs-workspace-subtle">' + escapeHtml(item.contextLabel || item.authorCompact) + '</p>' +
                    '</div>' +
                    '<div class="docs-workspace-card-meta">' +
                        '<span><i class="bi bi-person"></i>' + escapeHtml(item.authorCompact) + '</span>' +
                        '<span><i class="bi bi-download"></i>' + escapeHtml(item.downloadCount) + '</span>' +
                        '<span><i class="bi bi-calendar3"></i>' + escapeHtml(item.dateLabel) + '</span>' +
                    '</div>' +
                    renderBadges(item) +
                    '<div class="docs-workspace-card-actions docs-workspace-card-actions--strip">' +
                        '<button type="button" class="docs-workspace-iconbtn docs-workspace-strip-btn" data-action="quick-view" data-doc-id="' + escapeHtml(item.id) + '" title="Prévisualiser" aria-label="Prévisualiser">' +
                            '<i class="bi bi-eye"></i>' +
                        '</button>' +
                        renderDownloadForm(item, true, true) +
                        '<a href="' + escapeHtml(item.documentUrl) + '" class="docs-workspace-action docs-workspace-action--primary docs-workspace-strip-open">' +
                            '<i class="bi bi-arrow-up-right"></i><span>Ouvrir</span>' +
                        '</a>' +
                        renderFavoriteRemoval(item) +
                    '</div>' +
                '</div>' +
            '</article>';
    }

    function renderListRow(item) {
        return '' +
            '<tr data-doc-id="' + escapeHtml(item.id) + '">' +
                '<td>' +
                    '<strong>' + escapeHtml(item.title) + '</strong>' +
                    '<br><small>' + escapeHtml(item.contextLabel || item.authorCompact) + '</small>' +
                '</td>' +
                '<td><span class="docs-workspace-doc-type" style="' + buildTypeStyle(item) + '">' +
                    '<i class="bi ' + escapeHtml(item.typeIcon) + '"></i>' + escapeHtml(item.typeLabel) +
                '</span></td>' +
                '<td><small><i class="bi bi-person"></i> ' + escapeHtml(item.authorCompact) + '</small></td>' +
                '<td><small><i class="bi bi-file-earmark"></i> ' + escapeHtml(item.sizeLabel) + '</small></td>' +
                '<td><small><i class="bi bi-calendar3"></i> ' + escapeHtml(item.dateLabel) + '</small></td>' +
                '<td>' +
                    '<div class="docs-workspace-card-actions">' +
                        '<button type="button" class="docs-workspace-iconbtn" data-action="quick-view" data-doc-id="' + escapeHtml(item.id) + '" title="Apercu">' +
                            '<i class="bi bi-eye"></i>' +
                        '</button>' +
                        '<a href="' + escapeHtml(item.documentUrl) + '" class="docs-workspace-iconbtn" title="Ouvrir">' +
                            '<i class="bi bi-arrow-up-right"></i>' +
                        '</a>' +
                        renderDownloadForm(item, true) +
                    '</div>' +
                '</td>' +
            '</tr>';
    }

    function renderMobileItem(item) {
        return '' +
            '<article class="docs-workspace-item emsp-motion-item" data-doc-id="' + escapeHtml(item.id) + '">' +
                '<div class="docs-workspace-item-body">' +
                    '<div class="docs-workspace-item-cover' + (resolvePreviewMedia(item).url ? ' has-doc-preview' : '') + '" style="' + coverBackgroundStyle(item) + '">' +
                        renderPreviewSurface(item, 'mobile') +
                        '<span class="docs-workspace-item-kind" style="background:' + escapeHtml(item.typeAccent || '#e2e8f0') + ';color:' + escapeHtml(item.typeTone || '#0f172a') + '">' +
                            '<i class="bi ' + escapeHtml(item.typeIcon) + '"></i>' +
                        '</span>' +
                    '</div>' +
                    '<div class="docs-workspace-item-main">' +
                        '<div class="docs-workspace-item-head">' +
                            '<span class="docs-workspace-doc-type" style="' + buildTypeStyle(item) + '">' +
                                '<i class="bi ' + escapeHtml(item.typeIcon) + '"></i>' + escapeHtml(item.typeLabel) +
                            '</span>' +
                            renderBadges(item) +
                        '</div>' +
                        '<h3 class="docs-workspace-titleline">' + escapeHtml(item.title) + '</h3>' +
                        '<div class="docs-workspace-item-meta">' +
                            '<span><i class="bi bi-person"></i>' + escapeHtml(item.authorCompact) + '</span>' +
                            '<span><i class="bi bi-file-earmark"></i>' + escapeHtml(item.sizeLabel) + '</span>' +
                            '<span><i class="bi bi-download"></i>' + escapeHtml(item.downloadCount) + '</span>' +
                            '<span><i class="bi bi-calendar3"></i>' + escapeHtml(item.dateLabel) + '</span>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="docs-workspace-item-actions docs-workspace-item-actions--strip">' +
                    '<button type="button" class="docs-workspace-iconbtn docs-workspace-strip-btn" data-action="quick-view" data-doc-id="' + escapeHtml(item.id) + '" title="Apercu" aria-label="Apercu">' +
                        '<i class="bi bi-eye"></i>' +
                    '</button>' +
                    renderDownloadForm(item, true, true) +
                    '<a href="' + escapeHtml(item.documentUrl) + '" class="docs-workspace-action docs-workspace-action--primary docs-workspace-strip-open">' +
                        '<i class="bi bi-arrow-up-right"></i><span>Ouvrir</span>' +
                    '</a>' +
                    renderFavoriteRemoval(item) +
                '</div>' +
            '</article>';
    }

    function renderNativeMobileItem(item) {
        const metaParts = [
            item.typeLabel,
            item.authorCompact,
            item.sizeLabel,
            item.dateLabel
        ].filter(function (part) {
            return part && String(part).trim() !== '';
        });
        const media = resolvePreviewMedia(item);

        return '' +
            '<article class="docs-workspace-item docs-workspace-item--native emsp-motion-item" data-doc-id="' + escapeHtml(item.id) + '">' +
                '<div class="docs-workspace-native-card">' +
                    '<button type="button" class="docs-workspace-native-hero-thumb docs-workspace-native-thumb-btn' + (media.url ? ' has-doc-preview' : '') + '" style="' + coverBackgroundStyle(item) + '" data-action="quick-view" data-doc-id="' + escapeHtml(item.id) + '" aria-label="Aperçu de ' + escapeHtml(item.title) + '">' +
                        renderPreviewSurface(item, 'mobile') +
                        '<span class="docs-workspace-native-hero-badge" aria-hidden="true"><i class="bi bi-eye"></i> Aperçu</span>' +
                    '</button>' +
                    '<div class="docs-workspace-native-content">' +
                        '<div class="docs-workspace-item-head">' +
                            '<span class="docs-workspace-doc-type" style="' + buildTypeStyle(item) + '">' +
                                '<i class="bi ' + escapeHtml(item.typeIcon) + '"></i>' + escapeHtml(item.typeLabel) +
                            '</span>' +
                            renderBadges(item) +
                        '</div>' +
                        '<h3 class="docs-workspace-titleline docs-workspace-native-title">' + escapeHtml(item.title) + '</h3>' +
                        '<p class="docs-workspace-native-meta">' + escapeHtml(metaParts.join(' · ')) + '</p>' +
                    '</div>' +
                    '<div class="docs-workspace-item-actions docs-workspace-item-actions--native" role="group" aria-label="Actions document">' +
                        renderDownloadForm(item, true, true) +
                        '<button type="button" class="docs-workspace-action docs-workspace-action--ghost docs-workspace-native-btn-preview" data-action="quick-view" data-doc-id="' + escapeHtml(item.id) + '" title="Aperçu rapide" aria-label="Aperçu rapide">' +
                            '<i class="bi bi-eye"></i><span>Aperçu</span>' +
                        '</button>' +
                        '<a href="' + escapeHtml(item.documentUrl) + '" class="docs-workspace-action docs-workspace-action--primary docs-workspace-native-btn-open">' +
                            '<i class="bi bi-arrow-up-right"></i><span>Ouvrir</span>' +
                        '</a>' +
                        renderFavoriteRemoval(item) +
                    '</div>' +
                '</div>' +
            '</article>';
    }

    function isMobileAppViewport() {
        return !!(window.matchMedia && window.matchMedia('(max-width: 767.98px)').matches);
    }

    function resolveInitialViewMode(config) {
        const params = new URLSearchParams(window.location.search);
        if (params.has('viewMode')) {
            return params.get('viewMode') || 'editorial';
        }
        if (isMobileAppViewport()) {
            return 'list';
        }
        return config.defaultView === 'grid' ? 'grid' : 'editorial';
    }

    function normalizeViewModeForViewport(viewMode) {
        if (isMobileAppViewport() && viewMode === 'editorial') {
            return 'list';
        }
        return viewMode;
    }

    function collectUnique(documents, key) {
        const values = [];
        const seen = new Set();
        documents.forEach(function (item) {
            const value = (item[key] || '').trim();
            if (!value || seen.has(value)) {
                return;
            }
            seen.add(value);
            values.push(value);
        });
        return values.sort(function (a, b) {
            return a.localeCompare(b, 'fr', { sensitivity: 'base' });
        });
    }

    function hydrateSelects(root, documents, filterOptions) {
        root.querySelectorAll('[data-filter-control]').forEach(function (select) {
            const filterKey = select.getAttribute('data-filter-control');
            if (filterKey === 'query') {
                return;
            }
            const configuredOptions = filterOptions && Array.isArray(filterOptions[filterKey])
                ? filterOptions[filterKey]
                : [];
            const options = configuredOptions.length > 0
                ? configuredOptions
                    .map(function (value) { return String(value || '').trim(); })
                    .filter(function (value, index, values) { return value !== '' && values.indexOf(value) === index; })
                    .sort(function (a, b) {
                        return a.localeCompare(b, 'fr', { sensitivity: 'base' });
                    })
                : collectUnique(documents, filterKey);
            const currentValue = select.value || '';
            const label = select.getAttribute('data-label') || 'Tous';
            select.innerHTML = '<option value="">' + escapeHtml(label) + '</option>' + options.map(function (value) {
                return '<option value="' + escapeHtml(value) + '">' + escapeHtml(value) + '</option>';
            }).join('');
            select.value = currentValue;
        });
    }

    function snapshotPositions(container) {
        const positions = new Map();
        container.querySelectorAll('[data-doc-id]').forEach(function (element) {
            positions.set(element.getAttribute('data-doc-id'), element.getBoundingClientRect());
        });
        return positions;
    }

    function animateFlip(container, before) {
        container.querySelectorAll('[data-doc-id]').forEach(function (element) {
            const id = element.getAttribute('data-doc-id');
            const previous = before.get(id);
            const next = element.getBoundingClientRect();
            if (previous) {
                const dx = previous.left - next.left;
                const dy = previous.top - next.top;
                if (dx || dy) {
                    element.animate([
                        { transform: 'translate(' + dx + 'px,' + dy + 'px)' },
                        { transform: 'translate(0,0)' }
                    ], { duration: 240, easing: 'ease' });
                }
            } else {
                element.animate([
                    { opacity: 0, transform: 'translateY(12px)' },
                    { opacity: 1, transform: 'translateY(0)' }
                ], { duration: 220, easing: 'ease' });
            }
        });
    }

    function prefersReducedMotion() {
        return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }

    function initMobileTouchPolish(container) {
        if (!container || !isMobileAppViewport()) {
            return;
        }
        container.querySelectorAll('.docs-workspace-item--native, .docs-workspace-native-btn-preview, .docs-workspace-native-btn-open').forEach(function (node) {
            if (node.dataset.emspTouchBound === '1') {
                return;
            }
            node.dataset.emspTouchBound = '1';
            node.addEventListener('touchstart', function () {
                node.classList.add('is-touch-active');
            }, { passive: true });
            node.addEventListener('touchend', function () {
                window.setTimeout(function () {
                    node.classList.remove('is-touch-active');
                }, 120);
            }, { passive: true });
            node.addEventListener('touchcancel', function () {
                node.classList.remove('is-touch-active');
            }, { passive: true });
        });
    }

    function initCardHoverInteractions(container) {
        if (!container || prefersReducedMotion()) {
            return;
        }
        container.querySelectorAll('.docs-workspace-card, .docs-workspace-item').forEach(function (card) {
            if (card.dataset.emspHoverBound === '1') {
                return;
            }
            card.dataset.emspHoverBound = '1';
            card.addEventListener('mouseenter', function () {
                card.classList.add('is-js-hover');
            });
            card.addEventListener('mouseleave', function () {
                card.classList.remove('is-js-hover');
            });
            card.addEventListener('focusin', function () {
                card.classList.add('is-js-hover');
            });
            card.addEventListener('focusout', function () {
                card.classList.remove('is-js-hover');
            });
        });
    }

    function resetQuickViewStage(stage, footer) {
        if (stage) {
            stage.querySelectorAll('iframe[data-emsp-pdf-preview="1"]').forEach(function (frame) {
                if (window.emspPdfPreview && typeof window.emspPdfPreview.reset === 'function') {
                    window.emspPdfPreview.reset(frame);
                }
            });
            stage.innerHTML = '';
        }
        if (footer) {
            footer.innerHTML = '';
            footer.hidden = true;
        }
    }

    function createQuickView(root) {
        const modal = root.querySelector('.docs-quickview-modal');
        const title = root.querySelector('[data-role="quickview-title"]');
        const stage = root.querySelector('[data-role="quickview-stage"]');
        const footer = root.querySelector('[data-role="quickview-footer"]');
        let bootstrapModal = null;

        if (modal) {
            modal.setAttribute('data-emsp-motion-modal', '1');
        }

        function cleanupArtifacts() {
            if (window.emspModalGuard && typeof window.emspModalGuard.reset === 'function') {
                window.emspModalGuard.reset();
                return;
            }
            document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
                backdrop.remove();
            });
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
            document.documentElement.classList.remove('emsp-modal-scroll-lock');
        }

        if (modal) {
            modal.addEventListener('hide.bs.modal', function () {
                var active = document.activeElement;
                if (active && modal.contains(active)) {
                    active.blur();
                }
            });

            modal.addEventListener('hidden.bs.modal', function () {
                var active = document.activeElement;
                if (active && modal.contains(active)) {
                    active.blur();
                }
                resetQuickViewStage(stage, footer);
                cleanupArtifacts();
            });

            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    if (bootstrapModal) {
                        bootstrapModal.hide();
                    } else {
                        modal.classList.remove('show');
                        modal.style.display = 'none';
                        modal.setAttribute('aria-hidden', 'true');
                        resetQuickViewStage(stage, footer);
                        cleanupArtifacts();
                    }
                }
            });
        }

        function setQuickViewFooter(html) {
            if (!footer) {
                return;
            }
            if (html) {
                footer.innerHTML = html;
                footer.hidden = false;
            } else {
                footer.innerHTML = '';
                footer.hidden = true;
            }
        }

        function mountQuickViewPdf() {
            if (!stage || !window.emspPdfPreview) {
                return;
            }
            if (typeof window.emspPdfPreview.init === 'function') {
                window.emspPdfPreview.init(stage);
            }
        }

        function quickViewFooterLink(documentUrl, newTab) {
            const targetAttrs = newTab ? ' target="_blank" rel="noopener"' : '';
            return '<div class="docs-quickview-open"><a href="' + escapeHtml(documentUrl) + '"' + targetAttrs + '>Voir la fiche du document <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></div>';
        }

        function show(item) {
            if (!modal || !stage) {
                return;
            }
            const docTitle = item.title || 'Aperçu';
            if (title) {
                title.textContent = docTitle;
                title.title = docTitle;
            }
            modal.setAttribute('aria-label', 'Aperçu : ' + docTitle);

            if (item.pdfViewerUrl || item.pdfDataUrl || (item.fileKind === 'pdf' && item.previewUrl)) {
                const pdfDataUrl = item.pdfDataUrl || (item.previewUrl || item.pdfViewerUrl || '').replace(/([?&])preview=1/, '$1pdfdata=1');
                const previewUrl = item.pdfViewerUrl || item.previewUrl || '';
                const thumbUrl = item.previewImageUrl || item.thumbUrl || item.thumbLazyUrl || '';
                const posterHtml = thumbUrl
                    ? '<img src="' + escapeHtml(thumbUrl) + '" alt="" class="emsp-pdf-preview-poster" aria-hidden="true">'
                    : '';
                stage.innerHTML = '' +
                    '<div class="emsp-pdf-preview-wrap emsp-pdf-preview-wrap--modal' + (thumbUrl ? ' has-poster-thumb' : '') + '">' +
                        posterHtml +
                        '<div class="emsp-pdf-preview-loading" aria-live="polite">' +
                            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Chargement de l\'aperçu…' +
                        '</div>' +
                        '<iframe class="docs-quickview-pdf emsp-inline-pdf-frame" title="Aperçu PDF : ' + escapeHtml(item.title) + '"' +
                            ' data-emsp-pdf-preview="1"' +
                            ' data-pdf-data-url="' + escapeHtml(pdfDataUrl) + '"' +
                            ' data-preview-url="' + escapeHtml(previewUrl) + '"' +
                            ' data-fallback-thumb="' + escapeHtml(thumbUrl) + '"></iframe>' +
                    '</div>';
                setQuickViewFooter(quickViewFooterLink(item.documentUrl, false));
            } else if (item.fileKind === 'image' && item.previewUrl) {
                stage.innerHTML = '' +
                    '<figure class="docs-quickview-figure">' +
                        '<img src="' + escapeHtml(item.previewUrl) + '" alt="' + escapeHtml(item.title) + '">' +
                    '</figure>';
                setQuickViewFooter(quickViewFooterLink(item.documentUrl, false));
            } else if (item.previewImageUrl) {
                const imageUrl = item.previewImageUrl || item.previewUrl;
                stage.innerHTML = '' +
                    '<figure class="docs-quickview-figure">' +
                        '<img src="' + escapeHtml(imageUrl) + '" alt="Premiere page de ' + escapeHtml(item.title) + '">' +
                    '</figure>';
                setQuickViewFooter(quickViewFooterLink(item.documentUrl, true));
            } else {
                stage.innerHTML = '' +
                    '<div class="docs-quickview-fallback">' +
                        '<h4>Aperçu indisponible localement</h4>' +
                        '<p class="docs-workspace-subtle">Le fichier source n est pas disponible ou ce format ne peut pas être affiché ici. Les métadonnées du document restent consultables.</p>' +
                        '<div class="docs-workspace-card-meta">' +
                            '<span><i class="bi bi-person"></i>' + escapeHtml(item.author) + '</span>' +
                            '<span><i class="bi bi-diagram-3"></i>' + escapeHtml(item.contextLabel || item.typeLabel) + '</span>' +
                            '<span><i class="bi bi-download"></i>' + escapeHtml(item.downloadCount) + '</span>' +
                        '</div>' +
                        '<div class="docs-quickview-actions">' +
                            '<a href="' + escapeHtml(item.documentUrl) + '" class="docs-workspace-action"><i class="bi bi-arrow-up-right"></i><span>Ouvrir la fiche</span></a>' +
                            renderDownloadForm(item, false) +
                        '</div>' +
                    '</div>';
                setQuickViewFooter('');
            }

            if (window.bootstrap && window.bootstrap.Modal) {
                bootstrapModal = window.bootstrap.Modal.getOrCreateInstance(modal, {
                    backdrop: true,
                    focus: true,
                    keyboard: true
                });
                if (stage && stage.querySelector('[data-emsp-pdf-preview="1"]')) {
                    modal.addEventListener('shown.bs.modal', function onQuickViewShown() {
                        mountQuickViewPdf();
                    }, { once: true });
                }
                bootstrapModal.show();
            } else {
                modal.classList.add('show');
                modal.style.display = 'block';
                modal.removeAttribute('aria-hidden');
                mountQuickViewPdf();
            }
        }

        return { show: show };
    }

    document.querySelectorAll('[data-docs-workspace]').forEach(function (root) {
        const payload = parseJsonScript(root, '.docs-workspace-data', { documents: [] });
        const config = parseJsonScript(root, '.docs-workspace-config', {});
        const documents = Array.isArray(payload.documents) ? payload.documents : [];
        const viewButtons = Array.from(root.querySelectorAll('[data-view-mode]'));
        const chips = Array.from(root.querySelectorAll('[data-chip-type]'));
        const searchInput = root.querySelector('[data-filter-control="query"]');
        const grid = root.querySelector('[data-role="desktop-grid"]');
        const editorialList = root.querySelector('[data-role="editorial-list"]');
        const listBody = root.querySelector('[data-role="desktop-list-body"]');
        const mobileList = root.querySelector('[data-role="mobile-list"]');
        const skeletonList = root.querySelector('[data-role="skeleton-list"]');
        const emptyState = root.querySelector('[data-role="empty-state"]');
        const resultsCount = root.querySelector('[data-role="results-count"]');
        const resultsSummary = root.querySelector('[data-role="results-summary"]');
        const panel = root.querySelector('[data-role="filters-panel"]');
        const backdrop = root.querySelector('[data-role="filters-backdrop"]');
        const quickView = createQuickView(root);
        const state = {
            query: '',
            type: '',
            filiere: '',
            licence: '',
            matiere: '',
            semester: '',
            viewMode: resolveInitialViewMode(config)
        };

        hydrateSelects(root, documents, config.filterOptions || {});

        function syncUrl() {
            if (!window.history || !window.history.replaceState) {
                return;
            }
            const url = new URL(window.location.href);
            ['query', 'type', 'filiere', 'licence', 'matiere', 'semester', 'viewMode'].forEach(function (key) {
                const value = state[key];
                if (value) {
                    url.searchParams.set(key, value);
                } else {
                    url.searchParams.delete(key);
                }
            });
            window.history.replaceState({}, '', url.toString());
        }

        function applyStateFromUrl() {
            const params = new URLSearchParams(window.location.search);
            Object.keys(state).forEach(function (key) {
                const value = params.get(key);
                if (value) {
                    state[key] = value;
                }
            });
            state.viewMode = normalizeViewModeForViewport(state.viewMode);
        }

        function filteredDocuments() {
            return documents.filter(function (item) {
                if (state.type && item.typeKey !== state.type) {
                    return false;
                }
                if (state.filiere && item.filiere !== state.filiere) {
                    return false;
                }
                if (state.licence && item.licence !== state.licence) {
                    return false;
                }
                if (state.matiere && item.matiere !== state.matiere) {
                    return false;
                }
                if (state.semester && item.semester !== state.semester) {
                    return false;
                }
                if (state.query) {
                    const terms = state.query.toLowerCase().split(/\s+/).filter(Boolean);
                    return terms.every(function (term) {
                        return String(item.searchText || '').indexOf(term) !== -1;
                    });
                }
                return true;
            });
        }

        function render() {
            const results = filteredDocuments();
            const beforeGrid = grid ? snapshotPositions(grid) : new Map();
            const beforeMobile = mobileList ? snapshotPositions(mobileList) : new Map();

            if (resultsCount) {
                resultsCount.textContent = String(results.length);
            }
            if (resultsSummary) {
                const countLabel = results.length + ' ressource' + (results.length !== 1 ? 's' : '');
                if (state.query) {
                    resultsSummary.textContent = results.length > 0
                        ? countLabel + ' pour « ' + state.query + ' »'
                        : 'Aucun résultat pour « ' + state.query + ' »';
                } else {
                    resultsSummary.textContent = results.length > 0
                        ? countLabel
                        : 'Aucun document pour ces filtres';
                }
            }

            if (editorialList) {
                editorialList.innerHTML = results.map(renderEditorialRow).join('');
            }
            if (grid) {
                grid.innerHTML = results.map(renderGridCard).join('');
            }
            if (listBody) {
                listBody.innerHTML = results.map(renderListRow).join('');
            }
            if (mobileList) {
                const useNativeRows = isMobileAppViewport() && state.viewMode !== 'grid';
                mobileList.innerHTML = results.map(function (item) {
                    return useNativeRows ? renderNativeMobileItem(item) : renderMobileItem(item);
                }).join('');
                mobileList.hidden = state.viewMode === 'grid';
            }
            hydrateLazyThumbs(grid);
            hydrateLazyThumbs(mobileList);
            hydrateLazyThumbs(editorialList);
            bindThumbFallbacks(grid);
            bindThumbFallbacks(mobileList);
            bindThumbFallbacks(editorialList);
            initCardHoverInteractions(grid);
            initCardHoverInteractions(mobileList);
            initCardHoverInteractions(editorialList);
            initMobileTouchPolish(mobileList);
            if (window.emspMotion && typeof window.emspMotion.refresh === 'function') {
                window.emspMotion.refresh(root);
            }
            if (emptyState) {
                emptyState.hidden = results.length !== 0;
            }

            root.dataset.viewMode = state.viewMode;
            root.classList.toggle('is-mobile-native', isMobileAppViewport());
            if (editorialList) {
                editorialList.hidden = state.viewMode !== 'editorial';
                if (state.viewMode === 'editorial') {
                    animateFlip(editorialList, beforeGrid);
                }
            }
            if (grid) {
                grid.hidden = state.viewMode !== 'grid';
                if (state.viewMode === 'grid') {
                    animateFlip(grid, beforeGrid);
                }
            }
            const listWrap = root.querySelector('[data-role="list-wrap"]');
            if (listWrap) {
                listWrap.hidden = state.viewMode !== 'list';
            }
            if (mobileList) {
                animateFlip(mobileList, beforeMobile);
            }

            viewButtons.forEach(function (button) {
                const buttonMode = button.getAttribute('data-view-mode') || 'editorial';
                const activeMode = buttonMode === 'editorial' && isMobileAppViewport() ? 'list' : buttonMode;
                button.classList.toggle('is-active', activeMode === state.viewMode);
            });
            chips.forEach(function (chip) {
                chip.classList.toggle('is-active', chip.getAttribute('data-chip-type') === state.type);
            });
            root.querySelectorAll('[data-filter-control]').forEach(function (select) {
                const filterKey = select.getAttribute('data-filter-control');
                if (filterKey === 'query') {
                    return;
                }
                select.value = state[filterKey] || '';
            });
            if (searchInput) {
                searchInput.value = state.query;
            }

            syncUrl();
            root.classList.add('is-ready');
            if (skeletonList) {
                skeletonList.hidden = true;
                skeletonList.setAttribute('aria-hidden', 'true');
            }
        }

        function resetFilters() {
            state.query = '';
            state.type = '';
            state.filiere = '';
            state.licence = '';
            state.matiere = '';
            state.semester = '';
            render();
        }

        function toggleFilterUi(forceOpen) {
            if (window.matchMedia('(min-width: 992px)').matches) {
                const collapsed = typeof forceOpen === 'boolean'
                    ? !forceOpen
                    : !root.classList.contains('is-panel-collapsed');
                root.classList.toggle('is-panel-collapsed', collapsed);
                return;
            }

            const open = typeof forceOpen === 'boolean' ? forceOpen : !root.classList.contains('is-sheet-open');
            root.classList.toggle('is-sheet-open', open);
        }

        applyStateFromUrl();

        viewButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const selected = button.getAttribute('data-view-mode') || 'editorial';
                state.viewMode = normalizeViewModeForViewport(selected);
                render();
            });
        });

        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                state.type = chip.getAttribute('data-chip-type') || '';
                render();
            });
        });

        root.querySelectorAll('[data-filter-control]').forEach(function (select) {
            const filterKey = select.getAttribute('data-filter-control');
            if (filterKey === 'query') {
                return;
            }
            select.addEventListener('change', function () {
                state[filterKey] = select.value || '';
                render();
            });
        });

        root.querySelectorAll('[data-action="reset-filters"]').forEach(function (button) {
            button.addEventListener('click', function () {
                resetFilters();
            });
        });

        root.querySelectorAll('[data-action="toggle-filters"]').forEach(function (button) {
            button.addEventListener('click', function () {
                toggleFilterUi();
                button.classList.toggle('is-active', root.classList.contains('is-sheet-open') || !root.classList.contains('is-panel-collapsed'));
            });
        });

        if (backdrop) {
            backdrop.addEventListener('click', function () {
                toggleFilterUi(false);
            });
        }

        if (searchInput) {
            let timer = null;
            searchInput.addEventListener('input', function () {
                window.clearTimeout(timer);
                timer = window.setTimeout(function () {
                    state.query = searchInput.value.trim();
                    render();
                }, 80);
            });
        }

        root.addEventListener('click', function (event) {
            const quickViewTrigger = event.target.closest('[data-action="quick-view"]');
            if (quickViewTrigger) {
                event.preventDefault();
                event.stopPropagation();
                const id = Number(quickViewTrigger.getAttribute('data-doc-id'));
                const item = documents.find(function (entry) { return Number(entry.id) === id; });
                if (item) {
                    quickView.show(item);
                }
                return;
            }
        });

        document.addEventListener('keydown', function (event) {
            const targetTag = document.activeElement ? document.activeElement.tagName : '';
            if (event.key === '/' && searchInput && targetTag !== 'INPUT' && targetTag !== 'TEXTAREA') {
                event.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
            if (event.key === 'Escape') {
                toggleFilterUi(false);
            }
        });

        if (panel && window.matchMedia('(min-width: 992px)').matches) {
            root.classList.remove('is-sheet-open');
        }

        window.addEventListener('resize', function () {
            const normalized = normalizeViewModeForViewport(state.viewMode);
            if (normalized !== state.viewMode) {
                state.viewMode = normalized;
                render();
                return;
            }
            root.classList.toggle('is-mobile-native', isMobileAppViewport());
        }, { passive: true });

        render();
    });
})();
