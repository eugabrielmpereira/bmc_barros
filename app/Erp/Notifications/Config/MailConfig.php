<?php

declare(strict_types=1);

namespace Erp\Notifications\Config;

use Core\Database\TenantSettingsProvider;

final class MailConfig
{
    public function __construct(
        public readonly bool $enabled,
        public readonly string $host,
        public readonly int $port,
        public readonly string $username,
        public readonly string $password,
        public readonly string $encryption,
        public readonly string $fromAddress,
        public readonly string $fromName,
        public readonly string $adminTo
    ) {
    }

    public static function resolve(): self
    {
        $tenant = TenantSettingsProvider::mail();
        if ($tenant !== null) {
            return new self(
                enabled: (bool) ($tenant['enabled'] ?? false),
                host: (string) ($tenant['host'] ?? ''),
                port: (int) ($tenant['port'] ?? 2525),
                username: (string) ($tenant['username'] ?? ''),
                password: (string) ($tenant['password'] ?? ''),
                encryption: (string) ($tenant['encryption'] ?? 'tls'),
                fromAddress: (string) ($tenant['from_address'] ?? ''),
                fromName: (string) ($tenant['from_name'] ?? ''),
                adminTo: (string) ($tenant['admin_to'] ?? '')
            );
        }

        return self::fromEnv();
    }

    public static function fromEnv(): self
    {
        $enabled = filter_var(env('MAIL_ENABLED', false), FILTER_VALIDATE_BOOLEAN);

        return new self(
            enabled: $enabled,
            host: trim((string) env('MAIL_HOST', '')),
            port: (int) env('MAIL_PORT', 2525),
            username: trim((string) env('MAIL_USERNAME', '')),
            password: trim((string) env('MAIL_PASSWORD', '')),
            encryption: strtolower(trim((string) env('MAIL_ENCRYPTION', 'tls'))),
            fromAddress: trim((string) env('MAIL_FROM', 'noreply@example.com')),
            fromName: trim((string) env('MAIL_FROM_NAME', '')),
            adminTo: trim((string) env('MAIL_ADMIN_TO', ''))
        );
    }

    public function isConfigured(): bool
    {
        return $this->host !== ''
            && $this->username !== ''
            && $this->password !== ''
            && $this->fromAddress !== '';
    }
}
