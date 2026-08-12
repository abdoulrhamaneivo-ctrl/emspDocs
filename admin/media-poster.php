<?php
// admin/media-poster.php — endpoint migré vers /admin/mediatheque/poster (MediaController).
require __DIR__ . '/../config/bootstrap.php';
(new \App\Controllers\Admin\MediaController())->poster();
