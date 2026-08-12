<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\') === 0) {
        $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

require_once dirname(__DIR__) . '/includes/encoding.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/content-helpers.php';
require_once dirname(__DIR__) . '/includes/document-taxonomy.php';
require_once dirname(__DIR__) . '/includes/journal_helpers.php';
