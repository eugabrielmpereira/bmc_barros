<?php

declare(strict_types=1);

namespace Erp\Finance\Services;

use Core\Database;
use Erp\Audit\Services\AuditService;
use Erp\Finance\Support\PaymentStatusLabel;
use Erp\Orders\Repositories\OrderRepository;
use PDO;
use Throwable;

final class ReceivableService
{
    public function __construct(
        private ?PDO $pdo = null,
        private ?OrderRepository $orderRepository = null,
        private ?AuditService $auditService = null
    ) {
        $this->pdo ??= Database::getInstance();
        $this->orderRepository ??= new OrderRepository($this->pdo);
        $this->auditService ??= new AuditService();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listOpen(string $search = ''): array
    {
        return $this->orderRepository->listOpenReceivables($search);
    }

    /**
     * @return array{count: int, totalAmount: float}
     */
    public function openSummary(): array
    {
        return [
            'count' => $this->orderRepository->countOpenReceivables(),
            'totalAmount' => $this->orderRepository->sumOpenReceivables(),
        ];
    }

    /**
     * @return array{success: bool, message: string, errors: list<string>}
     */
    public function markAsPaid(int $orderId, ?int $userId = null): array
    {
        $order = $this->orderRepository->find($orderId);
        if ($order === null) {
            return ['success' => false, 'message' => 'Pedido não encontrado.', 'errors' => ['Pedido não encontrado.']];
        }

        if ((string) ($order['status'] ?? '') !== 'confirmed') {
            return [
                'success' => false,
                'message' => 'Somente pedidos faturados podem receber baixa de pagamento.',
                'errors' => ['Status comercial inválido para baixa.'],
            ];
        }

        if ((string) ($order['payment_status'] ?? '') === PaymentStatusLabel::PAID) {
            return ['success' => false, 'message' => 'Pedido já está marcado como pago.', 'errors' => ['Pagamento já registrado.']];
        }

        if ((string) ($order['payment_status'] ?? '') === PaymentStatusLabel::CANCELLED) {
            return ['success' => false, 'message' => 'Pedido cancelado não pode receber baixa.', 'errors' => ['Pagamento cancelado.']];
        }

        $paidAt = date('Y-m-d H:i:s');
        $updated = $this->orderRepository->updatePaymentStatus($orderId, PaymentStatusLabel::PAID, $paidAt);
        if (!$updated) {
            return ['success' => false, 'message' => 'Não foi possível registrar o pagamento.', 'errors' => ['Falha ao atualizar.']];
        }

        $this->auditService->record('payment_mark_paid', 'order', $orderId, [
            'paid_at' => $paidAt,
            'user_id' => $userId,
        ]);

        return ['success' => true, 'message' => 'Pagamento registrado com sucesso.', 'errors' => []];
    }

    /**
     * Baixa automática via webhook do gateway (sem usuário admin).
     *
     * @param array<string, mixed> $meta
     * @return array{success: bool, message: string, errors: list<string>}
     */
    public function markAsPaidFromGateway(int $orderId, ?string $paidAt = null, array $meta = []): array
    {
        $order = $this->orderRepository->find($orderId);
        if ($order === null) {
            return ['success' => false, 'message' => 'Pedido não encontrado.', 'errors' => ['Pedido não encontrado.']];
        }

        if ((string) ($order['payment_status'] ?? '') === PaymentStatusLabel::PAID) {
            return ['success' => true, 'message' => 'Pagamento já registrado.', 'errors' => []];
        }

        if ((string) ($order['status'] ?? '') !== 'confirmed') {
            return [
                'success' => false,
                'message' => 'Pedido ainda não faturado; pagamento guardado no gateway.',
                'errors' => ['Status comercial inválido para baixa automática.'],
            ];
        }

        $paidAt ??= date('Y-m-d H:i:s');
        $updated = $this->orderRepository->updatePaymentStatus($orderId, PaymentStatusLabel::PAID, $paidAt);
        if (!$updated) {
            return ['success' => false, 'message' => 'Não foi possível registrar o pagamento.', 'errors' => ['Falha ao atualizar.']];
        }

        $this->auditService->record('payment_gateway_paid', 'order', $orderId, array_merge($meta, [
            'paid_at' => $paidAt,
            'source' => 'gateway_webhook',
        ]));

        return ['success' => true, 'message' => 'Pagamento registrado via gateway.', 'errors' => []];
    }

    /**
     * Reversão financeira via estorno/exclusão no gateway (efeito inverso de markAsPaidFromGateway).
     *
     * @return array{success: bool, reverted: bool, message: string, errors: list<string>}
     */
    public function reopenFromGatewayRefund(int $orderId): array
    {
        $order = $this->orderRepository->find($orderId);
        if ($order === null) {
            return [
                'success' => false,
                'reverted' => false,
                'message' => 'Pedido não encontrado.',
                'errors' => ['Pedido não encontrado.'],
            ];
        }

        if ((string) ($order['payment_status'] ?? '') !== PaymentStatusLabel::PAID) {
            return [
                'success' => true,
                'reverted' => false,
                'message' => 'Pagamento já não estava baixado.',
                'errors' => [],
            ];
        }

        $updated = $this->orderRepository->updatePaymentStatus(
            $orderId,
            PaymentStatusLabel::PENDING,
            null,
            true
        );
        if (!$updated) {
            return [
                'success' => false,
                'reverted' => false,
                'message' => 'Não foi possível reverter o pagamento.',
                'errors' => ['Falha ao atualizar.'],
            ];
        }

        return [
            'success' => true,
            'reverted' => true,
            'message' => 'Pagamento revertido; recebível reaberto.',
            'errors' => [],
        ];
    }

    /**
     * Ao aprovar pedido: se Pix já foi pago no gateway, baixa automática; senão, pendente.
     */
    public function applyPaymentStatusOnConfirm(int $orderId, bool $gatewayAlreadyPaid): void
    {
        if ($gatewayAlreadyPaid) {
            $this->markAsPaidFromGateway($orderId, date('Y-m-d H:i:s'), ['source' => 'gateway_on_approve']);

            return;
        }

        $this->setPendingOnConfirm($orderId);
    }

    public function syncPaymentStatusForOrder(int $orderId, string $orderStatus): void
    {
        $paymentStatus = PaymentStatusLabel::forOrderStatus($orderStatus);
        $paidAt = $paymentStatus === PaymentStatusLabel::PAID ? date('Y-m-d H:i:s') : null;
        $this->orderRepository->updatePaymentStatus($orderId, $paymentStatus, $paidAt, $paymentStatus !== PaymentStatusLabel::PAID);
    }

    public function resetPaymentOnReopen(int $orderId): void
    {
        $this->orderRepository->updatePaymentStatus(
            $orderId,
            PaymentStatusLabel::NOT_APPLICABLE,
            null,
            true
        );
    }

    public function setPendingOnConfirm(int $orderId): void
    {
        $this->orderRepository->updatePaymentStatus(
            $orderId,
            PaymentStatusLabel::PENDING,
            null,
            true
        );
    }

    /**
     * @param callable(): mixed $callback
     */
    public function safeSummary(callable $callback, mixed $fallback): mixed
    {
        try {
            return $callback();
        } catch (Throwable) {
            return $fallback;
        }
    }
}
