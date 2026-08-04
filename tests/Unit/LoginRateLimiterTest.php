<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Security\LoginRateLimiter;
use PDO;
use PHPUnit\Framework\TestCase;

final class LoginRateLimiterTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $this->pdo->exec(
            'CREATE TABLE login_attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip_address TEXT NOT NULL,
                created_at TEXT NOT NULL
            )'
        );
    }

    public function testIsBlockedAfterFiveFailuresInWindow(): void
    {
        $ip = '192.168.1.50';
        $now = date('Y-m-d H:i:s');
        $ins = $this->pdo->prepare(
            'INSERT INTO login_attempts (ip_address, created_at) VALUES (?, ?)'
        );
        for ($i = 0; $i < 5; $i++) {
            $ins->execute([$ip, $now]);
        }

        $limiter = new LoginRateLimiter($this->pdo);
        $this->assertTrue($limiter->isBlocked($ip));
    }

    public function testClearForIpRemovesBlock(): void
    {
        $ip = '10.0.0.9';
        $now = date('Y-m-d H:i:s');
        $ins = $this->pdo->prepare(
            'INSERT INTO login_attempts (ip_address, created_at) VALUES (?, ?)'
        );
        for ($i = 0; $i < 5; $i++) {
            $ins->execute([$ip, $now]);
        }

        $limiter = new LoginRateLimiter($this->pdo);
        $this->assertTrue($limiter->isBlocked($ip));

        $limiter->clearForIp($ip);
        $this->assertFalse($limiter->isBlocked($ip));
    }
}
