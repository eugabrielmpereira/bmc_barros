<?php

declare(strict_types=1);

namespace Erp\Commerce\Services;

use Erp\Commerce\Repositories\CartItemRepository;
use Erp\Commerce\Repositories\CartRepository;
use Erp\Products\Repositories\ProductRepository;
use Erp\Stock\Services\StockAvailabilityService;
use PDO;

final class CartService
{
    private const COOKIE_NAME = 'cart_token';
    private const COOKIE_LIFETIME = 2592000; // 30 days
    private const COOKIE_PATH = '/';

    private ?array $currentCart = null;

    public function __construct(
        private ?ProductRepository $productRepository = null,
        private ?CartRepository $cartRepository = null,
        private ?CartItemRepository $cartItemRepository = null,
        private ?StockAvailabilityService $stockAvailabilityService = null
    ) {
        $this->productRepository ??= new ProductRepository();
        $this->cartRepository ??= new CartRepository();
        $this->cartItemRepository ??= new CartItemRepository();
        $this->stockAvailabilityService ??= new StockAvailabilityService();
        $this->ensureSessionStarted();
    }

    /**
     * @return array{success: bool, message: string, adjusted?: bool, available_stock?: int, quantity?: int, out_of_stock?: bool}
     */
    public function addProduct(int $productId, int $quantity): array
    {
        if ($productId <= 0 || $quantity <= 0) {
            return ['success' => false, 'message' => 'Produto ou quantidade inválida.'];
        }

        $product = $this->productRepository->find($productId);
        if ($product === null || (string) ($product['status'] ?? '') !== 'ativo') {
            return ['success' => false, 'message' => 'Produto indisponível para compra.'];
        }

        $availableStock = $this->stockAvailabilityService->getAvailableQuantity($productId);
        if ($availableStock <= 0) {
            return [
                'success' => false,
                'message' => 'Produto indisponível no momento.',
                'out_of_stock' => true,
            ];
        }

        $cart = $this->getCurrentCart();
        $item = $this->cartItemRepository->findByCartAndProduct($cart['id'], $productId);
        $requestedQuantity = $quantity;
        $existingQuantity = $item !== null ? (int) $item['quantity'] : 0;
        $desiredQuantity = min(999, $existingQuantity + $requestedQuantity);
        $newQuantity = min($desiredQuantity, $availableStock);
        $adjusted = $newQuantity < $desiredQuantity;

        if ($item === null) {
            $this->cartItemRepository->createItem(
                (int) $cart['id'],
                $productId,
                $newQuantity,
                (float) ($product['preco'] ?? 0)
            );
        } else {
            $this->cartItemRepository->updateItem(
                (int) $item['id'],
                $newQuantity,
                (float) ($product['preco'] ?? 0)
            );
        }

        if ($adjusted) {
            return [
                'success' => true,
                'message' => 'Apenas ' . $availableStock . ' unidade(s) disponível(is). Quantidade ajustada automaticamente.',
                'adjusted' => true,
                'available_stock' => $availableStock,
                'quantity' => $newQuantity,
            ];
        }

        return ['success' => true, 'message' => 'Produto adicionado ao carrinho.'];
    }

    /**
     * @param array<int|string, mixed> $quantities
     * @return array{adjusted: bool, message: string}
     */
    public function updateQuantities(array $quantities): array
    {
        $cart = $this->getCurrentCart();
        $items = $this->cartItemRepository->listByCart((int) $cart['id']);
        $adjusted = false;
        $adjustedProducts = [];

        foreach ($items as $item) {
            $requestedQuantity = (int) ($quantities[$item['product_id']] ?? $item['quantity']);
            if ($requestedQuantity <= 0) {
                $this->cartItemRepository->deleteByCartAndProduct((int) $cart['id'], (int) $item['product_id']);
                continue;
            }

            $product = $this->productRepository->find((int) $item['product_id']);
            $quantity = $requestedQuantity;
            if ($product !== null) {
                $availableStock = $this->stockAvailabilityService->getAvailableQuantity((int) $item['product_id']);
                if ($availableStock <= 0) {
                    $this->cartItemRepository->deleteByCartAndProduct((int) $cart['id'], (int) $item['product_id']);
                    $adjusted = true;
                    continue;
                }
                if ($quantity > $availableStock) {
                    $quantity = $availableStock;
                    $adjusted = true;
                    $adjustedProducts[] = (string) ($product['nome'] ?? 'Produto');
                }
            }

            $this->cartItemRepository->updateItem((int) $item['id'], $quantity, (float) $item['unit_price']);
        }

        $this->currentCart = null;

        $message = $adjusted
            ? 'Algumas quantidades foram ajustadas ao estoque disponível.'
            : 'Carrinho atualizado.';

        return ['adjusted' => $adjusted, 'message' => $message];
    }

    public function removeProduct(int $productId): void
    {
        $cart = $this->getCurrentCart();
        $this->cartItemRepository->deleteByCartAndProduct((int) $cart['id'], $productId);
        $this->currentCart = null;
    }

    public function clearItems(): void
    {
        $cart = $this->getCurrentCart();
        $this->cartItemRepository->deleteByCart((int) $cart['id']);
        $this->currentCart = null;
    }

    public function clear(): void
    {
        if (isset($_COOKIE[self::COOKIE_NAME])) {
            setcookie(self::COOKIE_NAME, '', [
                'expires' => time() - 3600,
                'path' => self::COOKIE_PATH,
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
            unset($_COOKIE[self::COOKIE_NAME]);
        }

        $this->currentCart = null;
    }

    public function finalizeCurrentCart(): void
    {
        $cart = $this->getCurrentCart();
        $this->cartRepository->markOrdered((int) $cart['id']);
        $this->clear();
    }

    /**
     * @return array{
     *     items: list<array<string, mixed>>,
     *     item_count: int,
     *     subtotal: float
     * }
     */
    public function summary(): array
    {
        $cart = $this->getCurrentCart();
        $items = $this->cartItemRepository->listByCart((int) $cart['id']);
        $subtotal = 0.0;
        $itemCount = 0;

        foreach ($items as &$item) {
            $quantity = max(1, (int) ($item['quantity'] ?? 0));
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $total = (float) ($item['total_amount'] ?? $quantity * $unitPrice);
            $item['name'] = (string) ($item['product_name'] ?? 'Produto');
            $item['image'] = (string) ($item['imagem'] ?? '');
            $item['stock'] = $this->stockAvailabilityService->getAvailableQuantity((int) ($item['product_id'] ?? 0));
            $item['public_slug'] = $this->productRepository->publicSlug([
                'id' => $item['product_id'],
                'nome' => (string) ($item['product_name'] ?? ''),
            ]);
            $item['total'] = $total;
            $subtotal += $total;
            $itemCount += $quantity;
        }
        unset($item);

        return [
            'items' => $items,
            'item_count' => $itemCount,
            'subtotal' => $subtotal,
        ];
    }

    private function getCurrentCart(): array
    {
        if ($this->currentCart !== null) {
            return $this->currentCart;
        }

        $sessionToken = $this->resolveCartToken();
        $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

        $cart = null;
        if ($userId > 0) {
            $cart = $this->cartRepository->findOpenByUserId($userId);
        }

        if ($cart === null) {
            $cart = $this->cartRepository->findOpenBySessionToken($sessionToken);
        }

        if ($cart === null) {
            if ($sessionToken !== '' && $this->cartRepository->findBySessionToken($sessionToken) !== null) {
                $sessionToken = $this->issueFreshCartToken();
            }
            $cartId = $this->cartRepository->createCart($userId, $sessionToken);
            $cart = $this->cartRepository->find($cartId);
        } elseif ($userId > 0 && $cart['user_id'] === null) {
            $this->cartRepository->associateUser((int) $cart['id'], $userId);
            $cart['user_id'] = $userId;
        }

        $this->currentCart = $cart;
        return $cart;
    }

    private function resolveCartToken(): string
    {
        $token = trim((string) ($_COOKIE[self::COOKIE_NAME] ?? ''));
        if ($token !== '') {
            return $token;
        }

        return $this->issueFreshCartToken();
    }

    private function issueFreshCartToken(): string
    {
        $token = bin2hex(random_bytes(16));
        setcookie(self::COOKIE_NAME, $token, [
            'expires' => time() + self::COOKIE_LIFETIME,
            'path' => self::COOKIE_PATH,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        $_COOKIE[self::COOKIE_NAME] = $token;

        return $token;
    }

    private function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
