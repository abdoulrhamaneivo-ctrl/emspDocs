<?php if ($isAuth && !empty($featuredJournalData)): ?>
<section class="home-section home-journal-feature emsp-section-band emsp-section-band--journal" data-emsp-parallax-bg="0.1">
    <div class="container">
        <article class="home-journal-feature-shell emsp-animate-in emsp-motion-item">
            <a class="home-journal-feature-media" href="journal/article?id=<?= (int) ($featuredJournalData['id'] ?? 0) ?>" aria-label="Lire l actualite a la une">
                <img src="<?= htmlspecialchars((string) ($featuredJournalData['cover'] ?? url('assets/images/emsp-ivoire-tech-forum-2025.jpg')), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) ($featuredJournalData['title'] ?? 'Actualite a la une'), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>" loading="lazy" data-fallback-src="<?= h(url('assets/images/emsp-ivoire-tech-forum-2025.jpg')) ?>">
            </a>
            <div class="home-journal-feature-panel">
                <span class="home-journal-feature-kicker">A la une du Journal</span>
                <h2><?= htmlspecialchars((string) ($featuredJournalData['title'] ?? 'Actualite EMSP'), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></h2>
                <p><?= htmlspecialchars((string) ($featuredJournalData['excerpt'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></p>
                <div class="home-journal-feature-meta">
                    <span><i class="bi bi-calendar3" aria-hidden="true"></i><?= date('d/m/Y', strtotime((string) ($featuredJournalData['date'] ?? 'now'))) ?></span>
                    <span><i class="bi bi-bookmark-star" aria-hidden="true"></i><?= htmlspecialchars((string) ($featuredJournalData['type_label'] ?? 'Annonce'), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></span>
                </div>
                <div class="home-journal-feature-actions">
                    <a href="journal/article?id=<?= (int) ($featuredJournalData['id'] ?? 0) ?>" class="btn btn-primary">Lire l actualite</a>
                    <a href="journal" class="btn btn-outline-light">Voir le journal</a>
                </div>
            </div>
        </article>
    </div>
</section>
<?php endif; ?>
