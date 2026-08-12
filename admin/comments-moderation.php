<?php
// admin/comments-moderation.php — page migrée vers /admin/moderation-commentaires
// (CommentModerationController::index).
require __DIR__ . '/../config/bootstrap.php';
$qs = $_GET ? '?' . http_build_query($_GET) : '';
redirect('admin/moderation-commentaires' . $qs, 301);
