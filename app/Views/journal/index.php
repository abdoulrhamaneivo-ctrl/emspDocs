<style>
.journal-card-annonce { border-top: 2px solid #004D2A; border-left: 0; }
.journal-card-defi { border-top: 2px solid #006B3C; border-left: 0; }
.journal-card-sondage { border-top: 2px solid #006B3C; border-left: 0; }
.journal-card {
  overflow: hidden;
  border-radius: 18px;
  border: 1px solid rgba(0, 107, 60, .12);
  box-shadow: 0 10px 28px rgba(0, 0, 0, .05) !important;
  background: #fff;
  transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
}
.journal-panel {
  border: 1px solid rgba(0, 107, 60, .1);
  border-radius: 18px;
  background: #fff;
  box-shadow: 0 10px 26px rgba(0, 0, 0, .04);
  padding: 1.1rem 1.2rem;
}
.journal-panel h3 { font-size: 1.05rem; font-weight: 700; margin-bottom: .55rem; color: #004D2A; }
.journal-card:hover { transform: translateY(-3px); box-shadow: 0 16px 34px rgba(0, 0, 0, .08) !important; border-color: rgba(200, 148, 22, .35); }
.journal-card .card-body { padding: 1.25rem; }
.journal-shell .alert { border-radius: 14px; border: 1px solid rgba(0, 107, 60, .12); background: #f4faf6; }
.journal-type-badge {
  display: inline-flex; align-items: center; gap: .35rem;
  padding: .35rem .75rem; border-radius: 999px;
  background: rgba(0, 107, 60, .08); color: #004D2A;
  font-weight: 700; font-size: .78rem;
}
.journal-cover { position: relative; overflow: hidden; background: #eef3f0; }
.journal-cover-feature { min-height: 250px; max-height: 360px; }
.journal-cover-feature .journal-cover-img { width: 100%; height: 100%; min-height: 250px; max-height: 360px; object-fit: cover; display: block; }
.journal-cover-side, .journal-cover-grid { height: 100%; min-height: 140px; }
.journal-cover-side .journal-cover-img, .journal-cover-grid .journal-cover-img { width: 100%; height: 100%; min-height: 140px; object-fit: cover; display: block; }
.journal-cover-placeholder {
  display: flex; align-items: center; justify-content: center; min-height: 180px; padding: 1rem;
  background: radial-gradient(circle at top right, rgba(255,255,255,.16), transparent 25%), linear-gradient(135deg, var(--journal-type-color), #004D2A);
  color: #fff;
}
.journal-cover-side.journal-cover-placeholder, .journal-cover-grid.journal-cover-placeholder { min-height: 140px; }
.journal-cover-type {
  position: absolute; top: 12px; left: 12px; padding: .3rem .7rem; border-radius: 999px;
  background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.24);
  font-size: .72rem; font-weight: 700; letter-spacing: .03em;
}
.journal-cover-initial { font-size: clamp(3rem, 8vw, 5rem); font-weight: 900; line-height: 1; opacity: .92; }
.journal-sidebar-card { display: grid; grid-template-columns: 118px minmax(0, 1fr); gap: 1rem; }
.journal-sidebar-card .journal-cover-side { border-radius: 12px; }
.journal-card-title { color: #132018; line-height: 1.3; }
.journal-card-excerpt { color: #5d6b62; }
.journal-card-actions { display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; margin-top: 1rem; }
.journal-card-link { color: #004D2A; font-weight: 700; text-decoration: none; }
.journal-card-link:hover { color: #006B3C; }
.journal-feature-card { box-shadow: var(--emsp-panel-shadow) !important; }
.journal-section-title { color: #004D2A; font-weight: 900; letter-spacing: .04em; }
.btn-read-modal { cursor: pointer; }
#jm-content { overflow-y: auto; max-height: 70vh; }
.journal-rich-content h1, .journal-rich-content h2, .journal-rich-content h3 { margin-top: 1rem; }
.journal-rich-content img { max-width: 100%; height: auto; border-radius: 8px; margin: .5rem 0; }
.journal-rich-content blockquote { border-left: 4px solid #006B3C; padding-left: 1rem; color: #64748b; }
.journal-rich-content ul, .journal-rich-content ol { padding-left: 1.5rem; }
.journal-rich-content p { margin-bottom: .75rem; }
.journal-rich-content a { color: #006B3C; }
.journal-carousel-track { display: grid; grid-template-columns: repeat(auto-fill, minmax(17rem, 1fr)); gap: 1rem; }
.journal-carousel-slide { scroll-snap-align: start; min-width: 0; }
.journal-carousel-slide .journal-card { height: 100%; }
.journal-carousel-nav { display: flex; gap: .5rem; }
.journal-carousel-btn {
  width: 2.75rem; height: 2.75rem; min-width: 2.75rem; min-height: 2.75rem;
  border: 1px solid rgba(200, 148, 22, .45); border-radius: 999px; background: #fff; color: #004D2A;
  display: inline-flex; align-items: center; justify-content: center;
  transition: background-color .2s ease, border-color .2s ease, transform .2s ease;
}
.journal-carousel-btn:hover { background: rgba(200, 148, 22, .12); border-color: #C89416; transform: translateY(-1px); }
@media (max-width: 767.98px) {
  .journal-sidebar-card { grid-template-columns: 1fr; }
  .journal-sidebar-card .journal-cover-side { min-height: 180px; }
  .journal-carousel-track {
    display: flex; overflow-x: auto; gap: .85rem; padding-bottom: .35rem;
    scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch;
  }
  .journal-carousel-slide { flex: 0 0 min(86vw, 18.5rem); }
}
</style>

<div class="emsp-page-shell emsp-page-shell--journal">
<section class="journal-editorial-hero emsp-editorial-bg">
    <div class="container">
        <span class="home-eyebrow">Actualités EMSP</span>
        <h1>Journal EMSP</h1>
        <p class="mb-0">Actualités, annonces, défis et ressources récentes pour la communauté EMSP.</p>
    </div>
</section>

<section class="section-pad journal-shell emsp-section-band">
    <div class="container">
        <div class="alert alert-info d-none" id="journal-new-badge">
            <strong>Nouvelle publication du journal.</strong>
            <span class="ms-1">Ouvrez l'article pour la consulter.</span>
        </div>
        <?php if ($featured): ?>
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <?php $featuredMeta = emsp_newsblog_journal_type_meta((string) ($featured['type'] ?? '')); ?>
                <article class="card shadow-sm border-0 h-100 journal-card journal-feature-card <?= htmlspecialchars($featuredMeta['class']) ?>">
                    <?= emsp_newsblog_journal_cover_html($featured, 'feature') ?>
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="journal-type-badge journal-type-<?= h(emsp_newsblog_type_key((string) ($featured['type'] ?? ''))) ?>">
                                <?= htmlspecialchars($featuredMeta['label']) ?>
                            </span>
                            <span class="small text-muted"><?= htmlspecialchars(emsp_relative_date($featured['created_at'])) ?></span>
                        </div>
                        <h2 class="h4 fw-bold journal-card-title"><?= htmlspecialchars($featured['title']) ?></h2>
                        <p class="mb-0 journal-card-excerpt"><?= htmlspecialchars(emsp_newsblog_excerpt((string) $featured['content'], 260)) ?></p>
                        <div class="journal-card-actions">
                            <a class="btn btn-emsp btn-sm" href="<?= url('journal/article') ?>?id=<?= intval($featured['id']) ?>">
                                Lire l'article <i class="bi bi-arrow-right-short"></i>
                            </a>
                        </div>
                    </div>
                </article>
            </div>
            <div class="col-lg-4">
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($sidebar as $i => $item): ?>
                    <?php $sideMeta = emsp_newsblog_journal_type_meta((string) ($item['type'] ?? '')); ?>
                    <article class="card shadow-sm border-0 journal-card <?= htmlspecialchars($sideMeta['class']) ?>">
                        <div class="card-body">
                            <div class="journal-sidebar-card">
                                <?= emsp_newsblog_journal_cover_html($item, 'side') ?>
                                <div>
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                        <span class="journal-type-badge journal-type-<?= h(emsp_newsblog_type_key((string) ($item['type'] ?? ''))) ?>">
                                            <?= htmlspecialchars($sideMeta['label']) ?>
                                        </span>
                                        <span class="small text-muted"><?= htmlspecialchars(emsp_relative_date($item['created_at'])) ?></span>
                                    </div>
                                    <h3 class="h6 fw-bold mb-2 journal-card-title"><?= htmlspecialchars($item['title']) ?></h3>
                                    <p class="small journal-card-excerpt mb-2"><?= htmlspecialchars(emsp_newsblog_excerpt((string) $item['content'], 105)) ?></p>
                                    <div class="journal-card-actions mt-0">
                                        <a class="journal-card-link small" href="<?= url('journal/article') ?>?id=<?= intval($item['id']) ?>">Lire l'article <i class="bi bi-arrow-right-short"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="journal-panel mb-4">
            <h3>Aucun article publié</h3>
            <p class="mb-0 text-muted">Publie un article dans <code>admin/journal.php</code>.</p>
        </div>
        <?php endif; ?>

        <div class="journal-actualites-wrap mb-5">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
                <h2 class="journal-section-title h4 mb-0">Actualités récentes</h2>
                <div class="journal-carousel-nav" aria-hidden="true">
                    <button type="button" class="journal-carousel-btn" data-journal-scroll="prev" aria-label="Articles précédents"><i class="bi bi-chevron-left"></i></button>
                    <button type="button" class="journal-carousel-btn" data-journal-scroll="next" aria-label="Articles suivants"><i class="bi bi-chevron-right"></i></button>
                </div>
            </div>
            <div class="journal-carousel-track" id="journal-carousel-track" role="list">
            <?php if (empty($recentJournal)): ?>
            <div class="journal-carousel-slide" role="listitem">
                <div class="journal-panel">
                    <h3>Pas d'autres actualités</h3>
                    <p class="mb-0 text-muted">Ajoute des contenus supplémentaires dans le journal.</p>
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($recentJournal as $item): ?>
            <?php $recentMeta = emsp_newsblog_journal_type_meta((string) ($item['type'] ?? '')); ?>
            <article class="journal-carousel-slide journal-card shadow-sm border-0 <?= htmlspecialchars($recentMeta['class']) ?>" role="listitem" id="news-<?= intval($item['id']) ?>">
                    <?= emsp_newsblog_journal_cover_html($item, 'grid') ?>
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <span class="journal-type-badge journal-type-<?= h(emsp_newsblog_type_key((string) ($item['type'] ?? ''))) ?>">
                                <?= htmlspecialchars($recentMeta['label']) ?>
                            </span>
                            <span class="small text-muted"><?= htmlspecialchars(emsp_relative_date($item['created_at'])) ?></span>
                        </div>
                        <h3 class="h6 fw-bold journal-card-title"><?= htmlspecialchars($item['title']) ?></h3>
                        <p class="mb-0 journal-card-excerpt small"><?= htmlspecialchars(emsp_newsblog_excerpt((string) $item['content'], 120)) ?></p>
                        <div class="journal-card-actions">
                            <a class="btn btn-sm btn-emsp-outline" href="<?= url('journal/article') ?>?id=<?= intval($item['id']) ?>">Lire l'article <i class="bi bi-arrow-right-short"></i></a>
                        </div>
                    </div>
            </article>
            <?php endforeach; ?>
            <?php endif; ?>
            </div>
        </div>
    </div>
</section>
</div>

<!-- L'ancienne lecture rapide est conservée comme référence technique mais
     reste inertée : la consultation se fait uniquement sur la page article. -->
<template id="journalQuickReadLegacyTemplate">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
            <div class="small text-muted" id="jm-date"></div>
            <h5 class="modal-title" id="jm-title">Lecture rapide</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
<div class="modal-body">
        <div class="journal-quick-state" id="jm-state"></div>
        <div id="jm-content" class="journal-rich-content mb-4"></div>
        <div id="jm-interaction"></div>
        <div id="jm-social" class="mt-4"></div>
      </div>
      <div class="modal-footer justify-content-between">
        <span class="small text-muted" id="jm-footer-note"></span>
        <a class="btn btn-emsp" id="jm-open-link" href="<?= url('journal') ?>">Voir l article complet</a>
      </div>
    </div>
  </div>
</template>

<?php
// Maintenance: the quick-read modal script is injected through the shared footer pipeline
// so it keeps the same JS stack as the rest of the public shell.
$isAuthenticatedJson = !empty($_SESSION['auth_user']['id']) ? 'true' : 'false';
$latestJournalIdJson = json_encode($latestJournalId, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$journalArticleUrl = url('journal/article');
$journalActionUrl = url('journal/action');
$page_scripts = <<<HTML
<script>
    const EMSP_JOURNAL_ARTICLE_URL = '{$journalArticleUrl}';
    const EMSP_JOURNAL_ACTION_URL = '{$journalActionUrl}';
(function () {
    var modalElement = document.getElementById('journalModal');
    if (!modalElement || typeof bootstrap === 'undefined') {
        return;
    }

    var isAuthenticated = {$isAuthenticatedJson};
    var modal = new bootstrap.Modal(modalElement);
    var csrfInput = document.getElementById('journal-modal-csrf');
    var csrfToken = csrfInput ? csrfInput.value : '';
    var titleEl = document.getElementById('jm-title');
    var dateEl = document.getElementById('jm-date');
    var stateEl = document.getElementById('jm-state');
    var contentEl = document.getElementById('jm-content');
    var interactionEl = document.getElementById('jm-interaction');
    var socialEl = document.getElementById('jm-social');
    var footerNoteEl = document.getElementById('jm-footer-note');
    var openLinkEl = document.getElementById('jm-open-link');
    var currentSummary = null;
    var latestJournalId = {$latestJournalIdJson};
    var newBadge = document.getElementById('journal-new-badge');
    var lastSeenKey = 'emsp-journal-last-seen';

    function markLastSeen(id) {
        if (!id || !window.localStorage) {
            return;
        }
        try {
            window.localStorage.setItem(lastSeenKey, String(id));
        } catch (e) {}
    }

    function refreshBadge() {
        if (!newBadge || !latestJournalId || !window.localStorage) {
            return;
        }
        var lastSeen = 0;
        try {
            lastSeen = parseInt(window.localStorage.getItem(lastSeenKey) || '0', 10);
        } catch (e) {
            lastSeen = 0;
        }
        if (latestJournalId > lastSeen) {
            newBadge.classList.remove('d-none');
        } else {
            newBadge.classList.add('d-none');
        }
    }

    function esc(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderState(summary) {
        var html = '';
        if (summary.type_meta) {
            var typeClass = String(summary.type || 'annonce').toLowerCase();
            if (['annonce', 'defi', 'sondage'].indexOf(typeClass) === -1) {
                typeClass = 'annonce';
            }
            html += '<span class="journal-type-badge journal-type-' + typeClass + '">' + esc(summary.type_meta.label || 'Article') + '</span>';
        }
        if (summary.state) {
            html += '<span class="journal-state-badge">' + esc(summary.state.label || 'Etat') + '</span>';
            if (summary.state.starts_at_label) {
                html += '<span class="small text-muted">Debut : ' + esc(summary.state.starts_at_label) + '</span>';
            }
            if (summary.state.ends_at_label) {
                html += '<span class="small text-muted">Fin : ' + esc(summary.state.ends_at_label) + '</span>';
            }
            if (summary.state.closed_at_label) {
                html += '<span class="small text-muted">Clos : ' + esc(summary.state.closed_at_label) + '</span>';
            }
        }
        stateEl.innerHTML = html;
    }

    function renderPoll(summary) {
        var poll = summary.poll || { options: [], total_votes: 0, my_option: 0 };
        if (!poll.options.length) {
            interactionEl.innerHTML = '<div class="alert alert-warning mb-0">Aucune option configuree pour ce sondage.</div>';
            return;
        }
        var total = parseInt(poll.total_votes || 0, 10);
        var controls = poll.options.map(function (option) {
            var votes = parseInt(option.votes || 0, 10);
            var percent = total > 0 ? Math.round((votes * 100) / total) : 0;
            var isMine = parseInt(poll.my_option || 0, 10) === parseInt(option.id || 0, 10);
            var disabled = (!summary.state || !summary.state.is_open || !isAuthenticated) ? 'disabled' : '';
            return '<div class="journal-poll-option' + (isMine ? ' active' : '') + ' mb-3" data-poll-card data-option-id="' + esc(option.id) + '">'
                + '<div class="d-flex justify-content-between align-items-center gap-3 mb-2">'
                + '<button type="button" class="btn btn-sm ' + (isMine ? 'btn-emsp' : 'btn-emsp-outline') + '" data-modal-poll-option data-option-id="' + esc(option.id) + '" ' + disabled + '>' + esc(option.label) + '</button>'
                + '<strong>' + votes + ' vote(s)</strong>'
                + '</div>'
                + '<div class="journal-poll-progress"><span class="journal-poll-progress-fill" data-emsp-width="' + percent + '"></span></div>'
                + '<div class="small text-muted mt-2">' + percent + '% des votes</div>'
                + '</div>';
        }).join('');
        var pollNote = summary.state && !summary.state.is_open
            ? 'Resultat final du sondage.'
            : 'Les resultats restent visibles publiquement.';
        interactionEl.innerHTML = '<div class="d-flex justify-content-between align-items-center mb-3"><strong>' + total + ' vote(s)</strong><span class="small text-muted">' + pollNote + '</span></div>' + controls;
    }

    function renderDefi(summary) {
        var defi = summary.defi || { participant_total: 0, participated: false, note: '' };
        var disabled = (!summary.state || !summary.state.is_open || !isAuthenticated) ? 'disabled' : '';
        var loginNotice = isAuthenticated ? '' : '<div class="alert alert-info">Connectez-vous pour participer a ce defi.</div>';
        interactionEl.innerHTML = '<div class="d-flex justify-content-between align-items-center mb-3"><strong>' + parseInt(defi.participant_total || 0, 10) + ' participation(s)</strong><span class="small text-muted">Les notes restent visibles seulement cote admin.</span></div>'
            + loginNotice
            + (isAuthenticated ? '<div class="mb-3"><label class="form-label fw-semibold" for="jm-defi-note">Votre note</label><textarea class="form-control" id="jm-defi-note" rows="5" ' + disabled + '>' + esc(defi.note || '') + '</textarea></div><div class="d-flex flex-wrap gap-3 align-items-center"><button type="button" class="btn btn-emsp" id="jm-defi-submit" ' + disabled + '>' + (defi.participated ? 'Mettre a jour ma participation' : 'Je participe') + '</button><span class="small text-muted" id="jm-defi-feedback">' + (defi.participated ? 'Votre participation est deja enregistree.' : 'Cliquez pour participer.') + '</span></div>' : '');
    }

    function renderAnnouncement() {
        interactionEl.innerHTML = '';
    }

    function renderSocial(summary) {
        if (!socialEl) {
            return;
        }
        var likeCount = parseInt(summary.like_count || 0, 10);
        var commentCount = parseInt(summary.comment_count || 0, 10);
        var liked = !!summary.liked;
        var likeLabel = liked ? 'Aime' : 'J aime';
        var likeClass = liked ? 'btn-emsp' : 'btn-emsp-outline';
        var disabled = isAuthenticated ? '' : 'disabled';
        var comments = Array.isArray(summary.comments) ? summary.comments : [];
        var commentsHtml = comments.length
            ? comments.map(function (comment) {
                var avatar = comment.photo_src
                    ? '<img src="' + esc(comment.photo_src) + '" alt="" class="emsp-comment-avatar" width="40" height="40" loading="lazy" decoding="async">'
                    : '<span class="emsp-comment-avatar emsp-comment-avatar--fallback" aria-hidden="true">' + esc(comment.initials || 'EM') + '</span>';
                var profileUrl = comment.profile_url || '';
                var avatarHtml = profileUrl
                    ? '<a href="' + esc(profileUrl) + '" class="emsp-comment-author-link emsp-comment-author-link--avatar" rel="nofollow">' + avatar + '</a>'
                    : avatar;
                var authorName = esc(comment.display_name || 'Utilisateur');
                var authorHtml = profileUrl
                    ? '<a href="' + esc(profileUrl) + '" class="emsp-comment-author-link" rel="nofollow"><strong class="emsp-comment-author">' + authorName + '</strong></a>'
                    : '<strong class="emsp-comment-author">' + authorName + '</strong>';
                return ''
                    + '<div class="d-flex gap-3 py-3 border-bottom emsp-comment-row">'
                    + '<div class="emsp-comment-row__avatar">' + avatarHtml + '</div>'
                    + '<div class="emsp-comment-row__body flex-grow-1">'
                    + '<div class="small text-muted mb-1 emsp-comment-row__head">' + authorHtml + ' · ' + esc(comment.relative_date || '') + '</div>'
                    + '<div class="emsp-comment-row__content">' + esc(comment.content || '').replace(/\\n/g, '<br>') + '</div>'
                    + '</div>'
                    + '</div>';
            }).join('')
            : '<div class="small text-muted">Aucun commentaire pour le moment.</div>';
        socialEl.innerHTML = ''
            + '<div class="d-flex flex-wrap align-items-center gap-3">'
            + '<button type="button" class="btn btn-sm ' + likeClass + '" id="jm-like-btn" ' + disabled + '>'
            + '<i class="bi ' + (liked ? 'bi-heart-fill' : 'bi-heart') + ' me-1"></i>' + likeLabel
            + '</button>'
            + '<span class="small text-muted" id="jm-like-count">' + likeCount + ' like(s)</span>'
            + '<span class="small text-muted">' + commentCount + ' commentaire(s)</span>'
            + (isAuthenticated ? '' : '<span class="small text-muted">Connectez-vous pour aimer ou commenter.</span>')
            + '</div>';
        socialEl.innerHTML += '<div class="mt-3" id="jm-comments">' + commentsHtml + '</div>';
        if (isAuthenticated) {
            socialEl.innerHTML += ''
                + '<div class="mt-3">'
                + '<label class="form-label fw-semibold" for="jm-comment-input">Ajouter un commentaire</label>'
                + '<textarea class="form-control" id="jm-comment-input" rows="3" placeholder="Votre commentaire..."></textarea>'
                + '<div class="d-flex flex-wrap align-items-center gap-3 mt-2">'
                + '<button type="button" class="btn btn-emsp btn-sm" id="jm-comment-submit"><i class="bi bi-send-fill me-1"></i>Publier</button>'
                + '<span class="small text-danger journal-comment-error" id="jm-comment-error"></span>'
                + '</div>'
                + '</div>';
        }
    }

    function renderSummary(summary) {
        currentSummary = summary;
        titleEl.textContent = summary.title || 'Lecture rapide';
        dateEl.textContent = summary.relative_date || '';
        contentEl.innerHTML = summary.content_html || '';
        openLinkEl.href = EMSP_JOURNAL_ARTICLE_URL + '?id=' + encodeURIComponent(summary.id || '');
        footerNoteEl.textContent = summary.state && summary.state.is_open ? 'Contenu actuellement ouvert.' : 'Contenu non interactif pour le moment.';
        renderState(summary);
        if (summary.type === 'sondage') {
            renderPoll(summary);
        } else if (summary.type === 'defi') {
            renderDefi(summary);
        } else {
            renderAnnouncement();
        }
        renderSocial(summary);
        markLastSeen(summary.id);
        refreshBadge();
    }

    function loadSummary(id) {
        titleEl.textContent = 'Chargement...';
        dateEl.textContent = '';
        stateEl.innerHTML = '';
        contentEl.innerHTML = '<div class="text-muted">Chargement du contenu...</div>';
        interactionEl.innerHTML = '';
        footerNoteEl.textContent = '';
        openLinkEl.href = '#';
        modal.show();

        fetch(EMSP_JOURNAL_ACTION_URL + '?action=summary&journal_id=' + encodeURIComponent(id), {
            credentials: 'include'
        })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (!payload || !payload.ok || !payload.summary) {
                    throw new Error('summary');
                }
                renderSummary(payload.summary);
            })
            .catch(function () {
                contentEl.innerHTML = '<div class="alert alert-danger mb-0">Impossible de charger cette lecture rapide pour le moment.</div>';
            });
    }

    document.querySelectorAll('.btn-read-modal').forEach(function (button) {
        button.addEventListener('click', function () {
            loadSummary(this.getAttribute('data-journal-id'));
        });
    });

    document.querySelectorAll('a[href^="' + EMSP_JOURNAL_ARTICLE_URL + '?id="]').forEach(function (link) {
        link.addEventListener('click', function () {
            var url = new URL(this.getAttribute('href'), window.location.href);
            var id = parseInt(url.searchParams.get('id') || '0', 10);
            if (id > 0) {
                markLastSeen(id);
                refreshBadge();
            }
        });
    });

    refreshBadge();

    interactionEl.addEventListener('click', function (event) {
        var pollButton = event.target.closest('[data-modal-poll-option]');
        if (pollButton && currentSummary) {
            var formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('action', 'vote');
            formData.append('journal_id', currentSummary.id);
            formData.append('option_id', pollButton.getAttribute('data-option-id'));
            fetch('{$journalActionUrl}', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            })
                .then(function (response) { return response.json(); })
                .then(function (payload) {
                    if (!payload || !payload.ok || !payload.summary) {
                        throw payload || new Error('vote');
                    }
                    renderSummary(payload.summary);
                })
                .catch(function (payload) {
                    if (payload && payload.summary) {
                        renderSummary(payload.summary);
                    }
                    var msg = payload && payload.message ? payload.message : 'Impossible d enregistrer votre vote pour le moment.';
                    if (window.emspUI && typeof window.emspUI.showError === 'function') {
                        window.emspUI.showError('Vote indisponible', msg);
                    } else {
                        console.error(msg);
                    }
                });
            return;
        }

        var defiButton = event.target.closest('#jm-defi-submit');
        if (defiButton && currentSummary) {
            var note = document.getElementById('jm-defi-note');
            var formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('action', 'defi');
            formData.append('journal_id', currentSummary.id);
            formData.append('note', note ? note.value : '');
            fetch('{$journalActionUrl}', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            })
                .then(function (response) { return response.json(); })
                .then(function (payload) {
                    if (!payload || !payload.ok || !payload.summary) {
                        throw payload || new Error('defi');
                    }
                    renderSummary(payload.summary);
                })
                .catch(function (payload) {
                    if (payload && payload.summary) {
                        renderSummary(payload.summary);
                    }
                    var feedback = document.getElementById('jm-defi-feedback');
                    if (feedback) {
                        feedback.textContent = payload && payload.message
                            ? payload.message
                            : 'Impossible d enregistrer la participation pour le moment.';
                    }
                });
        }
    });

    if (socialEl) {
        socialEl.addEventListener('click', function (event) {
            var likeButton = event.target.closest('#jm-like-btn');
            if (!likeButton || !currentSummary) {
                return;
            }
            if (!csrfToken || !currentSummary.id) {
                return;
            }
            var fd = new FormData();
            fd.append('csrf_token', csrfToken);
            fd.append('action', 'like');
            fd.append('journal_id', currentSummary.id);
            fetch('{$journalActionUrl}', {
                method: 'POST',
                body: fd,
                credentials: 'include'
            })
                .then(function (response) { return response.json(); })
                .then(function (payload) {
                    if (!payload || !payload.ok) {
                        throw payload || new Error('like');
                    }
                    if (payload.summary) {
                        renderSummary(payload.summary);
                        return;
                    }
                    var liked = !!payload.liked;
                    likeButton.classList.toggle('btn-emsp', liked);
                    likeButton.classList.toggle('btn-emsp-outline', !liked);
                    likeButton.innerHTML = '<i class="bi ' + (liked ? 'bi-heart-fill' : 'bi-heart') + ' me-1"></i>' + (liked ? 'Aime' : 'J aime');
                    var count = document.getElementById('jm-like-count');
                    if (count) { count.textContent = (payload.like_count || 0) + ' like(s)'; }
                })
                .catch(function (payload) {
                    var msg = payload && payload.message ? payload.message : 'Impossible d enregistrer le like.';
                    if (window.emspUI && typeof window.emspUI.showError === 'function') {
                        window.emspUI.showError('Like indisponible', msg);
                    } else {
                        console.error(msg);
                    }
                });
        });

        socialEl.addEventListener('click', function (event) {
            var commentButton = event.target.closest('#jm-comment-submit');
            if (!commentButton || !currentSummary) {
                return;
            }
            var commentInput = document.getElementById('jm-comment-input');
            var commentError = document.getElementById('jm-comment-error');
            var content = commentInput ? String(commentInput.value || '').trim() : '';
            if (commentError) {
                commentError.textContent = '';
                commentError.style.display = 'none';
            }
            if (content === '') {
                if (commentError) {
                    commentError.textContent = 'Le commentaire est vide.';
                    commentError.style.display = 'inline';
                }
                return;
            }
            var commentFd = new FormData();
            commentFd.append('csrf_token', csrfToken);
            commentFd.append('action', 'comment');
            commentFd.append('journal_id', currentSummary.id);
            commentFd.append('content', content);
            fetch('{$journalActionUrl}', {
                method: 'POST',
                body: commentFd,
                credentials: 'include'
            })
                .then(function (response) { return response.json(); })
                .then(function (payload) {
                    if (!payload || !payload.ok || !payload.summary) {
                        throw payload || new Error('comment');
                    }
                    if (commentInput) {
                        commentInput.value = '';
                    }
                    renderSummary(payload.summary);
                })
                .catch(function (payload) {
                    if (commentError) {
                        commentError.textContent = payload && payload.message ? payload.message : 'Impossible d enregistrer le commentaire.';
                        commentError.style.display = 'inline';
                    }
                });
        });
    }

    var journalTrack = document.getElementById('journal-carousel-track');
    document.querySelectorAll('[data-journal-scroll]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (!journalTrack) return;
            var direction = button.getAttribute('data-journal-scroll') === 'next' ? 1 : -1;
            var step = Math.max(280, Math.round(journalTrack.clientWidth * 0.82));
            journalTrack.scrollBy({ left: direction * step, behavior: 'smooth' });
        });
    });
})();
</script>
HTML;
