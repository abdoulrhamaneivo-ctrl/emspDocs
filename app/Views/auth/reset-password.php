<section class="emsp-section emsp-page-shell emsp-editorial-page emsp-auth-editorial-page emsp-auth-recover-page emsp-recover-premium py-4 py-md-5">
    <div class="emsp-container emsp-container-narrow emsp-auth-desktop-container">
        <div class="emsp-auth-desktop-grid emsp-auth-recover-layout">
            <aside class="emsp-auth-editorial-aside emsp-auth-recover-aside d-none d-lg-flex" aria-label="Aide réinitialisation">
                <span class="emsp-kicker emsp-auth-kicker emsp-auth-kicker--green">Compte EMSP Docs</span>
                <h2 class="emsp-auth-recover-aside__title font-heading">Nouveau mot de passe</h2>
                <p class="emsp-auth-recover-aside__lead">
                    Choisissez un mot de passe robuste, distinct de vos anciens identifiants. 8 caractères minimum, lettres et chiffres recommandés.
                </p>
                <ul class="emsp-auth-trust-list emsp-auth-trust-list--compact">
                    <li><i class="bi bi-key-fill" aria-hidden="true"></i> Token à usage unique via le lien reçu par email</li>
                    <li><i class="bi bi-shield-check" aria-hidden="true"></i> Session déconnectée sur les autres appareils après changement</li>
                </ul>
            </aside>

            <div class="emsp-auth-recover-main">
                <div class="emsp-recover-surface">
                    <header class="emsp-recover-surface__head emsp-native-screen-header emsp-auth-page-header mb-0 pb-0 border-0">
                        <div class="emsp-native-screen-header__main emsp-auth-page-header__stack">
                            <span class="emsp-kicker emsp-auth-kicker emsp-auth-kicker--green d-lg-none">Compte EMSP Docs</span>
                            <h1 class="emsp-native-screen-header__title h2 fw-bold font-heading mb-1">Nouveau mot de passe</h1>
                            <p class="emsp-native-screen-header__subtitle emsp-text-lead mb-0">Choisis un mot de passe sécurisé pour ton compte EMSP Docs (8 caractères minimum).</p>
                        </div>
                    </header>

                    <div class="emsp-recover-surface__body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger mb-4" role="alert"><?= h($error) ?></div>
                        <?php endif; ?>

                        <form action="<?= url('reset-password') ?>" method="post" class="emsp-auth-form-stack">
                            <?= csrf_field() ?>
                            <input type="hidden" name="token" value="<?= h($token) ?>">
                            <div class="emsp-form-group mb-3">
                                <label class="emsp-label" for="reset-password">Nouveau mot de passe</label>
                                <div class="emsp-input-wrap emsp-input-wrap--icon">
                                    <i class="bi bi-lock emsp-input-wrap__icon" aria-hidden="true"></i>
                                    <input id="reset-password" type="password" name="password" class="emsp-input emsp-input--with-icon" minlength="8" required autocomplete="new-password"
                                           placeholder="••••••••">
                                </div>
                            </div>
                            <div class="emsp-form-group mb-4">
                                <label class="emsp-label" for="reset-password-confirm">Confirmer le mot de passe</label>
                                <div class="emsp-input-wrap emsp-input-wrap--icon">
                                    <i class="bi bi-lock-fill emsp-input-wrap__icon" aria-hidden="true"></i>
                                    <input id="reset-password-confirm" type="password" name="password_confirm" class="emsp-input emsp-input--with-icon" minlength="8" required autocomplete="new-password"
                                           placeholder="••••••••">
                                </div>
                            </div>
                            <button type="submit" class="emsp-btn emsp-btn-gold emsp-auth-submit w-100">
                                Mettre à jour le mot de passe
                                <i class="bi bi-check-lg" aria-hidden="true"></i>
                            </button>
                            <p class="emsp-auth-form-foot-note text-center mb-0">
                                <a href="<?= url('login') ?>" class="emsp-auth-link">Retour à la connexion</a>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
