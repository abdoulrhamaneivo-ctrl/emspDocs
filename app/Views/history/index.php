<section class="emsp-section emsp-native-screen emsp-history-screen py-5">
    <div class="emsp-container emsp-container-narrow">
        <header class="emsp-native-screen-header emsp-history-header mb-0 pb-0 border-0">
            <div class="emsp-native-screen-header__main">
                <h1 class="emsp-native-screen-header__title h2 fw-bold font-heading mb-1">Mon historique</h1>
                <p class="emsp-native-screen-header__subtitle text-muted text-sm mb-0">Vos consultations et téléchargements récents.</p>
            </div>
        </header>

        <div class="emsp-native-segmented emsp-history-filters" role="tablist" aria-label="Filtrer l'historique">
            <a href="<?= url('historique') ?>" class="emsp-native-segmented__item<?= $filter === '' ? ' is-active' : '' ?>" role="tab" aria-selected="<?= $filter === '' ? 'true' : 'false' ?>">
                Tout
            </a>
            <a href="<?= url('historique?action=view') ?>" class="emsp-native-segmented__item<?= $filter === 'view' ? ' is-active' : '' ?>" role="tab" aria-selected="<?= $filter === 'view' ? 'true' : 'false' ?>">
                Consultés
            </a>
            <a href="<?= url('historique?action=download') ?>" class="emsp-native-segmented__item<?= $filter === 'download' ? ' is-active' : '' ?>" role="tab" aria-selected="<?= $filter === 'download' ? 'true' : 'false' ?>">
                Téléchargés
            </a>
        </div>

        <?php if (empty($history)): ?>
            <div class="emsp-native-empty emsp-history-empty">
                <span class="emsp-native-empty__icon" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
                <strong class="emsp-native-empty__title">Aucune activité enregistrée</strong>
                <p class="emsp-native-empty__copy">Vos consultations et téléchargements apparaîtront ici au fur et à mesure.</p>
                <a href="<?= url('documents') ?>" class="emsp-btn emsp-btn-primary btn-sm mt-2">Parcourir la bibliothèque</a>
            </div>
        <?php else: ?>
            <div class="emsp-native-list emsp-history-list" role="list">
                <?php foreach ($history as $h): ?>
                    <?php
                        $isDownload = ($h['action'] === 'download');
                        $formattedDate = date('d/m/Y à H:i', strtotime((string) $h['last_date']));
                    ?>
                    <a href="<?= url('document?id=' . (int) $h['doc_id']) ?>" class="emsp-native-list-item emsp-history-item" role="listitem">
                        <span class="emsp-native-list-item__icon emsp-history-item__icon<?= $isDownload ? ' is-download' : ' is-view' ?>" aria-hidden="true">
                            <i class="bi bi-<?= $isDownload ? 'download' : 'eye' ?>"></i>
                        </span>
                        <span class="emsp-native-list-item__body">
                            <span class="emsp-history-item__type"><?= $isDownload ? 'Téléchargement' : 'Consultation' ?></span>
                            <span class="emsp-history-item__title"><?= h($h['title']) ?></span>
                            <span class="emsp-history-item__meta">
                                <?= h(trim($h['first_name'] . ' ' . $h['last_name'])) ?>
                                · <?= h(ucfirst((string) $h['doc_type'])) ?>
                            </span>
                            <time class="emsp-history-item__time" datetime="<?= h((string) $h['last_date']) ?>"><?= h($formattedDate) ?></time>
                        </span>
                        <span class="emsp-native-list-item__chevron" aria-hidden="true">
                            <i class="bi bi-chevron-right"></i>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav class="mt-5 pt-4 border-top" aria-label="Pagination historique">
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?= $pageNum <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= url('historique?' . http_build_query(array_merge($_GET, ['page' => $pageNum - 1]))) ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <li class="page-item <?= $p === $pageNum ? 'active' : '' ?>">
                                <a class="page-link" href="<?= url('historique?' . http_build_query(array_merge($_GET, ['page' => $p]))) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $pageNum >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= url('historique?' . http_build_query(array_merge($_GET, ['page' => $pageNum + 1]))) ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
