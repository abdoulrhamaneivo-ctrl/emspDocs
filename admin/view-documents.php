<?php

declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';

// Legacy CoreUI page retired: every administrative document flow now uses
// the same modern administration shell.
redirect('admin/validation-documents', 302);
