<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\Admin\CommentModerationRepository;

final class CommentModerationController extends AdminController
{
    private const ALLOWED_STATUSES = ['visible', 'hidden', 'pending', 'deleted'];
    private const SOURCE_LABELS = [
        'document_comment' => 'Commentaires documents',
        'document_reply' => 'Réponses documents',
        'journal_comment' => 'Commentaires journal',
    ];

    public function index(): void
    {
        [$con] = $this->guard();
        $repo = new CommentModerationRepository($con);

        $status = trim((string) ($_GET['status'] ?? 'visible'));
        if ($status !== '' && !in_array($status, self::ALLOWED_STATUSES, true)) {
            $status = 'visible';
        }
        $source = trim((string) ($_GET['source'] ?? ''));
        if ($source !== '' && !isset(self::SOURCE_LABELS[$source])) {
            $source = '';
        }
        $perPage = emsp_per_page_from_request(20);
        $pageNum = max(1, (int) ($_GET['page'] ?? 1));

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action'], $_POST['item_id'], $_POST['source_type'])) {
            if (!verify_csrf($_POST['csrf_token'] ?? null)) {
                flash('danger', 'Token de sécurité invalide.');
            } elseif ($repo->updateStatus((string) $_POST['source_type'], (int) $_POST['item_id'], (string) $_POST['action'])) {
                flash('info', "Le statut de l'élément a été mis à jour.");
            } else {
                flash('danger', 'Impossible de mettre à jour cet élément.');
            }
            redirect('admin/moderation-commentaires?' . http_build_query(['status' => $status, 'source' => $source, 'page' => $pageNum]));
        }

        [, $total] = $repo->list($status, $source, 1, 0);
        $pagination = emsp_paginate($total, $pageNum, $perPage);
        [$items, , $sourceCounts] = $repo->list($status, $source, $pagination['perPage'], $pagination['offset']);

        $this->view('admin/moderation/comments', [
            'items' => $items,
            'total' => $total,
            'sourceCounts' => $sourceCounts,
            'sourceLabels' => self::SOURCE_LABELS,
            'fStatus' => $status,
            'fSource' => $source,
            'pageNum' => $pagination['page'],
            'totalPages' => $pagination['totalPages'],
            'pagination' => $pagination,
        ]);
    }
}
