<?php

declare(strict_types=1);

namespace Tests\Unit;

use Erp\Auth\Services\AuthService;
use Core\Security\LoginRateLimiter;
use PDO;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['REMOTE_ADDR']);
    }

    public function testAttemptLoginRejectsEmptyCredentials(): void
    {
        $auth = new AuthService();
        $this->assertFalse($auth->attemptLogin('', ''));
        $this->assertFalse($auth->attemptLogin('a@b.com', ''));
    }

    /**
     * Com 5 falhas registradas no limitador, a 6ª tentativa falha antes do banco (bloqueio genérico).
     */
    public function testAttemptLoginBlockedByRateLimiter(): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.7';

        $pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec(
            'CREATE TABLE login_attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip_address TEXT NOT NULL,
                created_at TEXT NOT NULL
            )'
        );
        $now = date('Y-m-d H:i:s');
        $ins = $pdo->prepare(
            'INSERT INTO login_attempts (ip_address, created_at) VALUES (?, ?)'
        );
        for ($i = 0; $i < 5; $i++) {
            $ins->execute([$_SERVER['REMOTE_ADDR'], $now]);
        }

        $limiter = new LoginRateLimiter($pdo);
        $auth = new AuthService($limiter);

        $this->assertFalse($auth->attemptLogin('qualquer@teste.com', 'qualquer'));
    }
}
