<?php

declare(strict_types=1);

namespace Tests\Unit;

use Erp\Notifications\Config\MailConfig;
use PHPUnit\Framework\TestCase;

final class MailConfigTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_ENV['MAIL_ENABLED'], $_SERVER['MAIL_ENABLED']);
        unset($_ENV['MAIL_HOST'], $_SERVER['MAIL_HOST']);
        unset($_ENV['MAIL_USERNAME'], $_SERVER['MAIL_USERNAME']);
        unset($_ENV['MAIL_PASSWORD'], $_SERVER['MAIL_PASSWORD']);
        unset($_ENV['MAIL_FROM'], $_SERVER['MAIL_FROM']);
        parent::tearDown();
    }

    public function testFromEnvReadsBooleanFlag(): void
    {
        $_ENV['MAIL_ENABLED'] = 'true';
        $_ENV['MAIL_HOST'] = 'sandbox.smtp.mailtrap.io';
        $_ENV['MAIL_USERNAME'] = 'user';
        $_ENV['MAIL_PASSWORD'] = 'pass';
        $_ENV['MAIL_FROM'] = 'noreply@example.com';

        $config = MailConfig::resolve();

        $this->assertTrue($config->enabled);
        $this->assertTrue($config->isConfigured());
    }

    public function testIsConfiguredRequiresCredentials(): void
    {
        $config = new MailConfig(
            enabled: true,
            host: '',
            port: 2525,
            username: '',
            password: '',
            encryption: 'tls',
            fromAddress: '',
            fromName: '',
            adminTo: ''
        );

        $this->assertFalse($config->isConfigured());
    }
}
