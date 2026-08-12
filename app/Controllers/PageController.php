<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Controller;
use App\Core\LegacyDb;
use App\Repositories\DocumentRepository;

final class PageController extends Controller
{
    public function faq(): void
    {
        $this->view('pages/faq', []);
    }

    public function concours(): void
    {
        $con = LegacyDb::documentHelpers();
        $documents = new DocumentRepository(Database::pdo());

        $yearFilter = (int) ($_GET['annee'] ?? 0);
        $search = trim((string) ($_GET['q'] ?? ''));

        $this->view('pages/concours', [
            'docs' => $documents->publicConcoursDocuments($yearFilter, $search),
            'years' => $documents->publicConcoursYears(),
            'yearFilter' => $yearFilter,
            'search' => $search,
        ]);
    }
}
