<section class="emsp-doc-page emsp-motion-page">
    <div class="emsp-container emsp-container-wide">
        <?php
        $preview_image_url = (string) ($preview_image_url ?? '');
        $is_pdf_document = str_contains(strtolower((string) ($doc['mime_type'] ?? '')), 'pdf')
            || strtolower(pathinfo((string) ($doc['file_path'] ?? ''), PATHINFO_EXTENSION)) === 'pdf';
        $inline_preview_available = $preview_image_url !== '' || ($preview_available && ($is_image || $is_pdf_document));
        $inline_preview_url = $is_image ? url('telecharger?id=' . $doc_id . '&preview=1') : '';
        $pdf_inline_url = $is_pdf_document ? url('telecharger?id=' . $doc_id . '&preview=1') : '';
        ?>

        <?php if (($doc['status'] ?? '') === 'pending' && $is_owner): ?>
            <div class="alert alert-warning mb-4 emsp-motion-item">
                <i class="bi bi-hourglass-split"></i>
                En attente de validation — visible après approbation par un modérateur.
                <a href="<?= url('dashboard') ?>">Voir mes documents →</a>
            </div>
        <?php endif; ?>
        <?php if (($doc['status'] ?? '') === 'rejected' && ($is_owner || $is_staff)): ?>
            <div class="alert alert-danger mb-4 emsp-motion-item">
                <i class="bi bi-x-circle-fill"></i>
                Document rejeté<?= !empty($doc['rejection_reason']) ? ' — ' . h($doc['rejection_reason']) : '' ?>
            </div>
        <?php endif; ?>

        <div class="emsp-doc-layout">
            <div class="emsp-doc-preview-col emsp-motion-item">
                <?php if ($can_view && $is_pdf_document && $pdf_inline_url !== ''): ?>
                    <div class="emsp-doc-inline-preview">
                        <div class="emsp-doc-inline-preview-head">
                            <span class="emsp-doc-preview-kicker">Aperçu du document</span>
                            <button class="emsp-btn emsp-btn-outline btn-sm" type="button"
                                    data-bs-toggle="modal" data-bs-target="#documentPreviewModal">
                                <i class="bi bi-arrows-fullscreen"></i> Plein écran
                            </button>
                        </div>
                        <div class="emsp-pdf-preview-wrap">
                            <div class="emsp-pdf-preview-loading" aria-live="polite">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                Chargement de l'aperçu…
                            </div>
                            <iframe
                                title="<?= h($doc['title'] ?? 'Aperçu PDF') ?>"
                                class="emsp-doc-preview-frame emsp-inline-pdf-frame emsp-doc-inline-frame"
                                data-emsp-pdf-preview="1"
                                data-pdf-data-url="<?= h(url('telecharger?id=' . $doc_id . '&pdfdata=1')) ?>"
                                data-preview-url="<?= h($pdf_inline_url) ?>"
                                data-fallback-thumb="<?= h($preview_image_url) ?>"
                            ></iframe>
                        </div>
                    </div>
                <?php elseif ($can_view && $is_image && $inline_preview_url !== ''): ?>
                    <figure class="emsp-doc-inline-preview emsp-doc-preview-figure">
                        <img src="<?= h($inline_preview_url) ?>" alt="<?= h($doc['title'] ?? 'Document') ?>" class="emsp-doc-preview-image">
                    </figure>
                <?php elseif ($can_view && $preview_image_url !== ''): ?>
                    <figure class="emsp-doc-inline-preview emsp-doc-preview-figure">
                        <img src="<?= h($preview_image_url) ?>" alt="<?= h($doc['title'] ?? 'Document') ?>" class="emsp-doc-preview-image">
                        <figcaption>Première page — ouvrez l'aperçu plein écran pour défiler le document.</figcaption>
                    </figure>
                <?php elseif ($can_view): ?>
                    <div class="emsp-doc-preview-placeholder">
                        <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                        <p>Aperçu non disponible pour ce format.</p>
                    </div>
                <?php else: ?>
                    <div class="emsp-doc-preview-placeholder">
                        <i class="bi bi-lock" aria-hidden="true"></i>
                        <p>Connecte-toi pour consulter ce document.</p>
                    </div>
                <?php endif; ?>
            </div>

            <aside class="emsp-doc-sidebar emsp-motion-item">
                <header class="emsp-doc-header">
                    <span class="emsp-doc-type"><?= h($tl) ?></span>
                    <?php if (!empty($status_label)): ?>
                        <span class="emsp-badge emsp-badge-warning ms-2"><?= h($status_label) ?></span>
                    <?php endif; ?>
                    <h1 class="emsp-doc-title"><?= h($doc['title'] ?? '') ?></h1>
                    <p class="emsp-doc-byline">
                        Par <?= h(trim(($doc['first_name'] ?? '') . ' ' . ($doc['last_name'] ?? ''))) ?>
                        · <?= h(time_ago((string) ($doc['created_at'] ?? ''))) ?>
                        · <?= (int) ($doc['download_count'] ?? 0) ?> téléchargement(s)
                    </p>
                    <?php if (!empty($doc['filiere_label_display']) || !empty($doc['licence_name']) || !empty($doc['matiere_display'])): ?>
                        <p class="emsp-doc-academic">
                            <?= h(implode(' · ', array_filter([
                                $doc['filiere_label_display'] ?? '',
                                $doc['licence_name'] ?? '',
                                $doc['matiere_display'] ?? '',
                            ]))) ?>
                        </p>
                    <?php endif; ?>
                </header>

                <?php if (!empty($doc['description'])): ?>
                    <div class="emsp-doc-description">
                        <?= nl2br(h($doc['description'] ?? '')) ?>
                    </div>
                <?php endif; ?>

                <?php if ($can_view): ?>
                    <div class="emsp-doc-actions">
                        <form method="post" action="<?= url('telecharger?id=' . $doc_id . '&download=1') ?>" class="d-inline">
                            <?= $csrf_field ?>
                            <button type="submit" class="emsp-btn emsp-btn-primary w-100">
                                <i class="bi bi-download"></i> Télécharger
                            </button>
                        </form>
                        <?php if ($inline_preview_available): ?>
                            <button class="emsp-btn emsp-btn-outline w-100" type="button"
                                    data-bs-toggle="modal" data-bs-target="#documentPreviewModal">
                                <i class="bi bi-eye"></i> Aperçu sur le site
                            </button>
                        <?php endif; ?>

                        <?php if ($isAuth): ?>
                            <form method="post" action="<?= url('document?id=' . $doc_id) ?>" class="d-inline">
                                <?= $csrf_field ?>
                                <input type="hidden" name="fav_action" value="<?= $is_fav ? 'remove' : 'add' ?>">
                                <button type="submit" class="emsp-btn emsp-btn-outline w-100">
                                    <i class="bi <?= $is_fav ? 'bi-star-fill' : 'bi-star' ?>"></i> <?= $is_fav ? 'Retirer des favoris' : 'Favoris' ?>
                                </button>
                            </form>
                            <form method="post" action="<?= url('document?id=' . $doc_id) ?>" class="d-inline">
                                <?= $csrf_field ?>
                                <input type="hidden" name="like_action" value="<?= $is_liked ? 'remove' : 'add' ?>">
                                <button type="submit" class="emsp-btn emsp-btn-outline w-100">
                                    <i class="bi <?= $is_liked ? 'bi-heart-fill' : 'bi-heart' ?>"></i> <?= $is_liked ? 'Je n\'aime plus' : 'J\'aime' ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <div class="emsp-doc-share">
                        <p class="emsp-doc-section-label">Partager</p>
                        <input type="text" class="emsp-input" readonly value="<?= h($share_url) ?>" onclick="this.select()">
                    </div>
                <?php endif; ?>
            </aside>
        </div>

        <section class="emsp-doc-comments emsp-motion-item" id="commentaires">
            <h2 class="emsp-doc-comments-title"><?= (int) $cmt_count ?> commentaire<?= (int) $cmt_count !== 1 ? 's' : '' ?></h2>

            <?php if ($isAuth): ?>
                <form method="post" action="<?= url('document?id=' . $doc_id . '#commentaires') ?>" class="emsp-doc-comment-form mb-4 pb-4 border-bottom">
                    <?= $csrf_field ?>
                    <textarea name="comment_content" class="emsp-textarea mb-2" rows="3" placeholder="Écrire un commentaire..." required></textarea>
                    <button type="submit" class="emsp-btn emsp-btn-primary btn-sm">Publier</button>
                </form>
            <?php else: ?>
                <p class="text-muted mb-4"><a href="<?= url('login') ?>">Connecte-toi</a> pour commenter.</p>
            <?php endif; ?>

            <?php foreach ($comments as $c): $cid = (int) $c['id']; $authorId = (int) ($c['author_id'] ?? 0); ?>
                <article class="emsp-doc-comment emsp-comment-row" id="comment-<?= $cid ?>">
                    <div class="emsp-comment-row__avatar">
                        <?= emsp_render_comment_author_link(
                            $authorId,
                            emsp_render_comment_avatar(
                                (string) ($c['photo_path'] ?? ''),
                                (string) ($c['first_name'] ?? ''),
                                (string) ($c['last_name'] ?? ''),
                                trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''))
                            ),
                            'emsp-comment-author-link--avatar'
                        ) ?>
                    </div>
                    <div class="emsp-comment-row__body">
                        <p class="emsp-comment-row__head mb-1">
                            <?= emsp_render_comment_author_link(
                                $authorId,
                                '<span class="emsp-doc-comment-author emsp-comment-author">' . h(trim($c['first_name'] . ' ' . $c['last_name'])) . '</span>'
                            ) ?>
                            <span class="emsp-doc-comment-date emsp-comment-date">· <?= h(time_ago((string) $c['created_at'])) ?></span>
                        </p>
                        <p class="emsp-comment-row__content mb-2"><?= nl2br(h($c['content'])) ?></p>

                    <?php foreach (($replies_by_cmt[$cid] ?? []) as $r): $replyAuthorId = (int) ($r['author_id'] ?? 0); ?>
                        <div class="emsp-doc-comment-reply emsp-comment-reply">
                            <div class="emsp-comment-row__avatar emsp-comment-row__avatar--sm">
                                <?= emsp_render_comment_author_link(
                                    $replyAuthorId,
                                    emsp_render_comment_avatar(
                                        (string) ($r['photo_path'] ?? ''),
                                        (string) ($r['first_name'] ?? ''),
                                        (string) ($r['last_name'] ?? '')
                                    ),
                                    'emsp-comment-author-link--avatar'
                                ) ?>
                            </div>
                            <div class="emsp-comment-row__body">
                                <p class="emsp-comment-row__head mb-1">
                                    <?= emsp_render_comment_author_link(
                                        $replyAuthorId,
                                        '<span class="emsp-doc-comment-author emsp-comment-author">' . h(trim($r['first_name'] . ' ' . $r['last_name'])) . '</span>'
                                    ) ?>
                                    <span class="emsp-doc-comment-date emsp-comment-date">· <?= h(time_ago((string) $r['created_at'])) ?></span>
                                </p>
                                <p class="emsp-comment-row__content mb-0"><?= nl2br(h($r['content'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($isAuth): ?>
                        <form method="post" action="<?= url('document?id=' . $doc_id . '#commentaires') ?>" class="mt-2">
                            <?= $csrf_field ?>
                            <input type="hidden" name="reply_comment_id" value="<?= $cid ?>">
                            <div class="d-flex gap-2 flex-wrap">
                                <input type="text" name="reply_content" class="emsp-input flex-grow-1" placeholder="Répondre..." required>
                                <button type="submit" class="emsp-btn emsp-btn-outline btn-sm">Répondre</button>
                            </div>
                        </form>
                    <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>

            <?php if (!empty($cmt_pagination) && (int) ($cmt_pagination['totalPages'] ?? 1) > 1): ?>
                <?php emsp_include_pagination(
                    $cmt_pagination,
                    'document',
                    ['id' => $doc_id],
                    'cmt_page'
                ); ?>
            <?php endif; ?>
        </section>

    </div>

    <?php if ($can_view && $inline_preview_available): ?>
        <div class="modal fade emsp-doc-preview-modal" id="documentPreviewModal" tabindex="-1"
             aria-labelledby="documentPreviewModalTitle" aria-hidden="true"
             data-bs-backdrop="true" data-bs-keyboard="true"
             data-emsp-motion-modal="1">
            <div class="modal-dialog modal-dialog-centered emsp-doc-preview-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="emsp-doc-preview-modal-head">
                            <span class="emsp-doc-preview-kicker">Aperçu du document</span>
                            <h2 class="modal-title" id="documentPreviewModalTitle"><?= h($doc['title'] ?? 'Document') ?></h2>
                        </div>
                        <button type="button" class="btn-close emsp-modal-close-touch" data-bs-dismiss="modal" aria-label="Fermer l'aperçu"></button>
                    </div>
                    <div class="modal-body emsp-doc-preview-body">
                        <?php if ($is_pdf_document && $pdf_inline_url !== ''): ?>
                            <div class="emsp-pdf-preview-wrap emsp-pdf-preview-wrap--modal">
                                <div class="emsp-pdf-preview-loading" aria-live="polite">
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    Chargement de l'aperçu…
                                </div>
                                <iframe
                                    title="<?= h($doc['title'] ?? 'Aperçu PDF') ?>"
                                    class="emsp-doc-preview-frame emsp-inline-pdf-frame"
                                    data-emsp-pdf-preview="1"
                                    data-pdf-data-url="<?= h(url('telecharger?id=' . $doc_id . '&pdfdata=1')) ?>"
                                    data-preview-url="<?= h($pdf_inline_url) ?>"
                                    data-fallback-thumb="<?= h($preview_image_url) ?>"
                                ></iframe>
                            </div>
                            <p class="emsp-doc-preview-note">Défilez le document dans la fenêtre — aucun téléchargement n'est lancé.</p>
                        <?php elseif ($is_image && $inline_preview_url !== ''): ?>
                            <figure class="emsp-doc-preview-figure">
                                <img src="<?= h($inline_preview_url) ?>" alt="<?= h($doc['title'] ?? 'Document') ?>" class="emsp-doc-preview-image">
                                <figcaption>Aperçu image — sans téléchargement.</figcaption>
                            </figure>
                        <?php elseif ($preview_image_url !== ''): ?>
                            <figure class="emsp-doc-preview-figure">
                                <img src="<?= h($preview_image_url) ?>" alt="<?= h($doc['title'] ?? 'Document') ?>" class="emsp-doc-preview-image">
                                <figcaption>Première page du document — sans téléchargement.</figcaption>
                            </figure>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer emsp-doc-preview-footer d-md-none">
                        <button type="button" class="emsp-btn emsp-btn-outline w-100" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg" aria-hidden="true"></i> Fermer l'aperçu
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>
