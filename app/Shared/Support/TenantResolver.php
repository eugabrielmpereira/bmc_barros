<?php

declare(strict_types=1);

namespace Shared\Support;

final class TenantResolver
{
    public static function normalizeHost(?string $httpHost): string
    {
        if ($httpHost === null || $httpHost === '') {
            return '';
        }

        $host = strtolower(trim($httpHost));
        if (str_contains($host, ':')) {
            $host = explode(':', $host, 2)[0];
        }

        return $host;
    }

    /**
     * @param array<string, string> $hostMap
     * @param list<string> $knownClients
     */
    public static function resolveClientKey(
        string $resolution,
        ?string $httpHost,
        string $envClient,
        array $hostMap,
        string $defaultClient,
        array $knownClients
    ): string {
        $fallback = self::sanitizeClientKey($envClient, $defaultClient, $knownClients);

        if ($resolution !== 'domain') {
            return $fallback;
        }

        $host = self::normalizeHost($httpHost);
        if ($host === '' || !isset($hostMap[$host])) {
            return $fallback;
        }

        $mapped = strtolower(trim((string) $hostMap[$host]));

        return in_array($mapped, $knownClients, true) ? $mapped : $fallback;
    }

    /**
     * @param array<string, string> $clientBaseUrls
     * @param array<string, mixed>|null $server
     */
    public static function resolveBaseUrl(
        string $resolution,
        string $clientKey,
        ?array $server,
        string $envBaseUrl,
        array $clientBaseUrls
    ): string {
        // Em modo env (loja única) vale sempre o BASE_URL do .env; o mapa por tenant só vale em modo domain.
        if ($resolution !== 'domain') {
            return rtrim($envBaseUrl, '/');
        }

        $configured = trim((string) ($clientBaseUrls[$clientKey] ?? ''));
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        if (is_array($server)) {
            $derived = self::baseUrlFromRequest($server);
            if ($derived !== '') {
                return $derived;
            }
        }

        return rtrim($envBaseUrl, '/');
    }

    /**
     * @param list<string> $knownClients
     */
    private static function sanitizeClientKey(string $envClient, string $defaultClient, array $knownClients): string
    {
        $envClient = strtolower(trim($envClient));
        if (in_array($envClient, $knownClients, true)) {
            return $envClient;
        }

        $defaultClient = strtolower(trim($defaultClient));
        if (in_array($defaultClient, $knownClients, true)) {
            return $defaultClient;
        }

        return $knownClients[0] ?? 'bmc';
    }

    /**
     * @param array<string, mixed> $server
     */
    private static function baseUrlFromRequest(array $server): string
    {
        $host = trim((string) ($server['HTTP_HOST'] ?? ''));
        if ($host === '') {
            return '';
        }

        $https = $server['HTTPS'] ?? '';
        $scheme = (!empty($https) && $https !== 'off') ? 'https' : 'http';

        $script = str_replace('\\', '/', (string) ($server['SCRIPT_NAME'] ?? '/index.php'));
        $basePath = dirname($script);
        if ($basePath === '/' || $basePath === '.') {
            $basePath = '';
        }

        return $scheme . '://' . $host . rtrim($basePath, '/');
    }
}
