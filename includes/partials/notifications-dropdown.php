<?php
/**
 * Panneau dropdown / bottom sheet notifications navbar.
 * Rendu ici pour le SSR ; déplacé dans document.body par emsp-notifications.js à l'init.
 */
$__notifPanelAuth = !empty($_SESSION['auth']) || !empty($_SESSION['auth_user']['id']);
$__notifPanelActive = $__notifPanelAuth
    && in_array(strtolower(trim((string) ($_SESSION['auth_user']['status'] ?? 'active'))), ['', 'active'], true);

if (!$__notifPanelActive) {
    return;
}
?>
<div
    id="emspNotifPanel"
    class="emsp-notif-panel"
    role="dialog"
    aria-modal="false"
    aria-label="Notifications"
    hidden
>
    <div class="emsp-notif-panel__handle" data-emsp-notif-handle aria-hidden="true"></div>

    <header class="emsp-notif-panel__header">
        <h2 class="emsp-notif-panel__title">Notifications</h2>
        <div class="emsp-notif-panel__header-actions">
            <button
                type="button"
                class="emsp-notif-panel__mark-all"
                data-emsp-notif-mark-all
                hidden
            >
                Tout marquer comme lu
            </button>
            <button
                type="button"
                class="emsp-notif-panel__close"
                data-emsp-notif-close
                aria-label="Fermer les notifications"
            >
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>
    </header>

    <div class="emsp-notif-panel__body" data-emsp-notif-body>
        <div class="emsp-notif-panel__loading" data-emsp-notif-loading hidden>
            <span class="emsp-notif-panel__spinner" aria-hidden="true"></span>
            <span>Chargement…</span>
        </div>

        <div class="emsp-notif-panel__empty" data-emsp-notif-empty>
            <span class="emsp-notif-panel__empty-icon" aria-hidden="true"><i class="bi bi-bell-slash"></i></span>
            <strong>Aucune alerte</strong>
            <p>Vos validations, commentaires et actualités EMSP apparaîtront ici.</p>
        </div>

        <ul class="emsp-notif-panel__list" data-emsp-notif-list role="list" hidden></ul>
    </div>

    <footer class="emsp-notif-panel__footer">
        <a href="<?= h(function_exists('url') ? url('dashboard') : './dashboard') ?>" class="emsp-notif-panel__see-all">
            Voir le tableau de bord
        </a>
    </footer>
</div>
