<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\LegacyDb;

final class HomeController extends Controller
{
    public function index(): void
    {
        $con = LegacyDb::mysqli();
        $rootDir = dirname(__DIR__, 2);
        require_once $rootDir . '/includes/content-helpers.php';
        require_once $rootDir . '/includes/formations-helpers.php';
        require_once $rootDir . '/includes/services/home-data.php';

        $homeViewModel = emsp_home_build_view_model($con, $_SESSION, $rootDir);

        $this->view('home/index', $homeViewModel);
    }
}
