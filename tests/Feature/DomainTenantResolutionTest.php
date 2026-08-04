<?php

declare(strict_types=1);

namespace Tests\Feature;

use Core\Database;
use Core\Database\TenantConnectionResolver;
use PHPUnit\Framework\TestCase;
use Shared\Support\MultitenantBootstrap;
use Tests\Support\IntegrationMasterSetup;

/**
 * Resolução por HTTP Host via tenant_domains (MULTITENANT_DB=true).
 */
final class DomainTenantResolutionTest extends TestCase
{
    private static bool $envLoaded = false;

    /** @var array<string, string|null> */
    private array $originalEnv = [];

    /** @var array<string, string> */
    private array $hostMap = [
        'localhost' => 'bmc',
        'bmc.local' => 'bmc',
        'demo.local' => 'demo',
    ];

    /** @var list<string> */
    private array $knownClients = ['bmc', 'demo'];

    public static function setUpBeforeClass(): void
    {
        if (!self::integrationEnabled()) {
            return;
        }

        self::loadProjectEnv();
        IntegrationMasterSetup::ensureMasterSeeded();
    }

    protected function setUp(): void
    {
        $this->snapshotEnv(['MULTITENANT_DB', 'TENANT_RESOLUTION', 'APP_ENV', 'DB_NAME', 'ERP_RUN_INTEGRATION']);
        $_ENV['ERP_RUN_INTEGRATION'] = $_ENV['ERP_RUN_INTEGRATION'] ?? $_SERVER['ERP_RUN_INTEGRATION'] ?? '1';
        $_SERVER['ERP_RUN_INTEGRATION'] = (string) $_ENV['ERP_RUN_INTEGRATION'];
        $_ENV['MULTITENANT_DB'] = 'true';
        $_SERVER['MULTITENANT_DB'] = 'true';
        $_ENV['TENANT_RESOLUTION'] = 'domain';
        $_SERVER['TENANT_RESOLUTION'] = 'domain';
        $_ENV['APP_ENV'] = 'local';
        $_SERVER['APP_ENV'] = 'local';

        if (!self::integrationEnabled()) {
            $this->markTestSkipped('Defina ERP_RUN_INTEGRATION=1 para testes de domínio.');
        }

        Database::resetForTesting();
    }

    protected function tearDown(): void
    {
        Database::resetForTesting();
        $this->restoreEnv();
    }

    public function testBmcLocalHostResolvesToBmcDatabase(): void
    {
        $clientKey = MultitenantBootstrap::resolveClientConfigKey(
            'domain',
            'bmc.local',
            'demo',
            $this->hostMap,
            'bmc',
            $this->knownClients
        );

        $this->assertSame('bmc', $clientKey);

        Database::initializeForRequest($clientKey);
        $dbName = TenantConnectionResolver::currentDatabaseName(Database::getInstance());
        $this->assertSame($this->expectedBmcDatabaseName(), $dbName);
    }

    public function testDemoLocalHostResolvesToDemoDatabase(): void
    {
        $clientKey = MultitenantBootstrap::resolveClientConfigKey(
            'domain',
            'demo.local',
            'bmc',
            $this->hostMap,
            'bmc',
            $this->knownClients
        );

        $this->assertSame('demo', $clientKey);

        Database::initializeForRequest($clientKey);
        $this->assertSame('erp_tenant_demo', TenantConnectionResolver::currentDatabaseName(Database::getInstance()));
    }

    public function testBrandingAndDatabaseStayConsistentAcrossHosts(): void
    {
        foreach (['bmc.local' => $this->expectedBmcDatabaseName(), 'demo.local' => 'erp_tenant_demo'] as $host => $expectedDb) {
            Database::resetForTesting();
            $_ENV['MULTITENANT_DB'] = 'true';
            $_SERVER['MULTITENANT_DB'] = 'true';

            $clientKey = MultitenantBootstrap::resolveClientConfigKey(
                'domain',
                $host,
                'bmc',
                $this->hostMap,
                'bmc',
                $this->knownClients
            );

            Database::initializeForRequest($clientKey);
            $masterKey = TenantConnectionResolver::findClientConfigKeyByHost($host);

            $this->assertSame($clientKey, $masterKey);
            $this->assertSame($expectedDb, TenantConnectionResolver::currentDatabaseName(Database::getInstance()));
        }
    }

    private function expectedBmcDatabaseName(): string
    {
        return trim((string) env('DB_NAME', 'erp_product')) ?: 'erp_product';
    }

    private static function integrationEnabled(): bool
    {
        $v = env('ERP_RUN_INTEGRATION', false);

        return $v === '1' || $v === 'true';
    }

    private static function multitenantEnabled(): bool
    {
        $v = env('MULTITENANT_DB', false);

        return $v === '1' || $v === 'true';
    }

    private static function loadProjectEnv(): void
    {
        if (self::$envLoaded) {
            return;
        }

        $root = dirname(__DIR__, 2);
        if (is_file($root . '/.env')) {
            \Dotenv\Dotenv::createImmutable($root)->safeLoad();
        }

        self::$envLoaded = true;
    }

    /**
     * @param list<string> $keys
     */
    private function snapshotEnv(array $keys): void
    {
        foreach ($keys as $key) {
            $this->originalEnv[$key] = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        }
    }

    private function restoreEnv(): void
    {
        foreach ($this->originalEnv as $key => $value) {
            if ($value === null) {
                unset($_ENV[$key], $_SERVER[$key]);
                continue;
            }
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}
