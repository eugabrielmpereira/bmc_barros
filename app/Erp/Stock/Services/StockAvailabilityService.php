<?php

declare(strict_types=1);

namespace Erp\Stock\Services;

use Erp\Products\Repositories\ProductRepository;
use Erp\Stock\Repositories\StockReservationRepository;
use PDO;

final class StockAvailabilityService
{
    public function __construct(
        private ?PDO $pdo = null,
        private ?ProductRepository $productRepository = null,
        private ?StockReservationRepository $reservationRepository = null
    ) {
        $this->pdo ??= \Core\Database::getInstance();
        $this->productRepository ??= new ProductRepository($this->pdo);
        $this->reservationRepository ??= new StockReservationRepository($this->pdo);
    }

    public function getAvailableQuantity(int $productId): int
    {
        if ($productId <= 0) {
            return 0;
        }

        $product = $this->productRepository->find($productId);
        if ($product === null || (string) ($product['status'] ?? '') !== 'ativo') {
            return 0;
        }

        $physicalStock = max(0, (int) ($product['estoque_atual'] ?? 0));
        $reserved = $this->reservationRepository->sumActiveByProduct($productId);

        return max(0, $physicalStock - $reserved);
    }
}
