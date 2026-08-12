<?php
// admin/pending-documents.php — page migrée vers /admin/validation-documents
// (PendingDocumentsController::index).
require __DIR__ . '/../config/bootstrap.php';
redirect('admin/validation-documents', 301);
