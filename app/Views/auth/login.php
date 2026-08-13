<section class="emsp-section emsp-page-shell emsp-editorial-page emsp-auth-editorial-page emsp-auth-login-page emsp-login-premium emsp-auth-mesh-page py-4 py-md-5">
    <div class="emsp-container emsp-container-narrow emsp-auth-desktop-container">
        <div class="emsp-auth-desktop-grid emsp-auth-login-layout">
            <aside class="emsp-auth-editorial-aside emsp-auth-login-aside d-none d-lg-block" aria-label="Vie du campus EMSP">
                <div class="emsp-register-aside-mesh" aria-hidden="true"></div>
                <div class="emsp-register-aside-dots" aria-hidden="true"></div>
                <?php require dirname(__DIR__, 3) . '/includes/partials/auth-aside-carousel.php'; ?>
            </aside>

            <div class="emsp-auth-login-main">
                <div class="emsp-auth-aside-carousel-mobile d-lg-none mb-3">
                    <?php $carouselModifier = 'emsp-auth-aside-carousel--compact'; require dirname(__DIR__, 3) . '/includes/partials/auth-aside-carousel.php'; unset($carouselModifier); ?>
                </div>

                <div class="emsp-login-surface">
                    <header class="emsp-login-surface__head emsp-native-screen-header emsp-auth-page-header mb-0 pb-0 border-0">
                        <div class="emsp-native-screen-header__main emsp-auth-page-header__stack">
                            <span class="emsp-auth-kicker emsp-auth-kicker--green">Connexion EMSP Docs</span>
                            <h1 class="emsp-login-surface__title emsp-native-screen-header__title h2 fw-bold font-heading mb-1">Connectez-vous</h1>
                            <p class="emsp-login-surface__subtitle emsp-native-screen-header__subtitle emsp-text-lead mb-0">
                                Entrez vos identifiants pour accéder à votre espace, consulter les ressources et gérer vos dépôts.
                            </p>
                        </div>
                    </header>

                    <div class="emsp-login-surface__body">
                        <form action="<?= url('login') ?>" method="post" class="emsp-auth-form-stack">
                            <?= csrf_field() ?>
                            <div class="emsp-form-group mb-3">
                                <label class="emsp-label" for="login-email">Adresse email</label>
                                <div class="emsp-input-wrap emsp-input-wrap--icon">
                                    <i class="bi bi-envelope emsp-input-wrap__icon" aria-hidden="true"></i>
                                    <input id="login-email" type="email" name="email" class="emsp-input emsp-input--with-icon" required autocomplete="email"
                                           placeholder="votre.email@emsp.ci"
                                           value="<?= h($old['email'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="emsp-form-group mb-4">
                                <div class="emsp-auth-form-row">
                                    <label class="emsp-label mb-0" for="login-password">Mot de passe</label>
                                    <a href="<?= url('forgot-password') ?>" class="emsp-auth-link text-xs">Oublié ?</a>
                                </div>
                                <div class="emsp-input-wrap emsp-input-wrap--icon">
                                    <i class="bi bi-lock emsp-input-wrap__icon" aria-hidden="true"></i>
                                    <input id="login-password" type="password" name="password" class="emsp-input emsp-input--with-icon" required autocomplete="current-password"
                                           placeholder="••••••••">
                                </div>
                            </div>

                            <button type="submit" class="emsp-btn emsp-btn-gold emsp-auth-submit w-100">
                                Se connecter
                                <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            </button>

                            <p class="emsp-auth-form-foot-note text-center mb-0">
                                Pas encore de compte ?
                                <a href="<?= url('register') ?>" class="emsp-auth-link fw-semibold">S'inscrire</a>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php ob_start(); ?>
<script src="<?= asset('js/emsp-auth-aside-carousel.js') ?>?v=<?= h(asset_version()) ?>"></script>
<?php $page_scripts = ob_get_clean(); ?>
