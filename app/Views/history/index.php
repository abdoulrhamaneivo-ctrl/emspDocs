<section class="section-pad emsp-dashboard-page emsp-history-page emsp-native-screen">
    <div class="container emsp-dashboard-container">
        <div class="emsp-dashboard-shell emsp-history-shell">

            <header class="emsp-native-screen-header emsp-history-mobile-header d-md-none">
                <a href="<?= url('dashboard') ?>" class="emsp-native-screen-header__back" aria-label="Retour au tableau de bord">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                </a>
                <div class="emsp-native-screen-header__main">
                    <h1 class="emsp-native-screen-header__title">
                        <i class="bi bi-clock-history" aria-hidden="true"></i>
                        Mon historique
                    </h1>
                    <p class="emsp-native-screen-header__subtitle text-muted mb-0">Consultations et téléchargements</p>
                </div>
            </header>

            <header class="emsp-dashboard-hero emsp-animate-in d-none d-md-block" aria-label="Mon historique">
                <div class="emsp-dashboard-hero__main">
                    <span class="emsp-dashboard-kicker"><i class="bi bi-activity" aria-hidden="true"></i> Votre activité</span>
                    <h1 class="emsp-dashboard-hero-title">Mon historique</h1>
                    <p class="emsp-dashboard-hero-copy">Retrouvez vos consultations et téléchargements récents dans la bibliothèque EMSP.</p>
                </div>
            </header>

            <section class="emsp-dashboard-section emsp-history-section emsp-animate-in emsp-animate-in--delay-1" aria-labelledby="history-list-heading">
                <header class="emsp-dashboard-section__head emsp-history-section__head">
                    <h2 class="emsp-dashboard-section__title" id="history-list-heading">
                        <i class="bi bi-journal-text" aria-hidden="true"></i> Activité
                    </h2>
                </header>

                <div class="emsp-dashboard-section__body">
                    <div class="emsp-history-segmented emsp-native-segmented" role="tablist" aria-label="Filtrer l'historique">
                        <a href="<?= url('historique') ?>" class="emsp-native-segmented__item<?= $filter === '' ? ' is-active' : '' ?>" role="tab" aria-selected="<?= $filter === '' ? 'true' : 'false' ?>">
                            Tout
                        </a>
                        <a href="<?= url('historique?action=view') ?>" class="emsp-native-segmented__item<?= $filter === 'view' ? ' is-active' : '' ?>" role="tab" aria-selected="<?= $filter === 'view' ? 'true' : 'false' ?>">
                            Consultés
                        </a>
                        <a href="<?= url('historique?action=download') ?>" class="emsp-native-segmented__item<?= $filter === 'download' ? ' is-active' : '' ?>" role="tab" aria-selected="<?= $filter === 'download' ? ' true' : 'false' ?>">
                            Téléchargés
                        </a>
                    </div>

                    <?php if (empty($history)): ?>
                        <div class="emsp-dashboard-empty emsp-history-empty">
                            <span class="emsp-dashboard-empty-icon" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
                            <p class="emsp-dashboard-empty__title">Aucune activité enregistrée</p>
                            <p class="emsp-dashboard-empty__copy">Vos consultations et téléchargements apparaîtront ici au fur et à mesure.</p>
                            <a href="<?= url('documents') ?>" class="btn btn-primary emsp-dashboard-btn">
                                <i class="bi bi-collection me-1" aria-hidden="true"></i>Parcourir la bibliothèque
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="emsp-history-list" role="list">
                            <?php foreach ($history as $h): ?>
                                <?php
                                    $isDownload = ($h['action'] === 'download');
                                    $formattedDate = date('d/m/Y à H:i', strtotime((string) $h['last_date']));
                                    $actionLabel = $isDownload ? 'Téléchargé' : 'Consulté';
                                    $iconState = $isDownload ? 'is-download' : 'is-view';
                                ?>
                                <a href="<?= url('document?id=' . (int) $h['doc_id']) ?>" class="emsp-history-row" role="listitem">
                                    <span class="emsp-history-row__icon emsp-history-row__icon--<?= $iconState ?>" aria-hidden="true">
                                        <i class="bi bi-<?= $isDownload ? 'download' : 'eye' ?>"></i>
                                    </span>
                                    <span class="emsp-history-row__main">
                                        <span class="emsp-history-row__top">
                                            <span class="emsp-history-row__action"><?= h($actionLabel) ?></span>
                                            <span class="emsp-history-row__badge"><?= h(ucfirst((string) $h['doc_type'])) ?></span>
                                        </span>
                                        <span class="emsp-history-row__title"><?= h($h['title']) ?></span>
                                        <span class="emsp-history-row__meta">
                                            <?= h(trim($h['first_name'] . ' ' . $h['last_name'])) ?>
                                        </span>
                                    </span>
                                    <time class="emsp-history-row__time" datetime="<?= h((string) $h['last_date']) ?>"><?= h($formattedDate) ?></time>
                                    <span class="emsp-history-row__chevron" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($totalPages > 1): ?>
                            <nav class="emsp-history-pagination" aria-label="Pagination historique">
                                <ul class="pagination justify-content-center mb-0">
                                    <li class="page-item <?= $pageNum <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= url('historique?' . http_build_query(array_merge($_GET, ['page' => $pageNum - 1]))) ?>" aria-label="Page précédente">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>
                                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                        <li class="page-item <?= $p === $pageNum ? 'active' : '' ?>">
                                            <a class="page-link" href="<?= url('historique?' . http_build_query(array_merge($_GET, ['page' => $p]))) ?>"><?= $p ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= $pageNum >= $totalPages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= url('historique?' . http_build_query(array_merge($_GET, ['page' => $pageNum + 1]))) ?>" aria-label="Page suivante">
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </section>

        </div>
    </div>
</section>
