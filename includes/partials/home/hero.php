<section class="home-hero emsp-animate-in">
    <div class="home-hero-shell emsp-institutional-hero">
        <div class="container">
            <div class="home-hero-intro">
                <span class="home-eyebrow emsp-kicker">EMSP · emsp.int</span>
                <h1 class="home-hero-title--wide">Bibliothèque <span class="home-hero-title-nowrap">académique</span> EMSP</h1>
                <p class="lead">Cours, TD, corrections, examens et concours — les ressources essentielles de la communauté EMSP, réunies dans un espace clair et institutionnel.</p>
                <?php $fsMenumProgram = function_exists('emsp_fs_menum_program_meta') ? emsp_fs_menum_program_meta() : null; ?>
                <?php if (!empty($fsMenumProgram)): ?>
                <p class="home-hero-program-badge">
                    <i class="bi bi-mortarboard-fill" aria-hidden="true"></i>
                    Programme <?= h($fsMenumProgram['short']) ?> · <?= h($fsMenumProgram['title']) ?>
                </p>
                <?php endif; ?>
                <div class="home-hero-actions">
                    <?php if ($isAuth): ?>
                        <a href="<?= url('dashboard') ?>" class="home-btn-primary">Accéder à mon espace</a>
                        <a href="documents" class="home-btn-secondary">Explorer la bibliothèque</a>
                    <?php else: ?>
                        <a href="register" class="home-btn-primary">Créer un compte</a>
                        <a href="login" class="home-btn-secondary emsp-open-login-modal" data-bs-toggle="modal" data-bs-target="#emspQuickLoginModal" data-emsp-modal-link="1">Se connecter</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="home-hero-stats emsp-motion-item" aria-label="Indicateurs de la plateforme">
            <div class="home-hero-stat"><i class="bi bi-journal-text" aria-hidden="true"></i><div><strong><?= $heroDocumentCount ?></strong><span>documents validés</span></div></div>
            <div class="home-hero-stat"><i class="bi bi-diagram-3" aria-hidden="true"></i><div><strong><?= $heroFiliereCount ?></strong><span>filières actives</span></div></div>
            <div class="home-hero-stat"><i class="bi bi-mortarboard" aria-hidden="true"></i><div><strong><?= $heroLicenceCount ?></strong><span>niveaux de licence</span></div></div>
            <?php if ($isAuth): ?><div class="home-hero-stat home-hero-stat--notice"><i class="bi bi-bell" aria-hidden="true"></i><div><strong><?= (int) $notificationsCount ?></strong><span>notifications non lues</span></div></div><?php endif; ?>
        </div>
    </div>
</section>
