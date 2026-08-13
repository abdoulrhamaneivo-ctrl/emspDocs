<section class="emsp-section emsp-page-shell emsp-editorial-page emsp-auth-editorial-page emsp-auth-register-page emsp-register-premium emsp-auth-mesh-page py-4 py-md-5">
    <div class="emsp-container emsp-container-narrow emsp-auth-desktop-container">
        <div class="emsp-auth-desktop-grid emsp-auth-register-layout">
            <aside class="emsp-auth-editorial-aside emsp-auth-register-aside d-none d-lg-block" aria-label="Vie du campus EMSP">
                <div class="emsp-register-aside-mesh" aria-hidden="true"></div>
                <div class="emsp-register-aside-dots" aria-hidden="true"></div>
                <?php require dirname(__DIR__, 3) . '/includes/partials/auth-aside-carousel.php'; ?>
            </aside>

            <div class="emsp-auth-register-main">
                <div class="emsp-register-surface emsp-register-form-unified">
                    <div class="emsp-auth-aside-carousel-mobile d-lg-none mb-3">
                        <?php $carouselModifier = 'emsp-auth-aside-carousel--compact'; require dirname(__DIR__, 3) . '/includes/partials/auth-aside-carousel.php'; unset($carouselModifier); ?>
                    </div>

                    <header class="emsp-register-surface__header emsp-native-screen-header emsp-auth-page-header emsp-auth-screen-header d-lg-none mb-0 pb-0 border-0">
                        <div class="emsp-native-screen-header__main emsp-auth-page-header__stack">
                            <span class="emsp-auth-kicker emsp-auth-kicker--green">Inscription EMSP Docs</span>
                            <h1 class="emsp-native-screen-header__title h2 fw-bold font-heading mb-1">Créer votre compte</h1>
                            <p class="emsp-native-screen-header__subtitle emsp-text-lead mb-0">
                                Trois étapes pour rejoindre la médiathèque académique EMSP.
                            </p>
                        </div>
                    </header>

                    <div class="emsp-register-unified-head d-none d-lg-block">
                        <h1 class="emsp-register-unified-title font-heading">Créer votre compte</h1>
                        <nav class="emsp-register-stepper emsp-register-stepper--dots" aria-label="Étapes d'inscription" data-emsp-register-stepper>
                            <ol class="emsp-register-stepper__list">
                                <li class="emsp-register-stepper__item is-active" data-register-progress-step="1">
                                    <span class="emsp-register-stepper__dot" aria-hidden="true"></span>
                                    <span class="visually-hidden">Méthode</span>
                                </li>
                                <li class="emsp-register-stepper__item" data-register-progress-step="2">
                                    <span class="emsp-register-stepper__dot" aria-hidden="true"></span>
                                    <span class="visually-hidden">Identité</span>
                                </li>
                                <li class="emsp-register-stepper__item" data-register-progress-step="3">
                                    <span class="emsp-register-stepper__dot" aria-hidden="true"></span>
                                    <span class="visually-hidden">Sécurité et parcours</span>
                                </li>
                            </ol>
                        </nav>
                    </div>

                    <nav class="emsp-register-stepper d-lg-none" aria-label="Étapes d'inscription" data-emsp-register-stepper>
                        <ol class="emsp-register-stepper__list">
                            <li class="emsp-register-stepper__item is-active" data-register-progress-step="1">
                                <span class="emsp-register-stepper__node" aria-hidden="true"><span class="emsp-register-stepper__num">01</span></span>
                                <span class="emsp-register-stepper__label">Méthode</span>
                            </li>
                            <li class="emsp-register-stepper__item" data-register-progress-step="2">
                                <span class="emsp-register-stepper__node" aria-hidden="true"><span class="emsp-register-stepper__num">02</span></span>
                                <span class="emsp-register-stepper__label">Identité</span>
                            </li>
                            <li class="emsp-register-stepper__item" data-register-progress-step="3">
                                <span class="emsp-register-stepper__node" aria-hidden="true"><span class="emsp-register-stepper__num">03</span></span>
                                <span class="emsp-register-stepper__label">Sécurité</span>
                            </li>
                        </ol>
                    </nav>

                    <p class="emsp-register-wizard-progress" data-emsp-register-progress aria-live="polite">Étape 1 sur 3</p>

                    <div class="emsp-register-surface__body">
                        <form action="<?= url('register') ?>" method="post" enctype="multipart/form-data" class="emsp-auth-form-stack emsp-register-form" data-emsp-register-form data-school-domains="<?= h(json_encode($schoolEmailDomains ?? ['@emsp.int'], JSON_UNESCAPED_UNICODE)) ?>" data-school-hint="<?= h($schoolEmailHint ?? '@emsp.int') ?>" novalidate>
                            <?= csrf_field() ?>

                            <div class="emsp-register-wizard-steps" data-emsp-register-wizard>
                                <!-- Étape 1 — Méthode de vérification -->
                                <div class="emsp-form-section emsp-register-section emsp-register-step is-active" data-register-step="1">
                                    <h2 class="emsp-register-section-label">Méthode de vérification</h2>
                                    <p class="emsp-field-help emsp-auth-verify-lead mb-3">Choisissez d'abord comment confirmer votre statut étudiant. Cette étape permet une vérification à temps avant la saisie de votre email.</p>

                                    <fieldset class="emsp-register-verify-fieldset">
                                        <legend class="visually-hidden">Méthode de vérification du statut étudiant</legend>
                                        <div class="emsp-verification-options emsp-register-verify-tiles">
                                            <label class="emsp-verification-option" for="method-school">
                                                <input type="radio" name="registration_method" id="method-school" value="school_email" <?= (($old['registrationMethod'] ?? 'school_email') === 'school_email') ? 'checked' : '' ?> required>
                                                <span class="emsp-verification-option__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
                                                <span class="emsp-verification-option__icon"><i class="bi bi-envelope-check" aria-hidden="true"></i></span>
                                                <span class="emsp-verification-option__copy">
                                                    <strong>Email institutionnel</strong>
                                                    <small>Activation automatique après confirmation de votre adresse EMSP.</small>
                                                </span>
                                            </label>
                                            <label class="emsp-verification-option" for="method-card">
                                                <input type="radio" name="registration_method" id="method-card" value="manual_card" <?= (($old['registrationMethod'] ?? '') === 'manual_card') ? 'checked' : '' ?>>
                                                <span class="emsp-verification-option__check" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
                                                <span class="emsp-verification-option__icon"><i class="bi bi-person-vcard" aria-hidden="true"></i></span>
                                                <span class="emsp-verification-option__copy">
                                                    <strong>Carte étudiante</strong>
                                                    <small>Validation manuelle par l'administration sous 24 h.</small>
                                                </span>
                                            </label>
                                        </div>
                                    </fieldset>
                                    <?php if (!empty($errors['registration_method'])): ?><div class="emsp-field-error mt-2" role="alert"><?= h($errors['registration_method']) ?></div><?php endif; ?>
                                </div>

                                <!-- Étape 2 — Identité (+ email ou carte selon méthode) -->
                                <div class="emsp-form-section emsp-register-section emsp-register-step" data-register-step="2" hidden>
                                    <h2 class="emsp-register-section-label">Identité</h2>
                                    <div class="row g-3 emsp-auth-field-grid emsp-register-field-grid">
                                        <div class="col-md-6 emsp-field">
                                            <label class="emsp-label" for="register-first-name">Prénom</label>
                                            <div class="emsp-input-wrap emsp-input-wrap--icon">
                                                <i class="bi bi-person emsp-input-wrap__icon" aria-hidden="true"></i>
                                                <input id="register-first-name" type="text" name="first_name" class="emsp-input emsp-input--with-icon<?= !empty($errors['first_name']) ? ' is-invalid' : '' ?>" placeholder="Jean" value="<?= h($old['firstName'] ?? '') ?>" required autocomplete="given-name"<?= !empty($errors['first_name']) ? ' aria-invalid="true"' : '' ?>>
                                            </div>
                                            <?php if (!empty($errors['first_name'])): ?><div class="emsp-field-error" role="alert"><?= h($errors['first_name']) ?></div><?php endif; ?>
                                        </div>
                                        <div class="col-md-6 emsp-field">
                                            <label class="emsp-label" for="register-last-name">Nom</label>
                                            <div class="emsp-input-wrap emsp-input-wrap--icon">
                                                <i class="bi bi-person emsp-input-wrap__icon" aria-hidden="true"></i>
                                                <input id="register-last-name" type="text" name="last_name" class="emsp-input emsp-input--with-icon<?= !empty($errors['last_name']) ? ' is-invalid' : '' ?>" placeholder="Kouassi" value="<?= h($old['lastName'] ?? '') ?>" required autocomplete="family-name"<?= !empty($errors['last_name']) ? ' aria-invalid="true"' : '' ?>>
                                            </div>
                                            <?php if (!empty($errors['last_name'])): ?><div class="emsp-field-error" role="alert"><?= h($errors['last_name']) ?></div><?php endif; ?>
                                        </div>
                                        <div class="col-12 emsp-field" data-emsp-register-email-wrap>
                                            <label class="emsp-label" for="register-email">Adresse email</label>
                                            <div class="emsp-input-wrap emsp-input-wrap--icon">
                                                <i class="bi bi-envelope emsp-input-wrap__icon" aria-hidden="true"></i>
                                                <input
                                                    id="register-email"
                                                    type="email"
                                                    name="email"
                                                    class="emsp-input emsp-input--with-icon<?= !empty($errors['email']) ? ' is-invalid' : '' ?>"
                                                    placeholder="<?= h((($old['registrationMethod'] ?? 'school_email') === 'school_email') ? 'prenom.nom@emsp.int' : 'votre.email@exemple.com') ?>"
                                                    value="<?= h($old['email'] ?? '') ?>"
                                                    required
                                                    autocomplete="email"
                                                    data-emsp-register-email
                                                    aria-describedby="register-email-help register-email-callout"
                                                    <?= !empty($errors['email']) ? ' aria-invalid="true"' : '' ?>
                                                >
                                            </div>
                                            <p class="emsp-register-email-note mt-2 mb-0" id="register-email-callout" data-emsp-email-callout>
                                                <i class="bi bi-info-circle" aria-hidden="true"></i>
                                                <span id="register-email-help" data-emsp-email-help>
                                                    <?php if (($old['registrationMethod'] ?? 'school_email') === 'school_email'): ?>
                                                        Obligatoire : adresse institutionnelle EMSP (<?= h($schoolEmailHint ?? '@emsp.int') ?>).
                                                    <?php else: ?>
                                                        Adresse personnelle valide — votre carte étudiante sera vérifiée par l'administration.
                                                    <?php endif; ?>
                                                </span>
                                            </p>
                                            <div class="emsp-field-error d-none" data-emsp-email-client-error role="alert" aria-live="polite"></div>
                                            <?php if (!empty($errors['email'])): ?><div class="emsp-field-error" role="alert"><?= $errors['email'] ?></div><?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="emsp-card-upload is-collapsed mt-3" data-emsp-card-upload>
                                        <label class="emsp-label" for="register-student-card">Joindre votre carte étudiante</label>
                                        <p class="emsp-field-help mb-2">PDF, PNG ou JPG · 10 Mo maximum.</p>
                                        <input id="register-student-card" type="file" class="emsp-input" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf">
                                        <?php if (!empty($errors['student_card'])): ?><div class="emsp-field-error" role="alert"><?= h($errors['student_card']) ?></div><?php endif; ?>
                                    </div>
                                </div>

                                <!-- Étape 3 — Sécurité + parcours -->
                                <div class="emsp-form-section emsp-register-section emsp-register-step" data-register-step="3" hidden>
                                    <h2 class="emsp-register-section-label">Sécurité et parcours</h2>
                                    <div class="row g-3 emsp-auth-field-grid emsp-register-field-grid mb-4">
                                        <div class="col-md-6 emsp-field">
                                            <label class="emsp-label" for="register-password">Mot de passe <span class="emsp-label-hint">(8 car. min.)</span></label>
                                            <div class="emsp-input-wrap emsp-input-wrap--icon">
                                                <i class="bi bi-lock emsp-input-wrap__icon" aria-hidden="true"></i>
                                                <input id="register-password" type="password" name="password" class="emsp-input emsp-input--with-icon<?= !empty($errors['password']) ? ' is-invalid' : '' ?>" minlength="8" autocomplete="new-password" required data-emsp-password-strength aria-describedby="register-password-hint register-password-reqs"<?= !empty($errors['password']) ? ' aria-invalid="true"' : '' ?>>
                                            </div>
                                            <div class="emsp-password-hint" id="register-password-hint" data-emsp-password-hint aria-live="polite">
                                                <span class="emsp-password-hint__bar" aria-hidden="true"><span class="emsp-password-hint__fill"></span></span>
                                                <span class="emsp-password-hint__text">Au moins 8 caractères — lettres et chiffres recommandés.</span>
                                            </div>
                                            <?php if (!empty($errors['password'])): ?><div class="emsp-field-error" role="alert"><?= h($errors['password']) ?></div><?php endif; ?>
                                        </div>
                                        <div class="col-md-6 emsp-field">
                                            <label class="emsp-label" for="register-password-confirm">Confirmer le mot de passe</label>
                                            <div class="emsp-input-wrap emsp-input-wrap--icon">
                                                <i class="bi bi-lock-fill emsp-input-wrap__icon" aria-hidden="true"></i>
                                                <input id="register-password-confirm" type="password" name="password_confirm" class="emsp-input emsp-input--with-icon<?= !empty($errors['password_confirm']) ? ' is-invalid' : '' ?>" minlength="8" autocomplete="new-password" required<?= !empty($errors['password_confirm']) ? ' aria-invalid="true"' : '' ?>>
                                            </div>
                                            <?php if (!empty($errors['password_confirm'])): ?><div class="emsp-field-error" role="alert"><?= h($errors['password_confirm']) ?></div><?php endif; ?>
                                        </div>
                                        <div class="col-12">
                                            <ul class="emsp-password-requirements" id="register-password-reqs" data-emsp-password-requirements aria-label="Critères du mot de passe">
                                                <li data-req="length"><i class="bi bi-circle" aria-hidden="true"></i> 8 caractères minimum</li>
                                                <li data-req="mixed"><i class="bi bi-circle" aria-hidden="true"></i> Lettres et chiffres</li>
                                                <li data-req="case"><i class="bi bi-circle" aria-hidden="true"></i> Majuscules et minuscules</li>
                                                <li data-req="symbol"><i class="bi bi-circle" aria-hidden="true"></i> Symbole recommandé</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6 emsp-field">
                                            <label class="emsp-label" for="register-filiere">Filière d'étude</label>
                                            <div class="emsp-input-wrap emsp-input-wrap--icon emsp-input-wrap--select">
                                                <i class="bi bi-mortarboard emsp-input-wrap__icon" aria-hidden="true"></i>
                                                <select id="register-filiere" name="filiere_id" class="emsp-select emsp-input--with-icon<?= !empty($errors['filiere_id']) ? ' is-invalid' : '' ?>" required<?= !empty($errors['filiere_id']) ? ' aria-invalid="true"' : '' ?>>
                                                    <option value="">— Sélectionner la filière —</option>
                                                    <option value="<?= emsp_tronc_commun_filiere_value() ?>" <?= ((int) ($old['filiereId'] ?? -1) === emsp_tronc_commun_filiere_value()) ? 'selected' : '' ?>>Tronc commun (affectation ultérieure)</option>
                                                    <?php foreach ($filieres as $f): ?>
                                                        <option value="<?= (int) $f['id'] ?>" <?= ((int) ($old['filiereId'] ?? 0) === (int) $f['id']) ? 'selected' : '' ?>><?= h($f['name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <?php if (!empty($errors['filiere_id'])): ?><div class="emsp-field-error" role="alert"><?= h($errors['filiere_id']) ?></div><?php endif; ?>
                                        </div>
                                        <div class="col-md-6 emsp-field">
                                            <label class="emsp-label" for="register-licence">Niveau d'étude</label>
                                            <div class="emsp-input-wrap emsp-input-wrap--icon emsp-input-wrap--select">
                                                <i class="bi bi-mortarboard-fill emsp-input-wrap__icon" aria-hidden="true"></i>
                                                <select id="register-licence" name="licence_id" class="emsp-select emsp-input--with-icon<?= !empty($errors['licence_id']) ? ' is-invalid' : '' ?>" required<?= !empty($errors['licence_id']) ? ' aria-invalid="true"' : '' ?>>
                                                    <option value="">— Sélectionner le niveau —</option>
                                                    <?php foreach ($licences as $l): ?>
                                                        <option value="<?= (int) $l['id'] ?>" <?= ((int) ($old['licenceId'] ?? 0) === (int) $l['id']) ? 'selected' : '' ?>><?= h($l['name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <?php if (!empty($errors['licence_id'])): ?><div class="emsp-field-error" role="alert"><?= h($errors['licence_id']) ?></div><?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="emsp-auth-form-foot emsp-register-form-foot">
                                <div class="emsp-register-wizard-nav" data-emsp-register-wizard-nav>
                                    <button type="button" class="emsp-btn emsp-btn-outline emsp-register-wizard-prev" data-emsp-register-prev hidden>
                                        <i class="bi bi-arrow-left" aria-hidden="true"></i>
                                        Précédent
                                    </button>
                                    <button type="button" class="emsp-btn emsp-btn-gold emsp-register-wizard-next" data-emsp-register-next>
                                        Suivant
                                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                    </button>
                                    <button type="submit" class="emsp-btn emsp-btn-gold emsp-auth-submit emsp-register-submit emsp-register-wizard-submit" data-emsp-register-submit disabled aria-disabled="true" hidden>
                                        Créer mon compte EMSP Docs
                                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <p class="emsp-auth-form-foot-note text-center mb-0">
                                    Déjà inscrit ?
                                    <a href="<?= url('login') ?>" class="emsp-auth-link fw-semibold">Se connecter</a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php
$initialWizardStep = 1;
if (!empty($errors['registration_method'])) {
    $initialWizardStep = 1;
} elseif (!empty($errors['first_name']) || !empty($errors['last_name']) || !empty($errors['email']) || !empty($errors['student_card'])) {
    $initialWizardStep = 2;
} elseif (!empty($errors['password']) || !empty($errors['password_confirm']) || !empty($errors['filiere_id']) || !empty($errors['licence_id'])) {
    $initialWizardStep = 3;
}
?>
<?php ob_start(); ?>
<script>window.__emspRegisterInitialStep = <?= (int) $initialWizardStep ?>;</script>
<script src="<?= asset('js/emsp-auth-aside-carousel.js') ?>?v=<?= h(asset_version()) ?>"></script>
<script src="<?= asset('js/emsp-register.js') ?>?v=<?= h(asset_version()) ?>"></script>
<?php $page_scripts = ob_get_clean(); ?>
