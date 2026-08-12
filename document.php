<?php

declare(strict_types=1);

require __DIR__ . '/config/bootstrap.php';

$id = max(0, (int) ($_GET['id'] ?? 0));
redirect('document' . ($id > 0 ? '?id=' . $id : ''), 302);
