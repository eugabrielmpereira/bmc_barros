<?php

declare(strict_types=1);

namespace Erp\Fulfillment\Support;

final class DeliveryMethodLabel
{
    /** @var array<string, string> */
    private const LABELS = [
        DeliveryMethod::DELIVERY => 'Entrega',
        DeliveryMethod::PICKUP => 'Retirada na loja',
    ];

    /** @var array<string, string> */
    private const ICONS = [
        DeliveryMethod::DELIVERY => 'ti-truck-delivery',
        DeliveryMethod::PICKUP => 'ti-building-store',
    ];

    public static function resolve(string $method): string
    {
        return self::LABELS[$method] ?? $method;
    }

    public static function icon(string $method): string
    {
        return self::ICONS[$method] ?? 'ti-package';
    }
}
