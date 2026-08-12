<section class="emsp-section emsp-auth-page py-5">
    <div class="emsp-container emsp-container-narrow">
        <div class="emsp-auth-form emsp-auth-form-card mx-auto">
            <h1 class="emsp-auth-form-title">Nouveau mot de passe</h1>
            <p class="emsp-auth-form-sub">Choisis un mot de passe sécurisé pour ton compte EMSP Docs.</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger mb-4"><?= h($error) ?></div>
            <?php endif; ?>

            <form action="<?= url('reset-password') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= h($token) ?>">
                <div class="emsp-form-group mb-3">
                    <label class="emsp-label">Nouveau mot de passe</label>
                    <input type="password" name="password" class="emsp-input" minlength="8" required autocomplete="new-password"
                           placeholder="••••••••">
                </div>
                <div class="emsp-form-group mb-4">
                    <label class="emsp-label">Confirmer le mot de passe</label>
                    <input type="password" name="password_confirm" class="emsp-input" minlength="8" required autocomplete="new-password"
                           placeholder="••••••••">
                </div>
                <button type="submit" class="emsp-btn emsp-btn-primary w-100 py-3 mb-3">Mettre à jour le mot de passe</button>
                <p class="text-center text-sm text-secondary mb-0">
                    <a href="<?= url('login') ?>" class="text-primary text-decoration-none">Retour à la connexion</a>
                </p>
            </form>
        </div>
    </div>
</section>
