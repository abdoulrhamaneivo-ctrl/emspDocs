<section class="section-pad emsp-dashboard-page emsp-upload-page emsp-native-screen emsp-upload-screen">
    <div class="container emsp-dashboard-container">
        <div class="emsp-dashboard-shell emsp-upload-shell">

            <header class="emsp-native-screen-header emsp-upload-mobile-header d-md-none">
                <a href="<?= url('dashboard') ?>" class="emsp-native-screen-header__back" aria-label="Retour au tableau de bord">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                </a>
                <div class="emsp-native-screen-header__main">
                    <h1 class="emsp-native-screen-header__title">
                        <i class="bi bi-cloud-upload" aria-hidden="true"></i>
                        Déposer une ressource
                    </h1>
                    <p class="emsp-native-screen-header__subtitle text-muted mb-0">Partagez un document utile</p>
                </div>
            </header>

            <header class="emsp-dashboard-hero emsp-upload-hero emsp-animate-in d-none d-md-block" aria-label="Déposer une ressource">
                <div class="emsp-dashboard-hero__main">
                    <span class="emsp-dashboard-kicker"><i class="bi bi-cloud-arrow-up" aria-hidden="true"></i> Dépôt de ressource</span>
                    <h1 class="emsp-dashboard-hero-title">Déposer une ressource</h1>
                    <p class="emsp-dashboard-hero-copy">Partagez un document utile à votre communauté académique — validation sous 48 h.</p>
                </div>
            </header>

            <div class="emsp-upload-progress emsp-animate-in emsp-animate-in--delay-1" aria-label="Étapes du formulaire">
                <ol class="emsp-upload-progress__list">
                    <li class="emsp-upload-progress__step is-current"><span>01</span> Fichier</li>
                    <li class="emsp-upload-progress__step"><span>02</span> Contenu</li>
                    <li class="emsp-upload-progress__step"><span>03</span> Classement</li>
                    <li class="emsp-upload-progress__step"><span>04</span> Publication</li>
                </ol>
            </div>

            <form action="<?= url('upload') ?>" method="post" enctype="multipart/form-data" id="uploadForm" class="emsp-upload-form">
                <?= csrf_field() ?>
                <input type="hidden" name="doc_type" id="doc_type" value="">

                <div class="emsp-upload-form-stack">
                    <section class="emsp-dashboard-section emsp-upload-section emsp-animate-in emsp-animate-in--delay-1" aria-labelledby="upload-step-01">
                        <header class="emsp-dashboard-section__head">
                            <h2 class="emsp-dashboard-section__title" id="upload-step-01">
                                <span class="emsp-upload-section__num">01</span>
                                <i class="bi bi-file-earmark-arrow-up" aria-hidden="true"></i> Fichier document
                            </h2>
                        </header>
                        <div class="emsp-dashboard-section__body">
                            <div class="emsp-upload-zone" id="upload-drop-zone" role="button" tabindex="0" aria-label="Choisir un fichier à déposer">
                                <input type="file" name="document" id="documentInput"
                                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.txt,.jpg,.jpeg,.png,.webp" required class="d-none">
                                <span class="emsp-upload-zone__icon" aria-hidden="true"><i class="bi bi-cloud-arrow-up"></i></span>
                                <p class="emsp-upload-zone__title">Déposer votre document</p>
                                <p class="emsp-upload-zone__hint">PDF, DOCX, PPTX, ZIP, Images — max 5 Mo</p>
                                <span class="btn btn-outline-primary emsp-dashboard-btn emsp-upload-zone__cta d-none d-md-inline-flex">Choisir un fichier</span>
                                <p class="emsp-upload-zone__tap d-md-none">Appuyez pour choisir un fichier</p>
                            </div>
                            <button type="button" class="emsp-upload-scan-entry d-md-none" id="uploadScanEntry">
                                <i class="bi bi-camera" aria-hidden="true"></i>
                                <span>Scanner ou importer une photo</span>
                            </button>
                            <div class="invalid-feedback d-block mt-2 d-none" id="fileError"></div>
                            <div class="emsp-upload-preview d-none" id="filePreview">
                                <span class="emsp-upload-preview__icon" aria-hidden="true"><i class="bi bi-file-earmark-pdf"></i></span>
                                <div class="emsp-upload-preview__body">
                                    <div class="emsp-upload-preview__name" id="fileName"></div>
                                    <div class="emsp-upload-preview__meta" id="fileMeta"></div>
                                </div>
                                <button class="emsp-upload-preview__clear" type="button" id="clearFileBtn" aria-label="Retirer le fichier">
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </section>

                    <section class="emsp-dashboard-section emsp-upload-section emsp-animate-in emsp-animate-in--delay-2" aria-labelledby="upload-step-02">
                        <header class="emsp-dashboard-section__head">
                            <h2 class="emsp-dashboard-section__title" id="upload-step-02">
                                <span class="emsp-upload-section__num">02</span>
                                <i class="bi bi-tags" aria-hidden="true"></i> Type &amp; contenu
                            </h2>
                        </header>
                        <div class="emsp-dashboard-section__body">
                            <div class="mb-4">
                                <label class="emsp-label emsp-upload-label">Type de document</label>
                                <div class="emsp-upload-type-grid">
                                    <button type="button" class="doc-type-btn" data-type="cours">Cours</button>
                                    <button type="button" class="doc-type-btn" data-type="td">TD</button>
                                    <button type="button" class="doc-type-btn" data-type="correction">Correction</button>
                                    <button type="button" class="doc-type-btn" data-type="examen">Examen</button>
                                    <button type="button" class="doc-type-btn" data-type="concours">Concours</button>
                                </div>
                                <div class="text-danger text-xs mt-1 d-none" id="docTypeError">Veuillez sélectionner un type de document.</div>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="emsp-label emsp-upload-label" for="titleInput">Titre de la ressource</label>
                                    <input type="text" class="emsp-input emsp-upload-input" id="titleInput" name="title" maxlength="150" placeholder="Ex : Cours de Finance Digitale — Chapitre 1" required>
                                </div>
                                <div class="col-12">
                                    <label class="emsp-label emsp-upload-label" for="uploadDescription">Description (facultatif)</label>
                                    <textarea class="emsp-textarea emsp-upload-textarea" id="uploadDescription" name="description" rows="3" placeholder="Présentation rapide du document…"></textarea>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="emsp-dashboard-section emsp-upload-section emsp-animate-in emsp-animate-in--delay-3" aria-labelledby="upload-step-03">
                        <header class="emsp-dashboard-section__head">
                            <h2 class="emsp-dashboard-section__title" id="upload-step-03">
                                <span class="emsp-upload-section__num">03</span>
                                <i class="bi bi-mortarboard" aria-hidden="true"></i> Classement académique
                            </h2>
                        </header>
                        <div class="emsp-dashboard-section__body">
                            <div class="row g-3">
                                <div class="col-12 mb-2">
                                    <span class="emsp-label emsp-upload-label d-block mb-2">Filières concernées</span>
                                    <div class="emsp-upload-filiere-grid">
                                        <?php foreach ($filieres as $row): ?>
                                            <label class="emsp-upload-filiere-check">
                                                <input type="checkbox" class="form-check-input" name="filiere_ids[]" value="<?= (int) $row['id'] ?>">
                                                <span class="emsp-upload-filiere-check__label"><?= h($row['name']) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="emsp-label emsp-upload-label" for="uploadLicence">Niveau d'étude</label>
                                    <select class="emsp-select emsp-upload-input" id="uploadLicence" name="licence_id">
                                        <option value="">-- Sélectionner le niveau --</option>
                                        <?php foreach ($licences as $row): ?>
                                            <option value="<?= (int) $row['id'] ?>"><?= h($row['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="emsp-label emsp-upload-label" for="matiereSelect">Matière existante</label>
                                    <select class="emsp-select emsp-upload-input" name="matiere_id" id="matiereSelect">
                                        <option value="">-- Choisir dans la liste --</option>
                                        <?php foreach ($matieres as $row): ?>
                                            <option value="<?= (int) $row['id'] ?>"><?= h($row['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="emsp-label emsp-upload-label" for="matiereFreeText">Ou nouvelle matière</label>
                                    <input type="text" class="emsp-input emsp-upload-input" name="matiere_free_text" id="matiereFreeText" maxlength="120" placeholder="Ex : Marketing des Services">
                                </div>

                                <div class="col-md-6">
                                    <label class="emsp-label emsp-upload-label" for="uploadSemester">Semestre</label>
                                    <select class="emsp-select emsp-upload-input" id="uploadSemester" name="semester">
                                        <option value="">-- Choisir --</option>
                                        <?php foreach (['S1', 'S2', 'S3', 'S4', 'S5', 'S6'] as $s): ?>
                                            <option value="<?= $s ?>"><?= $s ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="emsp-dashboard-section emsp-upload-section emsp-upload-section--publish emsp-animate-in emsp-animate-in--delay-4" aria-labelledby="upload-step-04">
                        <header class="emsp-dashboard-section__head">
                            <h2 class="emsp-dashboard-section__title" id="upload-step-04">
                                <span class="emsp-upload-section__num">04</span>
                                <i class="bi bi-send" aria-hidden="true"></i> Publication
                            </h2>
                        </header>
                        <div class="emsp-dashboard-section__body">
                            <div class="form-check form-switch emsp-upload-public-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="isPublicSwitch" name="is_public" value="1" checked>
                                <label class="form-check-label" for="isPublicSwitch">Visibilité publique dans la bibliothèque</label>
                            </div>

                            <p class="emsp-upload-status" id="uploadStatus" role="status" aria-live="polite">Après l'envoi, votre document sera mis en attente de validation et apparaîtra dans votre espace.</p>
                            <button type="submit" class="btn btn-primary emsp-dashboard-btn w-100 d-none d-md-inline-flex" id="uploadSubmit">
                                <i class="bi bi-cloud-upload me-1" aria-hidden="true"></i>
                                <span class="emsp-upload-submit-label">Soumettre le document pour validation</span>
                            </button>
                        </div>
                    </section>
                </div>
            </form>
        </div>
    </div>

    <div class="emsp-upload-sticky d-md-none" role="region" aria-label="Actions de dépôt">
        <button type="submit" class="btn btn-primary emsp-dashboard-btn w-100" id="uploadSubmitMobile" form="uploadForm">
            <i class="bi bi-cloud-upload me-1" aria-hidden="true"></i>
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
    dropZone.classList.add('has-file');
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
    dropZone.classList.remove('has-file');
});

dropZone.addEventListener('click', openFilePicker);
dropZone.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openFilePicker(); }
});
dropZone.addEventListener('dragover', function (e) { e.preventDefault(); dropZone.classList.add('is-dragover'); });
dropZone.addEventListener('dragleave', function () { dropZone.classList.remove('is-dragover'); });
dropZone.addEventListener('drop', function (e) {
    e.preventDefault();
    dropZone.classList.remove('is-dragover');
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
        document.querySelectorAll('.doc-type-btn').forEach(function (b) { b.classList.remove('active'); });
        this.classList.add('active');
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
