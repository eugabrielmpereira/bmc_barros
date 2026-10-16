<?php

declare(strict_types=1);

namespace Erp\Finance\Support;

use Erp\Finance\Gateways\Asaas\AsaasConfig;

/**
 * Formas de pagamento do checkout online e regras de parcelamento.
 *
 * Cartão é cobrado na fatura hospedada do gateway (o servidor nunca recebe dados do cartão).
 */
final class PaymentMethod
{
    public const PIX = 'pix';
    public const CREDIT_CARD = 'credit_card';

    /** @var list<string> */
    public const ALL = [self::PIX, self::CREDIT_CARD];

    public static function parse(string $value): string
    {
        $normalized = strtolower(trim($value));

        return in_array($normalized, self::ALL, true) ? $normalized : self::PIX;
    }

    public static function label(string $method, int $installments = 1): string
    {
        if ($method === self::CREDIT_CARD) {
            return $installments > 1 ? 'Cartão de crédito em ' . $installments . 'x' : 'Cartão de crédito à vista';
        }

        return 'Pix';
    }

    public static function cardEnabled(): bool
    {
        if (!AsaasConfig::isEnabled()) {
            return false;
        }

        return filter_var(env('PAYMENT_CARD_ENABLED', 'true'), FILTER_VALIDATE_BOOL);
    }

    public static function maxInstallments(): int
    {
        return max(1, min((int) env('PAYMENT_CARD_MAX_INSTALLMENTS', 6), 12));
    }

    public static function minInstallmentValue(): float
    {
        return max(5.0, (float) env('PAYMENT_CARD_MIN_INSTALLMENT', 50));
    }

    /**
     * Parcelas permitidas para o total (sem juros para o cliente).
     */
    public static function allowedInstallments(float $total, ?int $max = null, ?float $minValue = null): int
    {
        $max ??= self::maxInstallments();
        $minValue ??= self::minInstallmentValue();
        if ($total <= 0) {
            return 1;
        }

        return max(1, min($max, (int) floor($total / $minValue)));
    }

    /**
     * @return list<array{count: int, value: float, label: string}>
     */
    public static function installmentOptions(float $total, ?int $max = null, ?float $minValue = null): array
    {
        $allowed = self::allowedInstallments($total, $max, $minValue);
        $options = [];
        for ($count = 1; $count <= $allowed; $count++) {
            $value = round($total / $count, 2);
            $options[] = [
                'count' => $count,
                'value' => $value,
                'label' => $count === 1
                    ? 'À vista — R$ ' . number_format($total, 2, ',', '.')
                    : $count . 'x de R$ ' . number_format($value, 2, ',', '.') . ' sem juros',
            ];
        }

        return $options;
    }
}
