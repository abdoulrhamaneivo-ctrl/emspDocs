<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Controller;
use App\Core\LegacyDb;
use App\Repositories\HistoryRepository;

final class HistoryController extends Controller
{
    public function index(): void
    {
        require_auth();
        $con = LegacyDb::mysqli();
        $uid = (int) (current_user()['id'] ?? 0);

        $filter = trim((string) ($_GET['action'] ?? ''));
        $pageNum = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 15;

        $history = new HistoryRepository(Database::pdo());
        $total = $history->countForUser($uid, $filter);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $pageNum = min($pageNum, $totalPages);
        $offset = ($pageNum - 1) * $perPage;

        $rows = $history->listForUser($uid, $filter, $perPage, $offset);

        $this->view('history/index', [
            'history' => $rows,
            'filter' => $filter,
            'pageNum' => $pageNum,
            'totalPages' => $totalPages,
        ]);
    }
}
