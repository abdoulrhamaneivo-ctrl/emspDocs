<?php
declare(strict_types=1);

// The legacy dark CoreUI dashboard has been retired. Keep this address for
// bookmarks, but send everyone to the unified modern administration shell.
require __DIR__ . '/../config/bootstrap.php';
redirect('admin/journal', 301);
