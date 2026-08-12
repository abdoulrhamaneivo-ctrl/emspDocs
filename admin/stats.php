<?php
// admin/stats.php — page migrée vers /admin/statistiques (StatsController).
require __DIR__ . '/../config/bootstrap.php';
redirect('admin/statistiques', 301);
