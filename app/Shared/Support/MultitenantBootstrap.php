<?php

declare(strict_types=1);

namespace Shared\Support;

use Core\Database;
use Core\Database\TenantConnectionResolver;
use Core\Exceptions\TenantNotFoundException;

/**
 * Resolução de client_config_key quando MULTITENANT_DB=true.
 */
final class MultitenantBootstrap
{
    /**
     * Resolve a chave de branding/DB alinhada ao mestre.
     *
     * @param array<string, string> $legacyHostMap mapa config/tenants.php (fallback dev)
     * @param list<string> $knownClients
     */
    public static function resolveClientConfigKey(
        string $tenantResolution,
        ?string $httpHost,
        string $envActiveClient,
        array $legacyHostMap,
        string $defaultClient,
        array $knownClients
    ): string {
        $legacyFallback = TenantResolver::resolveClientKey(
            $tenantResolution,
            $httpHost,
            $envActiveClient,
            $legacyHostMap,
            $defaultClient,
            $knownClients
        );

        if (!Database::isMultitenantEnabled()) {
            return $legacyFallback;
        }

        if ($tenantResolution !== 'domain') {
            return $legacyFallback;
        }

        $host = TenantResolver::normalizeHost($httpHost);
        if ($host !== '') {
            $fromMaster = TenantConnectionResolver::findClientConfigKeyByHost($host);
            if ($fromMaster !== null) {
                return $fromMaster;
            }
        }

        if (self::allowsDomainResolutionFallback()) {
            return $legacyFallback;
        }

        throw new TenantNotFoundException(
            'Nenhum tenant registrado para o host: ' . ($host !== '' ? $host : '(vazio)')
        );
    }

    private static function allowsDomainResolutionFallback(): bool
    {
        $env = defined('APP_ENV') ? (string) APP_ENV : (string) env('APP_ENV', 'production');

        return in_array($env, ['local', 'testing', 'development', 'dev'], true)
            || filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL);
    }
}
