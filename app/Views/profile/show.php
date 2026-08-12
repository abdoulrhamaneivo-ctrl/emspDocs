<?php
$badgeColors = ['or' => '#F5A800', 'argent' => '#6B6B6B', 'bronze' => '#D4900A', 'none' => '#E0E0E0'];
$badgeLevel = $user['badge_level'] ?? 'none';
$fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
?>
<section class="emsp-profile-page emsp-motion-page emsp-native-screen">
    <div class="emsp-container emsp-container-wide">
        <div class="emsp-profile-layout">
            <aside class="emsp-profile-sidebar emsp-motion-item">
                <div class="emsp-profile-identity">
                    <?php $photoSrc = emsp_user_photo_src((string) ($user['photo_path'] ?? '')); ?>
                    <?php if ($photoSrc !== ''): ?>
                        <img src="<?= h($photoSrc) ?>" class="emsp-profile-avatar rounded-circle border"
                             onerror="this.src='<?= asset('images/logo-emsp.png') ?>';this.onerror=null;" alt="Photo profil">
                    <?php else: ?>
                        <div class="emsp-profile-avatar emsp-profile-avatar-fallback rounded-circle d-flex align-items-center justify-content-center fw-bold">
                            <?= h(emsp_user_initials((string) ($user['first_name'] ?? ''), (string) ($user['last_name'] ?? ''))) ?>
                        </div>
                    <?php endif; ?>

                    <span class="emsp-badge emsp-badge-primary">Espace Membre EMSP</span>
                    <h1 class="emsp-profile-name"><?= h($fullName) ?></h1>
                    <p class="emsp-profile-subline">
                        <?= h($user['filiere_name'] ?? 'Étudiant') ?> · <?= h($user['licence_name'] ?? '') ?>
                    </p>
                    <ul class="emsp-profile-stats">
                        <li><strong><?= (int) $stats['docs_approved'] ?></strong> ressources</li>
                        <li><strong><?= (int) $stats['fav_count'] ?></strong> favoris</li>
                        <li><strong><?= (int) $stats['dl_count'] ?></strong> téléchargements</li>
                    </ul>
                    <nav class="emsp-profile-quick-links d-md-none" aria-label="Accès rapides profil">
                        <a href="<?= url('dashboard') ?>" class="emsp-profile-quick-link">
                            <i class="bi bi-grid-fill" aria-hidden="true"></i>
                            <span>Mon espace</span>
                        </a>
                        <a href="<?= url('favoris') ?>" class="emsp-profile-quick-link">
                            <i class="bi bi-star-fill" aria-hidden="true"></i>
                            <span>Favoris</span>
                        </a>
                        <a href="<?= url('historique') ?>" class="emsp-profile-quick-link">
                            <i class="bi bi-clock-history" aria-hidden="true"></i>
                            <span>Historique</span>
                        </a>
                        <a href="<?= url('dashboard') ?>#notifications" class="emsp-profile-quick-link">
                            <i class="bi bi-bell-fill" aria-hidden="true"></i>
                            <span>Alertes</span>
                        </a>
                    </nav>
                </div>
            </aside>

            <div class="emsp-profile-main">
                <section class="emsp-profile-panel emsp-motion-item">
                    <h2 class="emsp-profile-panel-title">
                        <span class="emsp-profile-panel-num">01</span>
                        Informations académiques
                    </h2>
                    <div class="emsp-section-rule" aria-hidden="true"></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <span class="emsp-profile-label">Filière</span>
                            <span class="emsp-profile-value"><?= h($user['filiere_name'] ?? '—') ?></span>
                        </div>
                        <div class="col-md-6">
                            <span class="emsp-profile-label">Niveau</span>
                            <span class="emsp-profile-value"><?= h($user['licence_name'] ?? '—') ?></span>
                        </div>
                        <div class="col-md-6">
                            <span class="emsp-profile-label">Mode d'inscription</span>
                            <span class="emsp-profile-value"><?= ($user['registration_method'] ?? '') === 'school_email' ? 'Email école' : 'Carte étudiante' ?></span>
                        </div>
                        <div class="col-md-6">
                            <span class="emsp-profile-label">Membre depuis</span>
                            <span class="emsp-profile-value"><?= !empty($user['created_at']) ? h(date('d/m/Y', strtotime((string) $user['created_at']))) : '—' ?></span>
                        </div>
                    </div>
                </section>

                <section class="emsp-profile-panel emsp-motion-item">
                    <h2 class="emsp-profile-panel-title">
                        <span class="emsp-profile-panel-num">02</span>
                        Modifier mes informations
                    </h2>
                    <div class="emsp-section-rule" aria-hidden="true"></div>
                    <form method="post" action="<?= url('mon-profil') ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_profile">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="emsp-label">Prénom</label>
                                <input class="emsp-input" type="text" name="first_name" value="<?= h($user['first_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="emsp-label">Nom</label>
                                <input class="emsp-input" type="text" name="last_name" value="<?= h($user['last_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="emsp-label">Changer la photo de profil (JPG/PNG)</label>
                                <input class="emsp-input" type="file" name="photo" accept=".jpg,.jpeg,.png">
                            </div>
                        </div>
                        <button type="submit" class="emsp-btn emsp-btn-primary mt-3">Enregistrer les modifications</button>
                    </form>
                </section>

                <section class="emsp-profile-panel emsp-motion-item">
                    <h2 class="emsp-profile-panel-title">
                        <span class="emsp-profile-panel-num">03</span>
                        Sécurité du compte
                    </h2>
                    <div class="emsp-section-rule" aria-hidden="true"></div>
                    <form method="post" action="<?= url('mon-profil') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="change_password">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="emsp-label">Mot de passe actuel</label>
                                <input class="emsp-input" type="password" name="current_password" required autocomplete="current-password">
                            </div>
                            <div class="col-md-6">
                                <label class="emsp-label">Nouveau mot de passe</label>
                                <input class="emsp-input" type="password" name="new_password" minlength="8" required autocomplete="new-password">
                            </div>
                            <div class="col-md-6">
                                <label class="emsp-label">Confirmer le nouveau mot de passe</label>
                                <input class="emsp-input" type="password" name="confirm_password" minlength="8" required autocomplete="new-password">
                            </div>
                        </div>
                        <button type="submit" class="emsp-btn emsp-btn-gold mt-3">Mettre à jour le mot de passe</button>
                    </form>
                </section>
            </div>
        </div>
    </div>
</section>
