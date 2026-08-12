<?php
// admin/settings.php — page migrée vers /admin/parametres (SettingsController).
require __DIR__ . '/../config/bootstrap.php';
redirect('admin/parametres', 301);
