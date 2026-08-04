<?php

declare(strict_types=1);

namespace Tests\Unit;

use Erp\Finance\Services\ReceivableService;
use Erp\Finance\Support\PaymentStatusLabel;
use Erp\Orders\Repositories\OrderRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemorySchema;

final class ReceivableServiceTest extends TestCase
{
    private PDO $pdo;
    private ReceivableService $service;
    private OrderRepository $orderRepository;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $this->pdo->exec(
            InMemorySchema::commercialClientsMinimal() . '
             CREATE TABLE orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                commercial_client_id INTEGER NOT NULL,
                status TEXT NOT NULL DEFAULT \'draft\',
                payment_status TEXT NOT NULL DEFAULT \'not_applicable\',
                paid_at TEXT,
                total_amount REAL NOT NULL DEFAULT 0,
                public_reference TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
             );' . InMemorySchema::auditLogs()
        );

        $this->pdo->exec("INSERT INTO commercial_clients (id, name, email, phone) VALUES (1, 'Cliente Teste', 'a@test.com', '11999999999')");

        $this->orderRepository = new OrderRepository($this->pdo);
        $auditService = InMemorySchema::auditService($this->pdo);
        $this->service = new ReceivableService($this->pdo, $this->orderRepository, $auditService);
    }

    public function testListOpenReturnsOnlyConfirmedPendingOrders(): void
    {
        $this->insertOrder(1, 'confirmed', PaymentStatusLabel::PENDING, 150.0);
        $this->insertOrder(2, 'confirmed', PaymentStatusLabel::PAID, 200.0);
        $this->insertOrder(3, 'draft', PaymentStatusLabel::NOT_APPLICABLE, 80.0);
        $this->insertOrder(4, 'confirmed', PaymentStatusLabel::PENDING, 50.0);

        $rows = $this->service->listOpen();

        $this->assertCount(2, $rows);
        $this->assertSame(1, (int) $rows[0]['id']);
        $this->assertSame(4, (int) $rows[1]['id']);
    }

    public function testOpenSummaryAggregatesOpenReceivables(): void
    {
        $this->insertOrder(1, 'confirmed', PaymentStatusLabel::PENDING, 100.0);
        $this->insertOrder(2, 'confirmed', PaymentStatusLabel::PENDING, 250.5);
        $this->insertOrder(3, 'confirmed', PaymentStatusLabel::PAID, 999.0);

        $summary = $this->service->openSummary();

        $this->assertSame(2, $summary['count']);
        $this->assertSame(350.5, $summary['totalAmount']);
    }

    public function testMarkAsPaidUpdatesOrder(): void
    {
        $this->insertOrder(10, 'confirmed', PaymentStatusLabel::PENDING, 300.0);

        $result = $this->service->markAsPaid(10, 1);

        $this->assertTrue($result['success']);
        $order = $this->orderRepository->find(10);
        $this->assertSame(PaymentStatusLabel::PAID, $order['payment_status']);
        $this->assertNotEmpty($order['paid_at']);
    }

    public function testMarkAsPaidRejectsDraftOrder(): void
    {
        $this->insertOrder(11, 'draft', PaymentStatusLabel::NOT_APPLICABLE, 100.0);

        $result = $this->service->markAsPaid(11);

        $this->assertFalse($result['success']);
    }

    public function testMarkAsPaidRejectsAlreadyPaidOrder(): void
    {
        $this->insertOrder(12, 'confirmed', PaymentStatusLabel::PAID, 100.0, '2026-06-01 10:00:00');

        $result = $this->service->markAsPaid(12);

        $this->assertFalse($result['success']);
    }

    public function testSetPendingOnConfirm(): void
    {
        $this->insertOrder(20, 'draft', PaymentStatusLabel::NOT_APPLICABLE, 90.0);

        $this->service->setPendingOnConfirm(20);

        $order = $this->orderRepository->find(20);
        $this->assertSame(PaymentStatusLabel::PENDING, $order['payment_status']);
        $this->assertNull($order['paid_at']);
    }

    public function testResetPaymentOnReopen(): void
    {
        $this->insertOrder(21, 'cancelled', PaymentStatusLabel::CANCELLED, 90.0);

        $this->service->resetPaymentOnReopen(21);

        $order = $this->orderRepository->find(21);
        $this->assertSame(PaymentStatusLabel::NOT_APPLICABLE, $order['payment_status']);
        $this->assertNull($order['paid_at']);
    }

    public function testMarkAsPaidFromGatewayUpdatesConfirmedOrder(): void
    {
        $this->insertOrder(30, 'confirmed', PaymentStatusLabel::PENDING, 90.0);

        $result = $this->service->markAsPaidFromGateway(30, '2026-06-07 12:00:00', ['provider' => 'asaas']);

        $this->assertTrue($result['success']);
        $order = $this->orderRepository->find(30);
        $this->assertSame(PaymentStatusLabel::PAID, $order['payment_status']);
        $this->assertSame('2026-06-07 12:00:00', $order['paid_at']);
    }

    public function testApplyPaymentStatusOnConfirmUsesGatewayPaidFlag(): void
    {
        $this->insertOrder(31, 'confirmed', PaymentStatusLabel::PENDING, 50.0);
        $this->insertOrder(32, 'confirmed', PaymentStatusLabel::PENDING, 70.0);

        $this->service->applyPaymentStatusOnConfirm(31, true);
        $this->service->applyPaymentStatusOnConfirm(32, false);

        $this->assertSame(PaymentStatusLabel::PAID, $this->orderRepository->find(31)['payment_status']);
        $this->assertSame(PaymentStatusLabel::PENDING, $this->orderRepository->find(32)['payment_status']);
    }

    private function insertOrder(
        int $id,
        string $status,
        string $paymentStatus,
        float $total,
        ?string $paidAt = null
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO orders (id, commercial_client_id, status, payment_status, paid_at, total_amount)
             VALUES (?, 1, ?, ?, ?, ?)'
        );
        $stmt->execute([$id, $status, $paymentStatus, $paidAt, $total]);
    }
}
