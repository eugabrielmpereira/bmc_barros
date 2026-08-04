<?php

declare(strict_types=1);

namespace Erp\Orders\Services;

use Core\Security\OrderTrackingRateLimiter;
use Erp\Fulfillment\Support\DeliveryMethod;
use Erp\Fulfillment\Support\DeliveryMethodLabel;
use Erp\Fulfillment\Support\FulfillmentStatusLabel;
use Erp\Orders\Repositories\OrderItemRepository;
use Erp\Orders\Repositories\OrderRepository;
use Shared\Support\Phone;

final class OrderTrackingService
{
    /** @var array<string, string> */
    private const STATUS_LABELS = [
        'draft' => 'Aguardando confirmação da loja',
        'confirmed' => 'Pedido confirmado',
        'cancelled' => 'Pedido cancelado',
    ];

    public function __construct(
        private ?OrderRepository $orderRepository = null,
        private ?OrderItemRepository $itemRepository = null,
        private ?OrderTrackingRateLimiter $rateLimiter = null
    ) {
        $this->orderRepository ??= new OrderRepository();
        $this->itemRepository ??= new OrderItemRepository();
        $this->rateLimiter ??= new OrderTrackingRateLimiter();
    }

    /**
     * @return array{success: bool, message: string, result?: array<string, mixed>}
     */
    public function lookup(string $reference, string $phoneDigits, string $clientIp): array
    {
        $reference = strtoupper(trim($reference));
        $phoneDigits = preg_replace('/\D+/', '', trim($phoneDigits)) ?? '';

        if ($reference === '' || strlen($phoneDigits) < 4) {
            return ['success' => false, 'message' => 'Informe a referência do pedido e os 4 últimos dígitos do telefone.'];
        }

        if ($this->rateLimiter->isBlocked($clientIp)) {
            return ['success' => false, 'message' => 'Muitas tentativas. Aguarde alguns minutos e tente novamente.'];
        }

        $this->rateLimiter->recordAttempt($clientIp);

        $order = $this->orderRepository->findForPublicTracking($reference);
        if ($order === null || trim((string) ($order['public_reference'] ?? '')) === '') {
            return ['success' => false, 'message' => 'Pedido não encontrado. Verifique os dados informados.'];
        }

        $registeredPhone = (string) ($order['client_phone'] ?? '');
        if (!Phone::matchesLastFourDigits($registeredPhone, $phoneDigits)) {
            return ['success' => false, 'message' => 'Pedido não encontrado. Verifique os dados informados.'];
        }

        $orderId = (int) ($order['id'] ?? 0);
        $items = $this->itemRepository->listByOrder($orderId);
        $status = (string) ($order['status'] ?? 'draft');
        $deliveryMethod = DeliveryMethod::parse((string) ($order['delivery_method'] ?? DeliveryMethod::DELIVERY));
        $fulfillmentStatus = (string) ($order['fulfillment_status'] ?? 'pending');
        $operationalLabel = FulfillmentStatusLabel::forPublicTracking($status, $fulfillmentStatus, $deliveryMethod);

        return [
            'success' => true,
            'message' => '',
            'result' => [
                'public_reference' => (string) $order['public_reference'],
                'status' => $status,
                'status_label' => $operationalLabel,
                'commercial_status_label' => self::STATUS_LABELS[$status] ?? $status,
                'delivery_method' => $deliveryMethod,
                'delivery_method_label' => DeliveryMethodLabel::resolve($deliveryMethod),
                'fulfillment_status' => $fulfillmentStatus,
                'fulfillment_status_label' => FulfillmentStatusLabel::resolve($fulfillmentStatus),
                'total_amount' => (float) ($order['total_amount'] ?? 0),
                'created_at' => (string) ($order['created_at'] ?? ''),
                'shipping_city' => (string) ($order['shipping_city'] ?? ''),
                'shipping_state' => (string) ($order['shipping_state'] ?? ''),
                'items' => array_map(
                    static fn (array $item): array => [
                        'product_name' => (string) ($item['product_name'] ?? 'Produto'),
                        'quantity' => (int) ($item['quantity'] ?? 0),
                        'total_amount' => (float) ($item['total_amount'] ?? 0),
                    ],
                    $items
                ),
            ],
        ];
    }

    public static function statusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? $status;
    }
}
