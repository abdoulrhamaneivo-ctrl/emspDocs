<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\LegacyDb;
use App\Repositories\Admin\JournalAdminRepository;

final class JournalController extends AdminController
{
    private const ALLOWED_TYPES = ['annonce', 'defi', 'sondage'];
    private const ALLOWED_STATUS = ['published', 'draft'];
    private const ALLOWED_STATES = ['open', 'scheduled', 'closed', 'expired', 'draft'];

    public function index(): void
    {
        [$con, $adminUser] = $this->guard();
        LegacyDb::journalHelpers();
        $journal = new JournalAdminRepository($con);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handlePost($journal, (int) $adminUser['id']);
            return;
        }

        if (isset($_GET['details_id']) && strtolower((string) ($_GET['format'] ?? '')) === 'json') {
            $this->jsonDetails((int) $_GET['details_id']);
            return;
        }
        if (isset($_GET['export_defi_id'])) {
            $this->exportDefiCsv((int) $_GET['export_defi_id']);
            return;
        }

        $typeFilter = in_array(strtolower((string) ($_GET['type'] ?? '')), self::ALLOWED_TYPES, true) ? strtolower((string) $_GET['type']) : '';
        $statusFilter = in_array(strtolower((string) ($_GET['status'] ?? '')), self::ALLOWED_STATUS, true) ? strtolower((string) $_GET['status']) : '';
        $stateFilter = in_array(strtolower((string) ($_GET['state'] ?? '')), self::ALLOWED_STATES, true) ? strtolower((string) $_GET['state']) : '';

        $articles = $journal->list($typeFilter, $statusFilter, $stateFilter);

        $this->view('admin/journal/index', [
            'articles' => $articles,
            'typeFilter' => $typeFilter,
            'statusFilter' => $statusFilter,
            'stateFilter' => $stateFilter,
            'hasClosedAt' => $journal->hasClosedAt,
            'hasClosedBy' => $journal->hasClosedBy,
        ]);
    }

    private function handlePost(JournalAdminRepository $journal, int $adminId): void
    {
        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            flash('danger', 'Token de sécurité invalide.');
            $this->redirectWithFilters();
        }

        if (isset($_POST['toggle_id'], $_POST['new_status'])) {
            $id = (int) $_POST['toggle_id'];
            $newStatus = strtolower(trim((string) $_POST['new_status']));
            if ($id > 0 && in_array($newStatus, self::ALLOWED_STATUS, true)) {
                $result = $journal->toggleStatus($id, $newStatus);
                if ($result['notify']) {
                    $notifService = new \App\Services\NotificationService();
                    $notifService->notifyJournalPublished($id, $result['title'], $adminId);
                }
                flash('info', 'Le statut de publication a été modifié.');
            }
            $this->redirectWithFilters();
        }

        if (isset($_POST['close_id']) && $journal->hasClosedAt && $journal->hasClosedBy) {
            $id = (int) $_POST['close_id'];
            if ($id > 0) {
                $journal->close($id, $adminId);
                flash('info', 'Le sondage ou le défi a été fermé manuellement.');
            }
            $this->redirectWithFilters();
        }

        if (isset($_POST['reopen_id']) && $journal->hasClosedAt && $journal->hasClosedBy) {
            $id = (int) $_POST['reopen_id'];
            if ($id > 0) {
                $journal->reopen($id);
                flash('info', "Le contenu redevient interactif si sa date de fin n'est pas dépassée.");
            }
            $this->redirectWithFilters();
        }

        if (isset($_POST['delete_id'])) {
            $id = (int) $_POST['delete_id'];
            if ($id > 0) {
                $journal->delete($id);
                flash('info', "L'article a été supprimé définitivement.");
            }
            $this->redirectWithFilters();
        }

        $this->redirectWithFilters();
    }

    private function redirectWithFilters(): void
    {
        $qs = [];
        foreach (['type', 'status', 'state'] as $key) {
            if (!empty($_GET[$key])) {
                $qs[$key] = (string) $_GET[$key];
            }
        }
        redirect('admin/journal' . ($qs ? '?' . http_build_query($qs) : ''));
    }

    private function jsonDetails(int $id): void
    {
        $con = LegacyDb::mysqli();
        $details = $id > 0 ? emsp_journal_fetch_admin_details($con, $id) : null;
        header('Content-Type: application/json; charset=UTF-8');
        if (!$details) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'notfound']);
            exit;
        }
        $details['article']['content_html'] = emsp_journal_clean_html((string) ($details['article']['content'] ?? ''));
        echo json_encode(['ok' => true, 'details' => $details], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function exportDefiCsv(int $id): void
    {
        $con = LegacyDb::mysqli();
        $details = $id > 0 ? emsp_journal_fetch_admin_details($con, $id) : null;
        if (!$details || ($details['type'] ?? '') !== 'defi') {
            flash('danger', 'Le défi demandé est introuvable.');
            $this->redirectWithFilters();
        }

        $safeName = preg_replace('/[^a-z0-9]+/i', '-', strtolower((string) ($details['article']['title'] ?? 'defi')));
        $safeName = trim((string) $safeName, '-');
        if ($safeName === '') {
            $safeName = 'defi';
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="participants-' . $safeName . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Nom', 'Email', 'Note', 'Date']);
        foreach (($details['defi']['participants'] ?? []) as $participant) {
            fputcsv($out, [
                (string) ($participant['name'] ?? ''),
                (string) ($participant['email'] ?? ''),
                (string) ($participant['note'] ?? ''),
                (string) ($participant['created_at_label'] ?? ''),
            ]);
        }
        fclose($out);
        exit;
    }

    public function form(): void
    {
        [$con] = $this->guard();
        LegacyDb::journalHelpers();
        $journal = new JournalAdminRepository($con);

        $articleId = (int) ($_GET['id'] ?? 0);
        $editing = $articleId > 0;

        $form = ['title' => '', 'content' => '', 'type' => 'annonce', 'status' => 'draft', 'starts_at' => '', 'ends_at' => ''];
        $pollOptionsRaw = '';
        $currentType = '';
        $existingVoteCount = 0;
        $existingDefiCount = 0;

        if ($editing) {
            $row = $journal->findForEdit($articleId);
            if (!$row) {
                flash('warning', 'Article introuvable.');
                redirect('admin/journal');
            }
            $currentType = (string) $row['type'];
            $form['title'] = emsp_fix_mojibake((string) $row['title']);
            $form['content'] = emsp_fix_mojibake((string) $row['content']);
            $form['type'] = $currentType;
            $form['status'] = (string) $row['status'];
            if ($journal->hasLifecycle) {
                $form['starts_at'] = emsp_journal_datetime_local_value((string) ($row['starts_at'] ?? ''));
                $form['ends_at'] = emsp_journal_datetime_local_value((string) ($row['ends_at'] ?? ''));
            }

            if ($currentType === 'sondage') {
                $pollOptionsRaw = implode("\n", $journal->pollOptions($articleId));
                $existingVoteCount = $journal->countVotes($articleId);
            }
            if ($currentType === 'defi') {
                $existingDefiCount = $journal->countDefis($articleId);
            }
        }

        $this->view('admin/journal/form', [
            'id' => $articleId,
            'editing' => $editing,
            'form' => $form,
            'pollOptionsRaw' => $pollOptionsRaw,
            'currentType' => $currentType,
            'existingVoteCount' => $existingVoteCount,
            'existingDefiCount' => $existingDefiCount,
            'hasLifecycle' => $journal->hasLifecycle,
            'errors' => $_SESSION['journal_form_errors'] ?? [],
        ]);
        unset($_SESSION['journal_form_errors']);
    }

    public function save(): void
    {
        [$con, $adminUser] = $this->guard();
        LegacyDb::journalHelpers();
        $journal = new JournalAdminRepository($con);

        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            flash('danger', 'Token de sécurité invalide.');
            redirect('admin/journal');
        }

        $articleId = (int) ($_POST['id'] ?? 0);
        $editing = $articleId > 0;

        $title = trim((string) ($_POST['title'] ?? ''));
        $contentRaw = (string) ($_POST['content'] ?? '');
        $type = strtolower(trim((string) ($_POST['type'] ?? 'annonce')));
        $submitAction = strtolower(trim((string) ($_POST['submit_action'] ?? 'draft')));
        $status = $submitAction === 'publish' ? 'published' : 'draft';
        $pollOptionsRaw = trim((string) ($_POST['poll_options'] ?? ''));
        $confirmReset = !empty($_POST['confirm_reset_results']);

        $startsAt = $journal->hasLifecycle && in_array($type, ['sondage', 'defi'], true)
            ? emsp_journal_parse_datetime_input((string) ($_POST['starts_at'] ?? '')) : null;
        $endsAt = $journal->hasLifecycle && in_array($type, ['sondage', 'defi'], true)
            ? emsp_journal_parse_datetime_input((string) ($_POST['ends_at'] ?? '')) : null;

        $pollOptions = [];
        if ($type === 'sondage' && $pollOptionsRaw !== '') {
            foreach (preg_split('/\r\n|\n|\r/', $pollOptionsRaw) as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $pollOptions[] = $line;
                }
            }
        }

        $contentClean = emsp_journal_clean_html($contentRaw);
        $contentPlain = trim(strip_tags($contentClean));

        $errors = [];
        if ($title === '') {
            $errors[] = 'Le titre est obligatoire.';
        }
        if ($contentPlain === '') {
            $errors[] = 'Le contenu est obligatoire.';
        }
        if (mb_strlen($contentClean) > 65535) {
            $errors[] = 'Le contenu est trop long (max 65535 caractères).';
        }
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            $errors[] = 'Type invalide.';
        }
        if ($type === 'sondage' && count($pollOptions) < 2) {
            $errors[] = 'Le sondage doit avoir au moins 2 options.';
        }
        if ($journal->hasLifecycle && $startsAt !== null && $endsAt !== null && strtotime($endsAt) <= strtotime($startsAt)) {
            $errors[] = 'La date de fin doit être postérieure à la date de début.';
        }

        $currentType = '';
        $existingStatus = '';
        if ($editing) {
            $row = $journal->findForEdit($articleId);
            $currentType = (string) ($row['type'] ?? '');
            $existingStatus = (string) ($row['status'] ?? '');

            $currentOptions = $currentType === 'sondage' ? $journal->pollOptions($articleId) : [];
            $optionsChanged = ($type === 'sondage' || $currentType === 'sondage')
                && array_map('trim', $currentOptions) !== array_map('trim', $pollOptions);
            $willResetVotes = $currentType === 'sondage' && ($type !== 'sondage' || $optionsChanged);
            $willResetDefis = $currentType === 'defi' && $type !== 'defi';

            if ($willResetVotes && $journal->countVotes($articleId) > 0 && !$confirmReset) {
                $errors[] = 'Confirmez la réinitialisation des votes avant de modifier ce sondage.';
            }
            if ($willResetDefis && $journal->countDefis($articleId) > 0 && !$confirmReset) {
                $errors[] = 'Confirmez la réinitialisation des participations avant de modifier ce défi.';
            }
        }

        if (!empty($errors)) {
            $_SESSION['journal_form_errors'] = $errors;
            redirect('admin/journal/form' . ($editing ? '?id=' . $articleId : ''));
        }

        $result = $journal->save([
            'editing' => $editing,
            'id' => $articleId,
            'type' => $type,
            'title' => $title,
            'content' => $contentClean,
            'status' => $status,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'poll_options' => $pollOptions,
            'current_type' => $currentType,
            'existing_status' => $existingStatus,
            'author_id' => (int) $adminUser['id'],
        ]);

        if (!$result['ok']) {
            $_SESSION['journal_form_errors'] = ["Impossible d'enregistrer le contenu pour le moment."];
            redirect('admin/journal/form' . ($editing ? '?id=' . $articleId : ''));
        }

        if ($result['notify']) {
            $notifService = new \App\Services\NotificationService();
            $notifService->notifyJournalPublished((int) $result['id'], $result['title'], (int) $adminUser['id']);
        }

        flash('success', $result['status'] === 'published'
            ? ($editing ? 'Article mis à jour et publié.' : 'Article publié avec succès.')
            : 'Brouillon enregistré.');
        redirect('admin/journal');
    }
}
