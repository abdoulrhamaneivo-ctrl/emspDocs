<?php
// admin/media-categories.php — page migrée vers /admin/mediatheque/categories (MediaController).
require __DIR__ . '/../config/bootstrap.php';
redirect('admin/mediatheque/categories', 301);
