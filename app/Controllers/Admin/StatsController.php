<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\Admin\StatsRepository;

final class StatsController extends AdminController
{
    public function index(): void
    {
        [$con] = $this->guard();
        $stats = new StatsRepository($con);

        $globals = $stats->globals();
        $actionsRequired = $globals['docs_pending'] + $globals['users_pending'];
        $approvalRate = $globals['docs_total'] > 0 ? (int) round(($globals['docs_approved'] * 100) / max(1, $globals['docs_total'])) : 0;
        $activityRate = $globals['users_total'] > 0 ? (int) round(($globals['users_active'] * 100) / max(1, $globals['users_total'])) : 0;

        $this->view('admin/stats/index', [
            's' => $globals,
            'actionsRequired' => $actionsRequired,
            'approvalRate' => $approvalRate,
            'activityRate' => $activityRate,
            'typesData' => $stats->docsByType(),
            'topUploaders' => $stats->topUploaders(),
            'topDocs' => $stats->topDocuments(),
            'uploadsMonthly' => $stats->uploadsMonthly(),
        ]);
    }
}
