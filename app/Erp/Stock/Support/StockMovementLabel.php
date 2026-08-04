<?php

declare(strict_types=1);

namespace Erp\Stock\Support;

/**
 * Rótulos legíveis para movimentações de estoque no admin.
 */
final class StockMovementLabel
{
    /**
     * @param array<string, mixed> $movement
     */
    public static function resolve(array $movement): string
    {
        $type = strtolower((string) ($movement['tipo'] ?? $movement['type'] ?? ''));
        $reason = strtolower((string) ($movement['motivo'] ?? $movement['reason'] ?? ''));

        if ($type === 'entrada' && str_contains($reason, 'cancelado')) {
            return 'Devolução';
        }

        if ($type === 'saida' && str_contains($reason, 'pedido') && (str_contains($reason, 'aprovado') || str_contains($reason, 'confirmado'))) {
            return 'Saída por pedido';
        }

        if ($type === 'ajuste' || $type === 'entrada' || ($type === 'saida' && $reason !== '')) {
            return 'Entrada manual';
        }

        return match ($type) {
            'entrada' => 'Entrada manual',
            'saida' => 'Saída por pedido',
            'ajuste' => 'Entrada manual',
            default => ucfirst($type !== '' ? $type : 'Movimentação'),
        };
    }

    /**
     * @param array<string, mixed> $movement
     */
    public static function badgeClass(array $movement): string
    {
        $label = self::resolve($movement);

        return match ($label) {
            'Devolução' => 'badge-success',
            'Saída por pedido' => 'badge-warning',
            default => 'badge-info',
        };
    }
}
