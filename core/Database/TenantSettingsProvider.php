<?php

declare(strict_types=1);

namespace Core\Database;

/**
 * Cache request-scoped de configuração tenant (Asaas/Mail) lida do mestre.
 */
final class TenantSettingsProvider
{
    /** @var array<string, mixed>|null */
    private static ?array $asaasCache = null;

    /** @var array<string, mixed>|null */
    private static ?array $mailCache = null;

    private static bool $asaasLoaded = false;

    private static bool $mailLoaded = false;

    public static function resetForRequest(): void
    {
        self::$asaasCache = null;
        self::$mailCache = null;
        self::$asaasLoaded = false;
        self::$mailLoaded = false;
    }

    /**
     * @return array<string, mixed>|null null = sem registro no mestre (usar .env)
     */
    public static function asaas(): ?array
    {
        if (!\Core\Database::isMultitenantEnabled()) {
            return null;
        }

        if (self::$asaasLoaded) {
            return self::$asaasCache;
        }

        self::$asaasLoaded = true;
        $clientKey = self::currentClientKey();
        if ($clientKey === null) {
            return null;
        }

        self::$asaasCache = (new TenantSettingsRepository())->findAsaasByClientKey($clientKey);

        return self::$asaasCache;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function mail(): ?array
    {
        if (!\Core\Database::isMultitenantEnabled()) {
            return null;
        }

        if (self::$mailLoaded) {
            return self::$mailCache;
        }

        self::$mailLoaded = true;
        $clientKey = self::currentClientKey();
        if ($clientKey === null) {
            return null;
        }

        self::$mailCache = (new TenantSettingsRepository())->findMailByClientKey($clientKey);

        return self::$mailCache;
    }

    private static function currentClientKey(): ?string
    {
        $fromDb = \Core\Database::requestClientKey();
        if ($fromDb !== null && $fromDb !== '') {
            return $fromDb;
        }

        if (defined('ACTIVE_CLIENT')) {
            $key = strtolower(trim((string) ACTIVE_CLIENT));

            return $key !== '' ? $key : null;
        }

        return null;
    }
}
