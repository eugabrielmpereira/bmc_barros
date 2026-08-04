<?php

declare(strict_types=1);

/**
 * Seed idempotente do banco mestre: tenants BMC + Demo.
 *
 * Uso: php migrate_master.php seed
 */

use Core\Database\MasterDatabase;
use Core\Database\TenantSettingsRepository;
use Core\Migrator;

if (php_sapi_name() !== 'cli') {
    echo "❌ Apenas CLI\n";
    exit(1);
}

$root = dirname(__DIR__, 2);
require_once $root . '/vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::createImmutable($root);
$dotenv->safeLoad();

try {
    MasterDatabase::ensureDatabaseExists();
    $master = MasterDatabase::getConnection();
} catch (Throwable $e) {
    echo '❌ Mestre indisponível: ' . $e->getMessage() . "\n";
    exit(1);
}

$tenantDbFromEnv = trim((string) env('DB_NAME', 'erp_product'));
$demoDatabase = 'erp_tenant_demo';

$tenants = [
    [
        'slug' => 'bmc',
        'client_config_key' => 'bmc',
        'display_name' => 'BMC Barros',
        'status' => 'active',
        'database_name' => $tenantDbFromEnv,
        'domains' => [
            ['host' => 'localhost', 'is_primary' => 0],
            ['host' => '127.0.0.1', 'is_primary' => 0],
            ['host' => 'bmc.local', 'is_primary' => 1],
        ],
        'provision_demo_db' => false,
    ],
    [
        'slug' => 'demo',
        'client_config_key' => 'demo',
        'display_name' => 'Demo Store',
        'status' => 'active',
        'database_name' => $demoDatabase,
        'domains' => [
            ['host' => 'demo.local', 'is_primary' => 1],
        ],
        'provision_demo_db' => true,
    ],
];

foreach ($tenants as $tenantSpec) {
    seedTenant($master, $tenantSpec);
}

echo "✅ Seed mestre concluído (BMC → {$tenantDbFromEnv}, Demo → {$demoDatabase}).\n";

/**
 * @param array{
 *     slug: string,
 *     client_config_key: string,
 *     display_name: string,
 *     status: string,
 *     database_name: string,
 *     domains: list<array{host: string, is_primary: int}>,
 *     provision_demo_db: bool
 * } $spec
 */
function seedTenant(PDO $master, array $spec): void
{
    $stmt = $master->prepare(
        <<<'SQL'
        INSERT INTO tenants (slug, client_config_key, display_name, status)
        VALUES (:slug, :client_config_key, :display_name, :status)
        ON DUPLICATE KEY UPDATE
            client_config_key = VALUES(client_config_key),
            display_name = VALUES(display_name),
            status = VALUES(status),
            updated_at = CURRENT_TIMESTAMP
        SQL
    );
    $stmt->execute([
        'slug' => $spec['slug'],
        'client_config_key' => $spec['client_config_key'],
        'display_name' => $spec['display_name'],
        'status' => $spec['status'],
    ]);

    $tenantId = (int) $master->query(
        "SELECT id FROM tenants WHERE slug = " . $master->quote($spec['slug'])
    )->fetchColumn();

    $dbStmt = $master->prepare(
        <<<'SQL'
        INSERT INTO tenant_database (tenant_id, database_name, charset, provisioned_at)
        VALUES (:tenant_id, :database_name, 'utf8mb4', CURRENT_TIMESTAMP)
        ON DUPLICATE KEY UPDATE
            database_name = VALUES(database_name),
            charset = VALUES(charset)
        SQL
    );
    $dbStmt->execute([
        'tenant_id' => $tenantId,
        'database_name' => $spec['database_name'],
    ]);

    foreach ($spec['domains'] as $domain) {
        $domainStmt = $master->prepare(
            <<<'SQL'
            INSERT INTO tenant_domains (tenant_id, host, is_primary)
            VALUES (:tenant_id, :host, :is_primary)
            ON DUPLICATE KEY UPDATE
                tenant_id = VALUES(tenant_id),
                is_primary = VALUES(is_primary)
            SQL
        );
        $domainStmt->execute([
            'tenant_id' => $tenantId,
            'host' => strtolower($domain['host']),
            'is_primary' => (int) $domain['is_primary'],
        ]);
    }

    seedAsaasConfig($master, $tenantId, $spec['slug']);
    seedMailConfig($master, $tenantId);

    if ($spec['provision_demo_db']) {
        provisionTenantDatabase($spec['database_name']);
    }
}

function seedAsaasConfig(PDO $master, int $tenantId, string $slug): void
{
    $settings = new TenantSettingsRepository();
    $settings->upsertAsaas($tenantId, [
        'enabled' => env('ASAAS_ENABLED', 'false'),
        'sandbox' => env('ASAAS_SANDBOX', 'true'),
        'api_key' => trim((string) env('ASAAS_API_KEY', '')),
        'webhook_token' => webhookTokenForTenantSlug($slug),
        'pix_due_days' => (int) env('ASAAS_PIX_DUE_DAYS', 3),
    ]);
}

function webhookTokenForTenantSlug(string $slug): string
{
    $slug = strtolower(trim($slug));
    $defaults = [
        'bmc' => 'bmc-webhook-token-minimum-32-characters-long',
        'demo' => 'demo-webhook-token-minimum-32-characters-long',
    ];

    $envKey = $slug === 'demo' ? 'DEMO_ASAAS_WEBHOOK_TOKEN' : 'ASAAS_WEBHOOK_TOKEN';
    $fromEnv = trim((string) env($envKey, ''));

    if ($fromEnv !== '') {
        return $fromEnv;
    }

    return $defaults[$slug] ?? $defaults['bmc'];
}

function seedMailConfig(PDO $master, int $tenantId): void
{
    $settings = new TenantSettingsRepository();
    $settings->upsertMail($tenantId, [
        'enabled' => env('MAIL_ENABLED', false),
        'driver' => env('MAIL_DRIVER', 'smtp'),
        'host' => env('MAIL_HOST', ''),
        'port' => (int) env('MAIL_PORT', 2525),
        'username' => env('MAIL_USERNAME', ''),
        'password' => env('MAIL_PASSWORD', ''),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),
        'from_address' => env('MAIL_FROM', 'noreply@example.com'),
        'from_name' => env('MAIL_FROM_NAME', ''),
        'admin_to' => env('MAIL_ADMIN_TO', ''),
    ]);
}

function provisionTenantDatabase(string $databaseName): void
{
    try {
        MasterDatabase::ensureTenantDatabaseExists($databaseName);
    } catch (Throwable $e) {
        echo "⚠️  CREATE DATABASE falhou para {$databaseName}: {$e->getMessage()}\n";
        echo "    Execute manualmente: CREATE DATABASE IF NOT EXISTS `{$databaseName}` "
            . "CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";

        return;
    }

    $pdo = MasterDatabase::openTenantConnection($databaseName);
    $migrator = new Migrator($pdo, dirname(__DIR__) . '/migrations');
    $migrator->run();

    $master = MasterDatabase::getConnection();
    $update = $master->prepare(
        'UPDATE tenant_database SET last_migration_at = CURRENT_TIMESTAMP WHERE database_name = ?'
    );
    $update->execute([$databaseName]);

    echo "✅ Database {$databaseName} provisionado e migrado.\n";
}
