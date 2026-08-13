<?php
$badgeColors = ['or' => '#F5A800', 'argent' => '#6B6B6B', 'bronze' => '#D4900A', 'none' => '#E0E0E0'];
$badgeLevel = $user['badge_level'] ?? 'none';
$fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$prenom = trim((string) ($user['first_name'] ?? ''));
$photoSrc = emsp_user_photo_src((string) ($user['photo_path'] ?? ''));
$currentFiliereId = (int) ($user['filiere_id'] ?? 0);
$selectedFiliereValue = $currentFiliereId > 0 ? $currentFiliereId : emsp_tronc_commun_filiere_value();
$filiereLabel = emsp_user_filiere_label($user['filiere_name'] ?? null);
?>
<section class="emsp-profile-page emsp-profile-page--own emsp-motion-page emsp-native-screen">
    <header class="emsp-native-screen-header emsp-profile-mobile-header d-md-none">
        <a href="<?= url('dashboard') ?>" class="emsp-native-screen-header__back" aria-label="Retour au tableau de bord">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
        </a>
        <div class="emsp-native-screen-header__main">
            <h1 class="emsp-native-screen-header__title">
                <i class="bi bi-person-circle" aria-hidden="true"></i>
                Mon profil
            </h1>
            <p class="emsp-native-screen-header__subtitle text-muted mb-0"><?= h($fullName) ?></p>
        </div>
    </header>

    <div class="emsp-container emsp-container-wide">
        <header class="emsp-profile-hero-bento emsp-animate-in d-none d-md-flex" aria-label="Mon profil">
            <div class="emsp-profile-hero-bento__avatar">
                <?php if ($photoSrc !== ''): ?>
                    <img src="<?= h($photoSrc) ?>" class="emsp-profile-hero-bento__photo rounded-circle"
                         onerror="this.src='<?= asset('images/logo-emsp.png') ?>';this.onerror=null;" alt="Photo profil">
                <?php else: ?>
                    <div class="emsp-profile-hero-bento__photo emsp-profile-hero-bento__photo--fallback rounded-circle d-flex align-items-center justify-content-center fw-bold">
                        <?= h(emsp_user_initials((string) ($user['first_name'] ?? ''), (string) ($user['last_name'] ?? ''))) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="emsp-profile-hero-bento__main">
                <span class="emsp-profile-kicker"><i class="bi bi-person-badge" aria-hidden="true"></i> Espace membre EMSP</span>
                <h1 class="emsp-profile-hero-bento__title">Bonjour, <?= h($prenom !== '' ? $prenom : $fullName) ?> !</h1>
                <p class="emsp-profile-hero-bento__subline">
                    <?= h($filiereLabel) ?> · <?= h($user['licence_name'] ?? '') ?>
                </p>
                <ul class="emsp-profile-hero-bento__stats" aria-label="Statistiques profil">
                    <li><span class="emsp-profile-stat-badge emsp-profile-stat-badge--green"><strong><?= (int) $stats['docs_approved'] ?></strong> ressources</span></li>
                    <li><span class="emsp-profile-stat-badge emsp-profile-stat-badge--gold"><strong><?= (int) $stats['fav_count'] ?></strong> favoris</span></li>
                    <li><span class="emsp-profile-stat-badge emsp-profile-stat-badge--green"><strong><?= (int) $stats['dl_count'] ?></strong> téléchargements</span></li>
                </ul>
            </div>
        </header>

        <div class="emsp-mini-grid emsp-profile-kpis emsp-animate-in emsp-animate-in--delay-1 d-md-none" role="list" aria-label="Statistiques profil">
            <div class="emsp-mini-card emsp-kpi-card" role="listitem">
                <span class="emsp-kpi-card__icon emsp-kpi-card__icon--approved" aria-hidden="true"><i class="bi bi-files"></i></span>
                <div class="emsp-kpi-card__body">
                    <div class="value text-primary"><?= (int) $stats['docs_approved'] ?></div>
                    <div class="label">Ressources</div>
                </div>
            </div>
            <div class="emsp-mini-card emsp-kpi-card" role="listitem">
                <span class="emsp-kpi-card__icon emsp-kpi-card__icon--pending" aria-hidden="true"><i class="bi bi-star-fill"></i></span>
                <div class="emsp-kpi-card__body">
                    <div class="value text-warning"><?= (int) $stats['fav_count'] ?></div>
                    <div class="label">Favoris</div>
                </div>
            </div>
            <div class="emsp-mini-card emsp-kpi-card" role="listitem">
                <span class="emsp-kpi-card__icon emsp-kpi-card__icon--total" aria-hidden="true"><i class="bi bi-download"></i></span>
                <div class="emsp-kpi-card__body">
                    <div class="value text-success"><?= (int) $stats['dl_count'] ?></div>
                    <div class="label">Télécharg.</div>
                </div>
            </div>
        </div>

        <aside class="emsp-profile-sidebar emsp-animate-in d-md-none">
            <div class="emsp-profile-identity">
                <div class="emsp-profile-avatar-wrap">
                    <?php if ($photoSrc !== ''): ?>
                        <img src="<?= h($photoSrc) ?>" class="emsp-profile-avatar rounded-circle border"
                             onerror="this.src='<?= asset('images/logo-emsp.png') ?>';this.onerror=null;" alt="Photo profil">
                    <?php else: ?>
                        <div class="emsp-profile-avatar emsp-profile-avatar-fallback rounded-circle d-flex align-items-center justify-content-center fw-bold">
                            <?= h(emsp_user_initials((string) ($user['first_name'] ?? ''), (string) ($user['last_name'] ?? ''))) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <span class="emsp-badge emsp-badge-primary">Espace Membre EMSP</span>
                <h2 class="emsp-profile-name"><?= h($fullName) ?></h2>
                <p class="emsp-profile-subline">
                    <?= h($filiereLabel) ?> · <?= h($user['licence_name'] ?? '') ?>
                </p>
                <nav class="emsp-profile-quick-links" aria-label="Accès rapides profil">
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
                    <button type="button" class="emsp-profile-quick-link" data-emsp-notif-trigger data-emsp-notif-label="Alertes">
                        <i class="bi bi-bell-fill" aria-hidden="true"></i>
                        <span>Alertes</span>
                    </button>
                </nav>
            </div>
        </aside>

        <div class="emsp-profile-main emsp-profile-main--stacked">
            <section class="emsp-dashboard-section emsp-animate-in emsp-animate-in--delay-2" aria-labelledby="profile-academic-heading">
                <header class="emsp-dashboard-section__head">
                    <h2 class="emsp-dashboard-section__title" id="profile-academic-heading">
                        <i class="bi bi-mortarboard" aria-hidden="true"></i> Informations académiques
                    </h2>
                </header>
                <div class="emsp-dashboard-section__body">
                    <div class="emsp-profile-info-grid row g-3">
                        <div class="col-md-6">
                            <span class="emsp-profile-label">Filière</span>
                            <span class="emsp-profile-value"><?= h($filiereLabel) ?></span>
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
                    <form method="post" action="<?= url('mon-profil') ?>" class="mt-4 pt-3 border-top">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_academic">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="emsp-label" for="profile-filiere">Filière d'étude</label>
                                <select id="profile-filiere" name="filiere_id" class="emsp-select" required>
                                    <option value="<?= emsp_tronc_commun_filiere_value() ?>" <?= $selectedFiliereValue === emsp_tronc_commun_filiere_value() ? 'selected' : '' ?>>Tronc commun (affectation ultérieure)</option>
                                    <?php foreach ($filieres as $f): ?>
                                        <option value="<?= (int) $f['id'] ?>" <?= $selectedFiliereValue === (int) $f['id'] ? 'selected' : '' ?>><?= h($f['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="emsp-label" for="profile-licence">Niveau d'étude</label>
                                <select id="profile-licence" name="licence_id" class="emsp-select" required>
                                    <option value="">— Sélectionner le niveau —</option>
                                    <?php foreach ($licences as $l): ?>
                                        <option value="<?= (int) $l['id'] ?>" <?= ((int) ($user['licence_id'] ?? 0) === (int) $l['id']) ? 'selected' : '' ?>><?= h($l['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="emsp-btn emsp-btn-outline emsp-profile-action-btn mt-3">Mettre à jour le parcours académique</button>
                    </form>
                </div>
            </section>

            <section class="emsp-dashboard-section emsp-animate-in emsp-animate-in--delay-3" aria-labelledby="profile-info-heading">
                <header class="emsp-dashboard-section__head">
                    <h2 class="emsp-dashboard-section__title" id="profile-info-heading">
                        <i class="bi bi-person-lines-fill" aria-hidden="true"></i> Modifier mes informations
                    </h2>
                </header>
                <div class="emsp-dashboard-section__body">
                    <form method="post" action="<?= url('mon-profil') ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_profile">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="emsp-label" for="profile-first-name">Prénom</label>
                                <input id="profile-first-name" class="emsp-input" type="text" name="first_name" value="<?= h($user['first_name'] ?? '') ?>" required autocomplete="given-name">
                            </div>
                            <div class="col-md-6">
                                <label class="emsp-label" for="profile-last-name">Nom</label>
                                <input id="profile-last-name" class="emsp-input" type="text" name="last_name" value="<?= h($user['last_name'] ?? '') ?>" required autocomplete="family-name">
                            </div>
                            <div class="col-12">
                                <label class="emsp-label" for="profile-photo">Changer la photo de profil (JPG/PNG)</label>
                                <input id="profile-photo" class="emsp-input" type="file" name="photo" accept=".jpg,.jpeg,.png">
                            </div>
                        </div>
                        <button type="submit" class="emsp-btn emsp-btn-primary emsp-profile-action-btn mt-3">Enregistrer les modifications</button>
                    </form>
                </div>
            </section>

            <section class="emsp-dashboard-section emsp-animate-in emsp-animate-in--delay-3" aria-labelledby="profile-security-heading">
                <header class="emsp-dashboard-section__head">
                    <h2 class="emsp-dashboard-section__title" id="profile-security-heading">
                        <i class="bi bi-shield-lock" aria-hidden="true"></i> Sécurité du compte
                    </h2>
                </header>
                <div class="emsp-dashboard-section__body">
                    <form method="post" action="<?= url('mon-profil') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="change_password">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="emsp-label" for="profile-current-password">Mot de passe actuel</label>
                                <input id="profile-current-password" class="emsp-input" type="password" name="current_password" required autocomplete="current-password">
                            </div>
                            <div class="col-md-6">
                                <label class="emsp-label" for="profile-new-password">Nouveau mot de passe</label>
                                <input id="profile-new-password" class="emsp-input" type="password" name="new_password" minlength="8" required autocomplete="new-password">
                            </div>
                            <div class="col-md-6">
                                <label class="emsp-label" for="profile-confirm-password">Confirmer le nouveau mot de passe</label>
                                <input id="profile-confirm-password" class="emsp-input" type="password" name="confirm_password" minlength="8" required autocomplete="new-password">
                            </div>
                        </div>
                        <button type="submit" class="emsp-btn emsp-btn-outline emsp-profile-action-btn mt-3">Mettre à jour le mot de passe</button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</section>
