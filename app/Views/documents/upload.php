<section class="emsp-section emsp-native-screen emsp-upload-screen py-4 py-md-5">
    <div class="emsp-container emsp-container-narrow">
        <header class="emsp-native-screen-header emsp-upload-screen-header mb-0 pb-0 border-0">
            <a href="<?= url('dashboard') ?>" class="emsp-native-screen-header__back d-md-none" aria-label="Retour au tableau de bord">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
            </a>
            <div class="emsp-native-screen-header__main">
                <h1 class="emsp-native-screen-header__title h2 fw-bold font-heading mb-1">Déposer une ressource</h1>
                <p class="emsp-native-screen-header__subtitle emsp-upload-screen-header__subtitle emsp-text-lead mb-0">Partagez un document utile à votre communauté académique.</p>
            </div>
        </header>

        <form action="<?= url('upload') ?>" method="post" enctype="multipart/form-data" id="uploadForm" class="emsp-upload-form">
            <?= csrf_field() ?>
            <input type="hidden" name="doc_type" id="doc_type" value="">

            <div class="emsp-upload-form-stack">
                <div class="emsp-form-section">
                    <div class="emsp-section-head">
                        <span class="emsp-section-number">01</span>
                        <h2 class="emsp-section-title">Fichier document</h2>
                    </div>

                    <div class="emsp-upload-zone" id="upload-drop-zone" role="button" tabindex="0" aria-label="Choisir un fichier à déposer">
                        <input type="file" name="document" id="documentInput"
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.txt,.jpg,.jpeg,.png,.webp" required class="d-none">
                        <p class="fw-bold mb-1">Déposer votre document</p>
                        <p class="text-muted text-sm mb-0">PDF, DOCX, PPTX, ZIP, Images — max 5 Mo</p>
                        <p class="text-muted text-xs mb-0 d-md-none emsp-upload-zone__hint">Appuyez pour choisir un fichier</p>
                    </div>
                    <button type="button" class="emsp-btn emsp-btn-outline w-100 emsp-upload-scan-entry d-md-none mt-3" id="uploadScanEntry">
                        <i class="bi bi-camera me-2" aria-hidden="true"></i>Scanner ou importer une photo
                    </button>
                    <div class="invalid-feedback d-block mt-2 d-none" id="fileError"></div>
                    <div class="d-none align-items-center gap-3 mt-3 py-2" id="filePreview">
                        <i class="bi bi-file-earmark-pdf fs-4 text-danger"></i>
                        <div class="flex-grow-1">
                            <div class="fw-bold text-truncate" id="fileName"></div>
                            <div class="text-muted text-xs" id="fileMeta"></div>
                        </div>
                        <button class="btn btn-sm btn-link text-danger p-0" type="button" id="clearFileBtn" aria-label="Retirer le fichier">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>

                <div class="emsp-form-section">
                    <div class="emsp-section-head">
                        <span class="emsp-section-number">02</span>
                        <h2 class="emsp-section-title">Type & contenu</h2>
                    </div>

                    <div class="mb-4">
                        <label class="emsp-label mb-2">Type de document</label>
                        <div class="d-flex flex-wrap gap-2 emsp-upload-type-grid">
                            <button type="button" class="emsp-btn emsp-btn-outline btn-sm doc-type-btn" data-type="cours">Cours</button>
                            <button type="button" class="emsp-btn emsp-btn-outline btn-sm doc-type-btn" data-type="td">TD</button>
                            <button type="button" class="emsp-btn emsp-btn-outline btn-sm doc-type-btn" data-type="correction">Correction</button>
                            <button type="button" class="emsp-btn emsp-btn-outline btn-sm doc-type-btn" data-type="examen">Examen</button>
                            <button type="button" class="emsp-btn emsp-btn-outline btn-sm doc-type-btn" data-type="concours">Concours</button>
                        </div>
                        <div class="text-danger text-xs mt-1 d-none" id="docTypeError">Veuillez sélectionner un type de document.</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="emsp-label" for="titleInput">Titre de la ressource</label>
                            <input type="text" class="emsp-input" id="titleInput" name="title" maxlength="150" placeholder="Ex : Cours de Finance Digitale — Chapitre 1" required>
                        </div>
                        <div class="col-12">
                            <label class="emsp-label" for="uploadDescription">Description (facultatif)</label>
                            <textarea class="emsp-textarea" id="uploadDescription" name="description" rows="3" placeholder="Présentation rapide du document..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="emsp-form-section">
                    <div class="emsp-section-head">
                        <span class="emsp-section-number">03</span>
                        <h2 class="emsp-section-title">Classement académique</h2>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 mb-2">
                            <span class="emsp-label d-block mb-2">Filières concernées</span>
                            <div class="d-flex flex-wrap gap-2 emsp-upload-filiere-grid">
                                <?php foreach ($filieres as $row): ?>
                                    <label class="form-check emsp-upload-filiere-check">
                                        <input type="checkbox" class="form-check-input" name="filiere_ids[]" value="<?= (int) $row['id'] ?>">
                                        <span class="form-check-label fw-medium"><?= h($row['name']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="emsp-label" for="uploadLicence">Niveau d'étude</label>
                            <select class="emsp-select" id="uploadLicence" name="licence_id">
                                <option value="">-- Sélectionner le niveau --</option>
                                <?php foreach ($licences as $row): ?>
                                    <option value="<?= (int) $row['id'] ?>"><?= h($row['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="emsp-label" for="matiereSelect">Matière existante</label>
                            <select class="emsp-select" name="matiere_id" id="matiereSelect">
                                <option value="">-- Choisir dans la liste --</option>
                                <?php foreach ($matieres as $row): ?>
                                    <option value="<?= (int) $row['id'] ?>"><?= h($row['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="emsp-label" for="matiereFreeText">Ou nouvelle matière</label>
                            <input type="text" class="emsp-input" name="matiere_free_text" id="matiereFreeText" maxlength="120" placeholder="Ex : Marketing des Services">
                        </div>

                        <div class="col-md-6">
                            <label class="emsp-label" for="uploadSemester">Semestre</label>
                            <select class="emsp-select" id="uploadSemester" name="semester">
                                <option value="">-- Choisir --</option>
                                <?php foreach (['S1', 'S2', 'S3', 'S4', 'S5', 'S6'] as $s): ?>
                                    <option value="<?= $s ?>"><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="emsp-form-section emsp-upload-form-section--publish">
                    <div class="emsp-section-head">
                        <span class="emsp-section-number">04</span>
                        <h2 class="emsp-section-title">Publication</h2>
                    </div>

                    <div class="form-check form-switch mb-3 emsp-upload-public-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="isPublicSwitch" name="is_public" value="1" checked>
                        <label class="form-check-label fw-medium" for="isPublicSwitch">Visibilité publique dans la bibliothèque</label>
                    </div>

                    <p class="emsp-upload-status" id="uploadStatus" role="status" aria-live="polite">Après l'envoi, votre document sera mis en attente de validation et apparaîtra dans votre espace.</p>
                    <button type="submit" class="emsp-btn emsp-btn-primary w-100 py-3 d-none d-md-block" id="uploadSubmit">
                        <span class="emsp-upload-submit-label">Soumettre le document pour validation</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="emsp-upload-sticky d-md-none" role="region" aria-label="Actions de dépôt">
        <button type="submit" class="emsp-btn emsp-btn-primary w-100" id="uploadSubmitMobile" form="uploadForm">
            <span class="emsp-upload-submit-label">Soumettre le document</span>
        </button>
    </div>
</section>

<script>
const dropZone = document.getElementById('upload-drop-zone');
const fileInput = document.getElementById('documentInput');
const preview = document.getElementById('filePreview');
const fileNameEl = document.getElementById('fileName');
const fileMetaEl = document.getElementById('fileMeta');
const clearBtn = document.getElementById('clearFileBtn');
const titleInput = document.getElementById('titleInput');
const docTypeInput = document.getElementById('doc_type');
const docTypeError = document.getElementById('docTypeError');
const fileError = document.getElementById('fileError');
const matiereSelect = document.getElementById('matiereSelect');
const matiereFreeText = document.getElementById('matiereFreeText');
const uploadScanEntry = document.getElementById('uploadScanEntry');

function emspUploadReleaseScroll() {
    document.body.style.overflow = '';
    document.body.style.removeProperty('overflow');
    document.body.classList.remove('modal-open', 'emsp-scan-open', 'emsp-mobile-fab-open');
    document.documentElement.classList.remove('emsp-modal-scroll-lock');
}

function bytesToHuman(bytes) {
    if (bytes < 1024) return bytes + ' o';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' Ko';
    return (bytes / (1024 * 1024)).toFixed(1) + ' Mo';
}

function showPreview(file) {
    fileNameEl.textContent = file.name;
    fileMetaEl.textContent = bytesToHuman(file.size);
    preview.classList.remove('d-none');
    preview.classList.add('d-flex');
    if (titleInput.value.trim() === '') {
        titleInput.value = file.name.replace(/\.[^/.]+$/, '').replace(/[_-]+/g, ' ');
    }
}

function openFilePicker() {
    if (fileInput && typeof fileInput.click === 'function') {
        fileInput.click();
    }
}

fileInput.addEventListener('change', function () {
    if (this.files.length > 0) {
        fileError.classList.add('d-none');
        showPreview(this.files[0]);
    }
});

clearBtn.addEventListener('click', function () {
    fileInput.value = '';
    preview.classList.add('d-none');
    preview.classList.remove('d-flex');
});

dropZone.addEventListener('click', openFilePicker);
dropZone.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openFilePicker(); }
});
dropZone.addEventListener('dragover', function (e) { e.preventDefault(); dropZone.style.borderColor = 'var(--emsp-primary)'; });
dropZone.addEventListener('dragleave', function () { dropZone.style.borderColor = ''; });
dropZone.addEventListener('drop', function (e) {
    e.preventDefault();
    dropZone.style.borderColor = '';
    if (e.dataTransfer.files.length > 0) {
        fileInput.files = e.dataTransfer.files;
        showPreview(e.dataTransfer.files[0]);
    }
});

if (uploadScanEntry) {
    uploadScanEntry.addEventListener('click', function () {
        if (window.EMSPScan && typeof window.EMSPScan.open === 'function') {
            window.EMSPScan.open();
            return;
        }
        openFilePicker();
    });
}

document.querySelectorAll('.doc-type-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.doc-type-btn').forEach(function (b) { b.classList.remove('active', 'emsp-btn-primary'); b.classList.add('emsp-btn-outline'); });
        this.classList.add('active', 'emsp-btn-primary');
        this.classList.remove('emsp-btn-outline');
        docTypeInput.value = this.dataset.type;
        docTypeError.classList.add('d-none');
    });
});

if (matiereSelect && matiereFreeText) {
    matiereSelect.addEventListener('change', function () {
        if (this.value !== '') matiereFreeText.value = '';
    });
    matiereFreeText.addEventListener('input', function () {
        if (this.value.trim() !== '') matiereSelect.value = '';
    });
}

document.getElementById('uploadForm').addEventListener('submit', function (event) {
    var submit = document.getElementById('uploadSubmit') || document.getElementById('uploadSubmitMobile');
    var status = document.getElementById('uploadStatus');
    if (!fileInput.files.length) {
        event.preventDefault();
        fileError.textContent = 'Ajoutez un fichier avant de soumettre.';
        fileError.classList.remove('d-none');
        dropZone.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }
    if (!docTypeInput.value) {
        event.preventDefault();
        docTypeError.classList.remove('d-none');
        return;
    }
    if (submit && !submit.disabled) {
        submit.disabled = true;
        submit.classList.add('is-loading');
        document.querySelectorAll('.emsp-upload-submit-label').forEach(function (label) {
            label.textContent = 'Envoi en cours…';
        });
        if (status) status.textContent = 'Votre fichier est en cours d’envoi. Ne fermez pas cette page.';
    }
});

window.addEventListener('pageshow', emspUploadReleaseScroll);
document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'visible') {
        emspUploadReleaseScroll();
    }
});

var scanOverlay = document.getElementById('emsp-scan-overlay');
if (scanOverlay && typeof MutationObserver !== 'undefined') {
    var scanObserver = new MutationObserver(function () {
        if (!scanOverlay.classList.contains('show')) {
            emspUploadReleaseScroll();
        }
    });
    scanObserver.observe(scanOverlay, { attributes: true, attributeFilter: ['class'] });
}

emspUploadReleaseScroll();
</script>
