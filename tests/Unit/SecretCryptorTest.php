<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Security\SecretCryptor;
use PHPUnit\Framework\TestCase;

final class SecretCryptorTest extends TestCase
{
    /** @var array<string, string|null> */
    private array $originalEnv = [];

    protected function setUp(): void
    {
        $this->originalEnv['APP_KEY'] = $_ENV['APP_KEY'] ?? null;
        $_ENV['APP_KEY'] = 'test-app-key-for-secret-cryptor-unit-tests-32chars';
        $_SERVER['APP_KEY'] = $_ENV['APP_KEY'];
    }

    protected function tearDown(): void
    {
        if ($this->originalEnv['APP_KEY'] === null) {
            unset($_ENV['APP_KEY'], $_SERVER['APP_KEY']);
        } else {
            $_ENV['APP_KEY'] = $this->originalEnv['APP_KEY'];
            $_SERVER['APP_KEY'] = $this->originalEnv['APP_KEY'];
        }
    }

    public function testEncryptDecryptRoundTrip(): void
    {
        $plain = 'sk_live_test_api_key_12345';
        $encrypted = SecretCryptor::encrypt($plain);

        $this->assertTrue(SecretCryptor::isEncrypted($encrypted));
        $this->assertSame($plain, SecretCryptor::decrypt($encrypted));
    }

    public function testLegacyPlaintextPassesThroughDecrypt(): void
    {
        $this->assertSame('plain-secret', SecretCryptor::decrypt('plain-secret'));
    }

    public function testEmptyStringStaysEmpty(): void
    {
        $this->assertSame('', SecretCryptor::encrypt(''));
        $this->assertSame('', SecretCryptor::decrypt(''));
    }
}
