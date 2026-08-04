<?php

declare(strict_types=1);

namespace Erp\Fulfillment\Support;

final class FulfillmentStatus
{
    public const PENDING = 'pending';
    public const PICKING = 'picking';
    public const READY_FOR_PICKUP = 'ready_for_pickup';
    public const OUT_FOR_DELIVERY = 'out_for_delivery';
    public const COMPLETED = 'completed';
    public const CANCELLED = 'cancelled';

    /** @var list<string> */
    public const ACTIVE = [
        self::PENDING,
        self::PICKING,
        self::READY_FOR_PICKUP,
        self::OUT_FOR_DELIVERY,
        self::COMPLETED,
    ];

    /** @var list<string> */
    public const OPERATIONAL = [
        self::PENDING,
        self::PICKING,
        self::READY_FOR_PICKUP,
        self::OUT_FOR_DELIVERY,
        self::COMPLETED,
    ];

    public static function parse(string $value): string
    {
        $normalized = strtolower(trim($value));

        return in_array($normalized, self::ACTIVE, true) ? $normalized : self::PENDING;
    }

    /**
     * @return list<string>
     */
    public static function allowedForDeliveryMethod(string $deliveryMethod): array
    {
        $common = [self::PENDING, self::PICKING, self::COMPLETED];

        if ($deliveryMethod === DeliveryMethod::PICKUP) {
            return array_merge($common, [self::READY_FOR_PICKUP]);
        }

        return array_merge($common, [self::OUT_FOR_DELIVERY]);
    }

    public static function isAllowedForDeliveryMethod(string $status, string $deliveryMethod): bool
    {
        return in_array($status, self::allowedForDeliveryMethod($deliveryMethod), true);
    }
}
