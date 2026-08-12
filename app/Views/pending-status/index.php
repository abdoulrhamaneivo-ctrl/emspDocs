<?php
$step1State = $emailConfirmed ? 'is-done' : 'is-active';
$step2State = !$emailConfirmed ? 'is-upcoming' : (($status === 'rejected') ? 'is-rejected' : 'is-active');
$step3State = (!$emailConfirmed || $status === 'rejected') ? 'is-upcoming' : 'is-upcoming';
?>
<section class="emsp-section emsp-pending-v2 section-pad">
    <div class="emsp-container emsp-container-narrow">
        <header class="emsp-pending-v2-hero">
            <span class="emsp-pending-v2-kicker">EMSP Docs · suivi du compte</span>
            <h1 class="emsp-pending-v2-title">Où en est votre inscription&nbsp;?</h1>
            <p class="emsp-pending-v2-lead">
                Un parcours clair en trois étapes — confirmation email, validation administrateur, accès à la bibliothèque.
            </p>
        </header>

        <nav class="emsp-pending-v2-track" aria-label="Progression de l'inscription">
            <ol class="emsp-pending-v2-steps">
                <li class="emsp-pending-v2-step <?= h($step1State) ?>">
                    <span class="emsp-pending-v2-step-dot" aria-hidden="true">
                        <?php if ($emailConfirmed): ?><i class="bi bi-check-lg"></i><?php else: ?>1<?php endif; ?>
                    </span>
                    <span class="emsp-pending-v2-step-label">Email</span>
                </li>
                <li class="emsp-pending-v2-step <?= h($step2State) ?>">
                    <span class="emsp-pending-v2-step-dot" aria-hidden="true">
                        <?php if ($emailConfirmed && $status !== 'rejected'): ?>2<?php elseif ($status === 'rejected'): ?><i class="bi bi-x-lg"></i><?php else: ?>2<?php endif; ?>
                    </span>
                    <span class="emsp-pending-v2-step-label">Validation</span>
                </li>
                <li class="emsp-pending-v2-step <?= h($step3State) ?>">
                    <span class="emsp-pending-v2-step-dot" aria-hidden="true">3</span>
                    <span class="emsp-pending-v2-step-label">Accès</span>
                </li>
            </ol>
        </nav>

        <article class="emsp-pending-v2-sheet">
            <?php if (!$emailConfirmed): ?>
                <header class="emsp-pending-v2-head">
                    <span class="emsp-pending-v2-badge emsp-pending-v2-badge--gold">
                        <i class="bi bi-envelope-exclamation" aria-hidden="true"></i>
                        Email à confirmer
                    </span>
                    <h2 class="emsp-pending-v2-sheet-title">Confirmez votre adresse email</h2>
                    <p class="emsp-pending-v2-sheet-lead">
                        Un message vient d'être envoyé à
                        <strong><?= h((string) ($user['email'] ?? '')) ?></strong>.
                        Ouvrez-le sur cet appareil pour poursuivre.
                    </p>
                </header>

                <ul class="emsp-pending-v2-checklist">
                    <li class="emsp-pending-v2-checklist-item is-current">
                        <span class="emsp-pending-v2-checklist-icon"><i class="bi bi-envelope-open" aria-hidden="true"></i></span>
                        <div>
                            <strong>Ouvrez votre boîte mail</strong>
                            <span>Cherchez un message EMSP Docs — objet «&nbsp;Vérifiez votre email&nbsp;».</span>
                        </div>
                    </li>
                    <li class="emsp-pending-v2-checklist-item">
                        <span class="emsp-pending-v2-checklist-icon"><i class="bi bi-cursor-fill" aria-hidden="true"></i></span>
                        <div>
                            <strong>Cliquez sur «&nbsp;Confirmer mon email&nbsp;»</strong>
                            <span>Vous serez redirigé ici et cette page se mettra à jour.</span>
                        </div>
                    </li>
                    <li class="emsp-pending-v2-checklist-item is-tip">
                        <span class="emsp-pending-v2-checklist-icon"><i class="bi bi-folder2-open" aria-hidden="true"></i></span>
                        <div>
                            <strong>Vérifiez les indésirables</strong>
                            <span>Le message peut arriver dans Spam, Promotions ou Courrier indésirable.</span>
                        </div>
                    </li>
                </ul>

                <footer class="emsp-pending-v2-foot">
                    <div class="emsp-pending-v2-foot-copy">
                        <strong>Besoin d'un nouvel email&nbsp;?</strong>
                        <span>Le lien précédent devient obsolète dès qu'un nouveau message est généré.</span>
                    </div>
                    <form method="post" action="<?= url('resend-verification') ?>" class="m-0">
                        <?= csrf_field() ?>
                        <button type="submit" class="emsp-btn emsp-pending-v2-btn">
                            <i class="bi bi-envelope-arrow-up me-2" aria-hidden="true"></i>Renvoyer l'email
                        </button>
                    </form>
                </footer>

            <?php elseif ($status === 'rejected'): ?>
                <header class="emsp-pending-v2-head">
                    <span class="emsp-pending-v2-badge emsp-pending-v2-badge--danger">
                        <i class="bi bi-x-circle" aria-hidden="true"></i>
                        Demande refusée
                    </span>
                    <h2 class="emsp-pending-v2-sheet-title">Inscription non validée</h2>
                    <p class="emsp-pending-v2-sheet-lead">
                        Votre demande n'a pas été acceptée par l'administration EMSP.
                    </p>
                </header>

                <div class="emsp-pending-v2-alert" role="alert">
                    <i class="bi bi-x-circle-fill" aria-hidden="true"></i>
                    Votre compte ne peut pas être activé dans son état actuel.
                </div>

                <?php if (!empty($user['rejection_reason'])): ?>
                    <div class="emsp-pending-v2-reason">
                        <strong>Motif communiqué</strong>
                        <p><?= h((string) $user['rejection_reason']) ?></p>
                    </div>
                <?php endif; ?>

                <footer class="emsp-pending-v2-foot">
                    <div class="emsp-pending-v2-foot-copy">
                        <strong>Que faire ensuite&nbsp;?</strong>
                        <span>Contactez l'administration ou recommencez une inscription avec des informations exactes.</span>
                    </div>
                    <a href="<?= url('register') ?>" class="emsp-btn emsp-pending-v2-btn">
                        <i class="bi bi-arrow-repeat me-2" aria-hidden="true"></i>Nouvelle inscription
                    </a>
                </footer>

            <?php else: ?>
                <header class="emsp-pending-v2-head">
                    <span class="emsp-pending-v2-badge emsp-pending-v2-badge--ok">
                        <i class="bi bi-check-circle" aria-hidden="true"></i>
                        Email confirmé
                    </span>
                    <h2 class="emsp-pending-v2-sheet-title">Demande en cours d'examen</h2>
                    <p class="emsp-pending-v2-sheet-lead">
                        Bonjour <strong><?= h($firstName !== '' ? $firstName : 'étudiant') ?></strong>,
                        votre adresse est validée. Il reste la validation administrateur.
                    </p>
                </header>

                <ul class="emsp-pending-v2-checklist">
                    <li class="emsp-pending-v2-checklist-item is-done">
                        <span class="emsp-pending-v2-checklist-icon"><i class="bi bi-check-lg" aria-hidden="true"></i></span>
                        <div>
                            <strong>Adresse email confirmée</strong>
                            <span>Votre compte est identifié et sécurisé.</span>
                        </div>
                    </li>
                    <li class="emsp-pending-v2-checklist-item is-current">
                        <span class="emsp-pending-v2-checklist-icon"><i class="bi bi-hourglass-split" aria-hidden="true"></i></span>
                        <div>
                            <strong>Validation administrateur en attente</strong>
                            <span>Un administrateur EMSP examine votre dossier (filière, niveau, pièces jointes).</span>
                        </div>
                    </li>
                    <li class="emsp-pending-v2-checklist-item">
                        <span class="emsp-pending-v2-checklist-icon"><i class="bi bi-bell" aria-hidden="true"></i></span>
                        <div>
                            <strong>Notification finale par email</strong>
                            <span>Vous recevrez un message dès que votre accès sera ouvert.</span>
                        </div>
                    </li>
                </ul>

                <footer class="emsp-pending-v2-foot">
                    <div class="emsp-pending-v2-foot-copy">
                        <strong>Délai habituel</strong>
                        <span>
                            Comptez généralement <strong>24 à 48&nbsp;heures ouvrables</strong>.
                            Pensez à vérifier vos spams pour l'email d'activation.
                        </span>
                    </div>
                    <span class="emsp-pending-v2-date">
                        Demande du <?= date('d/m/Y à H:i', strtotime((string) ($user['created_at'] ?? 'now'))) ?>
                    </span>
                </footer>
            <?php endif; ?>
        </article>
    </div>
</section>
