<?php

declare(strict_types=1);

namespace Erp\Reports\Services;

use Erp\Commercial\Services\CommercialClientService;
use Erp\Finance\Services\ReceivableService;
use Erp\Orders\Services\OrderService;
use Erp\Products\Repositories\ProductRepository;
use Erp\Stock\Services\StockHistoryService;
use Throwable;

final class ReportService
{
    public function __construct(
        private ?ProductRepository $productRepository = null,
        private ?CommercialClientService $clientService = null,
        private ?OrderService $orderService = null,
        private ?StockHistoryService $stockHistoryService = null,
        private ?ReceivableService $receivableService = null
    ) {
        $this->productRepository ??= new ProductRepository();
        $this->clientService ??= new CommercialClientService();
        $this->orderService ??= new OrderService();
        $this->stockHistoryService ??= new StockHistoryService();
        $this->receivableService ??= new ReceivableService();
    }

    /**
     * @return array<string, mixed>
     */
    public function operationalSummary(?string $startDate = null, ?string $endDate = null): array
    {
        $period = $this->normalizePeriod($startDate, $endDate);

        return [
            'period' => $period,
            'products' => [
                'total' => $this->safe(fn (): int => $this->productRepository->countAllProducts(), 0),
                'active' => $this->safe(fn (): int => $this->productRepository->countByStatus('ativo'), 0),
                'inactive' => $this->safe(fn (): int => $this->productRepository->countByStatus('inativo'), 0),
                'zeroStock' => $this->safe(fn (): int => $this->productRepository->countZeroStock(), 0),
                'lowStock' => $this->safe(fn (): array => $this->productRepository->listActiveProductsLowStock(20), []),
            ],
            'commercialClients' => $this->safe(fn (): array => $this->clientService->statusSummary(), [
                'active' => 0,
                'inactive' => 0,
            ]),
            'orders' => $this->safe(fn (): array => $this->orderService->summaryByPeriod($period['startDateTime'], $period['endDateTime']), [
                'draft' => 0,
                'confirmed' => 0,
                'cancelled' => 0,
                'totalAmount' => 0.0,
            ]),
            'orderRows' => $this->safe(fn (): array => $this->orderService->listByPeriod($period['startDateTime'], $period['endDateTime']), []),
            'receivables' => $this->receivableService->safeSummary(
                fn (): array => $this->receivableService->openSummary(),
                ['count' => 0, 'totalAmount' => 0.0]
            ),
            'openReceivableRows' => $this->receivableService->safeSummary(
                fn (): array => $this->receivableService->listOpen(),
                []
            ),
            'stockMovements' => $this->safe(fn (): array => $this->stockHistoryService->getByPeriod($period['startDateTime'], $period['endDateTime'], 200), []),
        ];
    }

    /**
     * @return array{startDate: string, endDate: string, startDateTime: string, endDateTime: string}
     */
    public function normalizePeriod(?string $startDate, ?string $endDate): array
    {
        $today = new \DateTimeImmutable('today');
        $defaultStart = $today->modify('first day of this month');

        $start = $this->parseDate($startDate) ?? $defaultStart;
        $end = $this->parseDate($endDate) ?? $today;

        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        return [
            'startDate' => $start->format('Y-m-d'),
            'endDate' => $end->format('Y-m-d'),
            'startDateTime' => $start->format('Y-m-d') . ' 00:00:00',
            'endDateTime' => $end->format('Y-m-d') . ' 23:59:59',
        ];
    }

    private function safe(callable $callback, mixed $fallback): mixed
    {
        try {
            return $callback();
        } catch (Throwable) {
            return $fallback;
        }
    }

    private function parseDate(?string $date): ?\DateTimeImmutable
    {
        if ($date === null || trim($date) === '') {
            return null;
        }

        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', trim($date));

        return $parsed instanceof \DateTimeImmutable ? $parsed : null;
    }
}
