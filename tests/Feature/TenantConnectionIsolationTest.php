<?php

declare(strict_types=1);

namespace Tests\Feature;

use Core\Database;
use Core\Database\TenantConnectionResolver;
use PHPUnit\Framework\TestCase;
use Tests\Support\IntegrationMasterSetup;

/**
 * Valida isolamento de conexão tenant no mesmo processo PHP (simula PHP-FPM worker reuse).
 *
 * Requer: ERP_RUN_INTEGRATION=1, MySQL, erp_master migrado e seed (php migrate_master.php run && seed).
 */
final class TenantConnectionIsolationTest extends TestCase
{
    private static bool $envLoaded = false;

    /** @var array<string, string|null> */
    private array $originalEnv = [];

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
        $this->snapshotEnv(['MULTITENANT_DB', 'DB_NAME', 'MASTER_DB_NAME', 'ERP_RUN_INTEGRATION']);
        $_ENV['ERP_RUN_INTEGRATION'] = $_ENV['ERP_RUN_INTEGRATION'] ?? $_SERVER['ERP_RUN_INTEGRATION'] ?? '1';
        $_SERVER['ERP_RUN_INTEGRATION'] = (string) $_ENV['ERP_RUN_INTEGRATION'];
        $_ENV['MULTITENANT_DB'] = 'true';
        $_SERVER['MULTITENANT_DB'] = 'true';

        if (!self::integrationEnabled()) {
            $this->markTestSkipped(
                'Defina ERP_RUN_INTEGRATION=1 e MULTITENANT_DB=true para testes de isolamento tenant.'
            );
        }

        Database::resetForTesting();
    }

    protected function tearDown(): void
    {
        Database::resetForTesting();
        $this->restoreEnv();
    }

    public function testSequentialRequestsReceiveCorrectTenantConnectionWithoutLeakage(): void
    {
        Database::initializeForRequest('bmc');
        $bmcPdo = Database::getInstance();
        $bmcDb = TenantConnectionResolver::currentDatabaseName($bmcPdo);

        $this->assertNotSame('', $bmcDb);
        $this->assertSame($this->expectedBmcDatabaseName(), $bmcDb);

        Database::initializeForRequest('demo');
        $demoPdo = Database::getInstance();
        $demoDb = TenantConnectionResolver::currentDatabaseName($demoPdo);

        $this->assertSame('erp_tenant_demo', $demoDb);
        $this->assertNotSame($bmcDb, $demoDb);

        Database::initializeForRequest('bmc');
        $bmcAgain = TenantConnectionResolver::currentDatabaseName(Database::getInstance());
        $this->assertSame($bmcDb, $bmcAgain);
    }

    public function testGetInstanceRequiresInitializeWhenMultitenantEnabled(): void
    {
        Database::resetForTesting();
        $_ENV['MULTITENANT_DB'] = 'true';
        $_SERVER['MULTITENANT_DB'] = 'true';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Conexão tenant não inicializada');

        Database::getInstance();
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
