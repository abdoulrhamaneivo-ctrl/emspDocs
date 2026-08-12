const EMSPScan = (function () {
    'use strict';

    const state = {
        open: false,
        pages: [],
        stream: null,
        busy: false,
    };

    const selectors = {
        docInput: '#documentInput',
        uploadForm: '#uploadForm',
        titleInput: '#titleInput',
    };

    function isCompactViewport() {
        return window.matchMedia('(max-width: 991.98px)').matches;
    }

    function canUseFloatingActions() {
        return false;
    }

    function isUploadPage() {
        return (document.body.dataset.route || '') === 'upload' || !!document.querySelector(selectors.docInput);
    }

    function shouldAutoOpenScan() {
        try {
            const params = new URLSearchParams(window.location.search);
            return params.get('scan') === '1' || params.get('entry') === 'scan';
        } catch (err) {
            return false;
        }
    }

    function cleanupAutoOpenParam() {
        try {
            const url = new URL(window.location.href);
            if (!url.searchParams.has('scan') && url.searchParams.get('entry') !== 'scan') return;
            url.searchParams.delete('scan');
            url.searchParams.delete('entry');
            window.history.replaceState({}, document.title, url.toString());
        } catch (err) {
            // Ignore URL cleanup failures.
        }
    }

    function getAppBaseUrl() {
        try {
            var scripts = document.getElementsByTagName('script');
            for (var i = 0; i < scripts.length; i += 1) {
                var src = scripts[i].getAttribute('src') || '';
                if (src.indexOf('emsp-scanner.js') === -1) continue;
                var absolute = new URL(src, document.baseURI);
                var marker = '/assets/js/';
                var markerIndex = absolute.pathname.indexOf(marker);
                if (markerIndex !== -1) {
                    return absolute.origin + absolute.pathname.slice(0, markerIndex + 1);
                }
            }
            return new URL('/', document.baseURI).href;
        } catch (err) {
            return '/';
        }
    }

    function goToUpload(scanFirst) {
        var target = getAppBaseUrl() + 'upload' + (scanFirst ? '?entry=scan' : '');
        window.location.href = target;
    }

    function openNativeCapture() {
        const pageInput = document.getElementById('scanInput');
        if (pageInput && typeof pageInput.click === 'function') {
            pageInput.click();
            return true;
        }

        const overlayInput = document.querySelector('#emsp-scan-overlay input[type="file"]');
        if (overlayInput && typeof overlayInput.click === 'function') {
            overlayInput.click();
            return true;
        }

        return false;
    }

    function createFabStack() {
        if (document.getElementById('emsp-fab-stack')) return;
        const stack = document.createElement('div');
        stack.id = 'emsp-fab-stack';
        stack.className = 'emsp-fab-stack';

        const fab = document.createElement('button');
        fab.type = 'button';
        fab.id = 'emsp-scan-fab';
        fab.className = 'emsp-scan-fab';
        fab.innerHTML = '<i class="bi bi-camera"></i><span class="badge" style="display:none">0</span>';
        fab.setAttribute('aria-label', 'Scanner un document');
        fab.title = 'Scanner un document';
        fab.addEventListener('click', function () {
            if (isUploadPage()) {
                openScanner();
            } else {
                goToUpload(true);
            }
        });

        const uploadFab = document.createElement('a');
        uploadFab.id = 'emsp-upload-fab';
        uploadFab.className = 'emsp-upload-fab';
        uploadFab.href = getAppBaseUrl() + 'upload';
        uploadFab.setAttribute('aria-label', 'Ouvrir la page de dépôt');
        uploadFab.title = 'Déposer un document';
        uploadFab.innerHTML = '<i class="bi bi-cloud-arrow-up"></i>';

        stack.appendChild(uploadFab);
        stack.appendChild(fab);
        document.body.appendChild(stack);
        syncFabVisibility();
    }

    function updateFabBadge() {
        const badge = document.querySelector('#emsp-scan-fab .badge');
        if (!badge) return;
        const count = state.pages.length;
        if (count > 0) {
            badge.style.display = 'inline-flex';
            badge.textContent = String(count);
        } else {
            badge.style.display = 'none';
        }
    }

    function updatePageCount() {
        const countEl = document.querySelector('#emsp-scan-overlay .emsp-scan-count');
        if (!countEl) return;
        const count = state.pages.length;
        countEl.textContent = count === 0
            ? 'Aucune page'
            : (count === 1 ? '1 page' : count + ' pages');
    }

    function ensureOverlay() {
        if (document.getElementById('emsp-scan-overlay')) return;
        const overlay = document.createElement('div');
        overlay.id = 'emsp-scan-overlay';
        overlay.className = 'emsp-scan-overlay';
        overlay.innerHTML = `
        <div class="emsp-scan-backdrop" data-action="close" aria-hidden="true"></div>
        <div class="emsp-scan-sheet" role="dialog" aria-modal="true" aria-labelledby="emspScanTitle">
            <header class="emsp-scan-toolbar">
                <button class="emsp-scan-close" type="button" aria-label="Fermer">&times;</button>
                <div class="emsp-scan-title" id="emspScanTitle">Scanner un document</div>
                <span class="emsp-scan-count">Aucune page</span>
            </header>
            <div class="emsp-scan-main">
                <div class="emsp-scan-viewport">
                    <video class="emsp-scan-video" autoplay playsinline muted></video>
                    <div class="emsp-scan-frame" aria-hidden="true">
                        <span class="emsp-scan-corner emsp-scan-corner--tl"></span>
                        <span class="emsp-scan-corner emsp-scan-corner--tr"></span>
                        <span class="emsp-scan-corner emsp-scan-corner--bl"></span>
                        <span class="emsp-scan-corner emsp-scan-corner--br"></span>
                    </div>
                </div>
                <div class="emsp-scan-fallback">
                    <span>Caméra indisponible. Utilisez l'import photo ci-dessous.</span>
                </div>
                <div class="emsp-scan-gallery" id="emsp-scan-pages" aria-label="Pages capturées">
                    <p class="emsp-scan-gallery__empty">Aucune page — capturez ci-dessous</p>
                    <div class="emsp-scan-gallery__track" role="list"></div>
                </div>
            </div>
            <footer class="emsp-scan-controls">
                <div class="emsp-scan-secondary">
                    <button class="emsp-scan-btn" type="button" data-action="pages">Pages</button>
                    <button class="emsp-scan-btn warn" type="button" data-action="pdf">Créer PDF</button>
                    <label class="emsp-scan-btn" style="margin:0;cursor:pointer;">
                        <input type="file" accept="image/*" capture="environment" style="display:none" />
                        Importer
                    </label>
                </div>
                <button class="emsp-scan-capture" type="button" data-action="capture" aria-label="Capturer une page">
                    <span class="emsp-scan-capture-ring"></span>
                </button>
            </footer>
        </div>
        <div class="emsp-scan-preview" id="emsp-scan-preview" hidden aria-hidden="true">
            <div class="emsp-scan-preview__backdrop" data-action="close-preview"></div>
            <div class="emsp-scan-preview__panel" role="dialog" aria-label="Aperçu page">
                <header class="emsp-scan-preview__head">
                    <span class="emsp-scan-preview__label">Page 1</span>
                    <button type="button" class="emsp-scan-preview__close" aria-label="Fermer l'aperçu">&times;</button>
                </header>
                <div class="emsp-scan-preview__body">
                    <img src="" alt="" class="emsp-scan-preview__img">
                </div>
            </div>
        </div>`;
        document.body.appendChild(overlay);

        overlay.querySelector('.emsp-scan-close').addEventListener('click', closeScanner);
        overlay.querySelector('[data-action="close"]').addEventListener('click', closeScanner);
        overlay.querySelector('[data-action="capture"]').addEventListener('click', captureFrame);
        overlay.querySelector('[data-action="pages"]').addEventListener('click', focusPagesGallery);
        overlay.querySelector('[data-action="pdf"]').addEventListener('click', generatePdf);
        const input = overlay.querySelector('input[type="file"]');
        input.addEventListener('change', (e) => {
            if (e.target.files && e.target.files[0]) {
                addPageFromFile(e.target.files[0]);
                e.target.value = '';
            }
        });

        const preview = overlay.querySelector('#emsp-scan-preview');
        if (preview) {
            preview.querySelector('[data-action="close-preview"]').addEventListener('click', closePagePreview);
            preview.querySelector('.emsp-scan-preview__close').addEventListener('click', closePagePreview);
        }

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape' || !state.open) return;
            const previewEl = document.getElementById('emsp-scan-preview');
            if (previewEl && !previewEl.hidden) {
                closePagePreview();
                return;
            }
            closeScanner();
        });
    }

    function lockBodyScroll() {
        document.body.classList.add('emsp-scan-open');
        document.body.style.overflow = 'hidden';
    }

    function releaseBodyScroll() {
        document.body.style.overflow = '';
        document.body.style.removeProperty('overflow');
        document.body.classList.remove('modal-open', 'emsp-scan-open', 'emsp-mobile-fab-open');
        document.documentElement.classList.remove('emsp-modal-scroll-lock');
    }

    async function openScanner() {
        if (document.body.dataset.authenticated !== '1' || !isUploadPage()) return;
        if (document.permissionsPolicy && typeof document.permissionsPolicy.allowsFeature === 'function' && !document.permissionsPolicy.allowsFeature('camera')) {
            openNativeCapture();
            return;
        }
        ensureOverlay();
        const overlay = document.getElementById('emsp-scan-overlay');
        overlay.classList.add('show');
        lockBodyScroll();
        state.open = true;
        updateFabBadge();
        updatePageCount();
        const started = await startCamera();
        if (!started) {
            openNativeCapture();
        }
    }

    function closeScanner() {
        closePagePreview();
        const overlay = document.getElementById('emsp-scan-overlay');
        if (overlay) overlay.classList.remove('show');
        releaseBodyScroll();
        stopCamera();
        state.open = false;
    }

    function clearPages() {
        state.pages.forEach((page) => {
            if (page && page.url) {
                URL.revokeObjectURL(page.url);
            }
        });
        state.pages = [];
        updatePages();
        updateFabBadge();
        updatePageCount();
    }

    async function startCamera() {
        const overlay = document.getElementById('emsp-scan-overlay');
        if (!overlay) return false;
        const video = overlay.querySelector('video');
        const fallback = overlay.querySelector('.emsp-scan-fallback');
        if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
            fallback.classList.add('show');
            return false;
        }
        try {
            state.stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: 'environment' } },
                audio: false,
            });
            video.srcObject = state.stream;
            fallback.classList.remove('show');
            return true;
        } catch (err) {
            fallback.classList.add('show');
            return false;
        }
    }

    function stopCamera() {
        if (state.stream) {
            state.stream.getTracks().forEach(t => t.stop());
            state.stream = null;
        }
    }

    function captureFrame() {
        if (state.busy) return;
        const overlay = document.getElementById('emsp-scan-overlay');
        const video = overlay.querySelector('video');
        if (!video || !video.videoWidth) return;
        state.busy = true;
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        canvas.toBlob((blob) => {
            if (blob) {
                addPageBlob(blob);
            }
            state.busy = false;
        }, 'image/jpeg', 0.92);
    }

    function addPageBlob(blob) {
        const url = URL.createObjectURL(blob);
        state.pages.push({ blob, url });
        updatePages();
        updateFabBadge();
        updatePageCount();
        scrollGalleryToEnd();
    }

    function addPageFromFile(file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const blob = dataUrlToBlob(e.target.result);
            addPageBlob(blob);
        };
        reader.readAsDataURL(file);
    }

    function removePage(idx) {
        const page = state.pages[idx];
        if (page && page.url) {
            URL.revokeObjectURL(page.url);
        }
        state.pages.splice(idx, 1);
        updatePages();
        updateFabBadge();
        updatePageCount();
    }

    function updatePages() {
        const wrap = document.getElementById('emsp-scan-pages');
        if (!wrap) return;
        const track = wrap.querySelector('.emsp-scan-gallery__track');
        const empty = wrap.querySelector('.emsp-scan-gallery__empty');
        if (!track) return;

        track.innerHTML = '';
        const count = state.pages.length;
        wrap.classList.toggle('has-pages', count > 0);
        if (empty) {
            empty.hidden = count > 0;
        }

        state.pages.forEach((p, idx) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'emsp-scan-gallery__item';
            item.setAttribute('role', 'listitem');
            item.setAttribute('aria-label', 'Page ' + (idx + 1) + ', aperçu');
            item.innerHTML = `
                <span class="emsp-scan-gallery__thumb"><img src="${p.url}" alt="Page ${idx + 1}"></span>
                <span class="emsp-scan-gallery__num">${idx + 1}</span>
                <span class="emsp-scan-gallery__remove" role="button" tabindex="-1" aria-label="Supprimer page ${idx + 1}">&times;</span>
            `;
            item.addEventListener('click', function (event) {
                if (event.target.closest('.emsp-scan-gallery__remove')) return;
                openPagePreview(idx);
            });
            item.querySelector('.emsp-scan-gallery__remove').addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                removePage(idx);
            });
            track.appendChild(item);
        });
    }

    function scrollGalleryToEnd() {
        const track = document.querySelector('#emsp-scan-pages .emsp-scan-gallery__track');
        if (!track) return;
        window.requestAnimationFrame(function () {
            track.scrollLeft = track.scrollWidth;
        });
    }

    function focusPagesGallery() {
        const wrap = document.getElementById('emsp-scan-pages');
        if (!wrap || state.pages.length === 0) return;
        wrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        const track = wrap.querySelector('.emsp-scan-gallery__track');
        if (track && track.lastElementChild) {
            track.lastElementChild.focus({ preventScroll: true });
        }
    }

    function openPagePreview(idx) {
        const page = state.pages[idx];
        const preview = document.getElementById('emsp-scan-preview');
        if (!page || !preview) return;
        const img = preview.querySelector('.emsp-scan-preview__img');
        const label = preview.querySelector('.emsp-scan-preview__label');
        if (img) {
            img.src = page.url;
            img.alt = 'Page ' + (idx + 1);
        }
        if (label) {
            label.textContent = 'Page ' + (idx + 1);
        }
        preview.hidden = false;
        preview.setAttribute('aria-hidden', 'false');
    }

    function closePagePreview() {
        const preview = document.getElementById('emsp-scan-preview');
        if (!preview || preview.hidden) return;
        preview.hidden = true;
        preview.setAttribute('aria-hidden', 'true');
        const img = preview.querySelector('.emsp-scan-preview__img');
        if (img) img.removeAttribute('src');
    }

    async function generatePdf() {
        if (state.pages.length === 0) return;
        const jspdf = window.jspdf && window.jspdf.jsPDF;
        if (!jspdf) {
            alert("Le module PDF n'est pas chargé.");
            return;
        }
        const doc = new jspdf({ orientation: 'portrait', unit: 'pt', format: 'a4' });
        for (let i = 0; i < state.pages.length; i++) {
            const imgData = await blobToDataUrl(state.pages[i].blob);
            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();
            const img = new Image();
            await new Promise(res => { img.onload = res; img.src = imgData; });
            const ratio = Math.min(pageWidth / img.width, pageHeight / img.height);
            const w = img.width * ratio;
            const h = img.height * ratio;
            const x = (pageWidth - w) / 2;
            const y = (pageHeight - h) / 2;
            if (i > 0) doc.addPage();
            doc.addImage(imgData, 'JPEG', x, y, w, h, undefined, 'FAST');
        }
        const pdfBlob = doc.output('blob');
        const file = new File([pdfBlob], `scan_${Date.now()}.pdf`, { type: 'application/pdf' });
        injectIntoUpload(file);
        clearPages();
        closeScanner();
    }

    function injectIntoUpload(file) {
        const input = document.querySelector(selectors.docInput);
        if (input) {
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            const changeEvent = new Event('change', { bubbles: true });
            input.dispatchEvent(changeEvent);
            const form = document.querySelector(selectors.uploadForm) || input.closest('form');
            if (form) {
                form.classList.add('emsp-upload-highlight');
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                setTimeout(() => form.classList.remove('emsp-upload-highlight'), 2200);
            }
            const titleInput = document.querySelector(selectors.titleInput);
            if (titleInput) {
                setTimeout(() => titleInput.focus(), 250);
            }
            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    icon: 'success',
                    title: 'PDF ajouté',
                    text: 'Le fichier est prêt. Complète maintenant les informations puis valide le dépôt.',
                    timer: 2200,
                    showConfirmButton: false
                });
            } else {
                alert('Le fichier est prêt. Complète maintenant les informations puis valide le dépôt.');
            }
            return;
        }
        const url = URL.createObjectURL(file);
        const a = document.createElement('a');
        a.href = url;
        a.download = file.name;
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    }

    function syncFabVisibility() {
        const stack = document.getElementById('emsp-fab-stack');
        if (!stack) return;
        const uploadFab = document.getElementById('emsp-upload-fab');
        stack.style.display = (isCompactViewport() && canUseFloatingActions()) ? 'flex' : 'none';
        if (uploadFab) {
            uploadFab.style.display = isUploadPage() ? 'none' : 'flex';
        }
    }

    function ensureFloatingActions() {
        if (!canUseFloatingActions()) {
            syncFabVisibility();
            return;
        }
        createFabStack();
        syncFabVisibility();
    }

    function dataUrlToBlob(dataUrl) {
        const arr = dataUrl.split(',');
        const mime = arr[0].match(/:(.*?);/)[1];
        const bstr = atob(arr[1]);
        let n = bstr.length;
        const u8arr = new Uint8Array(n);
        while (n--) u8arr[n] = bstr.charCodeAt(n);
        return new Blob([u8arr], { type: mime });
    }

    function blobToDataUrl(blob) {
        return new Promise((resolve) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.readAsDataURL(blob);
        });
    }

    function init() {
        ensureFloatingActions();
        if (isUploadPage() && shouldAutoOpenScan()) {
            cleanupAutoOpenParam();
            window.setTimeout(openScanner, 250);
        }
    }

    window.addEventListener('DOMContentLoaded', init);
    window.addEventListener('resize', () => {
        ensureFloatingActions();
    });
    window.addEventListener('pageshow', function () {
        ensureFloatingActions();
        if (!state.open) {
            releaseBodyScroll();
        }
    });
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            ensureFloatingActions();
            if (!state.open) {
                releaseBodyScroll();
            }
        }
    });

    return { open: openScanner };
})();

window.EMSPScan = EMSPScan;
