<section class="section-pad emsp-dashboard-page">
<div class="container emsp-dashboard-container">

<div class="emsp-dashboard-shell emsp-native-screen">

    <header class="emsp-dashboard-hero emsp-animate-in">
        <div class="emsp-dashboard-hero__main">
            <?php if (!empty($badge_html)): ?>
                <div class="emsp-dashboard-hero__welcome" aria-label="Accueil personnalisé">
                    <span class="emsp-dashboard-hero__badge"><?= $badge_html ?></span>
                    <span class="emsp-dashboard-hero__greeting">Bonjour, <?= $prenom ?> !</span>
                </div>
            <?php else: ?>
                <div class="emsp-dashboard-hero__welcome" aria-label="Accueil personnalisé">
                    <span class="emsp-dashboard-hero__greeting">Bonjour, <?= $prenom ?> !</span>
                </div>
            <?php endif; ?>
            <span class="emsp-dashboard-kicker"><i class="bi bi-stars" aria-hidden="true"></i> Tableau de bord étudiant</span>
            <h1 class="emsp-dashboard-hero-title">Votre espace EMSP Docs, plus rapide et plus clair.</h1>
            <p class="emsp-dashboard-hero-copy">Gérez vos documents, surveillez vos validations et retrouvez vite vos actions utiles.</p>
        </div>
        <div class="emsp-dashboard-hero__shortcuts" aria-label="Raccourcis">
            <button type="button" class="emsp-dashboard-shortcut emsp-dashboard-shortcut--primary" data-emsp-notif-trigger data-emsp-notif-label="Notifications">
                <i class="bi bi-bell-fill" aria-hidden="true"></i>
                <span>Notifications</span>
                <strong class="emsp-dashboard-shortcut__count" data-emsp-notif-dashboard-count><?= (int) $nb_unread ?></strong>
            </button>
            <a href="<?= url('mon-profil') ?>" class="emsp-dashboard-shortcut emsp-dashboard-shortcut--outline">
                <i class="bi bi-person-circle" aria-hidden="true"></i>
                <span>Mon profil</span>
            </a>
            <a href="<?= url('upload') ?>?entry=scan#upload-drop-zone" class="emsp-dashboard-shortcut emsp-dashboard-shortcut--outline d-lg-none">
                <i class="bi bi-qr-code-scan" aria-hidden="true"></i>
                <span>Scanner</span>
            </a>
        </div>
    </header>

    <div class="emsp-mini-grid emsp-dashboard-kpis emsp-animate-in emsp-animate-in--delay-1" role="list" aria-label="Statistiques documents">
        <div class="emsp-mini-card emsp-kpi-card" role="listitem">
            <span class="emsp-kpi-card__icon emsp-kpi-card__icon--total" aria-hidden="true"><i class="bi bi-files"></i></span>
            <div class="emsp-kpi-card__body">
                <div class="value text-primary"><?= $stats['total'] ?></div>
                <div class="label">Documents déposés</div>
            </div>
        </div>
        <div class="emsp-mini-card emsp-kpi-card" role="listitem">
            <span class="emsp-kpi-card__icon emsp-kpi-card__icon--pending" aria-hidden="true"><i class="bi bi-hourglass-split"></i></span>
            <div class="emsp-kpi-card__body">
                <div class="value text-warning"><?= $stats['pending'] ?></div>
                <div class="label">En attente</div>
            </div>
        </div>
        <div class="emsp-mini-card emsp-kpi-card" role="listitem">
            <span class="emsp-kpi-card__icon emsp-kpi-card__icon--approved" aria-hidden="true"><i class="bi bi-check-circle"></i></span>
            <div class="emsp-kpi-card__body">
                <div class="value text-success"><?= $stats['approved'] ?></div>
                <div class="label">Approuvés</div>
            </div>
        </div>
        <div class="emsp-mini-card emsp-kpi-card" role="listitem">
            <span class="emsp-kpi-card__icon emsp-kpi-card__icon--rejected" aria-hidden="true"><i class="bi bi-x-circle"></i></span>
            <div class="emsp-kpi-card__body">
                <div class="value text-danger"><?= $stats['rejected'] ?></div>
                <div class="label">Rejetés</div>
            </div>
        </div>
    </div>

    <section class="emsp-dashboard-section emsp-animate-in emsp-animate-in--delay-2" aria-labelledby="dashboard-activity-heading">
        <header class="emsp-dashboard-section__head">
            <h2 class="emsp-dashboard-section__title" id="dashboard-activity-heading">
                <i class="bi bi-activity" aria-hidden="true"></i> Activité récente
            </h2>
        </header>
        <div class="emsp-dashboard-section__body">
            <?php if (empty($recent_activity)): ?>
                <div class="emsp-dashboard-empty">
                    <span class="emsp-dashboard-empty-icon" aria-hidden="true"><i class="bi bi-cloud-upload"></i></span>
                    <p class="emsp-dashboard-empty__title">Aucune activité récente</p>
                    <p class="emsp-dashboard-empty__copy">Déposez un premier document pour lancer votre historique et recevoir vos retours plus vite.</p>
                    <a href="<?= url('upload') ?>" class="btn btn-primary emsp-dashboard-btn">
                        <i class="bi bi-cloud-upload me-1" aria-hidden="true"></i>Déposer un document
                    </a>
                </div>
            <?php else: ?>
                <div class="emsp-dashboard-activity-list">
                    <?php foreach ($recent_activity as $activity): ?>
                        <?php
                        $isUpload = $activity['action_type'] === 'upload';
                        $iconClass = $isUpload ? 'bi-cloud-arrow-up-fill text-primary' : 'bi-star-fill text-warning';
                        $activityIconStateClass = $isUpload ? 'is-upload' : 'is-favorite';
                        $label = $isUpload ? 'Document déposé' : 'Ajouté aux favoris';
                        ?>
                        <div class="emsp-activity-item">
                            <div class="emsp-activity-item-icon <?= h($activityIconStateClass) ?>" aria-hidden="true">
                                <i class="bi <?= $iconClass ?>"></i>
                            </div>
                            <div class="emsp-activity-item__main">
                                <div class="emsp-activity-item__label"><?= htmlspecialchars($label) ?></div>
                                <div class="emsp-activity-item__meta"><?= htmlspecialchars($activity['title']) ?></div>
                            </div>
                            <time class="emsp-activity-item__time" datetime="<?= h($activity['created_at']) ?>">
                                <?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?>
                            </time>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="emsp-dashboard-section emsp-animate-in emsp-animate-in--delay-3" aria-labelledby="dashboard-docs-heading">
        <header class="emsp-dashboard-section__head">
            <h2 class="emsp-dashboard-section__title" id="dashboard-docs-heading">
                <i class="bi bi-files" aria-hidden="true"></i> Mes documents
            </h2>
            <?php if (count($mes_docs) > 0): ?>
                <a href="<?= url('upload') ?>" class="btn btn-primary emsp-dashboard-btn">
                    <i class="bi bi-cloud-upload me-1" aria-hidden="true"></i>Déposer
                </a>
            <?php endif; ?>
        </header>
        <div class="emsp-dashboard-section__body">
            <?php if (count($mes_docs) === 0): ?>
                <div class="emsp-dashboard-empty">
                    <span class="emsp-dashboard-empty-icon" aria-hidden="true"><i class="bi bi-inbox"></i></span>
                    <p class="emsp-dashboard-empty__title">Aucun document déposé</p>
                    <p class="emsp-dashboard-empty__copy">Commencez par déposer votre premier document pour le soumettre à validation.</p>
                    <a href="<?= url('upload') ?>" class="btn btn-primary emsp-dashboard-btn">
                        <i class="bi bi-cloud-upload me-1" aria-hidden="true"></i>Déposer mon premier document
                    </a>
                </div>
            <?php else: ?>
                <div class="emsp-dashboard-doc-list">
                <?php foreach ($mes_docs as $d): ?>

                    <?php
                    $status_class = [
                        'pending'  => 'border-warning',
                        'approved' => 'border-success',
                        'rejected' => 'border-danger',
                    ][$d['status']] ?? '';
                    $status_label = [
                        'pending'  => '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>En attente de validation</span>',
                        'approved' => '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Approuvé</span>',
                        'rejected' => '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Rejeté</span>',
                    ][$d['status']] ?? '';
                    ?>

                    <article class="emsp-doc-row <?= $status_class ?>">
                        <div class="emsp-doc-row__inner">
                            <div class="emsp-doc-row__content">
                                <div class="emsp-doc-row__badges">
                                    <span class="badge <?= $type_colors[$d['doc_type']] ?? 'bg-secondary' ?>">
                                        <?= ucfirst($d['doc_type']) ?>
                                    </span>
                                    <?= $status_label ?>
                                    <span class="text-muted small">
                                        <?= date('d/m/Y', strtotime($d['created_at'])) ?>
                                    </span>
                                </div>
                                <h3 class="emsp-doc-row__title">
                                    <?php if ($d['status'] === 'approved'): ?>
                                        <a href="<?= url('document') ?>?id=<?= $d['id'] ?>" class="text-decoration-none text-dark">
                                            <?= htmlspecialchars($d['title']) ?>
                                        </a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($d['title']) ?>
                                    <?php endif; ?>
                                </h3>

                                <?php if ($d['status'] === 'pending'): ?>
                                    <p class="emsp-doc-row__note text-warning-emphasis">
                                        <i class="bi bi-clock me-1" aria-hidden="true"></i>
                                        Votre document est en cours de vérification par un modérateur. Vous serez notifié(e) dès qu'une décision sera prise.
                                    </p>
                                <?php elseif ($d['status'] === 'rejected' && $d['rejection_reason']): ?>
                                    <div class="alert alert-danger py-1 px-2 mb-0 small mt-1">
                                        <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
                                        <strong>Motif du rejet :</strong> <?= htmlspecialchars($d['rejection_reason']) ?>
                                    </div>
                                <?php elseif ($d['status'] === 'approved'): ?>
                                    <div class="emsp-doc-row__stats">
                                        <span><i class="bi bi-download me-1" aria-hidden="true"></i><?= $d['download_count'] ?> télécharg.</span>
                                        <span><i class="bi bi-heart me-1" aria-hidden="true"></i><?= $d['like_count'] ?> likes</span>
                                        <span><i class="bi bi-chat me-1" aria-hidden="true"></i><?= $d['nb_comments'] ?> commentaires</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($d['status'] === 'approved'): ?>
                            <a href="<?= url('document') ?>?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-primary emsp-dashboard-btn emsp-dashboard-btn--sm">
                                <i class="bi bi-eye me-1" aria-hidden="true"></i>Voir
                            </a>
                            <?php endif; ?>
                        </div>
                    </article>

                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

</div><!-- /dashboard-shell -->
</div>
</section>
