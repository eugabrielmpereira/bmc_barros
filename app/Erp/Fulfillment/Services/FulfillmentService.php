<?php

declare(strict_types=1);

namespace Erp\Fulfillment\Services;

use Erp\Audit\Services\AuditService;
use Erp\Fulfillment\Support\DeliveryMethod;
use Erp\Fulfillment\Support\FulfillmentStatus;
use Erp\Orders\Repositories\OrderRepository;

final class FulfillmentService
{
    public function __construct(
        private ?OrderRepository $orderRepository = null,
        private ?AuditService $auditService = null
    ) {
        $this->orderRepository ??= new OrderRepository();
        $this->auditService ??= new AuditService();
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function updateDraftDeliveryMethod(int $orderId, array $post, ?int $userId = null): array
    {
        $order = $this->orderRepository->find($orderId);
        if ($order === null) {
            return ['success' => false, 'message' => 'Pedido não encontrado.'];
        }

        if ((string) ($order['status'] ?? '') !== 'draft') {
            return ['success' => false, 'message' => 'Forma de entrega só pode ser alterada em pedidos pendentes.'];
        }

        $deliveryMethod = DeliveryMethod::parse((string) ($post['delivery_method'] ?? ''));
        $before = $this->snapshot($order);

        $this->orderRepository->updateFulfillmentFields($orderId, [
            'delivery_method' => $deliveryMethod,
        ]);

        $this->auditService->record('order_delivery_method_update', 'order', $orderId, [
            'before' => $before,
            'after' => array_merge($before, ['delivery_method' => $deliveryMethod]),
            'user_id' => $userId,
        ]);

        return ['success' => true, 'message' => 'Forma de entrega atualizada.'];
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function updateConfirmedFulfillment(int $orderId, array $post, ?int $userId = null): array
    {
        $order = $this->orderRepository->find($orderId);
        if ($order === null) {
            return ['success' => false, 'message' => 'Pedido não encontrado.'];
        }

        if ((string) ($order['status'] ?? '') !== 'confirmed') {
            return ['success' => false, 'message' => 'Expedição só pode ser atualizada em pedidos faturados.'];
        }

        $deliveryMethod = DeliveryMethod::parse((string) ($order['delivery_method'] ?? DeliveryMethod::DELIVERY));
        $fulfillmentStatus = FulfillmentStatus::parse((string) ($post['fulfillment_status'] ?? ''));

        if (!FulfillmentStatus::isAllowedForDeliveryMethod($fulfillmentStatus, $deliveryMethod)) {
            return ['success' => false, 'message' => 'Status de expedição inválido para a forma de entrega deste pedido.'];
        }

        $fulfillmentNotes = trim((string) ($post['fulfillment_notes'] ?? ''));
        $before = $this->snapshot($order);

        $this->orderRepository->updateFulfillmentFields($orderId, [
            'fulfillment_status' => $fulfillmentStatus,
            'fulfillment_notes' => $fulfillmentNotes !== '' ? $fulfillmentNotes : null,
            'fulfillment_updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->auditService->record('order_fulfillment_update', 'order', $orderId, [
            'before' => $before,
            'after' => array_merge($before, [
                'fulfillment_status' => $fulfillmentStatus,
                'fulfillment_notes' => $fulfillmentNotes !== '' ? $fulfillmentNotes : null,
            ]),
            'user_id' => $userId,
        ]);

        return ['success' => true, 'message' => 'Status de expedição atualizado.'];
    }

    public function markCancelled(int $orderId): void
    {
        $this->orderRepository->updateFulfillmentFields($orderId, [
            'fulfillment_status' => FulfillmentStatus::CANCELLED,
            'fulfillment_updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function resetAfterReopen(int $orderId): void
    {
        $this->orderRepository->updateFulfillmentFields($orderId, [
            'fulfillment_status' => FulfillmentStatus::PENDING,
            'fulfillment_updated_at' => null,
        ]);
    }

    public function ensurePendingOnConfirm(int $orderId): void
    {
        $order = $this->orderRepository->find($orderId);
        if ($order === null) {
            return;
        }

        $current = (string) ($order['fulfillment_status'] ?? FulfillmentStatus::PENDING);
        if ($current === FulfillmentStatus::CANCELLED) {
            $this->orderRepository->updateFulfillmentFields($orderId, [
                'fulfillment_status' => FulfillmentStatus::PENDING,
                'fulfillment_updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * @param array<string, mixed> $order
     * @return array<string, mixed>
     */
    private function snapshot(array $order): array
    {
        return [
            'delivery_method' => (string) ($order['delivery_method'] ?? DeliveryMethod::DELIVERY),
            'fulfillment_status' => (string) ($order['fulfillment_status'] ?? FulfillmentStatus::PENDING),
            'fulfillment_notes' => $order['fulfillment_notes'] ?? null,
        ];
    }
}
