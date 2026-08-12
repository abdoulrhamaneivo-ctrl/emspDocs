(function (window, document) {
    'use strict';

    var FETCH_TIMEOUT_MS = 18000;
    var LOAD_TIMEOUT_MS = 12000;

    function escapeAttr(value) {
        return String(value || '').replace(/"/g, '&quot;');
    }

    function showLoading(wrap, show) {
        if (!wrap) {
            return;
        }
        var loading = wrap.querySelector('.emsp-pdf-preview-loading');
        if (loading) {
            loading.hidden = !show;
        }
    }

    function showFallback(wrap, frame, thumbUrl, previewUrl) {
        showLoading(wrap, false);
        if (thumbUrl) {
            var figure = document.createElement('figure');
            figure.className = 'emsp-pdf-preview-fallback';
            figure.innerHTML =
                '<img src="' + escapeAttr(thumbUrl) + '" alt="Aperçu du document" class="emsp-doc-preview-image">' +
                (previewUrl
                    ? '<figcaption><a href="' + escapeAttr(previewUrl) + '" target="_blank" rel="noopener">Ouvrir le PDF dans un nouvel onglet</a></figcaption>'
                    : '');
            if (frame && frame.parentNode) {
                frame.replaceWith(figure);
            } else if (wrap) {
                wrap.appendChild(figure);
            }
            return;
        }
        if (frame && previewUrl) {
            frame.src = previewUrl;
            armLoadTimeout(wrap, frame);
            return;
        }
        showLoading(wrap, false);
        if (wrap) {
            var note = document.createElement('p');
            note.className = 'emsp-pdf-preview-error';
            note.textContent = 'Impossible de charger l\'aperçu. Ouvrez la fiche du document.';
            wrap.appendChild(note);
        }
    }

    function isFrameInHiddenModal(frame) {
        var modal = frame && frame.closest('.modal');
        return !!(modal && !modal.classList.contains('show'));
    }

    function armLoadTimeout(wrap, frame) {
        if (!wrap || !frame) {
            return;
        }
        var timerKey = 'emspPdfLoadTimer';
        if (frame[timerKey]) {
            window.clearTimeout(frame[timerKey]);
        }
        frame[timerKey] = window.setTimeout(function () {
            var loading = wrap.querySelector('.emsp-pdf-preview-loading');
            if (loading && !loading.hidden) {
                showLoading(wrap, false);
            }
        }, LOAD_TIMEOUT_MS);
    }

    function fetchWithTimeout(url, options, timeoutMs) {
        return new Promise(function (resolve, reject) {
            var timer = window.setTimeout(function () {
                reject(new Error('pdfdata timeout'));
            }, timeoutMs);
            window.fetch(url, options)
                .then(function (response) {
                    window.clearTimeout(timer);
                    resolve(response);
                })
                .catch(function (error) {
                    window.clearTimeout(timer);
                    reject(error);
                });
        });
    }

    function assignBlobToFrame(frame, wrap, bytes, thumbUrl, previewUrl) {
        var blob = new Blob([bytes], { type: 'application/pdf' });
        var blobUrl = URL.createObjectURL(blob);
        frame.dataset.emspPdfLoaded = '1';
        frame.dataset.emspPdfBlobUrl = blobUrl;
        frame.addEventListener('load', function () {
            showLoading(wrap, false);
            if (wrap) {
                wrap.classList.add('is-pdf-ready');
            }
        }, { once: true });
        frame.addEventListener('error', function () {
            showFallback(wrap, frame, thumbUrl, previewUrl);
        }, { once: true });
        frame.src = blobUrl;
        armLoadTimeout(wrap, frame);
    }

    function tryPreviewUrl(frame, wrap, previewUrl, thumbUrl) {
        if (!previewUrl) {
            showFallback(wrap, frame, thumbUrl, previewUrl);
            return;
        }
        frame.dataset.emspPdfLoaded = 'preview';
        frame.addEventListener('load', function () {
            showLoading(wrap, false);
            if (wrap) {
                wrap.classList.add('is-pdf-ready');
            }
        }, { once: true });
        frame.addEventListener('error', function () {
            showFallback(wrap, frame, thumbUrl, previewUrl);
        }, { once: true });
        frame.src = previewUrl;
        armLoadTimeout(wrap, frame);
    }

    function loadPdfFrame(frame) {
        if (!frame || frame.dataset.emspPdfLoaded === '1') {
            return;
        }

        if (isFrameInHiddenModal(frame)) {
            frame.dataset.emspPdfDeferred = '1';
            return;
        }

        if (frame.dataset.emspPdfLoaded === 'pending') {
            return;
        }

        var wrap = frame.closest('.emsp-pdf-preview-wrap') || frame.parentElement;
        var pdfDataUrl = frame.getAttribute('data-pdf-data-url') || '';
        var previewUrl = frame.getAttribute('data-preview-url') || frame.getAttribute('src') || '';
        var thumbUrl = frame.getAttribute('data-fallback-thumb') || '';

        if (!pdfDataUrl && previewUrl) {
            pdfDataUrl = previewUrl.replace(/([?&])preview=1/, '$1pdfdata=1');
            if (pdfDataUrl === previewUrl) {
                pdfDataUrl = previewUrl + (previewUrl.indexOf('?') >= 0 ? '&' : '?') + 'pdfdata=1';
            }
        }

        if (!pdfDataUrl && previewUrl) {
            tryPreviewUrl(frame, wrap, previewUrl, thumbUrl);
            return;
        }

        if (!pdfDataUrl) {
            showFallback(wrap, frame, thumbUrl, previewUrl);
            return;
        }

        frame.removeAttribute('src');
        frame.dataset.emspPdfLoaded = 'pending';
        frame.dataset.emspPdfDeferred = '0';
        showLoading(wrap, true);

        fetchWithTimeout(pdfDataUrl, {
            credentials: 'include',
            headers: { Accept: 'application/json' }
        }, FETCH_TIMEOUT_MS)
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('pdfdata HTTP ' + response.status);
                }
                var contentType = (response.headers.get('content-type') || '').toLowerCase();
                if (contentType.indexOf('json') === -1) {
                    throw new Error('pdfdata not json');
                }
                return response.json();
            })
            .then(function (payload) {
                if (!payload || !payload.data) {
                    throw new Error('pdfdata empty');
                }
                var binary = window.atob(payload.data);
                var bytes = new Uint8Array(binary.length);
                for (var i = 0; i < binary.length; i++) {
                    bytes[i] = binary.charCodeAt(i);
                }
                assignBlobToFrame(frame, wrap, bytes, thumbUrl, previewUrl);
            })
            .catch(function () {
                frame.dataset.emspPdfLoaded = '0';
                tryPreviewUrl(frame, wrap, previewUrl, thumbUrl);
            });
    }

    function init(root) {
        var scope = root || document;
        scope.querySelectorAll('iframe[data-emsp-pdf-preview="1"], iframe.emsp-inline-pdf-frame[data-emsp-preview="1"]').forEach(loadPdfFrame);
    }

    function resetFrame(frame) {
        if (!frame) {
            return;
        }
        if (frame.emspPdfLoadTimer) {
            window.clearTimeout(frame.emspPdfLoadTimer);
            frame.emspPdfLoadTimer = null;
        }
        if (frame.dataset.emspPdfBlobUrl) {
            try {
                URL.revokeObjectURL(frame.dataset.emspPdfBlobUrl);
            } catch (error) {
                /* noop */
            }
        }
        delete frame.dataset.emspPdfLoaded;
        delete frame.dataset.emspPdfDeferred;
        delete frame.dataset.emspPdfBlobUrl;
        frame.removeAttribute('src');
        var wrap = frame.closest('.emsp-pdf-preview-wrap');
        if (wrap) {
            wrap.classList.remove('is-pdf-ready');
        }
    }

    function onModalShown(modal) {
        if (!modal) {
            init(document);
            return;
        }
        modal.querySelectorAll('iframe[data-emsp-pdf-preview="1"], iframe.emsp-inline-pdf-frame[data-emsp-preview="1"]').forEach(function (frame) {
            if (frame.dataset.emspPdfDeferred === '1' || frame.dataset.emspPdfLoaded === '0') {
                delete frame.dataset.emspPdfLoaded;
            }
            loadPdfFrame(frame);
        });
        if (window.emspMotion && typeof window.emspMotion.refresh === 'function') {
            window.emspMotion.refresh(modal);
        }
    }

    window.emspPdfPreview = {
        init: init,
        load: loadPdfFrame,
        reset: resetFrame
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { init(document); });
    } else {
        init(document);
    }

    document.addEventListener('shown.bs.modal', function (event) {
        onModalShown(event.target);
    });

    document.addEventListener('hidden.bs.modal', function (event) {
        var modal = event.target;
        if (!modal || !modal.classList || !modal.classList.contains('modal')) {
            return;
        }
        modal.querySelectorAll('iframe[data-emsp-pdf-preview="1"], iframe.emsp-inline-pdf-frame[data-emsp-preview="1"]').forEach(resetFrame);
    });
})(window, document);
