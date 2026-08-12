<?php

declare(strict_types=1);

// phpdotenv renseigne $_ENV (et non getenv() par défaut). La petite
// fermeture conserve aussi la compatibilité avec les variables injectées par
// un serveur d'hébergement.
$env = static function (string $key, string $default = ''): string {
    $value = $_ENV[$key] ?? getenv($key);
    return $value === false || $value === null || $value === '' ? $default : (string) $value;
};

return [
    'name' => 'EMSP Docs',
    'asset_version' => '20260813w',
    'base_url' => $env('APP_URL'),
    'timezone' => 'Africa/Abidjan',
    'env' => $env('APP_ENV', 'production'),

    // Repris tel quel de votre .env actuel (admin/config/config.php)
    'brevo_api_key' => $env('BREVO_API_KEY'),
    'brevo_from_email' => $env('BREVO_FROM_EMAIL'),
    'brevo_from_name' => $env('BREVO_FROM_NAME', 'EMSP Docs'),
    'debug_email' => filter_var($env('EMSP_DEBUG_EMAIL'), FILTER_VALIDATE_BOOLEAN),
    'school_email_domain' => $env('SCHOOL_EMAIL_DOMAIN'),
];
