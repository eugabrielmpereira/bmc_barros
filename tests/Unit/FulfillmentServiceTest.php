<?php

declare(strict_types=1);

namespace Tests\Unit;

use Erp\Audit\Repositories\AuditLogRepository;
use Erp\Audit\Services\AuditService;
use Erp\Fulfillment\Services\FulfillmentService;
use Erp\Fulfillment\Support\DeliveryMethod;
use Erp\Fulfillment\Support\FulfillmentStatus;
use Erp\Orders\Repositories\OrderRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class FulfillmentServiceTest extends TestCase
{
    private PDO $pdo;
    private FulfillmentService $service;
    private OrderRepository $orderRepository;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $this->pdo->exec(
            'CREATE TABLE orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                commercial_client_id INTEGER NOT NULL DEFAULT 1,
                status TEXT NOT NULL DEFAULT \'draft\',
                delivery_method TEXT NOT NULL DEFAULT \'delivery\',
                fulfillment_status TEXT NOT NULL DEFAULT \'pending\',
                fulfillment_notes TEXT,
                fulfillment_updated_at TEXT
             );
             CREATE TABLE audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                action TEXT NOT NULL,
                entity TEXT NOT NULL,
                entity_id INTEGER,
                user_id INTEGER,
                metadata TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
             );'
        );

        $this->orderRepository = new OrderRepository($this->pdo);
        $this->service = new FulfillmentService(
            $this->orderRepository,
            new AuditService(new AuditLogRepository($this->pdo))
        );
    }

    public function testUpdateDraftDeliveryMethodPersistsPickup(): void
    {
        $this->pdo->exec("INSERT INTO orders (id, status, delivery_method) VALUES (1, 'draft', 'delivery')");

        $result = $this->service->updateDraftDeliveryMethod(1, ['delivery_method' => 'pickup']);

        $this->assertTrue($result['success']);
        $order = $this->orderRepository->find(1);
        $this->assertSame(DeliveryMethod::PICKUP, $order['delivery_method']);
    }

    public function testUpdateConfirmedFulfillmentRejectsInvalidStatusForPickup(): void
    {
        $this->pdo->exec("INSERT INTO orders (id, status, delivery_method, fulfillment_status)
                          VALUES (2, 'confirmed', 'pickup', 'pending')");

        $result = $this->service->updateConfirmedFulfillment(2, [
            'fulfillment_status' => FulfillmentStatus::OUT_FOR_DELIVERY,
        ]);

        $this->assertFalse($result['success']);
    }

    public function testUpdateConfirmedFulfillmentAcceptsReadyForPickup(): void
    {
        $this->pdo->exec("INSERT INTO orders (id, status, delivery_method, fulfillment_status)
                          VALUES (3, 'confirmed', 'pickup', 'picking')");

        $result = $this->service->updateConfirmedFulfillment(3, [
            'fulfillment_status' => FulfillmentStatus::READY_FOR_PICKUP,
            'fulfillment_notes' => 'Balcão 2',
        ]);

        $this->assertTrue($result['success']);
        $order = $this->orderRepository->find(3);
        $this->assertSame(FulfillmentStatus::READY_FOR_PICKUP, $order['fulfillment_status']);
        $this->assertSame('Balcão 2', $order['fulfillment_notes']);
        $this->assertNotEmpty($order['fulfillment_updated_at']);
    }

    public function testMarkCancelledSetsFulfillmentStatus(): void
    {
        $this->pdo->exec("INSERT INTO orders (id, status, fulfillment_status) VALUES (4, 'confirmed', 'picking')");

        $this->service->markCancelled(4);

        $order = $this->orderRepository->find(4);
        $this->assertSame(FulfillmentStatus::CANCELLED, $order['fulfillment_status']);
    }
}
