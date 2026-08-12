<?php

if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token(): string
    {
        if (function_exists('csrf_token')) {
            return csrf_token();
        }

        include_once __DIR__ . '/bootstrap.php';

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return generate_csrf_token();
    }
}

function verify_csrf_token(): void
{
    if (function_exists('verify_csrf') && verify_csrf($_POST['csrf_token'] ?? null)) {
        return;
    }

    include_once __DIR__ . '/bootstrap.php';

    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $postToken = $_POST['csrf_token'] ?? '';

    if ($sessionToken === '' || $postToken === '' || !hash_equals($sessionToken, $postToken)) {
        $_SESSION['message'] = 'Action non autorisee (CSRF).';
        header('Location: index.php');
        exit;
    }
}

function csrf_input(): void
{
    $token = generate_csrf_token();
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '">';
}
