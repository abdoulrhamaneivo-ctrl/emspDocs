<section class="emsp-section emsp-auth-page py-5">
    <div class="emsp-container">
        <div class="emsp-auth-layout">
            <div class="emsp-auth-editorial">
                <span class="emsp-auth-kicker emsp-kicker">EMSP Docs</span>
                <h1 class="emsp-auth-editorial-title">La bibliothèque académique de l'EMSP</h1>
                <p class="emsp-auth-editorial-lead">
                    Cours, travaux dirigés et annales vérifiés par l'École Multinationale Supérieure des Postes.
                </p>
                <ul class="emsp-auth-benefits" aria-label="Avantages">
                    <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Ressources validées par filière</li>
                    <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Concours et annales centralisés</li>
                    <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Espace personnel sécurisé</li>
                </ul>
            </div>

            <div class="emsp-auth-form emsp-auth-form-card">
                <h2 class="emsp-auth-form-title">Connexion</h2>
                <p class="emsp-auth-form-sub">Entrez vos identifiants pour accéder à votre espace.</p>

                <form action="<?= url('login') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="emsp-form-group mb-3">
                        <label class="emsp-label" for="login-email">Adresse email</label>
                        <input id="login-email" type="email" name="email" class="emsp-input" required autocomplete="email"
                               placeholder="votre.email@emsp.ci"
                               value="<?= h($old['email'] ?? '') ?>">
                    </div>
                    <div class="emsp-form-group mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="emsp-label mb-0" for="login-password">Mot de passe</label>
                            <a href="<?= url('forgot-password') ?>" class="emsp-auth-link text-xs">Oublié ?</a>
                        </div>
                        <input id="login-password" type="password" name="password" class="emsp-input" required autocomplete="current-password"
                               placeholder="••••••••">
                    </div>

                    <button type="submit" class="emsp-btn emsp-btn-primary w-100 py-3 mb-3">
                        Se connecter
                    </button>

                    <p class="text-center text-sm text-secondary mb-0">
                        Pas encore de compte ?
                        <a href="<?= url('register') ?>" class="fw-semibold emsp-auth-link ms-1">S'inscrire</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</section>
