<?php

declare(strict_types=1);

namespace Tests\Unit;

use Erp\Finance\Repositories\PaymentChargeRepository;
use Erp\Finance\Services\PaymentChargeService;
use Erp\Orders\Repositories\OrderRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakePaymentGateway;
use Tests\Support\InMemorySchema;
use Tests\Support\RaceSimulatingPaymentChargeRepository;

final class PaymentChargeServiceTest extends TestCase
{
    private PDO $pdo;
    private FakePaymentGateway $gateway;
    private PaymentChargeService $service;

    protected function setUp(): void
    {
        $this->pdo = InMemorySchema::createPdo();
        $this->pdo->exec(InMemorySchema::financeWebhookCore());

        $this->pdo->exec("INSERT INTO commercial_clients (id, name) VALUES (1, 'Cliente')");
        $this->pdo->exec(
            "INSERT INTO orders (id, commercial_client_id, status, payment_status, total_amount, public_reference)
             VALUES (7, 1, 'draft', 'not_applicable', 199.90, 'BMC-20260614-TEST01')"
        );

        $this->gateway = new FakePaymentGateway();
        $this->service = new PaymentChargeService(
            $this->pdo,
            new PaymentChargeRepository($this->pdo),
            new OrderRepository($this->pdo),
            $this->gateway
        );
    }

    public function testCreatePixForOnlineOrderPersistsCharge(): void
    {
        $customerData = [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '16999990000',
            'document' => '39053344705',
        ];

        $charge = $this->service->createPixForOnlineOrder(7, $customerData);

        $this->assertNotNull($charge);
        $this->assertSame(7, (int) ($charge['order_id'] ?? 0));
        $this->assertSame('fake', (string) ($charge['provider'] ?? ''));
        $this->assertSame('pay_fake_1', (string) ($charge['external_payment_id'] ?? ''));
        $this->assertSame('PENDING', (string) ($charge['status'] ?? ''));
        $this->assertSame(1, $this->gateway->createCallCount);

        $rowCount = (int) $this->pdo->query('SELECT COUNT(*) FROM order_payment_charges')->fetchColumn();
        $this->assertSame(1, $rowCount);
    }

    public function testCardOrderCreatesCardChargeWithInstallments(): void
    {
        $this->pdo->exec("UPDATE orders SET payment_method = 'credit_card', installments = 3 WHERE id = 7");

        $charge = $this->service->createPixForOnlineOrder(7, [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '16999990000',
            'document' => '39053344705',
        ]);

        $this->assertNotNull($charge);
        $this->assertSame('CREDIT_CARD', (string) $charge['billing_type']);
        $this->assertSame('https://example.test/card-invoice/1', (string) $charge['invoice_url']);
        $this->assertSame(3, (int) ($this->gateway->lastCardContext['installments'] ?? 0));
        $this->assertSame(199.9, (float) ($this->gateway->lastCardContext['amount'] ?? 0));
    }

    public function testCreatePixForOnlineOrderIsIdempotentForSameOrder(): void
    {
        $customerData = [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '16999990000',
            'document' => '39053344705',
        ];

        $first = $this->service->createPixForOnlineOrder(7, $customerData);
        $second = $this->service->createPixForOnlineOrder(7, $customerData);

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertSame((int) $first['id'], (int) $second['id']);
        $this->assertSame(1, $this->gateway->createCallCount);

        $rowCount = (int) $this->pdo->query('SELECT COUNT(*) FROM order_payment_charges')->fetchColumn();
        $this->assertSame(1, $rowCount);
    }

    public function testCreatePixReturnsNullWhenGatewayDisabled(): void
    {
        $disabledGateway = new FakePaymentGateway(enabled: false);
        $service = new PaymentChargeService(
            $this->pdo,
            new PaymentChargeRepository($this->pdo),
            new OrderRepository($this->pdo),
            $disabledGateway
        );

        $charge = $service->createPixForOnlineOrder(7, [
            'name' => 'Maria',
            'email' => 'maria@example.com',
            'phone' => '16999990000',
            'document' => '39053344705',
        ]);

        $this->assertNull($charge);
        $this->assertSame(0, $disabledGateway->createCallCount);
    }

    public function testCreatePixForOnlineOrderRecoversFromUniqueConstraintRace(): void
    {
        $customerData = [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '16999990000',
            'document' => '39053344705',
        ];

        $first = $this->service->createPixForOnlineOrder(7, $customerData);
        $this->assertNotNull($first);

        $raceRepository = new RaceSimulatingPaymentChargeRepository($this->pdo);
        $raceService = new PaymentChargeService(
            $this->pdo,
            $raceRepository,
            new OrderRepository($this->pdo),
            $this->gateway
        );

        $second = $raceService->createPixForOnlineOrder(7, $customerData);

        $this->assertNotNull($second);
        $this->assertSame((int) $first['id'], (int) $second['id']);

        $rowCount = (int) $this->pdo->query('SELECT COUNT(*) FROM order_payment_charges WHERE order_id = 7')->fetchColumn();
        $this->assertSame(1, $rowCount);
    }
}
