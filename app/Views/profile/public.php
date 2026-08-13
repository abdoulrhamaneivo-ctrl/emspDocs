<section class="emsp-profile-page emsp-profile-page--public emsp-motion-page emsp-native-screen">
    <header class="emsp-native-screen-header emsp-profile-mobile-header d-md-none">
        <a href="<?= url('documents') ?>" class="emsp-native-screen-header__back" aria-label="Retour à la bibliothèque">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
        </a>
        <div class="emsp-native-screen-header__main">
            <h1 class="emsp-native-screen-header__title">
                <i class="bi bi-person-badge" aria-hidden="true"></i>
                Profil étudiant
            </h1>
            <p class="emsp-native-screen-header__subtitle text-muted mb-0">Documents partagés</p>
        </div>
    </header>

    <div class="emsp-container emsp-container-wide">
        <?php $publicProfilePhoto = emsp_user_photo_src((string) ($profil['photo_path'] ?? '')); ?>
        <?php $publicFullName = trim(($profil['first_name'] ?? '') . ' ' . ($profil['last_name'] ?? '')); ?>
        <?php $publicFiliereLabel = emsp_user_filiere_label($profil['filiere_name'] ?? null); ?>

        <header class="emsp-profile-hero-bento emsp-profile-hero-bento--public emsp-animate-in d-none d-md-flex" aria-label="Profil étudiant">
            <div class="emsp-profile-hero-bento__avatar">
                <?php if ($publicProfilePhoto !== ''): ?>
                    <img src="<?= h($publicProfilePhoto) ?>" class="emsp-profile-hero-bento__photo rounded-circle" alt="Photo de <?= h($publicFullName) ?>">
                <?php else: ?>
                    <div class="emsp-profile-hero-bento__photo emsp-profile-hero-bento__photo--fallback rounded-circle d-flex align-items-center justify-content-center fw-bold">
                        <?= h(emsp_user_initials((string) ($profil['first_name'] ?? ''), (string) ($profil['last_name'] ?? ''))) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="emsp-profile-hero-bento__main">
                <span class="emsp-profile-kicker">Profil étudiant EMSP</span>
                <h1 class="emsp-profile-hero-bento__title"><?= h($publicFullName) ?></h1>
                <p class="emsp-profile-hero-bento__subline">
                    <?= h($publicFiliereLabel) ?>
                    <?php if (!empty($profil['licence_name'])): ?>
                        — <?= h($profil['licence_name']) ?>
                    <?php endif; ?>
                </p>
                <div class="emsp-profile-hero-bento__meta">
                    <?php if ($profil['badge_level'] !== 'none'): ?>
                        <span class="emsp-profile-badge-chip <?= h($badge_class_map[$profil['badge_level']] ?? 'emsp-profile-badge-level-none') ?>">
                            <?= $badge_labels[$profil['badge_level']] ?>
                        </span>
                    <?php endif; ?>
                    <span class="emsp-profile-stat-badge emsp-profile-stat-badge--green">
                        <strong><?= (int) $profil['upload_count'] ?></strong>
                        document<?= (int) $profil['upload_count'] > 1 ? 's' : '' ?> partagé<?= (int) $profil['upload_count'] > 1 ? 's' : '' ?>
                    </span>
                    <span class="emsp-profile-hero-bento__since">Membre depuis <?= date('d/m/Y', strtotime($profil['created_at'])) ?></span>
                </div>
            </div>
        </header>

        <?php if (!$isAuthViewer): ?>
        <div class="emsp-profile-cta emsp-animate-in">
            <div class="emsp-profile-cta__copy">
                <strong>Rejoins la communauté EMSP Docs</strong>
                <p class="mb-0 mt-1">
                    Accède à tous les documents, partage tes ressources et rejoins
                    <?= number_format($docs_count) ?> documents déjà partagés.
                </p>
            </div>
            <div class="emsp-profile-cta__actions d-flex gap-2 flex-shrink-0">
                <a href="<?= url('register') ?>" class="emsp-btn emsp-btn-primary emsp-profile-action-btn btn-sm">S'inscrire</a>
                <a href="<?= url('index.php') ?>?open_login=1" class="emsp-btn emsp-btn-outline btn-sm emsp-profile-action-btn">Se connecter</a>
            </div>
        </div>
        <?php endif; ?>

        <aside class="emsp-profile-sidebar emsp-animate-in d-md-none">
            <div class="emsp-profile-identity text-center">
                <div class="emsp-profile-avatar-wrap">
                    <?php if ($publicProfilePhoto !== ''): ?>
                        <img src="<?= h($publicProfilePhoto) ?>"
                             class="emsp-profile-avatar rounded-circle mb-0 border emsp-profile-avatar-img" alt="Photo">
                    <?php else: ?>
                        <div class="emsp-profile-avatar emsp-profile-avatar-fallback rounded-circle d-flex align-items-center justify-content-center fw-bold mx-auto mb-0">
                            <?= h(emsp_user_initials((string) ($profil['first_name'] ?? ''), (string) ($profil['last_name'] ?? ''))) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <h2 class="emsp-profile-name h5 mb-1"><?= h($publicFullName) ?></h2>
                <p class="emsp-profile-subline mb-2">
                    <?= h($publicFiliereLabel) ?>
                    <?php if (!empty($profil['licence_name'])): ?>
                        — <?= h($profil['licence_name']) ?>
                    <?php endif; ?>
                </p>
                <?php if ($profil['badge_level'] !== 'none'): ?>
                    <div class="emsp-profile-badge-chip mb-3 <?= h($badge_class_map[$profil['badge_level']] ?? 'emsp-profile-badge-level-none') ?>">
                        <?= $badge_labels[$profil['badge_level']] ?>
                    </div>
                <?php endif; ?>
                <div class="emsp-profile-stat-highlight">
                    <div class="emsp-profile-stat-number"><?= (int) $profil['upload_count'] ?></div>
                    <div class="emsp-profile-subline">
                        document<?= (int) $profil['upload_count'] > 1 ? 's' : '' ?> partagé<?= (int) $profil['upload_count'] > 1 ? 's' : '' ?>
                    </div>
                </div>
                <p class="emsp-profile-subline mt-2 mb-0">
                    Membre depuis <?= date('d/m/Y', strtotime($profil['created_at'])) ?>
                </p>
            </div>
        </aside>

        <div class="emsp-profile-main emsp-profile-main--stacked">
            <section class="emsp-dashboard-section emsp-animate-in emsp-animate-in--delay-1" aria-labelledby="profile-docs-heading">
                <header class="emsp-dashboard-section__head">
                    <h2 class="emsp-dashboard-section__title" id="profile-docs-heading">
                        <i class="bi bi-files" aria-hidden="true"></i>
                        Documents partagés (<?= (int) $docs_count ?>)
                    </h2>
                </header>
                <div class="emsp-dashboard-section__body">
                    <?php if ($docs_count === 0): ?>
                        <div class="emsp-profile-empty emsp-native-empty">
                            <span class="emsp-profile-empty__icon" aria-hidden="true"><i class="bi bi-folder2-open"></i></span>
                            <strong>Aucun document partagé</strong>
                            <p class="mb-0">Cet étudiant n'a pas encore publié de ressources sur EMSP Docs.</p>
                        </div>
                    <?php else: ?>
                        <div class="emsp-profile-doc-grid">
                        <?php foreach ($docs_result as $d): ?>
                            <a href="<?= url('document') ?>?id=<?= $d['id'] ?>" class="emsp-profile-doc-card emsp-animate-in text-decoration-none text-reset">
                                <div class="emsp-profile-doc-card__badges d-flex justify-content-between mb-2">
                                    <span class="badge <?= $type_colors[$d['doc_type']] ?? 'bg-secondary' ?>">
                                        <?= ucfirst($d['doc_type']) ?>
                                    </span>
                                    <?php if ($d['semester']): ?>
                                        <span class="badge bg-light text-muted border">
                                            <?= h($d['semester']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="emsp-profile-doc-title emsp-line-clamp-2">
                                    <?= h($d['title']) ?>
                                </h3>
                                <div class="emsp-profile-doc-meta">
                                    <span><?= date('d/m/Y', strtotime($d['created_at'])) ?></span>
                                    <span><i class="bi bi-download me-1" aria-hidden="true"></i><?= (int) $d['download_count'] ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mt-3">
                        <a href="<?= url('documents') ?>" class="emsp-btn emsp-btn-outline emsp-profile-action-btn btn-sm">
                            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Retour à la bibliothèque
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </div>
</section>
