<section class="emsp-profile-page emsp-profile-page--public emsp-motion-page">
    <div class="emsp-container emsp-container-wide">
        <header class="emsp-profile-public-header emsp-motion-item">
            <span class="emsp-kicker">Profil étudiant</span>
            <h1 class="emsp-profile-public-title">Documents partagés</h1>
        </header>

        <?php if (!$isAuthViewer): ?>
        <div class="emsp-profile-cta emsp-motion-item">
            <div>
                <strong>Rejoins la communauté EMSP Docs</strong>
                <p class="mb-0 mt-1">
                    Accède à tous les documents, partage tes ressources et rejoins
                    <?= number_format($docs_count) ?> documents déjà partagés.
                </p>
            </div>
            <div class="d-flex gap-2 flex-shrink-0">
                <a href="<?= url('register') ?>" class="emsp-btn emsp-btn-gold btn-sm">S'inscrire</a>
                <a href="<?= url('index.php') ?>?open_login=1" class="emsp-btn emsp-btn-outline btn-sm emsp-btn-outline-light">Se connecter</a>
            </div>
        </div>
        <?php endif; ?>

        <div class="emsp-profile-layout emsp-profile-layout--public">
            <aside class="emsp-profile-sidebar emsp-motion-item">
                <div class="emsp-profile-identity text-center">
                    <?php $publicProfilePhoto = emsp_user_photo_src((string) ($profil['photo_path'] ?? '')); ?>
                    <?php if ($publicProfilePhoto !== ''): ?>
                        <img src="<?= h($publicProfilePhoto) ?>"
                             class="emsp-profile-avatar rounded-circle mb-3 border emsp-profile-avatar-img" alt="Photo">
                    <?php else: ?>
                        <div class="emsp-profile-avatar emsp-profile-avatar-fallback rounded-circle d-flex align-items-center justify-content-center fw-bold mx-auto mb-3">
                            <?= h(emsp_user_initials((string) ($profil['first_name'] ?? ''), (string) ($profil['last_name'] ?? ''))) ?>
                        </div>
                    <?php endif; ?>

                    <h2 class="emsp-profile-name h5 mb-1">
                        <?= htmlspecialchars($profil['first_name'] . ' ' . $profil['last_name']) ?>
                    </h2>

                    <?php if ($profil['filiere_name']): ?>
                        <p class="emsp-profile-subline mb-2">
                            <?= htmlspecialchars($profil['filiere_name']) ?>
                            <?php if ($profil['licence_name']): ?>
                                — <?= htmlspecialchars($profil['licence_name']) ?>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>

                    <?php if ($profil['badge_level'] !== 'none'): ?>
                        <div class="emsp-profile-badge-chip mb-3 <?= h($badge_class_map[$profil['badge_level']] ?? 'emsp-profile-badge-level-none') ?>">
                            <?= $badge_labels[$profil['badge_level']] ?>
                        </div>
                    <?php endif; ?>

                    <div class="emsp-profile-stat-highlight">
                        <div class="emsp-profile-stat-number"><?= $profil['upload_count'] ?></div>
                        <div class="emsp-profile-subline">
                            document<?= $profil['upload_count'] > 1 ? 's' : '' ?> partagé<?= $profil['upload_count'] > 1 ? 's' : '' ?>
                        </div>
                    </div>

                    <p class="emsp-profile-subline mt-2 mb-0">
                        Membre depuis <?= date('d/m/Y', strtotime($profil['created_at'])) ?>
                    </p>
                </div>
            </aside>

            <div class="emsp-profile-main">
                <h2 class="emsp-profile-docs-heading emsp-motion-item">
                    <i class="bi bi-files me-2" aria-hidden="true"></i>
                    Documents partagés (<?= $docs_count ?>)
                </h2>

                <?php if ($docs_count === 0): ?>
                    <div class="emsp-profile-panel emsp-profile-empty emsp-motion-item">
                        Aucun document partagé pour le moment.
                    </div>
                <?php else: ?>
                    <div class="emsp-profile-doc-grid">
                    <?php foreach ($docs_result as $d): ?>
                        <a href="<?= url('document') ?>?id=<?= $d['id'] ?>" class="emsp-profile-doc-card emsp-motion-item text-decoration-none text-reset">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="badge <?= $type_colors[$d['doc_type']] ?? 'bg-secondary' ?>">
                                    <?= ucfirst($d['doc_type']) ?>
                                </span>
                                <?php if ($d['semester']): ?>
                                    <span class="badge bg-light text-muted border">
                                        <?= htmlspecialchars($d['semester']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h3 class="emsp-profile-doc-title emsp-line-clamp-2">
                                <?= htmlspecialchars($d['title']) ?>
                            </h3>
                            <div class="emsp-profile-doc-meta">
                                <span><?= date('d/m/Y', strtotime($d['created_at'])) ?></span>
                                <span><i class="bi bi-download me-1" aria-hidden="true"></i><?= $d['download_count'] ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="mt-4 emsp-motion-item">
                    <a href="<?= url('documents') ?>" class="emsp-btn emsp-btn-outline btn-sm">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Retour à la bibliothèque
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
