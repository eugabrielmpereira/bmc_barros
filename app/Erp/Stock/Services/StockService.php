<?php

declare(strict_types=1);

namespace Erp\Stock\Services;

use Core\Database;
use Core\Exceptions\NotFoundException;
use Core\Exceptions\ValidationException;
use Erp\Products\Repositories\ProductRepository;
use Erp\Stock\Repositories\StockMovementRepository;
use PDO;
use Throwable;

/**
 * Regras de negócio de estoque: atualiza `produtos.estoque_atual` e registra `stock_movements`.
 * Transações são abertas apenas aqui (repositórios não iniciam transação).
 */
class StockService
{
    private PDO $pdo;

    private ProductRepository $produtoRepository;

    private StockMovementRepository $stockMovementRepository;

    public function __construct(
        ?PDO $pdo = null,
        ?ProductRepository $produtoRepository = null,
        ?StockMovementRepository $stockMovementRepository = null
    ) {
        $this->pdo = $pdo ?? Database::getInstance();
        $this->produtoRepository = $produtoRepository ?? new ProductRepository($this->pdo);
        $this->stockMovementRepository = $stockMovementRepository ?? new StockMovementRepository($this->pdo);
    }

    /**
     * Incrementa estoque e grava movimento do tipo `entrada`.
     */
    public function addStock(int $produtoId, int $quantidade, ?int $userId = null, ?string $reason = null): void
    {
        $this->assertPositiveProductId($produtoId);
        $this->assertPositiveQuantity($quantidade);

        $produto = $this->requireProduct($produtoId);
        $previous = $this->normalizeStock($produto['estoque_atual'] ?? 0);
        $newStock = $previous + $quantidade;

        $this->persistMovementAndStock(
            $produtoId,
            'entrada',
            $quantidade,
            $previous,
            $newStock,
            $this->normalizeReason($reason),
            $userId
        );
    }

    /**
     * Decrementa estoque e grava movimento do tipo `saida` (estoque não pode ficar negativo).
     */
    public function removeStock(int $produtoId, int $quantidade, ?int $userId = null, ?string $reason = null): void
    {
        $this->assertPositiveProductId($produtoId);
        $this->assertPositiveQuantity($quantidade);

        $produto = $this->requireProduct($produtoId);
        $previous = $this->normalizeStock($produto['estoque_atual'] ?? 0);

        if ($previous < $quantidade) {
            throw new ValidationException(
                'Estoque insuficiente.',
                ['quantidade' => ['Não há unidades suficientes para esta saída.']]
            );
        }

        $newStock = $previous - $quantidade;

        $this->persistMovementAndStock(
            $produtoId,
            'saida',
            $quantidade,
            $previous,
            $newStock,
            $this->normalizeReason($reason),
            $userId
        );
    }

    /**
     * Define estoque absoluto; o movimento registrado é `entrada` ou `saida` conforme o delta.
     */
    public function adjustStock(int $produtoId, int $novoEstoque, ?int $userId = null, ?string $reason = null): void
    {
        $this->assertPositiveProductId($produtoId);

        if ($novoEstoque < 0) {
            throw new ValidationException(
                'Estoque inválido.',
                ['novoEstoque' => ['O estoque não pode ser negativo.']]
            );
        }

        $produto = $this->requireProduct($produtoId);
        $previous = $this->normalizeStock($produto['estoque_atual'] ?? 0);

        if ($novoEstoque === $previous) {
            return;
        }

        if ($novoEstoque > $previous) {
            $type = 'entrada';
            $quantity = $novoEstoque - $previous;
        } else {
            $type = 'saida';
            $quantity = $previous - $novoEstoque;
        }

        $this->persistMovementAndStock(
            $produtoId,
            $type,
            $quantity,
            $previous,
            $novoEstoque,
            $this->normalizeReason($reason),
            $userId
        );
    }

    /**
     * Ajusta múltiplos saldos em uma única transação e ignora linhas sem alteração.
     *
     * @param array<int|string, int|string> $stocks
     * @return array{updated: int, skipped: int}
     */
    public function adjustMany(array $stocks, ?int $userId = null, ?string $reason = null): array
    {
        $updated = 0;
        $skipped = 0;
        $normalizedReason = $this->normalizeReason($reason) ?? 'Inventário rápido';

        $this->pdo->beginTransaction();

        try {
            foreach ($stocks as $productId => $stockValue) {
                $produtoId = filter_var($productId, FILTER_VALIDATE_INT);
                $novoEstoque = filter_var($stockValue, FILTER_VALIDATE_INT);

                if ($produtoId === false || $produtoId <= 0 || $novoEstoque === false || $novoEstoque < 0) {
                    $skipped++;
                    continue;
                }

                $produto = $this->requireProduct($produtoId);
                $previous = $this->normalizeStock($produto['estoque_atual'] ?? 0);
                if ($novoEstoque === $previous) {
                    $skipped++;
                    continue;
                }

                if ($novoEstoque > $previous) {
                    $type = 'entrada';
                    $quantity = $novoEstoque - $previous;
                } else {
                    $type = 'saida';
                    $quantity = $previous - $novoEstoque;
                }

                $this->persistMovementAndStock(
                    $produtoId,
                    $type,
                    $quantity,
                    $previous,
                    $novoEstoque,
                    $normalizedReason,
                    $userId
                );
                $updated++;
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }

        return ['updated' => $updated, 'skipped' => $skipped];
    }

    /**
     * @param 'entrada'|'saida' $type
     */
    private function persistMovementAndStock(
        int $produtoId,
        string $type,
        int $quantity,
        int $previousStock,
        int $newStock,
        ?string $reason,
        ?int $userId
    ): void {
        $ownsTransaction = !$this->pdo->inTransaction();
        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $updated = $this->produtoRepository->update($produtoId, ['estoque_atual' => $newStock]);

            if (!$updated) {
                throw new \RuntimeException('Não foi possível atualizar o estoque do produto.');
            }

            $this->stockMovementRepository->createMovement(
                $produtoId,
                $type,
                $quantity,
                $previousStock,
                $newStock,
                $reason,
                $userId
            );

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

    /** @return array<string, mixed> */
    private function requireProduct(int $produtoId): array
    {
        $produto = $this->produtoRepository->find($produtoId);

        if ($produto === null) {
            throw new NotFoundException('Produto não encontrado.');
        }

        return $produto;
    }

    private function assertPositiveProductId(int $produtoId): void
    {
        if ($produtoId <= 0) {
            throw new ValidationException(
                'Produto inválido.',
                ['produtoId' => ['Identificador do produto inválido.']]
            );
        }
    }

    private function assertPositiveQuantity(int $quantidade): void
    {
        if ($quantidade <= 0) {
            throw new ValidationException(
                'Quantidade inválida.',
                ['quantidade' => ['Informe uma quantidade maior que zero.']]
            );
        }
    }

    /**
     * Garante inteiro não negativo para leitura do legado (string/int no PDO).
     */
    private function normalizeStock(mixed $value): int
    {
        $n = (int) $value;

        return max(0, $n);
    }

    private function normalizeReason(?string $reason): ?string
    {
        if ($reason === null) {
            return null;
        }

        $trimmed = trim($reason);

        return $trimmed === '' ? null : $trimmed;
    }
}
