<?php

declare(strict_types=1);

namespace Core\Database;

use Core\Migrator;
use PDO;
use RuntimeException;
use Shared\Support\TenantResolver;

/**
 * Operações CRUD no banco mestre para onboarding CLI.
 */
final class TenantMasterRepository
{
    public function slugExists(string $slug): bool
    {
        $master = MasterDatabase::getConnection();
        $stmt = $master->prepare('SELECT 1 FROM tenants WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listTenants(): array
    {
        $master = MasterDatabase::getConnection();
        $sql = <<<'SQL'
            SELECT t.slug, t.client_config_key, t.display_name, t.status,
                   td.database_name,
                   (SELECT GROUP_CONCAT(d.host ORDER BY d.is_primary DESC, d.host SEPARATOR ', ')
                    FROM tenant_domains d WHERE d.tenant_id = t.id) AS domains
            FROM tenants t
            LEFT JOIN tenant_database td ON td.tenant_id = t.id
            ORDER BY t.slug
            SQL;

        return $master->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array{tenant_id: int, database_name: string, client_config_key: string}
     */
    public function createTenant(
        string $slug,
        string $clientConfigKey,
        string $displayName,
        string $domainHost,
        ?string $databaseName = null
    ): array {
        $slug = self::sanitizeSlug($slug);
        $clientConfigKey = strtolower(trim($clientConfigKey));
        $domainHost = TenantResolver::normalizeHost($domainHost);
        $databaseName ??= 'erp_tenant_' . $slug;

        if ($slug === '' || $clientConfigKey === '' || $domainHost === '') {
            throw new RuntimeException('slug, client_config_key e domain são obrigatórios.');
        }

        if ($this->slugExists($slug)) {
            throw new RuntimeException("Tenant '{$slug}' já existe no mestre.");
        }

        $master = MasterDatabase::getConnection();
        $master->beginTransaction();

        try {
            $insert = $master->prepare(
                'INSERT INTO tenants (slug, client_config_key, display_name, status)
                 VALUES (:slug, :client_config_key, :display_name, :status)'
            );
            $insert->execute([
                'slug' => $slug,
                'client_config_key' => $clientConfigKey,
                'display_name' => $displayName !== '' ? $displayName : ucfirst($slug),
                'status' => 'provisioning',
            ]);

            $tenantId = (int) $master->lastInsertId();

            $master->prepare(
                'INSERT INTO tenant_database (tenant_id, database_name, charset, provisioned_at)
                 VALUES (?, ?, ?, CURRENT_TIMESTAMP)'
            )->execute([$tenantId, $databaseName, 'utf8mb4']);

            $master->prepare(
                'INSERT INTO tenant_domains (tenant_id, host, is_primary) VALUES (?, ?, 1)'
            )->execute([$tenantId, $domainHost]);

            $settings = new TenantSettingsRepository();
            $settings->upsertAsaas($tenantId, [
                'enabled' => false,
                'sandbox' => true,
                'api_key' => '',
                'webhook_token' => '',
                'pix_due_days' => 3,
            ]);
            $settings->upsertMail($tenantId, [
                'enabled' => false,
                'driver' => 'smtp',
                'host' => '',
                'port' => 2525,
                'username' => '',
                'password' => '',
                'encryption' => 'tls',
                'from_address' => '',
                'from_name' => '',
                'admin_to' => '',
            ]);

            $master->commit();
        } catch (\Throwable $e) {
            $master->rollBack();
            throw $e;
        }

        return [
            'tenant_id' => $tenantId,
            'database_name' => $databaseName,
            'client_config_key' => $clientConfigKey,
        ];
    }

    public function addDomain(string $slug, string $host, bool $primary = false): void
    {
        $tenantId = $this->requireTenantId($slug);
        $host = TenantResolver::normalizeHost($host);
        if ($host === '') {
            throw new RuntimeException('Host inválido.');
        }

        $master = MasterDatabase::getConnection();
        if ($primary) {
            $master->prepare('UPDATE tenant_domains SET is_primary = 0 WHERE tenant_id = ?')
                ->execute([$tenantId]);
        }

        $master->prepare(
            'INSERT INTO tenant_domains (tenant_id, host, is_primary)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE tenant_id = VALUES(tenant_id), is_primary = VALUES(is_primary)'
        )->execute([$tenantId, $host, $primary ? 1 : 0]);
    }

    public function setStatus(string $slug, string $status, ?string $reason = null): void
    {
        $allowed = ['active', 'suspended', 'provisioning', 'archived'];
        if (!in_array($status, $allowed, true)) {
            throw new RuntimeException('Status inválido: ' . $status);
        }

        $tenantId = $this->requireTenantId($slug);
        $master = MasterDatabase::getConnection();
        $master->prepare(
            'UPDATE tenants SET status = :status, suspension_reason = :reason,
             suspended_at = CASE WHEN :status_flag = \'suspended\' THEN CURRENT_TIMESTAMP ELSE NULL END
             WHERE id = :id'
        )->execute([
            'status' => $status,
            'status_flag' => $status,
            'reason' => $status === 'suspended' ? ($reason ?? 'manual_operator') : null,
            'id' => $tenantId,
        ]);
    }

    public function provisionDatabase(string $databaseName): void
    {
        MasterDatabase::ensureTenantDatabaseExists($databaseName);
        $pdo = MasterDatabase::openTenantConnection($databaseName);
        $migrator = new Migrator($pdo, dirname(__DIR__, 2) . '/database/migrations');
        $migrator->run();

        $master = MasterDatabase::getConnection();
        $master->prepare(
            'UPDATE tenant_database SET last_migration_at = CURRENT_TIMESTAMP WHERE database_name = ?'
        )->execute([$databaseName]);
    }

    public function markActive(string $slug): void
    {
        $this->setStatus($slug, 'active');
    }

    public function requireTenantId(string $slug): int
    {
        $master = MasterDatabase::getConnection();
        $stmt = $master->prepare('SELECT id FROM tenants WHERE slug = ? LIMIT 1');
        $stmt->execute([self::sanitizeSlug($slug)]);
        $id = $stmt->fetchColumn();
        if ($id === false) {
            throw new RuntimeException("Tenant '{$slug}' não encontrado no mestre.");
        }

        return (int) $id;
    }

    public function getClientConfigKey(string $slug): string
    {
        $master = MasterDatabase::getConnection();
        $stmt = $master->prepare('SELECT client_config_key FROM tenants WHERE slug = ? LIMIT 1');
        $stmt->execute([self::sanitizeSlug($slug)]);
        $key = $stmt->fetchColumn();

        if ($key === false) {
            throw new RuntimeException("Tenant '{$slug}' não encontrado.");
        }

        return strtolower(trim((string) $key));
    }

    public static function sanitizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug) ?? '';
        $slug = trim(preg_replace('/-+/', '-', $slug) ?? '', '-');

        return $slug;
    }
}
