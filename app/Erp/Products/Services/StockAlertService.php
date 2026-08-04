<?php

declare(strict_types=1);

namespace Erp\Products\Services;

use Erp\Products\Repositories\ProductRepository;
use Erp\Products\Repositories\StockAlertRepository;

final class StockAlertService
{
    public function __construct(
        private ?ProductRepository $productRepository = null,
        private ?StockAlertRepository $alertRepository = null
    ) {
        $this->productRepository ??= new ProductRepository();
        $this->alertRepository ??= new StockAlertRepository();
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function subscribe(int $productId, string $email): array
    {
        if ($productId <= 0) {
            return ['success' => false, 'message' => 'Produto inválido.'];
        }

        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Informe um e-mail válido.'];
        }

        $product = $this->productRepository->find($productId);
        if ($product === null || (string) ($product['status'] ?? '') !== 'ativo') {
            return ['success' => false, 'message' => 'Produto não encontrado.'];
        }

        if ((int) ($product['estoque_atual'] ?? 0) > 0) {
            return ['success' => false, 'message' => 'Este produto já está disponível em estoque.'];
        }

        if ($this->alertRepository->exists($productId, $email)) {
            return ['success' => true, 'message' => 'Você já está na lista de aviso para este produto.'];
        }

        if (!$this->alertRepository->createAlert($productId, $email)) {
            return ['success' => false, 'message' => 'Não foi possível registrar seu e-mail. Tente novamente.'];
        }

        return ['success' => true, 'message' => 'E-mail registrado! Avisaremos quando o produto voltar ao estoque.'];
    }
}
