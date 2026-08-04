<?php

declare(strict_types=1);

namespace Erp\Stock\Services;

use Erp\Stock\Repositories\StockMovementRepository;

/**
 * Orquestra a leitura do histórico de movimentações de estoque.
 * Não aplica regras de negócio.
 */
class StockHistoryService
{
    public function __construct(
        private ?StockMovementRepository $stockMovementRepository = null
    ) {
        $this->stockMovementRepository ??= new StockMovementRepository();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getLatest(int $limit = 50): array
    {
        return $this->stockMovementRepository->listLatestMovements($limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getByPeriod(string $startDateTime, string $endDateTime, int $limit = 200): array
    {
        return $this->stockMovementRepository->listMovementsByPeriod($startDateTime, $endDateTime, $limit);
    }
}
