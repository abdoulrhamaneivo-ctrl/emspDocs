<section class="emsp-section emsp-page-shell emsp-editorial-page emsp-auth-editorial-page emsp-auth-recover-page emsp-recover-premium py-4 py-md-5">
    <div class="emsp-container emsp-container-narrow emsp-auth-desktop-container">
        <div class="emsp-auth-desktop-grid emsp-auth-recover-layout">
            <aside class="emsp-auth-editorial-aside emsp-auth-recover-aside d-none d-lg-flex" aria-label="Aide récupération">
                <span class="emsp-kicker emsp-auth-kicker emsp-auth-kicker--green">Compte EMSP Docs</span>
                <h2 class="emsp-auth-recover-aside__title font-heading">Récupération sécurisée</h2>
                <p class="emsp-auth-recover-aside__lead">
                    Un lien de réinitialisation vous est envoyé par email. Il reste valable 1 heure et ne peut être utilisé qu'une seule fois.
                </p>
                <ul class="emsp-auth-trust-list emsp-auth-trust-list--compact">
                    <li><i class="bi bi-envelope-check" aria-hidden="true"></i> Vérifiez vos spams si le message n'arrive pas</li>
                    <li><i class="bi bi-shield-lock-fill" aria-hidden="true"></i> Lien à usage unique, chiffré côté serveur</li>
                </ul>
            </aside>

            <div class="emsp-auth-recover-main">
                <div class="emsp-recover-surface">
                    <header class="emsp-recover-surface__head emsp-native-screen-header emsp-auth-page-header mb-0 pb-0 border-0">
                        <div class="emsp-native-screen-header__main emsp-auth-page-header__stack">
                            <span class="emsp-kicker emsp-auth-kicker emsp-auth-kicker--green d-lg-none">Compte EMSP Docs</span>
                            <?php if ($sent): ?>
                                <h1 class="emsp-native-screen-header__title h2 fw-bold font-heading mb-1">Email envoyé</h1>
                                <p class="emsp-native-screen-header__subtitle emsp-text-lead mb-0">
                                    Si un compte correspond, un lien vient d'être envoyé
                                    <?php if (!empty($emailMasked)): ?>
                                        à <strong><?= h($emailMasked) ?></strong>
                                    <?php endif; ?>.
                                    Le lien reste valable 1 heure. Vérifie aussi tes spams.
                                </p>
                            <?php else: ?>
                                <h1 class="emsp-native-screen-header__title h2 fw-bold font-heading mb-1">Mot de passe oublié</h1>
                                <p class="emsp-native-screen-header__subtitle emsp-text-lead mb-0">Indique l'email de ton compte pour recevoir un lien de réinitialisation sécurisé.</p>
                            <?php endif; ?>
                        </div>
                    </header>

                    <div class="emsp-recover-surface__body">
                        <?php if ($sent): ?>
                            <div class="d-flex flex-wrap gap-2 emsp-auth-form-actions">
                                <a class="emsp-btn emsp-btn-outline" href="<?= url('forgot-password') ?>">Renvoyer un lien</a>
                                <a class="emsp-btn emsp-btn-gold emsp-auth-submit" href="<?= url('login') ?>">
                                    Retour à la connexion
                                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <form action="<?= url('forgot-password') ?>" method="post" class="emsp-auth-form-stack">
                                <?= csrf_field() ?>
                                <div class="emsp-form-group mb-4">
                                    <label class="emsp-label" for="forgot-email">Adresse email</label>
                                    <div class="emsp-input-wrap emsp-input-wrap--icon">
                                        <i class="bi bi-envelope emsp-input-wrap__icon" aria-hidden="true"></i>
                                        <input id="forgot-email" type="email" name="email" class="emsp-input emsp-input--with-icon" required autocomplete="email"
                                               placeholder="votre.email@emsp.ci">
                                    </div>
                                </div>
                                <button type="submit" class="emsp-btn emsp-btn-gold emsp-auth-submit w-100">
                                    Envoyer le lien
                                    <i class="bi bi-send-fill" aria-hidden="true"></i>
                                </button>
                                <p class="emsp-auth-form-foot-note text-center mb-0">
                                    <a href="<?= url('login') ?>" class="emsp-auth-link">Revenir à la connexion</a>
                                </p>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
