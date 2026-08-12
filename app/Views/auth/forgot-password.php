<section class="emsp-section emsp-auth-page py-5">
    <div class="emsp-container emsp-container-narrow">
        <div class="emsp-auth-form emsp-auth-form-card mx-auto">
            <?php if ($sent): ?>
                <h1 class="emsp-auth-form-title">Email envoyé</h1>
                <p class="emsp-auth-form-sub mb-4">
                    Si un compte correspond, un lien vient d'être envoyé
                    <?php if (!empty($emailMasked)): ?>
                        à <strong><?= h($emailMasked) ?></strong>
                    <?php endif; ?>.
                    Le lien reste valable 1 heure. Vérifie aussi tes spams.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a class="emsp-btn emsp-btn-outline" href="<?= url('forgot-password') ?>">Renvoyer un lien</a>
                    <a class="emsp-btn emsp-btn-primary" href="<?= url('login') ?>">Retour à la connexion</a>
                </div>
            <?php else: ?>
                <h1 class="emsp-auth-form-title">Mot de passe oublié</h1>
                <p class="emsp-auth-form-sub">Indique l'email de ton compte pour recevoir un lien de réinitialisation.</p>
                <form action="<?= url('forgot-password') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="emsp-form-group mb-4">
                        <label class="emsp-label">Adresse email</label>
                        <input type="email" name="email" class="emsp-input" required autocomplete="email"
                               placeholder="votre.email@emsp.ci">
                    </div>
                    <button type="submit" class="emsp-btn emsp-btn-primary w-100 py-3 mb-3">Envoyer le lien</button>
                    <p class="text-center text-sm text-secondary mb-0">
                        <a href="<?= url('login') ?>" class="text-primary text-decoration-none">Revenir à la connexion</a>
                    </p>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>
