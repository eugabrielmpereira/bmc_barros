<?php

declare(strict_types=1);

namespace Erp\Stock\Services;

use Core\Database;
use Core\Exceptions\ValidationException;
use Erp\Orders\Repositories\OrderItemRepository;
use Erp\Orders\Repositories\OrderRepository;
use Erp\Products\Repositories\ProductRepository;
use Erp\Stock\Repositories\StockReservationRepository;
use PDO;
use Throwable;

final class StockReservationService
{
    public function __construct(
        private ?PDO $pdo = null,
        private ?OrderRepository $orderRepository = null,
        private ?OrderItemRepository $orderItemRepository = null,
        private ?ProductRepository $productRepository = null,
        private ?StockReservationRepository $reservationRepository = null,
        private ?StockAvailabilityService $availabilityService = null
    ) {
        $this->pdo ??= Database::getInstance();
        $this->orderRepository ??= new OrderRepository($this->pdo);
        $this->orderItemRepository ??= new OrderItemRepository($this->pdo);
        $this->productRepository ??= new ProductRepository($this->pdo);
        $this->reservationRepository ??= new StockReservationRepository($this->pdo);
        $this->availabilityService ??= new StockAvailabilityService(
            $this->pdo,
            $this->productRepository,
            $this->reservationRepository
        );
    }

    /**
     * Recria reservas ativas de um pedido pendente a partir dos itens atuais.
     *
     * @throws ValidationException
     */
    public function syncForDraftOrder(int $orderId): void
    {
        if ($orderId <= 0) {
            return;
        }

        $order = $this->orderRepository->find($orderId);
        if ($order === null || (string) ($order['status'] ?? '') !== 'draft') {
            return;
        }

        $items = $this->orderItemRepository->listByOrder($orderId);
        $ownsTransaction = !$this->pdo->inTransaction();

        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $this->reservationRepository->releaseByOrder($orderId);

            foreach ($items as $item) {
                $productId = (int) ($item['product_id'] ?? 0);
                $quantity = (int) ($item['quantity'] ?? 0);
                if ($productId <= 0 || $quantity <= 0) {
                    continue;
                }

                $this->assertAvailable($productId, $quantity);
                $this->reservationRepository->createReservation($orderId, $productId, $quantity);
            }

            if ($ownsTransaction) {
                $this->pdo->commit();
            }
        } catch (Throwable $e) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function releaseByOrder(int $orderId): void
    {
        $this->reservationRepository->releaseByOrder($orderId);
    }

    public function consumeByOrder(int $orderId): void
    {
        $this->reservationRepository->consumeByOrder($orderId);
    }

    /**
     * @throws ValidationException
     */
    private function assertAvailable(int $productId, int $quantity): void
    {
        $available = $this->availabilityService->getAvailableQuantity($productId);
        if ($quantity > $available) {
            $product = $this->productRepository->find($productId);
            $productName = trim((string) ($product['nome'] ?? 'Produto'));

            throw new ValidationException(
                'Estoque insuficiente.',
                ['quantity' => ['Não há unidades suficientes disponíveis para "' . $productName . '".']]
            );
        }
    }
}
