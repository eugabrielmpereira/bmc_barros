<?php

declare(strict_types=1);

namespace Erp\Fulfillment\Support;

final class FulfillmentStatusLabel
{
    /** @var array<string, string> */
    private const LABELS = [
        FulfillmentStatus::PENDING => 'Aguardando separação',
        FulfillmentStatus::PICKING => 'Em separação',
        FulfillmentStatus::READY_FOR_PICKUP => 'Pronto para retirada',
        FulfillmentStatus::OUT_FOR_DELIVERY => 'Saiu para entrega',
        FulfillmentStatus::COMPLETED => 'Entregue / retirado',
        FulfillmentStatus::CANCELLED => 'Expedição cancelada',
    ];

    /** @var array<string, string> */
    private const BADGE_CLASSES = [
        FulfillmentStatus::PENDING => 'badge-pill-pending',
        FulfillmentStatus::PICKING => 'badge-pill-origin',
        FulfillmentStatus::READY_FOR_PICKUP => 'badge-pill-origin',
        FulfillmentStatus::OUT_FOR_DELIVERY => 'badge-pill-origin',
        FulfillmentStatus::COMPLETED => 'badge-pill-confirmed',
        FulfillmentStatus::CANCELLED => 'badge-pill-cancelled',
    ];

    /** @var array<string, string> */
    private const ICONS = [
        FulfillmentStatus::PENDING => 'ti-clock',
        FulfillmentStatus::PICKING => 'ti-box',
        FulfillmentStatus::READY_FOR_PICKUP => 'ti-building-store',
        FulfillmentStatus::OUT_FOR_DELIVERY => 'ti-truck-delivery',
        FulfillmentStatus::COMPLETED => 'ti-check',
        FulfillmentStatus::CANCELLED => 'ti-x',
    ];

    public static function resolve(string $status): string
    {
        return self::LABELS[$status] ?? $status;
    }

    public static function badgeClass(string $status): string
    {
        return self::BADGE_CLASSES[$status] ?? 'badge-pill-origin';
    }

    public static function icon(string $status): string
    {
        return self::ICONS[$status] ?? 'ti-package';
    }

    /**
     * Rótulo público considerando status comercial do pedido.
     */
    public static function forPublicTracking(string $orderStatus, string $fulfillmentStatus, string $deliveryMethod): string
    {
        if ($orderStatus === 'cancelled') {
            return 'Pedido cancelado';
        }

        if ($orderStatus === 'draft') {
            return 'Aguardando confirmação da loja';
        }

        if ($orderStatus !== 'confirmed') {
            return self::resolve($fulfillmentStatus);
        }

        if ($fulfillmentStatus === FulfillmentStatus::COMPLETED) {
            return $deliveryMethod === DeliveryMethod::PICKUP
                ? 'Pedido retirado'
                : 'Pedido entregue';
        }

        return self::resolve($fulfillmentStatus);
    }
}
