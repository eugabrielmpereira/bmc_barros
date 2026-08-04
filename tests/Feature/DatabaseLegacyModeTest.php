<?php

declare(strict_types=1);

namespace Tests\Feature;

use Core\Database;
use Core\Database\TenantConnectionResolver;
use PHPUnit\Framework\TestCase;

/**
 * Regressão: MULTITENANT_DB=false mantém comportamento legado do singleton.
 *
 * Requer: ERP_RUN_INTEGRATION=1 e MySQL com DB_NAME do .env.
 */
final class DatabaseLegacyModeTest extends TestCase
{
    /** @var array<string, string|null> */
    private array $originalEnv = [];

    protected function setUp(): void
    {
        if (!self::integrationEnabled()) {
            $this->markTestSkipped('Defina ERP_RUN_INTEGRATION=1 para teste de regressão legado.');
        }

        $root = dirname(__DIR__, 2);
        if (is_file($root . '/.env')) {
            \Dotenv\Dotenv::createImmutable($root)->safeLoad();
        }

        Database::resetForTesting();
        $this->snapshotEnv(['MULTITENANT_DB', 'DB_NAME']);
        $_ENV['MULTITENANT_DB'] = 'false';
        $_SERVER['MULTITENANT_DB'] = 'false';
    }

    protected function tearDown(): void
    {
        Database::resetForTesting();
        $this->restoreEnv();
    }

    public function testGetInstanceUsesEnvDatabaseWithoutInitialize(): void
    {
        $this->assertFalse(Database::isMultitenantEnabled());

        $expectedDb = trim((string) (getenv('DB_NAME') ?: $_ENV['DB_NAME'] ?? 'erp_product'));
        if ($expectedDb === '') {
            $expectedDb = 'erp_product';
        }

        $pdo = Database::getInstance();
        $actualDb = TenantConnectionResolver::currentDatabaseName($pdo);

        $this->assertSame($expectedDb, $actualDb);

        // Segunda chamada retorna a mesma instância (singleton legado)
        $this->assertSame($pdo, Database::getInstance());
    }

    public function testInitializeForRequestIsNoOpWhenMultitenantDisabled(): void
    {
        Database::initializeForRequest('demo');

        $expectedDb = trim((string) (getenv('DB_NAME') ?: $_ENV['DB_NAME'] ?? 'erp_product'));
        if ($expectedDb === '') {
            $expectedDb = 'erp_product';
        }

        $actualDb = TenantConnectionResolver::currentDatabaseName(Database::getInstance());
        $this->assertSame($expectedDb, $actualDb);
        $this->assertNotSame('erp_tenant_demo', $actualDb);
    }

    private static function integrationEnabled(): bool
    {
        $v = $_ENV['ERP_RUN_INTEGRATION'] ?? getenv('ERP_RUN_INTEGRATION');

        return $v === '1' || $v === 'true';
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
