<?php if (!empty($featuredDocs)): ?>
<section class="emsp-featured-section emsp-section-band">
    <div class="container">
        <header class="emsp-featured-section__head emsp-motion-item">
            <div>
                <span class="section-chip">Documents du moment</span>
                <h2>Les plus téléchargés</h2>
            </div>
            <a href="documents" class="btn btn-sm btn-outline-primary fw-semibold">
                Voir tout <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
            </a>
        </header>
        <div class="row g-3">
            <?php foreach ($featuredDocs as $fd): ?>
            <?php
            $docType = strtolower(trim((string) ($fd['doc_type'] ?? '')));
            $badgeClass = match ($docType) {
                'concours' => 'emsp-featured-card__badge--concours',
                'examen' => 'emsp-featured-card__badge--examen',
                'cours' => 'emsp-featured-card__badge--cours',
                'td' => 'emsp-featured-card__badge--td',
                'correction' => 'emsp-featured-card__badge--correction',
                default => 'emsp-featured-card__badge--default',
            };
            ?>
            <div class="col-12 col-sm-6 col-lg-3 emsp-motion-item">
                <a href="document?id=<?= (int) $fd['id'] ?>" class="emsp-featured-card">
                    <div class="emsp-featured-card__body">
                        <span class="emsp-featured-card__badge <?= h($badgeClass) ?>">
                            <?= h((string) ($fd['doc_type'] ?? 'document')) ?>
                        </span>
                        <h3 class="emsp-featured-card__title">
                            <?= h((string) ($fd['title'] ?? '')) ?>
                        </h3>
                        <?php if (!empty($fd['filiere_name'])): ?>
                        <span class="emsp-featured-card__filiere">
                            <?= h((string) $fd['filiere_name']) ?>
                        </span>
                        <?php endif; ?>
                        <div class="emsp-featured-card__meta">
                            <span><i class="bi bi-download" aria-hidden="true"></i><?= number_format((int) ($fd['download_count'] ?? 0), 0, ',', ' ') ?></span>
                            <span><i class="bi bi-heart" aria-hidden="true"></i><?= (int) ($fd['like_count'] ?? 0) ?></span>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
