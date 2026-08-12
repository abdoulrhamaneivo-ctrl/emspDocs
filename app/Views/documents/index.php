<?php
$__av = asset_version();
?>
<link rel="stylesheet" href="<?= asset('css/docs-workspace.css') ?>?v=<?= h($__av) ?>">

<section class="docs-workspace-root emsp-page-shell is-panel-collapsed" data-docs-workspace data-page="library">
    <div class="emsp-container">
        <header class="docs-workspace-hero emsp-editorial-bg">
            <h1 class="docs-workspace-hero-title">Bibliothèque EMSP</h1>
            <p class="docs-workspace-hero-lead">
                Toutes les ressources académiques<br>de l'École.
            </p>
        </header>

        <div class="docs-workspace-toolbar">
            <div class="docs-workspace-search">
                <i class="bi bi-search"></i>
                <input type="search" data-filter-control="query" placeholder="Rechercher un cours, une matière...">
            </div>
            <button type="button" class="docs-workspace-filter-btn" data-action="toggle-filters">
                Filtres
            </button>
        </div>

        <div class="docs-workspace-chip-row">
            <button type="button" class="docs-workspace-chip is-active" data-chip-type="">Tous</button>
            <button type="button" class="docs-workspace-chip" data-chip-type="cours">Cours</button>
            <button type="button" class="docs-workspace-chip" data-chip-type="td">TD</button>
            <button type="button" class="docs-workspace-chip" data-chip-type="examen">Examens</button>
            <button type="button" class="docs-workspace-chip" data-chip-type="correction">Corrections</button>
            <button type="button" class="docs-workspace-chip" data-chip-type="concours">Concours</button>
        </div>

        <div class="docs-workspace-divider"></div>

        <div class="docs-workspace-layout">
            <div class="docs-workspace-content">
                <div class="docs-workspace-resultsbar">
                    <span class="docs-workspace-count" data-role="results-summary">Chargement...</span>
                    <div class="docs-workspace-view-switch">
                        <button type="button" class="docs-workspace-view-btn is-active" data-view-mode="editorial" title="Liste" aria-label="Vue liste">
                            <i class="bi bi-list-ul"></i>
                        </button>
                        <button type="button" class="docs-workspace-view-btn" data-view-mode="grid" title="Grille" aria-label="Vue grille">
                            <i class="bi bi-grid-3x3-gap"></i>
                        </button>
                    </div>
                </div>

                <div class="docs-workspace-canvas">
                    <div class="docs-workspace-empty" data-role="empty-state" hidden>
                        <h3 data-role="empty-title">Aucun document trouvé</h3>
                        <p data-role="empty-text">Essayez d'ajuster votre recherche ou de réinitialiser vos filtres.</p>
                        <button type="button" class="emsp-btn emsp-btn-outline btn-sm mt-2" data-action="reset-filters">
                            Réinitialiser
                        </button>
                    </div>

                    <div class="docs-workspace-editorial" data-role="editorial-list"></div>
                    <div class="docs-workspace-grid" data-role="desktop-grid" hidden></div>

                    <div class="docs-workspace-listwrap" data-role="list-wrap" hidden>
                        <table class="docs-workspace-list">
                            <thead>
                                <tr>
                                    <th>Document</th>
                                    <th>Type</th>
                                    <th>Auteur</th>
                                    <th>Taille</th>
                                    <th>Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody data-role="desktop-list-body"></tbody>
                        </table>
                    </div>

                    <div class="docs-workspace-skeleton-list" data-role="skeleton-list" aria-hidden="true">
                        <?php for ($__sk = 0; $__sk < 4; $__sk++): ?>
                        <div class="docs-workspace-skeleton-item">
                            <div class="docs-workspace-skeleton-thumb"></div>
                            <div class="docs-workspace-skeleton-body">
                                <div class="docs-workspace-skeleton-line docs-workspace-skeleton-line--short"></div>
                                <div class="docs-workspace-skeleton-line"></div>
                                <div class="docs-workspace-skeleton-line docs-workspace-skeleton-line--meta"></div>
                            </div>
                            <div class="docs-workspace-skeleton-actions">
                                <span></span><span></span>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                    <div class="docs-workspace-mobile-list" data-role="mobile-list"></div>
                </div>
            </div>

            <aside class="docs-workspace-panel" data-role="filters-panel">
                <h3 class="docs-workspace-panel-title">Filtres</h3>
                <div class="docs-workspace-field">
                    <label class="emsp-label">Filière</label>
                    <select data-filter-control="filiere" class="emsp-select" data-label="Toutes les filières"></select>
                </div>
                <div class="docs-workspace-field">
                    <label class="emsp-label">Niveau</label>
                    <select data-filter-control="licence" class="emsp-select" data-label="Tous les niveaux"></select>
                </div>
                <div class="docs-workspace-field">
                    <label class="emsp-label">Matière</label>
                    <select data-filter-control="matiere" class="emsp-select" data-label="Toutes les matières"></select>
                </div>
                <div class="docs-workspace-field">
                    <label class="emsp-label">Semestre</label>
                    <select data-filter-control="semester" class="emsp-select" data-label="Tous les semestres"></select>
                </div>
                <button type="button" class="emsp-btn emsp-btn-outline btn-sm w-100" data-action="reset-filters">
                    Tout effacer
                </button>
            </aside>
        </div>
    </div>

    <div class="docs-workspace-backdrop" data-role="filters-backdrop"></div>
    <div class="docs-workspace-bottomsheet" data-role="filters-sheet">
        <h3 class="docs-workspace-panel-title">Filtres</h3>
        <div class="docs-workspace-field">
            <label class="emsp-label">Filière</label>
            <select data-filter-control="filiere" class="emsp-select" data-label="Toutes les filières"></select>
        </div>
        <div class="docs-workspace-field">
            <label class="emsp-label">Niveau</label>
            <select data-filter-control="licence" class="emsp-select" data-label="Tous les niveaux"></select>
        </div>
        <div class="docs-workspace-field">
            <label class="emsp-label">Matière</label>
            <select data-filter-control="matiere" class="emsp-select" data-label="Toutes les matières"></select>
        </div>
        <div class="docs-workspace-field">
            <label class="emsp-label">Semestre</label>
            <select data-filter-control="semester" class="emsp-select" data-label="Tous les semestres"></select>
        </div>
        <button type="button" class="emsp-btn emsp-btn-outline btn-sm w-100" data-action="reset-filters">
            Tout effacer
        </button>
    </div>

    <div class="modal fade docs-quickview-modal" id="docsQuickViewModal" tabindex="-1" aria-hidden="true"
         data-emsp-motion-modal="1" data-bs-backdrop="true" data-bs-keyboard="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0">
                <div class="modal-header border-bottom docs-quickview-modal-head">
                    <div class="docs-quickview-modal-head-text">
                        <h5 class="modal-title font-heading fw-bold docs-quickview-modal-title" data-role="quickview-title">Aperçu rapide</h5>
                        <div class="docs-workspace-quick-badges" data-role="quickview-meta"></div>
                    </div>
                    <button type="button" class="btn-close emsp-modal-close-touch" data-bs-dismiss="modal" aria-label="Fermer l'aperçu"></button>
                </div>
                <div class="docs-quickview-body p-0">
                    <div class="docs-quickview-stage" data-role="quickview-stage"></div>
                </div>
            </div>
        </div>
    </div>

    <script type="application/json" class="docs-workspace-data"><?= $workspacePayloadJson ?></script>
    <script type="application/json" class="docs-workspace-config"><?= $workspaceConfigJson ?></script>
</section>

<script src="<?= asset('js/docs-workspace.js') ?>?v=<?= h($__av) ?>"></script>
