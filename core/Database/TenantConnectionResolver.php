<?php

declare(strict_types=1);

namespace Core\Database;

use Core\Exceptions\TenantSuspendedException;
use PDO;
use RuntimeException;

/**
 * Resolve conexão PDO do tenant via banco mestre (modo MULTITENANT_DB=true).
 */
final class TenantConnectionResolver
{
    private const CONNECTABLE_STATUSES = ['active', 'provisioning'];

    public static function findClientConfigKeyByHost(string $host): ?string
    {
        $row = self::fetchTenantRowByHost($host);

        return $row !== null
            ? strtolower(trim((string) ($row['client_config_key'] ?? '')))
            : null;
    }

    public static function resolve(string $clientKey): PDO
    {
        $row = self::fetchTenantRowByClientKey($clientKey);
        if ($row === null) {
            throw new RuntimeException(
                'Tenant não registrado no mestre para client key: ' . strtolower(trim($clientKey))
            );
        }

        return self::openConnectionFromRow($row, $clientKey);
    }

    /**
     * Retorna o nome do database MySQL ativo na conexão (útil para testes).
     */
    public static function currentDatabaseName(PDO $pdo): string
    {
        $name = $pdo->query('SELECT DATABASE()')->fetchColumn();

        return is_string($name) ? $name : '';
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function fetchTenantRowByHost(string $host): ?array
    {
        $host = strtolower(trim($host));
        if ($host === '') {
            return null;
        }

        $master = MasterDatabase::getConnection();
        $stmt = $master->prepare(
            <<<'SQL'
            SELECT
                t.id,
                t.slug,
                t.client_config_key,
                t.status,
                td.database_name,
                td.charset
            FROM tenant_domains d
            INNER JOIN tenants t ON t.id = d.tenant_id
            INNER JOIN tenant_database td ON td.tenant_id = t.id
            WHERE d.host = :host
            LIMIT 1
            SQL
        );
        $stmt->execute(['host' => $host]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function fetchTenantRowByClientKey(string $clientKey): ?array
    {
        $clientKey = strtolower(trim($clientKey));
        if ($clientKey === '') {
            return null;
        }

        $master = MasterDatabase::getConnection();
        $stmt = $master->prepare(
            <<<'SQL'
            SELECT
                t.id,
                t.slug,
                t.client_config_key,
                t.status,
                td.database_name,
                td.charset
            FROM tenants t
            INNER JOIN tenant_database td ON td.tenant_id = t.id
            WHERE t.client_config_key = :key OR t.slug = :slug
            LIMIT 1
            SQL
        );
        $stmt->execute(['key' => $clientKey, 'slug' => $clientKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function openConnectionFromRow(array $row, string $contextKey): PDO
    {
        $status = (string) ($row['status'] ?? '');
        if (!in_array($status, self::CONNECTABLE_STATUSES, true)) {
            throw new TenantSuspendedException(
                "Tenant '{$contextKey}' indisponível (status: {$status})."
            );
        }

        $databaseName = (string) ($row['database_name'] ?? '');
        if ($databaseName === '') {
            throw new RuntimeException("Tenant '{$contextKey}' sem database_name no mestre.");
        }

        $charset = (string) ($row['charset'] ?? 'utf8mb4');

        return MasterDatabase::openTenantConnection($databaseName, $charset);
    }
}
