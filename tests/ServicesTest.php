<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Services\RateLimiterService;
use App\Services\UploadService;
use App\Services\NotificationService;

class ServicesTest extends TestCase
{
    public function testRateLimiterEmptyIp(): void
    {
        $limiter = new RateLimiterService();
        $check = $limiter->check('');
        $this->assertFalse($check['blocked']);
        $this->assertSame(0, $check['retry_in']);
    }

    public function testRateLimiterClientIpFallback(): void
    {
        $limiter = new RateLimiterService();
        $ip = $limiter->clientIp();
        $this->assertIsString($ip);
        $this->assertNotEmpty($ip);
    }

    public function testUploadServiceMimeValidationInvalidFile(): void
    {
        $uploader = new UploadService();
        $dummyFile = [
            'name' => 'malicious.php',
            'type' => 'text/php',
            'tmp_name' => '/non/existent/path.php',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 1024,
        ];
        $result = $uploader->processDocument($dummyFile, 1);
        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testNotificationServiceUnreadCountForInvalidUser(): void
    {
        $notifService = new NotificationService();
        $count = $notifService->unreadCount(0);
        $this->assertSame(0, $count);
    }
}
