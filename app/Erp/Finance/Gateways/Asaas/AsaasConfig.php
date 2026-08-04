<?php

declare(strict_types=1);

namespace Erp\Finance\Gateways\Asaas;

use Core\Database\TenantSettingsProvider;

final class AsaasConfig
{
    private const SANDBOX_BASE_URL = 'https://api-sandbox.asaas.com/v3';
    private const PRODUCTION_BASE_URL = 'https://api.asaas.com/v3';

    public static function isEnabled(): bool
    {
        $tenant = TenantSettingsProvider::asaas();
        if ($tenant !== null) {
            return ($tenant['enabled'] ?? false) && trim((string) ($tenant['api_key'] ?? '')) !== '';
        }

        return filter_var(env('ASAAS_ENABLED', 'false'), FILTER_VALIDATE_BOOL)
            && trim((string) env('ASAAS_API_KEY', '')) !== '';
    }

    public static function apiKey(): string
    {
        $tenant = TenantSettingsProvider::asaas();
        if ($tenant !== null) {
            return trim((string) ($tenant['api_key'] ?? ''));
        }

        return trim((string) env('ASAAS_API_KEY', ''));
    }

    public static function webhookToken(): string
    {
        $tenant = TenantSettingsProvider::asaas();
        if ($tenant !== null) {
            return trim((string) ($tenant['webhook_token'] ?? ''));
        }

        return trim((string) env('ASAAS_WEBHOOK_TOKEN', ''));
    }

    public static function baseUrl(): string
    {
        $tenant = TenantSettingsProvider::asaas();
        if ($tenant !== null) {
            return ($tenant['sandbox'] ?? true) ? self::SANDBOX_BASE_URL : self::PRODUCTION_BASE_URL;
        }

        $sandbox = filter_var(env('ASAAS_SANDBOX', 'true'), FILTER_VALIDATE_BOOL);

        return $sandbox ? self::SANDBOX_BASE_URL : self::PRODUCTION_BASE_URL;
    }

    public static function pixDueDays(): int
    {
        $tenant = TenantSettingsProvider::asaas();
        if ($tenant !== null) {
            return max(1, min((int) ($tenant['pix_due_days'] ?? 3), 30));
        }

        $days = (int) env('ASAAS_PIX_DUE_DAYS', 3);

        return max(1, min($days, 30));
    }

    public static function userAgent(): string
    {
        return trim((string) env('ASAAS_USER_AGENT', 'ErpCommerce/1.0'));
    }
}
