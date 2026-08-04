<?php

declare(strict_types=1);

namespace Tests\Unit;

use Erp\Audit\Repositories\AuditLogRepository;
use Erp\Audit\Services\AuditService;
use Erp\Finance\Repositories\PaymentChargeRepository;
use Erp\Finance\Services\AsaasWebhookService;
use Erp\Finance\Services\ReceivableService;
use Erp\Finance\Support\PaymentStatusLabel;
use Erp\Orders\Repositories\OrderRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemorySchema;

final class AsaasWebhookServiceTest extends TestCase
{
    private PDO $pdo;
    private AsaasWebhookService $service;
    private OrderRepository $orderRepository;

    protected function setUp(): void
    {
        $_ENV['ASAAS_WEBHOOK_TOKEN'] = 'test-webhook-token-with-minimum-length-32chars';
        $_SERVER['ASAAS_WEBHOOK_TOKEN'] = $_ENV['ASAAS_WEBHOOK_TOKEN'];

        $this->pdo = InMemorySchema::createPdo();
        $this->pdo->exec(InMemorySchema::financeWebhookCore());

        $this->pdo->exec('INSERT INTO commercial_clients (id, name) VALUES (1, \'Cliente\')');
        $this->pdo->exec("INSERT INTO categorias (id, nome) VALUES (1, 'Geral')");
        $this->pdo->exec("INSERT INTO produtos (id, categoria_id, nome, estoque_atual, status) VALUES (1, 1, 'Produto', 10, 'ativo')");
        $this->pdo->exec(
            "INSERT INTO orders (id, commercial_client_id, status, payment_status, total_amount)
             VALUES (5, 1, 'confirmed', 'pending', 120.0)"
        );
        $this->pdo->exec(
            "INSERT INTO order_payment_charges
             (order_id, provider, external_payment_id, status, billing_type, amount, due_date)
             VALUES (5, 'asaas', 'pay_test_1', 'PENDING', 'PIX', 120.0, '2026-06-10')"
        );

        $this->orderRepository = new OrderRepository($this->pdo);
        $auditService = InMemorySchema::auditService($this->pdo);
        $receivableService = new ReceivableService($this->pdo, $this->orderRepository, $auditService);
        $orderService = InMemorySchema::orderService($this->pdo, $auditService, $this->orderRepository);

        $this->service = new AsaasWebhookService(
            $this->pdo,
            new PaymentChargeRepository($this->pdo),
            $this->orderRepository,
            $receivableService,
            $auditService,
            $orderService
        );
    }

    protected function tearDown(): void
    {
        unset($_ENV['ASAAS_WEBHOOK_TOKEN'], $_SERVER['ASAAS_WEBHOOK_TOKEN']);
    }

    public function testIsAuthorizedComparesTokenSafely(): void
    {
        $this->assertTrue($this->service->isAuthorized('test-webhook-token-with-minimum-length-32chars'));
        $this->assertFalse($this->service->isAuthorized('wrong-token'));
        $this->assertFalse($this->service->isAuthorized(null));
    }

    public function testHandlePaymentReceivedMarksChargeAndOrderPaid(): void
    {
        $result = $this->service->handle([
            'event' => 'PAYMENT_RECEIVED',
            'payment' => [
                'id' => 'pay_test_1',
                'status' => 'RECEIVED',
                'externalReference' => '5',
                'paymentDate' => '2026-06-07',
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(200, $result['http_status']);

        $charge = $this->pdo->query('SELECT status, paid_at FROM order_payment_charges WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('RECEIVED', $charge['status']);
        $this->assertNotEmpty($charge['paid_at']);

        $order = $this->orderRepository->find(5);
        $this->assertSame(PaymentStatusLabel::PAID, $order['payment_status']);
    }

    public function testHandlePixOnDraftOrderDoesNotMarkOrderPaid(): void
    {
        $this->pdo->exec(
            "INSERT INTO orders (id, commercial_client_id, status, payment_status, total_amount)
             VALUES (6, 1, 'draft', 'not_applicable', 80.0)"
        );
        $this->pdo->exec(
            "INSERT INTO order_payment_charges
             (id, order_id, provider, external_payment_id, status, billing_type, amount, due_date)
             VALUES (2, 6, 'asaas', 'pay_test_2', 'PENDING', 'PIX', 80.0, '2026-06-10')"
        );

        $result = $this->service->handle([
            'event' => 'PAYMENT_RECEIVED',
            'payment' => [
                'id' => 'pay_test_2',
                'status' => 'RECEIVED',
                'externalReference' => '6',
                'paymentDate' => '2026-06-07',
            ],
        ]);

        $this->assertTrue($result['success']);

        $order = $this->orderRepository->find(6);
        $this->assertSame('draft', $order['status']);
        $this->assertSame(PaymentStatusLabel::NOT_APPLICABLE, $order['payment_status']);
    }

    public function testHandlePaymentOverdueCancelsDraftOrderAndReleasesStockReservation(): void
    {
        $this->pdo->exec(
            "INSERT INTO orders (id, commercial_client_id, status, payment_status, total_amount)
             VALUES (8, 1, 'draft', 'not_applicable', 55.0)"
        );
        $this->pdo->exec(
            "INSERT INTO order_payment_charges
             (id, order_id, provider, external_payment_id, status, billing_type, amount, due_date)
             VALUES (3, 8, 'asaas', 'pay_test_overdue', 'PENDING', 'PIX', 55.0, '2026-06-10')"
        );
        $this->pdo->exec(
            "INSERT INTO stock_reservations (order_id, product_id, quantity, status)
             VALUES (8, 1, 2, 'active')"
        );

        $result = $this->service->handle([
            'event' => 'PAYMENT_OVERDUE',
            'payment' => [
                'id' => 'pay_test_overdue',
                'status' => 'OVERDUE',
                'externalReference' => '8',
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('cancelado', strtolower($result['message']));

        $charge = $this->pdo->query('SELECT status FROM order_payment_charges WHERE id = 3')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('OVERDUE', $charge['status']);

        $order = $this->orderRepository->find(8);
        $this->assertSame('cancelled', $order['status']);

        $activeReservations = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM stock_reservations WHERE order_id = 8 AND status = 'active'"
        )->fetchColumn();
        $this->assertSame(0, $activeReservations);

        $releasedReservations = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM stock_reservations WHERE order_id = 8 AND status = 'released'"
        )->fetchColumn();
        $this->assertSame(1, $releasedReservations);

        $audit = $this->pdo->query(
            "SELECT action FROM audit_logs WHERE entity = 'order' AND entity_id = 8"
        )->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('cancel_pix_overdue_auto', $audit['action']);
    }

    public function testHandlePaymentOverdueOnNonDraftOrderRecordsOrphanAuditWithoutChangingOrder(): void
    {
        $this->pdo->exec(
            "INSERT INTO orders (id, commercial_client_id, status, payment_status, total_amount)
             VALUES (10, 1, 'confirmed', 'pending', 200.0)"
        );
        $this->pdo->exec(
            "INSERT INTO order_payment_charges
             (id, order_id, provider, external_payment_id, status, billing_type, amount, due_date)
             VALUES (5, 10, 'asaas', 'pay_test_overdue_orphan', 'PENDING', 'PIX', 200.0, '2026-06-10')"
        );

        $result = $this->service->handle([
            'event' => 'PAYMENT_OVERDUE',
            'payment' => [
                'id' => 'pay_test_overdue_orphan',
                'status' => 'OVERDUE',
                'externalReference' => '10',
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('investigação manual', $result['message']);

        $order = $this->orderRepository->find(10);
        $this->assertSame('confirmed', $order['status']);

        $audit = $this->pdo->query(
            "SELECT action FROM audit_logs WHERE entity = 'order' AND entity_id = 10"
        )->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('gateway_pix_overdue_orphan', $audit['action']);
    }

    /**
     * Nível 1: estorno com pedido pago reverte payment_status e reabre recebível.
     */
    public function testHandlePaymentRefundedRevertsPaymentStatusAndReopensReceivable(): void
    {
        $this->pdo->exec(
            "INSERT INTO orders (id, commercial_client_id, status, payment_status, paid_at, total_amount)
             VALUES (9, 1, 'confirmed', 'paid', '2026-06-07 10:00:00', 90.0)"
        );
        $this->pdo->exec(
            "INSERT INTO order_payment_charges
             (id, order_id, provider, external_payment_id, status, billing_type, amount, due_date, paid_at)
             VALUES (4, 9, 'asaas', 'pay_test_refund', 'RECEIVED', 'PIX', 90.0, '2026-06-10', '2026-06-07 10:00:00')"
        );

        $receivableService = new ReceivableService($this->pdo, $this->orderRepository, InMemorySchema::auditService($this->pdo));
        $openBefore = $receivableService->openSummary()['count'];

        $result = $this->service->handle([
            'event' => 'PAYMENT_REFUNDED',
            'payment' => [
                'id' => 'pay_test_refund',
                'status' => 'REFUNDED',
                'externalReference' => '9',
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('revertido', strtolower($result['message']));

        $charge = $this->pdo->query('SELECT status, paid_at FROM order_payment_charges WHERE id = 4')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('REFUNDED', $charge['status']);
        $this->assertNull($charge['paid_at']);

        $order = $this->orderRepository->find(9);
        $this->assertSame(PaymentStatusLabel::PENDING, $order['payment_status']);
        $this->assertNull($order['paid_at']);

        $this->assertSame($openBefore + 1, $receivableService->openSummary()['count']);

        $auditActions = $this->pdo->query(
            "SELECT action FROM audit_logs WHERE entity = 'order' AND entity_id = 9 ORDER BY id ASC"
        )->fetchAll(PDO::FETCH_COLUMN);
        $this->assertContains('payment_reverted_pix_refunded', $auditActions);
        $this->assertContains('gateway_pix_refunded_requires_review', $auditActions);
    }

    public function testHandlePaymentRefundedWhenNotPaidRecordsReviewWithoutFinancialRevert(): void
    {
        $this->pdo->exec(
            "INSERT INTO orders (id, commercial_client_id, status, payment_status, total_amount)
             VALUES (11, 1, 'confirmed', 'pending', 75.0)"
        );
        $this->pdo->exec(
            "INSERT INTO order_payment_charges
             (id, order_id, provider, external_payment_id, status, billing_type, amount, due_date, paid_at)
             VALUES (6, 11, 'asaas', 'pay_test_refund_pending', 'RECEIVED', 'PIX', 75.0, '2026-06-10', '2026-06-07 10:00:00')"
        );

        $result = $this->service->handle([
            'event' => 'PAYMENT_REFUNDED',
            'payment' => [
                'id' => 'pay_test_refund_pending',
                'status' => 'REFUNDED',
                'externalReference' => '11',
            ],
        ]);

        $this->assertTrue($result['success']);

        $order = $this->orderRepository->find(11);
        $this->assertSame(PaymentStatusLabel::PENDING, $order['payment_status']);

        $auditActions = $this->pdo->query(
            "SELECT action FROM audit_logs WHERE entity = 'order' AND entity_id = 11"
        )->fetchAll(PDO::FETCH_COLUMN);
        $this->assertNotContains('payment_reverted_pix_refunded', $auditActions);
        $this->assertContains('gateway_pix_refunded_requires_review', $auditActions);
    }

    public function testHandlePaymentDeletedFollowsSamePathAsRefunded(): void
    {
        $this->pdo->exec(
            "INSERT INTO orders (id, commercial_client_id, status, payment_status, paid_at, total_amount)
             VALUES (12, 1, 'confirmed', 'paid', '2026-06-07 11:00:00', 60.0)"
        );
        $this->pdo->exec(
            "INSERT INTO order_payment_charges
             (id, order_id, provider, external_payment_id, status, billing_type, amount, due_date, paid_at)
             VALUES (7, 12, 'asaas', 'pay_test_deleted', 'RECEIVED', 'PIX', 60.0, '2026-06-10', '2026-06-07 11:00:00')"
        );

        $result = $this->service->handle([
            'event' => 'PAYMENT_DELETED',
            'payment' => [
                'id' => 'pay_test_deleted',
                'status' => 'DELETED',
                'externalReference' => '12',
            ],
        ]);

        $this->assertTrue($result['success']);

        $charge = $this->pdo->query('SELECT status FROM order_payment_charges WHERE id = 7')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('DELETED', $charge['status']);

        $order = $this->orderRepository->find(12);
        $this->assertSame(PaymentStatusLabel::PENDING, $order['payment_status']);

        $reviewAudit = $this->pdo->query(
            "SELECT metadata FROM audit_logs
             WHERE entity = 'order' AND entity_id = 12 AND action = 'gateway_pix_refunded_requires_review'"
        )->fetch(PDO::FETCH_ASSOC);
        $this->assertNotFalse($reviewAudit);
        $metadata = json_decode((string) $reviewAudit['metadata'], true);
        $this->assertSame('PAYMENT_DELETED', $metadata['gateway_event'] ?? '');
    }

    public function testHandlePaymentRefundedOnConfirmedOrderRecordsStockReviewHint(): void
    {
        $this->pdo->exec(
            "INSERT INTO orders (id, commercial_client_id, status, payment_status, paid_at, fulfillment_status, total_amount)
             VALUES (13, 1, 'confirmed', 'paid', '2026-06-07 12:00:00', 'shipped', 150.0)"
        );
        $this->pdo->exec(
            "INSERT INTO order_payment_charges
             (id, order_id, provider, external_payment_id, status, billing_type, amount, due_date, paid_at)
             VALUES (8, 13, 'asaas', 'pay_test_refund_stock', 'RECEIVED', 'PIX', 150.0, '2026-06-10', '2026-06-07 12:00:00')"
        );

        $result = $this->service->handle([
            'event' => 'PAYMENT_REFUNDED',
            'payment' => [
                'id' => 'pay_test_refund_stock',
                'status' => 'REFUNDED',
                'externalReference' => '13',
            ],
        ]);

        $this->assertTrue($result['success']);

        $reviewAudit = $this->pdo->query(
            "SELECT metadata FROM audit_logs
             WHERE entity = 'order' AND entity_id = 13 AND action = 'gateway_pix_refunded_requires_review'"
        )->fetch(PDO::FETCH_ASSOC);
        $metadata = json_decode((string) $reviewAudit['metadata'], true);
        $this->assertTrue((bool) ($metadata['stock_likely_reduced'] ?? false));
        $this->assertSame('confirmed', $metadata['order_status'] ?? '');
        $this->assertSame('shipped', $metadata['fulfillment_status'] ?? '');
    }

    public function testHandlePaymentRefundedReplayIsIdempotent(): void
    {
        $this->pdo->exec(
            "INSERT INTO orders (id, commercial_client_id, status, payment_status, paid_at, total_amount)
             VALUES (14, 1, 'confirmed', 'paid', '2026-06-07 13:00:00', 40.0)"
        );
        $this->pdo->exec(
            "INSERT INTO order_payment_charges
             (id, order_id, provider, external_payment_id, status, billing_type, amount, due_date, paid_at)
             VALUES (9, 14, 'asaas', 'pay_test_refund_replay', 'RECEIVED', 'PIX', 40.0, '2026-06-10', '2026-06-07 13:00:00')"
        );

        $payload = [
            'event' => 'PAYMENT_REFUNDED',
            'payment' => [
                'id' => 'pay_test_refund_replay',
                'status' => 'REFUNDED',
                'externalReference' => '14',
            ],
        ];

        $this->assertTrue($this->service->handle($payload)['success']);
        $this->assertTrue($this->service->handle($payload)['success']);

        $order = $this->orderRepository->find(14);
        $this->assertSame(PaymentStatusLabel::PENDING, $order['payment_status']);

        $revertedCount = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM audit_logs
             WHERE entity = 'order' AND entity_id = 14 AND action = 'payment_reverted_pix_refunded'"
        )->fetchColumn();
        $this->assertSame(1, $revertedCount);

        $reviewCount = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM audit_logs
             WHERE entity = 'order' AND entity_id = 14 AND action = 'gateway_pix_refunded_requires_review'"
        )->fetchColumn();
        $this->assertSame(1, $reviewCount);
    }
}
