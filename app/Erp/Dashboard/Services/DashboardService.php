<?php

declare(strict_types=1);

namespace Erp\Dashboard\Services;

use Erp\Audit\Repositories\AuditLogRepository;
use Erp\Commercial\Repositories\CommercialClientRepository;
use Erp\Orders\Repositories\OrderRepository;
use Erp\Products\Repositories\ProductRepository;
use Erp\Stock\Repositories\StockMovementRepository;
use Throwable;

/**
 * Resumo do painel admin (métricas e alertas de estoque).
 */
class DashboardService
{
    public function __construct(
        private ?ProductRepository $produtoRepository = null,
        private ?StockMovementRepository $stockMovementRepository = null,
        private ?AuditLogRepository $auditLogRepository = null,
        private ?OrderRepository $orderRepository = null,
        private ?CommercialClientRepository $clientRepository = null
    ) {
        $this->produtoRepository ??= new ProductRepository();
        $this->stockMovementRepository ??= new StockMovementRepository();
        $this->auditLogRepository ??= new AuditLogRepository();
        $this->orderRepository ??= new OrderRepository();
        $this->clientRepository ??= new CommercialClientRepository();
    }

    /**
     * Dados para a view admin/dashboard (sem SQL na apresentação).
     *
     * @return array{
     *     totalProdutos: int,
     *     totalAtivos: int,
     *     semEstoque: int,
     *     estoqueAlerta: int,
     *     monthlyRevenue: float,
     *     pendingOrders: int,
     *     newClientsWeek: int,
     *     auditorias: int,
     *     produtosAlerta: list<array<string, mixed>>,
     *     movimentacoesRecentes: list<array<string, mixed>>,
     *     revenueTrend: list<array{label: string, total: float}>
     * }
     */
    public function getSummary(): array
    {
        $today = new \DateTimeImmutable('today');
        $monthStart = $today->modify('first day of this month')->format('Y-m-d') . ' 00:00:00';
        $monthEnd = $today->format('Y-m-d') . ' 23:59:59';
        $weekStart = $today->modify('-6 days')->format('Y-m-d') . ' 00:00:00';

        return [
            'totalProdutos' => $this->produtoRepository->countAllProducts(),
            'totalAtivos' => $this->produtoRepository->countByStatus('ativo'),
            'semEstoque' => $this->produtoRepository->countZeroStock(),
            'estoqueAlerta' => $this->produtoRepository->countLowStockNonZero(),
            'monthlyRevenue' => $this->safeFloat(fn (): float => $this->orderRepository->sumTotalByStatusBetween('confirmed', $monthStart, $monthEnd)),
            'pendingOrders' => $this->safeCount(fn (): int => $this->orderRepository->countByStatus('draft')),
            'newClientsWeek' => $this->safeCount(fn (): int => $this->clientRepository->countCreatedBetween($weekStart, $monthEnd)),
            'auditorias' => $this->safeCount(fn (): int => $this->auditLogRepository->countAll()),
            'produtosAlerta' => $this->produtoRepository->listActiveProductsLowStock(10),
            'movimentacoesRecentes' => $this->safeList(fn (): array => $this->stockMovementRepository->getRecentMovements(8)),
            'revenueTrend' => $this->revenueTrend($today),
        ];
    }

    private function safeCount(callable $callback): int
    {
        try {
            return (int) $callback();
        } catch (Throwable) {
            return 0;
        }
    }

    private function safeFloat(callable $callback): float
    {
        try {
            return (float) $callback();
        } catch (Throwable) {
            return 0.0;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function safeList(callable $callback): array
    {
        try {
            $result = $callback();

            return is_array($result) ? $result : [];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return list<array{label: string, total: float}>
     */
    private function revenueTrend(\DateTimeImmutable $today): array
    {
        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $today->modify("-{$i} months");
            $start = $month->modify('first day of this month')->format('Y-m-d') . ' 00:00:00';
            $end = $month->modify('last day of this month')->format('Y-m-d') . ' 23:59:59';
            $trend[] = [
                'label' => $month->format('m/Y'),
                'total' => $this->safeFloat(fn (): float => $this->orderRepository->sumTotalByStatusBetween('confirmed', $start, $end)),
            ];
        }

        return $trend;
    }
}
