<?php
/** @var array<string, mixed> $filiere */
/** @var array<int, string[]> $licenceMap */
$filiereId = (int) ($filiere['id'] ?? 0);
$filiereName = (string) ($filiere['name'] ?? 'Filière');
$imageSrc = emsp_formation_image_src((string) ($filiere['cover_image_path'] ?? ''));
$summary = emsp_formation_summary($filiere);
$hasDetails = emsp_formation_has_details($filiere);
$detailId = 'formation-details-' . $filiereId;
$licenceLabels = $licenceMap[$filiereId] ?? [];
?>
<article class="formation-card emsp-editorial-card emsp-float-card emsp-motion-item">
    <div class="formation-card-media">
        <?php if ($imageSrc !== ''): ?>
            <img src="<?= h($imageSrc) ?>" alt="<?= h($filiereName) ?>" loading="lazy">
        <?php else: ?>
            <div class="formation-card-placeholder">
                <i class="bi bi-image" aria-hidden="true"></i>
                <strong>Visuel à ajouter</strong>
                <span>Ajoutez l'image depuis l'administration de la filière.</span>
            </div>
        <?php endif; ?>
    </div>
    <div class="formation-card-body">
        <div>
            <h3><?= h($filiereName) ?></h3>
        </div>

        <?php if (!empty($licenceLabels)): ?>
            <div class="formation-card-licences">
                <?php foreach ($licenceLabels as $licenceLabel): ?>
                    <span class="formation-card-licence"><?= h($licenceLabel) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p class="formation-card-summary"><?= h($summary) ?></p>

        <div class="formation-card-actions">
            <a href="<?= url('documents') ?>?filiere=<?= $filiereId ?>" class="btn btn-emsp">Explorer les documents</a>
            <?php if ($hasDetails): ?>
                <button class="btn btn-emsp-outline" type="button" data-bs-toggle="collapse" data-bs-target="#<?= h($detailId) ?>" aria-expanded="false" aria-controls="<?= h($detailId) ?>">
                    Présentation
                </button>
            <?php endif; ?>
        </div>

        <?php if ($hasDetails): ?>
            <div class="collapse formation-card-details" id="<?= h($detailId) ?>">
                <div class="formations-rich-content emsp-prose">
                    <?= function_exists('emsp_sanitize_rich_html') ? emsp_sanitize_rich_html((string) ($filiere['description_html'] ?? '')) : h((string) ($filiere['description_html'] ?? '')) ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</article>
