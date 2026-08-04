<?php

declare(strict_types=1);

namespace Erp\Fulfillment\Support;

final class DeliveryMethod
{
    public const DELIVERY = 'delivery';
    public const PICKUP = 'pickup';

    /** @var list<string> */
    public const ALL = [
        self::DELIVERY,
        self::PICKUP,
    ];

    public static function parse(string $value): string
    {
        $normalized = strtolower(trim($value));

        return in_array($normalized, self::ALL, true) ? $normalized : self::DELIVERY;
    }
}
