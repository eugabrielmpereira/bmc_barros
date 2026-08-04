<?php

declare(strict_types=1);

namespace Erp\Finance\Support;

final class PaymentStatusLabel
{
    public const NOT_APPLICABLE = 'not_applicable';
    public const PENDING = 'pending';
    public const PAID = 'paid';
    public const CANCELLED = 'cancelled';

    private const LABELS = [
        self::NOT_APPLICABLE => 'Não aplicável',
        self::PENDING => 'Aguardando pagamento',
        self::PAID => 'Pago',
        self::CANCELLED => 'Cancelado',
    ];

    private const BADGE_CLASSES = [
        self::NOT_APPLICABLE => 'badge-pill-origin',
        self::PENDING => 'badge-pill-pending',
        self::PAID => 'badge-pill-confirmed',
        self::CANCELLED => 'badge-pill-cancelled',
    ];

    private const ICONS = [
        self::NOT_APPLICABLE => 'ti-minus',
        self::PENDING => 'ti-clock',
        self::PAID => 'ti-check',
        self::CANCELLED => 'ti-x',
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
        return self::ICONS[$status] ?? 'ti-info-circle';
    }

    public static function forOrderStatus(string $orderStatus): string
    {
        return match ($orderStatus) {
            'confirmed' => self::PENDING,
            'cancelled' => self::CANCELLED,
            default => self::NOT_APPLICABLE,
        };
    }
}
