<?php

declare(strict_types=1);

namespace Erp\Finance\Services;

use Core\Database;
use Erp\Audit\Services\AuditService;
use Erp\Finance\Gateways\Asaas\AsaasConfig;
use Erp\Finance\Repositories\PaymentChargeRepository;
use Erp\Finance\Support\PaymentStatusLabel;
use Erp\Orders\Repositories\OrderRepository;
use Erp\Orders\Services\OrderService;
use PDO;
use RuntimeException;
use Throwable;

final class AsaasWebhookService
{
    /** @var list<string> */
    private const PAID_EVENTS = ['PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED'];

    public function __construct(
        private ?PDO $pdo = null,
        private ?PaymentChargeRepository $chargeRepository = null,
        private ?OrderRepository $orderRepository = null,
        private ?ReceivableService $receivableService = null,
        private ?AuditService $auditService = null,
        private ?OrderService $orderService = null
    ) {
        $this->pdo ??= Database::getInstance();
        $this->chargeRepository ??= new PaymentChargeRepository($this->pdo);
        $this->orderRepository ??= new OrderRepository($this->pdo);
        $this->auditService ??= new AuditService();
        $this->receivableService ??= new ReceivableService($this->pdo, $this->orderRepository, $this->auditService);
        $this->orderService ??= new OrderService(
            $this->pdo,
            $this->orderRepository,
            auditService: $this->auditService,
            receivableService: $this->receivableService
        );
    }

    public function isAuthorized(?string $headerToken, ?string $expectedToken = null): bool
    {
        $expected = trim((string) ($expectedToken ?? AsaasConfig::webhookToken()));
        if ($expected === '') {
            return false;
        }

        $received = trim((string) $headerToken);

        return $received !== '' && hash_equals($expected, $received);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{success: bool, message: string, http_status: int}
     */
    public function handle(array $payload): array
    {
        $event = strtoupper(trim((string) ($payload['event'] ?? '')));
        $payment = $payload['payment'] ?? null;
        if (!is_array($payment)) {
            return [
                'success' => false,
                'message' => 'Payload inválido.',
                'http_status' => 400,
            ];
        }

        $externalPaymentId = trim((string) ($payment['id'] ?? ''));
        if ($externalPaymentId === '') {
            return [
                'success' => false,
                'message' => 'Cobrança não identificada.',
                'http_status' => 400,
            ];
        }

        $charge = $this->chargeRepository->findByExternalPaymentId('asaas', $externalPaymentId);
        if ($charge === null) {
            $orderId = $this->resolveOrderIdFromPayment($payment);
            if ($orderId !== null) {
                $charge = $this->chargeRepository->findByOrderId($orderId);
            }
        }

        if ($charge === null) {
            error_log('[asaas_webhook] charge not found for payment ' . $externalPaymentId);

            return [
                'success' => true,
                'message' => 'Cobrança não vinculada; ignorado.',
                'http_status' => 200,
            ];
        }

        $paymentStatus = strtoupper(trim((string) ($payment['status'] ?? '')));
        $chargeId = (int) ($charge['id'] ?? 0);
        $orderId = (int) ($charge['order_id'] ?? 0);

        try {
            if (in_array($event, self::PAID_EVENTS, true) || PaymentChargeService::isPaidStatus($paymentStatus)) {
                return $this->markChargePaid($chargeId, $orderId, $paymentStatus, $payment);
            }

            if ($event === 'PAYMENT_OVERDUE' || $paymentStatus === 'OVERDUE') {
                return $this->handlePaymentOverdue($chargeId, $orderId, $payment);
            }

            if (in_array($event, ['PAYMENT_REFUNDED', 'PAYMENT_DELETED'], true)) {
                return $this->handlePaymentRefunded($chargeId, $orderId, $payment, $event);
            }

            if ($paymentStatus !== '') {
                $this->chargeRepository->updateCharge($chargeId, ['status' => $paymentStatus]);
            }

            return [
                'success' => true,
                'message' => 'Evento registrado.',
                'http_status' => 200,
            ];
        } catch (Throwable $e) {
            error_log('[asaas_webhook] ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro interno ao processar webhook.',
                'http_status' => 500,
            ];
        }
    }

    /**
     * @param array<string, mixed> $payment
     * @return array{success: bool, message: string, http_status: int}
     */
    private function markChargePaid(int $chargeId, int $orderId, string $paymentStatus, array $payment): array
    {
        $paidAt = $this->resolvePaidAt($payment);
        $this->chargeRepository->updateCharge($chargeId, [
            'status' => $paymentStatus !== '' ? $paymentStatus : 'RECEIVED',
            'paid_at' => $paidAt,
        ]);

        $order = $this->orderRepository->find($orderId);
        if ($order === null) {
            return [
                'success' => true,
                'message' => 'Cobrança paga; pedido não encontrado.',
                'http_status' => 200,
            ];
        }

        $orderStatus = (string) ($order['status'] ?? '');
        if ($orderStatus === 'confirmed') {
            $this->receivableService->markAsPaidFromGateway($orderId, $paidAt, [
                'provider' => 'asaas',
                'external_payment_id' => (string) ($payment['id'] ?? ''),
            ]);
        } else {
            $this->auditService->record('gateway_pix_received', 'order', $orderId, [
                'provider' => 'asaas',
                'external_payment_id' => (string) ($payment['id'] ?? ''),
                'order_status' => $orderStatus,
            ]);
        }

        return [
            'success' => true,
            'message' => 'Pagamento Pix registrado.',
            'http_status' => 200,
        ];
    }

    /**
     * @param array<string, mixed> $payment
     */
    private function resolveOrderIdFromPayment(array $payment): ?int
    {
        $externalReference = trim((string) ($payment['externalReference'] ?? ''));
        if ($externalReference !== '' && ctype_digit($externalReference)) {
            return (int) $externalReference;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payment
     */
    private function resolvePaidAt(array $payment): string
    {
        foreach (['paymentDate', 'clientPaymentDate', 'confirmedDate', 'creditDate'] as $field) {
            $value = trim((string) ($payment[$field] ?? ''));
            if ($value !== '') {
                $timestamp = strtotime($value);
                if ($timestamp !== false) {
                    return date('Y-m-d H:i:s', $timestamp);
                }
            }
        }

        return date('Y-m-d H:i:s');
    }

    /**
     * @param array<string, mixed> $payment
     * @return array{success: bool, message: string, http_status: int}
     */
    private function handlePaymentOverdue(int $chargeId, int $orderId, array $payment): array
    {
        $ownsTransaction = !$this->pdo->inTransaction();
        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $this->chargeRepository->updateCharge($chargeId, ['status' => 'OVERDUE']);

            $order = $this->orderRepository->find($orderId);
            if ($order === null) {
                if ($ownsTransaction) {
                    $this->pdo->commit();
                }

                return [
                    'success' => true,
                    'message' => 'Cobrança vencida registrada.',
                    'http_status' => 200,
                ];
            }

            $orderStatus = (string) ($order['status'] ?? '');
            $message = 'Cobrança vencida registrada.';

            if ($orderStatus === 'draft') {
                $cancelResult = $this->orderService->cancel($orderId, null, OrderService::CANCEL_SOURCE_PIX_OVERDUE_AUTO);
                if (!$cancelResult['success']) {
                    throw new RuntimeException($cancelResult['message']);
                }

                $message = 'Cobrança vencida; pedido cancelado e reserva de estoque liberada.';
            } elseif ($orderStatus !== 'cancelled') {
                $this->auditService->record('gateway_pix_overdue_orphan', 'order', $orderId, [
                    'provider' => 'asaas',
                    'external_payment_id' => (string) ($payment['id'] ?? ''),
                    'order_status' => $orderStatus,
                    'charge_id' => $chargeId,
                ]);
                $message = 'Cobrança vencida registrada para investigação manual.';
            }

            if ($ownsTransaction) {
                $this->pdo->commit();
            }

            return [
                'success' => true,
                'message' => $message,
                'http_status' => 200,
            ];
        } catch (Throwable $e) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $payment
     * @return array{success: bool, message: string, http_status: int}
     */
    private function handlePaymentRefunded(int $chargeId, int $orderId, array $payment, string $event): array
    {
        $chargeStatus = $event === 'PAYMENT_DELETED' ? 'DELETED' : 'REFUNDED';
        $externalPaymentId = (string) ($payment['id'] ?? '');

        $ownsTransaction = !$this->pdo->inTransaction();
        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $this->chargeRepository->updateCharge($chargeId, [
                'status' => $chargeStatus,
                'paid_at' => null,
            ]);

            $financialReverted = false;
            $orderStatus = '';
            $fulfillmentStatus = '';

            $order = $this->orderRepository->find($orderId);
            if ($order !== null) {
                $orderStatus = (string) ($order['status'] ?? '');
                $fulfillmentStatus = (string) ($order['fulfillment_status'] ?? 'pending');

                if ((string) ($order['payment_status'] ?? '') === PaymentStatusLabel::PAID) {
                    $reopenResult = $this->receivableService->reopenFromGatewayRefund($orderId);
                    if (!$reopenResult['success']) {
                        throw new RuntimeException($reopenResult['message']);
                    }

                    if ($reopenResult['reverted']) {
                        $financialReverted = true;
                        if (!$this->auditService->hasActionForEntity('payment_reverted_pix_refunded', 'order', $orderId)) {
                            $this->auditService->record('payment_reverted_pix_refunded', 'order', $orderId, [
                                'provider' => 'asaas',
                                'external_payment_id' => $externalPaymentId,
                                'previous_payment_status' => PaymentStatusLabel::PAID,
                                'new_payment_status' => PaymentStatusLabel::PENDING,
                                'gateway_event' => $event,
                            ]);
                        }
                    }
                }

                if (!$this->auditService->hasActionForEntity('gateway_pix_refunded_requires_review', 'order', $orderId)) {
                    $this->auditService->record('gateway_pix_refunded_requires_review', 'order', $orderId, [
                        'provider' => 'asaas',
                        'external_payment_id' => $externalPaymentId,
                        'order_status' => $orderStatus,
                        'fulfillment_status' => $fulfillmentStatus,
                        'stock_likely_reduced' => $orderStatus === 'confirmed',
                        'financial_reverted' => $financialReverted,
                        'gateway_event' => $event,
                        'refunded_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }

            if ($ownsTransaction) {
                $this->pdo->commit();
            }

            $message = $financialReverted
                ? 'Estorno registrado; pagamento revertido e recebível reaberto.'
                : 'Estorno registrado; revisão operacional pendente.';

            return [
                'success' => true,
                'message' => $message,
                'http_status' => 200,
            ];
        } catch (Throwable $e) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }
}
