<section class="page-header">
    <div class="container">
        <h1 class="mb-1">
            <?= $badge_html ?>
            Bonjour, <?= $prenom ?> !
        </h1>
        <p class="mb-0 text-white-50 emsp-dashboard-subtitle">Gérez vos documents, surveillez vos validations et retrouvez vite vos actions utiles.</p>
    </div>
</section>

<section class="section-pad">
<div class="container">
<?php if (!empty($_SESSION['message'])): ?>
    <div class="alert alert-info alert-dismissible fade show">
        <?= h($_SESSION['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>

<div class="emsp-dashboard-shell emsp-native-screen">
    <div class="emsp-dashboard-hero">
        <div class="emsp-dashboard-hero-card">
            <span class="emsp-dashboard-kicker"><i class="bi bi-stars"></i> Tableau de bord etudiant</span>
            <h2 class="emsp-dashboard-hero-title">Votre espace EMSP Docs, plus rapide et plus clair.</h2>
            <p class="emsp-dashboard-hero-copy">Suivez vos documents, vos validations et vos notifications depuis un espace mobile plus simple a utiliser.</p>
            <div class="emsp-dashboard-cta-grid d-lg-none">
                <a href="<?= url('upload') ?>?entry=scan#upload-drop-zone" class="emsp-dashboard-cta emsp-dashboard-cta--secondary">
                    <span><i class="bi bi-qr-code-scan me-2"></i>Scanner / capture</span>
                    <i class="bi bi-arrow-up-right"></i>
                </a>
            </div>
        </div>
        <div class="emsp-dashboard-cta-card">
            <span class="emsp-dashboard-kicker"><i class="bi bi-bell-fill"></i> Raccourcis</span>
            <div class="emsp-dashboard-cta-grid mt-3">
                <a href="<?= url('dashboard') ?>#notifications" class="emsp-dashboard-cta emsp-dashboard-cta--primary">
                    <span><i class="bi bi-bell-fill me-2"></i>Notifications</span>
                    <strong data-emsp-notif-dashboard-count><?= (int) $nb_unread ?></strong>
                </a>
                <a href="<?= url('mon-profil') ?>" class="emsp-dashboard-cta emsp-dashboard-cta--secondary">
                    <span><i class="bi bi-person-circle me-2"></i>Mon profil</span>
                    <i class="bi bi-chevron-right"></i>
                </a>
            </div>
        </div>
    </div>

<!-- Cartes stats -->
<div class="emsp-mini-grid emsp-dashboard-kpis mb-4">
    <div class="emsp-mini-card">
        <div class="value text-primary"><?= $stats['total'] ?></div>
        <div class="label">Documents déposés</div>
    </div>
    <div class="emsp-mini-card">
        <div class="value text-warning"><?= $stats['pending'] ?></div>
        <div class="label">En attente</div>
    </div>
    <div class="emsp-mini-card">
        <div class="value text-success"><?= $stats['approved'] ?></div>
        <div class="label">Approuvés</div>
    </div>
    <div class="emsp-mini-card">
        <div class="value text-danger"><?= $stats['rejected'] ?></div>
        <div class="label">Rejetés</div>
    </div>
</div>

<div class="emsp-panel mb-4">
    <div class="emsp-panel-header">
        <i class="bi bi-activity text-primary"></i>Activité récente
    </div>
    <div class="emsp-panel-body">
        <?php if (empty($recent_activity)): ?>
            <div class="emsp-dashboard-empty">
                <span class="emsp-dashboard-empty-icon"><i class="bi bi-cloud-upload"></i></span>
                <strong>Aucune activité récente pour le moment.</strong>
                <span>Déposez un premier document pour lancer votre historique et recevoir vos retours plus vite.</span>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($recent_activity as $activity): ?>
                    <?php
                    $isUpload = $activity['action_type'] === 'upload';
                    $iconClass = $isUpload ? 'bi-cloud-arrow-up-fill text-primary' : 'bi-star-fill text-warning';
                    $activityIconStateClass = $isUpload ? 'is-upload' : 'is-favorite';
                    $label = $isUpload ? 'Document déposé' : 'Ajouté aux favoris';
                    ?>
                    <div class="emsp-activity-item d-flex gap-3 align-items-start">
                        <div class="emsp-activity-item-icon flex-shrink-0 <?= h($activityIconStateClass) ?>">
                            <i class="bi <?= $iconClass ?>"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold"><?= htmlspecialchars($label) ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($activity['title']) ?></div>
                        </div>
                        <div class="text-muted small text-nowrap">
                            <?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">

<!-- Colonne gauche : mes documents -->
<div class="col-lg-8">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">
            <i class="bi bi-files me-2 text-primary"></i>Mes documents
        </h5>
        <a href="<?= url('upload') ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-cloud-upload me-1"></i>Déposer un document
        </a>
    </div>

    <?php if (count($mes_docs) === 0): ?>
        <div class="emsp-panel">
            <div class="emsp-panel-body text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                Vous n'avez pas encore déposé de document.<br>
                <a href="<?= url('upload') ?>" class="btn btn-primary mt-3">
                    <i class="bi bi-cloud-upload me-1"></i>Déposer mon premier document
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-3">
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

            <div class="emsp-doc-row <?= $status_class ?>">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                <span class="badge <?= $type_colors[$d['doc_type']] ?? 'bg-secondary' ?>">
                                    <?= ucfirst($d['doc_type']) ?>
                                </span>
                                <?= $status_label ?>
                                <span class="text-muted small">
                                    <?= date('d/m/Y', strtotime($d['created_at'])) ?>
                                </span>
                            </div>
                            <div class="doc-title mb-1">
                                <?php if ($d['status'] === 'approved'): ?>
                                    <a href="<?= url('document') ?>?id=<?= $d['id'] ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($d['title']) ?>
                                    </a>
                                <?php else: ?>
                                    <?= htmlspecialchars($d['title']) ?>
                                <?php endif; ?>
                            </div>

                            <?php if ($d['status'] === 'pending'): ?>
                                <p class="mb-0 small text-warning-emphasis">
                                    <i class="bi bi-clock me-1"></i>
                                    Votre document est en cours de vérification par un modérateur. Vous serez notifié(e) dès qu'une décision sera prise.
                                </p>
                            <?php elseif ($d['status'] === 'rejected' && $d['rejection_reason']): ?>
                                <div class="alert alert-danger py-1 px-2 mb-0 small mt-1">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    <strong>Motif du rejet :</strong> <?= htmlspecialchars($d['rejection_reason']) ?>
                                </div>
                            <?php elseif ($d['status'] === 'approved'): ?>
                                <div class="doc-meta d-flex gap-3 mt-1">
                                    <span><i class="bi bi-download me-1"></i><?= $d['download_count'] ?> télécharg.</span>
                                    <span><i class="bi bi-heart me-1"></i><?= $d['like_count'] ?> likes</span>
                                    <span><i class="bi bi-chat me-1"></i><?= $d['nb_comments'] ?> commentaires</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($d['status'] === 'approved'): ?>
                        <a href="<?= url('document') ?>?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1"></i>Voir
                        </a>
                        <?php endif; ?>
                    </div>
            </div>

        <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- Colonne droite : notifications -->
<div class="col-lg-4 emsp-dashboard-notifications-col" id="notifications">

    <div class="emsp-notifications-shell">
        <header class="emsp-native-screen-header emsp-notifications-header">
            <div class="emsp-native-screen-header__main">
                <h2 class="emsp-native-screen-header__title">
                    <i class="bi bi-bell-fill" aria-hidden="true"></i>
                    Notifications
                </h2>
                <?php if ($nb_unread > 0): ?>
                    <span class="emsp-native-screen-header__badge" aria-label="<?= (int) $nb_unread ?> non lues"><?= $nb_unread > 99 ? '99+' : $nb_unread ?></span>
                <?php endif; ?>
            </div>
            <?php if ($nb_unread > 0): ?>
            <form method="POST" action="<?= url('notifications/mark-read') ?>" class="m-0">
                <?php csrf_input(); ?>
                <button type="submit" class="emsp-native-screen-header__action">
                    <i class="bi bi-check-all" aria-hidden="true"></i>
                    Tout lire
                </button>
            </form>
            <?php endif; ?>
        </header>

        <?php
        $notif_icons = function_exists('emsp_notification_icon_map')
            ? emsp_notification_icon_map()
            : [];
        $has_notif = !empty($notifs_res);
        ?>

        <?php if (!$has_notif): ?>
            <div class="emsp-native-empty emsp-notifications-empty">
                <span class="emsp-native-empty__icon" aria-hidden="true"><i class="bi bi-bell-slash"></i></span>
                <strong class="emsp-native-empty__title">Aucune alerte</strong>
                <p class="emsp-native-empty__copy">Vos alertes de validation, commentaires et actualités EMSP apparaîtront ici.</p>
            </div>
        <?php else: ?>
            <div class="emsp-native-list emsp-notifications-list" role="list">
                <?php foreach ($notifs_res as $n):
                    $icon = $notif_icons[$n['type']] ?? ['icon'=>'bi-bell-fill','color'=>'is-muted'];
                    $notifPayload = $n;
                    if (!empty($n['doc_id_resolved'])) {
                        $notifPayload['document_id'] = (int) $n['doc_id_resolved'];
                    }
                    $notifLink = emsp_notification_target($notifPayload, 'dashboard');
                    $isJournalNotif = in_array($n['type'], ['journal_published','journal_liked','journal_commented'], true);
                ?>
                    <form method="POST" action="<?= url('notifications/mark-read') ?>" class="m-0 emsp-native-list-item-wrap" role="listitem">
                        <?php csrf_input(); ?>
                        <input type="hidden" name="notification_id" value="<?= (int) $n['id'] ?>">
                        <input type="hidden" name="redirect_to" value="<?= h($notifLink) ?>">
                        <button type="submit"
                                class="emsp-native-list-item emsp-notification-item<?= !$n['is_read'] ? ' is-unread' : '' ?>"
                                aria-label="Voir la notification">
                            <span class="emsp-native-list-item__icon emsp-notification-item__icon <?= h($icon['color']) ?>" aria-hidden="true">
                                <i class="bi <?= h($icon['icon']) ?>"></i>
                            </span>
                            <span class="emsp-native-list-item__body">
                                <span class="emsp-notification-item__message<?= !$n['is_read'] ? ' is-unread' : '' ?>">
                                    <?= h($n['message']) ?>
                                </span>
                                <?php if (!$isJournalNotif && !empty($n['doc_title'])): ?>
                                    <span class="emsp-notification-item__doc"><?= h($n['doc_title']) ?></span>
                                <?php endif; ?>
                                <time class="emsp-notification-item__time" datetime="<?= h((string) $n['created_at']) ?>">
                                    <?= date('d/m/Y à H:i', strtotime($n['created_at'])) ?>
                                </time>
                            </span>
                            <span class="emsp-native-list-item__chevron" aria-hidden="true">
                                <i class="bi bi-chevron-right"></i>
                            </span>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div><!-- /col notifs -->
</div><!-- /row -->

</div><!-- /dashboard-shell -->
</div>
</section>
