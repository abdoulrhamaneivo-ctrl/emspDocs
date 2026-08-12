
<section class="formations-shell emsp-page-shell">
    <div class="container">
        <div class="formations-hero emsp-editorial-bg emsp-institutional-hero emsp-animate-in">
            <span class="emsp-kicker">Formations · EMSP</span>
            <h1>Parcours académiques</h1>
            <p>
                Retrouvez ici les programmes et filières actifs, leur présentation et les ressources associées — pilotés depuis l'administration, avec une section dédiée au programme FS-MENUM.
            </p>
            <div class="formations-hero-meta">
                <span class="formations-hero-pill"><i class="bi bi-diagram-3" aria-hidden="true"></i><?= count($fsMenumFilieres) + count($filieresMain) ?> filière(s) active(s)</span>
                <span class="formations-hero-pill"><i class="bi bi-layers" aria-hidden="true"></i><?= count($licences) ?> niveau(x) disponible(s)</span>
                <a class="formations-hero-pill" href="<?= url('documents') ?>"><i class="bi bi-collection" aria-hidden="true"></i>Voir les ressources</a>
            </div>
        </div>

        <?php if (!empty($troncCommuns)): ?>
            <div class="formations-panel emsp-motion-item">
                <div class="formations-section-head">
                    <div>
                        <span class="emsp-kicker">Tronc commun</span>
                        <h2>Base partagée</h2>
                        <p>Une base commune avant la spécialisation par filière.</p>
                    </div>
                </div>
                <div class="formations-tronc-list">
                    <?php foreach ($troncCommuns as $tronc): ?>
                        <a class="formations-tronc-link" href="<?= url('documents') ?>?filiere=<?= (int) ($tronc['id'] ?? 0) ?>">
                            <i class="bi bi-arrow-up-right-circle-fill" aria-hidden="true"></i>
                            <span><?= h((string) ($tronc['name'] ?? 'Tronc commun')) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="formations-panel formations-program-panel emsp-motion-item">
            <div class="formations-section-head">
                <div>
                    <span class="formations-program-badge"><i class="bi bi-mortarboard-fill" aria-hidden="true"></i>Programme <?= h($fsMenumProgram['short']) ?></span>
                    <h2><?= h($fsMenumProgram['title']) ?></h2>
                    <p><?= h($fsMenumProgram['summary']) ?></p>
                </div>
            </div>

            <?php if (empty($fsMenumFilieres)): ?>
                <div class="alert alert-info mb-0">
                    Aucune filière n'est encore rattachée au programme FS-MENUM. Depuis l'administration, assignez le programme « FS-MENUM » aux filières concernées.
                </div>
            <?php else: ?>
                <p class="formations-program-count"><?= count($fsMenumFilieres) ?> filière(s) de spécialisation</p>
                <div class="formations-grid">
                    <?php foreach ($fsMenumFilieres as $filiere): ?>
                        <?php require __DIR__ . '/_filiere-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="formations-panel emsp-motion-item">
            <div class="formations-section-head">
                <div>
                    <span class="emsp-kicker">Autres filières</span>
                    <h2>Catalogue des filières</h2>
                    <p>Contenus et visuels alimentés depuis l'administration pour les parcours hors programme FS-MENUM.</p>
                </div>
            </div>

            <?php if (empty($filieresMain)): ?>
                <div class="alert alert-info mb-0">Aucune autre filière active pour le moment.</div>
            <?php else: ?>
                <div class="formations-grid">
                    <?php foreach ($filieresMain as $filiere): ?>
                        <?php require __DIR__ . '/_filiere-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($licences)): ?>
            <div class="formations-panel emsp-motion-item">
                <div class="formations-section-head">
                    <div>
                        <span class="emsp-kicker">Licences</span>
                        <h2>Niveaux académiques</h2>
                        <p>Repères actuellement disponibles sur la plateforme.</p>
                    </div>
                </div>
                <div class="formations-licence-pills">
                    <?php foreach ($licences as $licence): ?>
                        <span class="formations-licence-pill">
                            <i class="bi bi-mortarboard" aria-hidden="true"></i>
                            <?= h((string) ($licence['name'] ?? 'Niveau')) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
