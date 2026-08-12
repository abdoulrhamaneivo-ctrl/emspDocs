<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0"><i class="bi bi-newspaper text-primary me-2"></i><?= $editing ? 'Modifier le contenu' : 'Nouveau contenu du journal' ?></h1>
    <a href="<?= url('admin/journal') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="card" style="max-width:760px;">
    <div class="card-body">
        <form method="post" action="<?= url('admin/journal/form') ?>" id="journalForm">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $id ?>">

            <div class="mb-3">
                <label class="form-label fw-semibold">Type de contenu</label>
                <select class="form-select" name="type" id="journalType" <?= $editing ? '' : '' ?>>
                    <option value="annonce" <?= $form['type'] === 'annonce' ? 'selected' : '' ?>>Annonce</option>
                    <option value="defi" <?= $form['type'] === 'defi' ? 'selected' : '' ?>>Défi</option>
                    <option value="sondage" <?= $form['type'] === 'sondage' ? 'selected' : '' ?>>Sondage</option>
                </select>
                <?php if ($editing && $currentType === 'sondage' && $existingVoteCount > 0): ?>
                    <div class="form-text text-warning">Ce sondage a déjà <?= $existingVoteCount ?> vote(s). Changer le type ou les options réinitialisera les résultats.</div>
                <?php endif; ?>
                <?php if ($editing && $currentType === 'defi' && $existingDefiCount > 0): ?>
                    <div class="form-text text-warning">Ce défi a déjà <?= $existingDefiCount ?> participation(s). Changer le type réinitialisera les participations.</div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Titre <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="title" required value="<?= h($form['title']) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Contenu (HTML) <span class="text-danger">*</span></label>
                <textarea class="form-control" name="content" rows="8" required><?= h($form['content']) ?></textarea>
                <div class="form-text">
                    Balises HTML simples acceptées. L'éditeur visuel riche de l'original (Quill.js) n'a pas été
                    repris ici — voir README_PHASE5H_JOURNAL.md.
                </div>
            </div>

            <?php if ($hasLifecycle): ?>
                <div class="row g-3 mb-3" id="lifecycleFields">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Début</label>
                        <input type="datetime-local" class="form-control" name="starts_at" value="<?= h($form['starts_at']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Fin</label>
                        <input type="datetime-local" class="form-control" name="ends_at" value="<?= h($form['ends_at']) ?>">
                    </div>
                </div>
            <?php endif; ?>

            <div class="mb-3" id="pollOptionsField">
                <label class="form-label fw-semibold">Options du sondage (une par ligne, 2 minimum)</label>
                <textarea class="form-control" name="poll_options" rows="4"><?= h($pollOptionsRaw) ?></textarea>
            </div>

            <?php if ($editing && (($currentType === 'sondage' && $existingVoteCount > 0) || ($currentType === 'defi' && $existingDefiCount > 0))): ?>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="confirm_reset_results" value="1" id="confirmReset">
                    <label class="form-check-label small" for="confirmReset">
                        Je confirme vouloir réinitialiser les résultats/participations existants si je change le type ou les options.
                    </label>
                </div>
            <?php endif; ?>

            <div class="emsp-admin-sticky-actions d-flex gap-2">
                <button type="submit" name="submit_action" value="draft" class="btn btn-outline-secondary">
                    <i class="bi bi-file-earmark me-1"></i>Enregistrer en brouillon
                </button>
                <button type="submit" name="submit_action" value="publish" class="btn btn-primary">
                    <i class="bi bi-upload me-1"></i>Publier
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var typeSelect = document.getElementById('journalType');
    var pollField = document.getElementById('pollOptionsField');
    function togglePollField() {
        pollField.style.display = typeSelect.value === 'sondage' ? '' : 'none';
    }
    typeSelect.addEventListener('change', togglePollField);
    togglePollField();
})();
</script>
