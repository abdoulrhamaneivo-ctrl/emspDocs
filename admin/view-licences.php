<?php
// admin/view-licences.php — page migrée vers /admin/licences (LicencesController).
require __DIR__ . '/../config/bootstrap.php';
redirect('admin/licences', 301);
