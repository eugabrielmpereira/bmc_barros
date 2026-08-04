<?php

declare(strict_types=1);

namespace Tests\Feature;

use Core\Database;
use Core\Database\MasterDatabase;
use Core\Database\TenantConnectionResolver;
use Core\Database\TenantWebhookResolver;
use Core\Database\WebhookTokenHasher;
use Erp\Finance\Repositories\PaymentChargeRepository;
use Erp\Finance\Services\AsaasWebhookService;
use Erp\Finance\Support\PaymentStatusLabel;
use Erp\Orders\Repositories\OrderRepository;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemorySchema;
use Tests\Support\IntegrationMasterSetup;

/**
 * Webhook Asaas roteado por webhook_token_hash no mestre.
 */
final class AsaasWebhookTenantIsolationTest extends TestCase
{
    private const BMC_TOKEN = 'bmc-webhook-token-minimum-32-characters-long';
    private const DEMO_TOKEN = 'demo-webhook-token-minimum-32-characters-long';

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
        self::ensureKnownWebhookTokens();
    }

    private static function ensureKnownWebhookTokens(): void
    {
        try {
            $master = MasterDatabase::getConnection();
            foreach (['bmc' => self::BMC_TOKEN, 'demo' => self::DEMO_TOKEN] as $slug => $token) {
                $find = $master->prepare('SELECT id FROM tenants WHERE slug = ? LIMIT 1');
                $find->execute([$slug]);
                $tenantId = (int) $find->fetchColumn();
                if ($tenantId <= 0) {
                    continue;
                }

                $update = $master->prepare(
                    'UPDATE tenant_asaas_config
                     SET webhook_token = :token, webhook_token_hash = :hash
                     WHERE tenant_id = :tenant_id'
                );
                $update->execute([
                    'token' => $token,
                    'hash' => WebhookTokenHasher::hash($token),
                    'tenant_id' => $tenantId,
                ]);
            }
        } catch (\Throwable) {
            // Testes marcam skip se mestre indisponível.
        }
    }

    protected function setUp(): void
    {
        $this->snapshotEnv(['MULTITENANT_DB', 'ERP_RUN_INTEGRATION']);
        $_ENV['ERP_RUN_INTEGRATION'] = $_ENV['ERP_RUN_INTEGRATION'] ?? $_SERVER['ERP_RUN_INTEGRATION'] ?? '1';
        $_SERVER['ERP_RUN_INTEGRATION'] = (string) $_ENV['ERP_RUN_INTEGRATION'];
        $_ENV['MULTITENANT_DB'] = 'true';
        $_SERVER['MULTITENANT_DB'] = 'true';

        if (!self::integrationEnabled()) {
            $this->markTestSkipped('Defina ERP_RUN_INTEGRATION=1 para teste de webhook multi-tenant.');
        }

        Database::resetForTesting();
    }

    protected function tearDown(): void
    {
        Database::resetForTesting();
        $this->restoreEnv();
    }

    public function testWebhookTokenRoutesToCorrectTenantDatabase(): void
    {
        $this->seedTenantOrder('erp_product', 501, 'pay_bmc_webhook_1');
        $this->seedTenantOrder('erp_tenant_demo', 502, 'pay_demo_webhook_1');

        $match = TenantWebhookResolver::resolveByToken(self::BMC_TOKEN);
        $this->assertNotNull($match);
        $this->assertSame('bmc', $match['client_config_key']);

        Database::initializeForRequest($match['client_config_key']);
        $service = $this->createWebhookService(Database::getInstance());

        $this->assertTrue($service->isAuthorized(self::BMC_TOKEN, $match['webhook_token']));

        $result = $service->handle([
            'event' => 'PAYMENT_RECEIVED',
            'payment' => [
                'id' => 'pay_bmc_webhook_1',
                'status' => 'RECEIVED',
                'externalReference' => '501',
                'paymentDate' => '2026-06-15',
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(PaymentStatusLabel::PAID, $this->orderPaymentStatus(Database::getInstance(), 501));
        $this->assertSame(PaymentStatusLabel::PENDING, $this->orderPaymentStatus(
            MasterDatabase::openTenantConnection('erp_tenant_demo'),
            502
        ));
    }

    public function testDemoWebhookTokenDoesNotAffectBmcOrders(): void
    {
        $this->seedTenantOrder('erp_product', 601, 'pay_bmc_webhook_2');
        $this->seedTenantOrder('erp_tenant_demo', 602, 'pay_demo_webhook_2');

        $match = TenantWebhookResolver::resolveByToken(self::DEMO_TOKEN);
        $this->assertNotNull($match);
        $this->assertSame('demo', $match['client_config_key']);

        Database::initializeForRequest($match['client_config_key']);
        $service = $this->createWebhookService(Database::getInstance());

        $result = $service->handle([
            'event' => 'PAYMENT_RECEIVED',
            'payment' => [
                'id' => 'pay_demo_webhook_2',
                'status' => 'RECEIVED',
                'externalReference' => '602',
                'paymentDate' => '2026-06-15',
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(PaymentStatusLabel::PENDING, $this->orderPaymentStatus(
            MasterDatabase::openTenantConnection('erp_product'),
            601
        ));
        $this->assertSame(PaymentStatusLabel::PAID, $this->orderPaymentStatus(Database::getInstance(), 602));
    }

    private function createWebhookService(\PDO $pdo): AsaasWebhookService
    {
        $orderRepository = new OrderRepository($pdo);
        $auditService = InMemorySchema::auditService($pdo);
        $receivableService = new \Erp\Finance\Services\ReceivableService($pdo, $orderRepository, $auditService);

        return new AsaasWebhookService(
            $pdo,
            new PaymentChargeRepository($pdo),
            $orderRepository,
            $receivableService,
            $auditService,
            InMemorySchema::orderService($pdo, $auditService, $orderRepository)
        );
    }

    private function seedTenantOrder(string $databaseName, int $orderId, string $externalPaymentId): void
    {
        $pdo = MasterDatabase::openTenantConnection($databaseName);
        $clientId = 9000 + ($orderId % 1000);

        $pdo->exec('DELETE FROM order_payment_charges WHERE order_id = ' . (int) $orderId);
        $pdo->exec('DELETE FROM order_items WHERE order_id = ' . (int) $orderId);
        $pdo->exec('DELETE FROM orders WHERE id = ' . (int) $orderId);
        $pdo->exec('DELETE FROM commercial_clients WHERE id = ' . (int) $clientId);

        $pdo->exec("INSERT INTO commercial_clients (id, name, status) VALUES ({$clientId}, 'Webhook Test', 'active')");
        $pdo->exec(
            "INSERT INTO orders (id, commercial_client_id, status, payment_status, total_amount)
             VALUES ({$orderId}, {$clientId}, 'confirmed', 'pending', 99.0)"
        );
        $pdo->exec(
            "INSERT INTO order_payment_charges
             (order_id, provider, external_payment_id, status, billing_type, amount, due_date)
             VALUES ({$orderId}, 'asaas', " . $pdo->quote($externalPaymentId) . ", 'PENDING', 'PIX', 99.0, '2026-06-20')"
        );
    }

    private function orderPaymentStatus(\PDO $pdo, int $orderId): string
    {
        $stmt = $pdo->prepare('SELECT payment_status FROM orders WHERE id = ? LIMIT 1');
        $stmt->execute([$orderId]);
        $status = $stmt->fetchColumn();

        return is_string($status) ? $status : '';
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
