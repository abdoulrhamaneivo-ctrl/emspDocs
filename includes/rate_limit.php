<?php

declare(strict_types=1);

use App\Services\RateLimiterService;

function emsp_client_ip(): string
{
    return (new RateLimiterService())->clientIp();
}

function rate_limit_check(string $ip, mixed $con = null): array
{
    return (new RateLimiterService())->check($ip);
}

function rate_limit_record_failure(string $ip, mixed $con = null): void
{
    (new RateLimiterService())->recordFailure($ip);
}

function rate_limit_clear(string $ip, mixed $con = null): void
{
    (new RateLimiterService())->clear($ip);
}
