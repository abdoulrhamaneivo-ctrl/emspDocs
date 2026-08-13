<?php
$emailConfigured = $emailConfigured ?? ($mailConfigured ?? true);
$emailSendFailed = $emailSendFailed ?? ($verificationEmailFailed ?? false);
$cooldownRemaining = (int) ($cooldownRemaining ?? ($resendCooldown ?? 0));
$userEmail = (string) ($userEmail ?? ($user['email'] ?? ''));
$accountStatus = (string) ($accountStatus ?? 'pending_email');

if ($accountStatus === 'pending_email' && !empty($emailConfirmed)) {
    $accountStatus = ($status ?? '') === 'rejected' ? 'rejected' : 'pending_admin';
}

$step1State = $emailConfirmed ? 'is-complete' : 'is-active';
$step2State = !$emailConfirmed ? '' : (($status === 'rejected') ? 'is-rejected' : 'is-active');
$step3State = ($emailConfirmed && $status !== 'rejected') ? '' : '';

$heroIcon = 'hourglass-split';
$heroTone = 'waiting';
$heroTitle = 'Demande en cours d\'examen';
$heroLead = 'Votre adresse est validée. L\'administration EMSP examine votre dossier.';

if ($accountStatus === 'pending_email') {
    $heroIcon = 'envelope-exclamation';
    $heroTone = 'email';
    $heroTitle = 'Confirmez votre email';
    $heroLead = 'Un message de confirmation vous attend. Ouvrez-le sur cet appareil pour poursuivre.';
} elseif ($accountStatus === 'rejected') {
    $heroIcon = 'x-circle';
    $heroTone = 'rejected';
    $heroTitle = 'Inscription non validée';
    $heroLead = 'Votre demande n\'a pas été acceptée par l\'administration EMSP.';
}

$verificationEmailSent = $verificationEmailSent ?? false;
$lastResendAt = $lastResendAt ?? null;
$shouldPoll = $accountStatus === 'pending_email';
?>
<section
    class="emsp-section emsp-pending-app emsp-pending-native emsp-pending-v3 section-pad"
    data-emsp-pending-status
    data-poll="<?= $shouldPoll ? '1' : '0' ?>"
    data-poll-url="<?= h(url('pending-status')) ?>"
    data-cooldown="<?= h((string) max(0, $cooldownRemaining)) ?>"
>
    <div class="emsp-container emsp-container-narrow emsp-pending-native-shell">
        <header class="emsp-pending-surface-header d-md-none" aria-label="En-tête suivi du compte">
            <a href="<?= url('login') ?>" class="emsp-pending-surface-header__back" aria-label="Retour à la connexion">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
            </a>
            <div class="emsp-pending-surface-header__main">
                <h1 class="emsp-pending-surface-header__title">Compte en attente</h1>
            </div>
            <button type="button" class="emsp-pending-surface-header__refresh emsp-pending-refresh-btn" aria-label="Actualiser le statut">
                <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
            </button>
        </header>

        <div class="emsp-pending-surface">
            <header class="emsp-pending-surface__intro d-none d-md-block">
                <span class="emsp-auth-kicker emsp-auth-kicker--green">EMSP Docs · suivi du compte</span>
                <h1 class="emsp-pending-surface__title">Où en est votre inscription&nbsp;?</h1>
                <p class="emsp-pending-surface__lead">
                    Confirmation email, validation administrateur, puis accès à la bibliothèque.
                </p>
            </header>

            <div class="emsp-pending-surface__hero emsp-pending-surface__hero--<?= h($heroTone) ?>" role="status" aria-live="polite">
                <div class="emsp-pending-surface__hero-visual" aria-hidden="true">
                    <span class="emsp-pending-surface__hero-pulse"></span>
                    <span class="emsp-pending-surface__hero-icon">
                        <i class="bi bi-<?= h($heroIcon) ?>"></i>
                    </span>
                </div>
                <h2 class="emsp-pending-surface__hero-title"><?= h($heroTitle) ?></h2>
                <p class="emsp-pending-surface__hero-lead"><?= h($heroLead) ?></p>
                <?php if ($accountStatus === 'pending_admin'): ?>
                    <p class="emsp-pending-surface__hero-meta">
                        Délai habituel&nbsp;: <strong>24&nbsp;heures ouvrables</strong>
                    </p>
                <?php endif; ?>
            </div>

            <nav class="emsp-pending-stepper" aria-label="Progression de l'inscription">
                <ol class="emsp-pending-stepper__list">
                    <li class="emsp-pending-stepper__item <?= h($step1State) ?>">
                        <span class="emsp-pending-stepper__node" aria-hidden="true">
                            <?php if ($emailConfirmed): ?>
                                <i class="bi bi-check-lg"></i>
                            <?php else: ?>
                                <span class="emsp-pending-stepper__num">1</span>
                            <?php endif; ?>
                        </span>
                        <span class="emsp-pending-stepper__label">Email</span>
                    </li>
                    <li class="emsp-pending-stepper__item <?= h($step2State) ?>">
                        <span class="emsp-pending-stepper__node" aria-hidden="true">
                            <?php if ($status === 'rejected'): ?>
                                <i class="bi bi-x-lg"></i>
                            <?php else: ?>
                                <span class="emsp-pending-stepper__num">2</span>
                            <?php endif; ?>
                        </span>
                        <span class="emsp-pending-stepper__label">Validation</span>
                    </li>
                    <li class="emsp-pending-stepper__item <?= h($step3State) ?>">
                        <span class="emsp-pending-stepper__node" aria-hidden="true">
                            <span class="emsp-pending-stepper__num">3</span>
                        </span>
                        <span class="emsp-pending-stepper__label">Accès</span>
                    </li>
                </ol>
            </nav>

            <div class="emsp-pending-surface__body">
                <?php if ($accountStatus === 'pending_email'): ?>
                    <?php if (!$emailConfigured): ?>
                        <div class="emsp-pending-callout emsp-pending-callout--warning" role="alert">
                            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                            <div>
                                <strong>Service email non configuré</strong>
                                <p>
                                    La clé API Brevo (<code>BREVO_API_KEY</code>) ou l'expéditeur
                                    (<code>BREVO_FROM_EMAIL</code>) n'est pas défini sur ce serveur.
                                    Contactez l'administrateur EMSP Docs ou réessayez plus tard.
                                </p>
                            </div>
                        </div>
                    <?php elseif ($emailSendFailed && !$verificationEmailSent): ?>
                        <div class="emsp-pending-callout emsp-pending-callout--danger" role="alert">
                            <i class="bi bi-envelope-x-fill" aria-hidden="true"></i>
                            <div>
                                <strong>L'email de confirmation n'a pas pu être envoyé</strong>
                                <p>Utilisez le bouton ci-dessous pour renvoyer un nouveau lien de vérification.</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($userEmail !== ''): ?>
                        <div class="emsp-pending-email-pill" aria-label="Adresse email concernée">
                            <i class="bi bi-envelope-at" aria-hidden="true"></i>
                            <span><?= h($userEmail) ?></span>
                        </div>
                    <?php endif; ?>

                    <ul class="emsp-pending-steps-list">
                        <li class="emsp-pending-step">
                            <span class="emsp-pending-step__icon" aria-hidden="true"><i class="bi bi-envelope-check"></i></span>
                            <div class="emsp-pending-step__body">
                                <strong class="emsp-pending-step__title">Ouvrez votre boîte mail</strong>
                                <p class="emsp-pending-step__desc">Cherchez un message EMSP Docs — objet «&nbsp;Vérifiez votre email&nbsp;».</p>
                            </div>
                        </li>
                        <li class="emsp-pending-step">
                            <span class="emsp-pending-step__icon" aria-hidden="true"><i class="bi bi-send-fill"></i></span>
                            <div class="emsp-pending-step__body">
                                <strong class="emsp-pending-step__title">Cliquez sur «&nbsp;Confirmer mon email&nbsp;»</strong>
                                <p class="emsp-pending-step__desc">Vous serez redirigé ici et cette page se mettra à jour automatiquement.</p>
                            </div>
                        </li>
                        <li class="emsp-pending-step">
                            <span class="emsp-pending-step__icon" aria-hidden="true"><i class="bi bi-folder2-open"></i></span>
                            <div class="emsp-pending-step__body">
                                <strong class="emsp-pending-step__title">Vérifiez les indésirables</strong>
                                <p class="emsp-pending-step__desc">Le message peut arriver dans Spam, Promotions ou Courrier indésirable.</p>
                            </div>
                        </li>
                    </ul>

                    <?php if ($lastResendAt): ?>
                        <p class="emsp-pending-resend-meta">
                            Dernier renvoi&nbsp;: <?= h(date('d/m/Y à H:i', (int) $lastResendAt)) ?>
                        </p>
                    <?php endif; ?>

                    <footer class="emsp-pending-actions">
                        <?php if ($cooldownRemaining > 0): ?>
                            <button type="button" class="emsp-btn emsp-btn-gold emsp-pending-cta emsp-pending-resend-btn w-100" disabled data-resend-form="pending-resend-form">
                                <i class="bi bi-envelope-arrow-up me-2" aria-hidden="true"></i>
                                <span data-resend-label>Renvoyer dans <?= h((string) max(1, (int) ceil($cooldownRemaining / 60))) ?> min</span>
                            </button>
                            <form method="post" action="<?= url('resend-verification') ?>" id="pending-resend-form" class="visually-hidden">
                                <?= csrf_field() ?>
                            </form>
                        <?php else: ?>
                            <form method="post" action="<?= url('resend-verification') ?>" id="pending-resend-form" class="m-0 w-100">
                                <?= csrf_field() ?>
                                <button type="submit" class="emsp-btn emsp-btn-gold emsp-pending-cta emsp-pending-resend-btn w-100"<?= !$emailConfigured ? ' disabled' : '' ?>>
                                    <i class="bi bi-envelope-arrow-up me-2" aria-hidden="true"></i>Renvoyer l'email
                                </button>
                            </form>
                        <?php endif; ?>
                        <button type="button" class="emsp-btn emsp-btn-outline emsp-pending-cta emsp-pending-refresh-btn w-100">
                            <i class="bi bi-arrow-clockwise me-2" aria-hidden="true"></i>Actualiser
                        </button>
                        <p class="emsp-pending-actions__note mb-0">
                            Le lien précédent devient obsolète dès qu'un nouveau message est généré.
                        </p>
                    </footer>

                <?php elseif ($accountStatus === 'rejected'): ?>
                    <div class="emsp-pending-callout emsp-pending-callout--danger" role="alert">
                        <i class="bi bi-x-circle-fill" aria-hidden="true"></i>
                        <div>
                            <strong>Votre compte ne peut pas être activé</strong>
                            <p>Consultez le motif ci-dessous ou contactez l'administration EMSP Docs.</p>
                        </div>
                    </div>

                    <?php if (!empty($user['rejection_reason'])): ?>
                        <div class="emsp-pending-reason">
                            <strong>Motif communiqué</strong>
                            <p><?= h((string) $user['rejection_reason']) ?></p>
                        </div>
                    <?php endif; ?>

                    <ul class="emsp-pending-steps-list">
                        <li class="emsp-pending-step">
                            <span class="emsp-pending-step__icon" aria-hidden="true"><i class="bi bi-headset"></i></span>
                            <div class="emsp-pending-step__body">
                                <strong class="emsp-pending-step__title">Contacter l'administration</strong>
                                <p class="emsp-pending-step__desc">Écrivez à l'équipe EMSP Docs si vous pensez qu'il s'agit d'une erreur.</p>
                            </div>
                        </li>
                    </ul>

                    <footer class="emsp-pending-actions">
                        <a href="<?= url('register') ?>" class="emsp-btn emsp-btn-gold emsp-pending-cta w-100">
                            <i class="bi bi-arrow-repeat me-2" aria-hidden="true"></i>Nouvelle inscription
                        </a>
                        <button type="button" class="emsp-btn emsp-btn-outline emsp-pending-cta emsp-pending-refresh-btn w-100">
                            <i class="bi bi-arrow-clockwise me-2" aria-hidden="true"></i>Actualiser
                        </button>
                    </footer>

                <?php else: ?>
                    <ul class="emsp-pending-steps-list">
                        <li class="emsp-pending-step">
                            <span class="emsp-pending-step__icon" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
                            <div class="emsp-pending-step__body">
                                <strong class="emsp-pending-step__title">Adresse email confirmée</strong>
                                <p class="emsp-pending-step__desc">Votre compte est identifié et sécurisé.</p>
                            </div>
                        </li>
                        <li class="emsp-pending-step">
                            <span class="emsp-pending-step__icon" aria-hidden="true"><i class="bi bi-hourglass-split"></i></span>
                            <div class="emsp-pending-step__body">
                                <strong class="emsp-pending-step__title">Validation administrateur en attente</strong>
                                <p class="emsp-pending-step__desc">Un administrateur EMSP examine votre dossier (filière, niveau, pièces jointes).</p>
                            </div>
                        </li>
                        <li class="emsp-pending-step">
                            <span class="emsp-pending-step__icon" aria-hidden="true"><i class="bi bi-bell"></i></span>
                            <div class="emsp-pending-step__body">
                                <strong class="emsp-pending-step__title">Notification finale par email</strong>
                                <p class="emsp-pending-step__desc">Vous recevrez un message dès que votre accès sera ouvert.</p>
                            </div>
                        </li>
                    </ul>

                    <footer class="emsp-pending-actions">
                        <button type="button" class="emsp-btn emsp-btn-outline emsp-pending-cta emsp-pending-refresh-btn w-100">
                            <i class="bi bi-arrow-clockwise me-2" aria-hidden="true"></i>Actualiser le statut
                        </button>
                        <a href="<?= url('login') ?>" class="emsp-btn emsp-btn-gold emsp-pending-cta w-100">
                            <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>Se connecter
                        </a>
                        <span class="emsp-pending-date">
                            Demande du <?= date('d/m/Y à H:i', strtotime((string) ($user['created_at'] ?? 'now'))) ?>
                        </span>
                    </footer>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
