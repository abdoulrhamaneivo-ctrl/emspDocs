<?php if (!$isAuth): ?>
<section class="home-section home-journal-feature home-journal-feature--visitor emsp-section-band" data-emsp-parallax-bg="0.03">
    <div class="container">
        <header class="home-journal-feature-heading emsp-motion-item">
            <div>
                <span class="home-section-eyebrow emsp-kicker"><i class="bi bi-newspaper" aria-hidden="true"></i> Journal EMSP</span>
                <h2>À la une</h2>
            </div>
            <a href="journal" class="btn btn-outline-primary">Voir le journal <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </header>

        <?php if (empty($journalVisitorItems)): ?>
            <article class="home-journal-empty">
                <i class="bi bi-newspaper" aria-hidden="true"></i>
                <p>Aucune actualite n'est disponible pour le moment.</p>
            </article>
        <?php else: ?>
            <?php
            $journalItem = $journalVisitorItems[0];
            $journalTitle = trim((string) ($journalItem['title'] ?? 'Actualite EMSP'));
            $journalExcerpt = emsp_home_excerpt(strip_tags((string) ($journalItem['content'] ?? '')), 220);
            $journalCover = emsp_home_journal_cover_src((string) ($journalItem['content'] ?? ''));
            if ($journalCover === '') {
                $journalCover = url('assets/images/emsp-ivoire-tech-forum-2025.jpg');
            }
            $journalType = ucfirst((string) ($journalItem['type'] ?? 'Annonce'));
            $journalDate = date('d/m/Y', strtotime((string) ($journalItem['created_at'] ?? 'now')));
            $journalHref = 'journal/article?id=' . (int) ($journalItem['id'] ?? 0);
            ?>
            <article class="home-journal-feature-shell emsp-motion-item">
                <a class="home-journal-feature-media" href="<?= $journalHref ?>" aria-label="Lire l actualite <?= htmlspecialchars($journalTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>">
                    <img src="<?= htmlspecialchars($journalCover, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>" alt="<?= htmlspecialchars($journalTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>" loading="lazy" data-fallback-src="<?= h(url('assets/images/emsp-ivoire-tech-forum-2025.jpg')) ?>">
                </a>
                <div class="home-journal-feature-panel">
                    <span class="home-journal-feature-kicker"><?= h($journalType) ?></span>
                    <h3><?= htmlspecialchars($journalTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></h3>
                    <p><?= htmlspecialchars($journalExcerpt, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></p>
                    <div class="home-journal-feature-meta">
                        <span><i class="bi bi-calendar3" aria-hidden="true"></i><?= $journalDate ?></span>
                        <span><i class="bi bi-clock" aria-hidden="true"></i>Lecture rapide</span>
                    </div>
                    <div class="home-journal-feature-actions">
                        <a href="<?= $journalHref ?>" class="btn btn-primary">Lire l'actualite <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </div>
                </div>
            </article>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>
