<?php
// admin/school-domains.php — page migrée vers /admin/domaines-email (SchoolDomainsController).
require __DIR__ . '/../config/bootstrap.php';
redirect('admin/domaines-email', 301);
