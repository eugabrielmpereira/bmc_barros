<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Security\Csrf;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        session_start();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    public function testTokenIsGeneratedAndStableInSameSession(): void
    {
        $a = Csrf::token();
        $b = Csrf::token();
        $this->assertNotSame('', $a);
        $this->assertSame($a, $b);
    }

    public function testValidatePostedFailsOnWrongToken(): void
    {
        $_SESSION['csrf'] = 'token_correto';
        $_POST = ['csrf_token' => 'outro'];

        $this->assertFalse(Csrf::validatePosted());
    }

    public function testValidatePostedSucceeds(): void
    {
        $t = Csrf::token();
        $_POST = ['csrf_token' => $t];

        $this->assertTrue(Csrf::validatePosted());
    }
}
