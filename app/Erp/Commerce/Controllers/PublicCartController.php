<?php

declare(strict_types=1);

namespace Erp\Commerce\Controllers;

use Core\Controller;
use Core\Security\Csrf;
use Erp\Commerce\Services\CartService;
use Erp\Commerce\Services\CepLookupService;
use Erp\Customers\Services\CustomerAccountService;
use Erp\Customers\Services\CustomerAuthService;
use Erp\Finance\Services\PaymentChargeService;
use Erp\Orders\Services\OrderService;
use Erp\Finance\Support\PaymentMethod;
use Erp\Products\Services\StockAlertService;
use Erp\Shipping\Services\ShippingQuoteService;
use Shared\Support\BrazilianAddress;
use Throwable;

abstract class PublicCartController extends Controller
{
    public function __construct(
        private ?CartService $cartService = null,
        private ?OrderService $orderService = null,
        private ?CepLookupService $cepLookupService = null,
        private ?StockAlertService $stockAlertService = null,
        private ?CustomerAuthService $customerAuthService = null,
        private ?CustomerAccountService $customerAccountService = null,
        private ?PaymentChargeService $paymentChargeService = null,
        private ?ShippingQuoteService $shippingQuoteService = null
    ) {
        $this->cartService ??= new CartService();
        $this->orderService ??= new OrderService();
        $this->cepLookupService ??= new CepLookupService();
        $this->stockAlertService ??= new StockAlertService();
        $this->customerAuthService ??= new CustomerAuthService();
        $this->customerAccountService ??= new CustomerAccountService();
        $this->paymentChargeService ??= new PaymentChargeService();
        $this->shippingQuoteService ??= new ShippingQuoteService();
    }

    public function index(): void
    {
        $this->view('client::commerce/cart', [
            'client' => client_config(),
            'cart' => $this->cartService->summary(),
            'msg' => flash('commerce_message'),
            'tipo' => flash('commerce_message_type') ?? 'ok',
        ]);
    }

    public function summary(): void
    {
        if ($this->wantsJson()) {
            $this->respondJson($this->cartPayload());
            return;
        }

        $this->redirect('/carrinho');
    }

    public function add(): void
    {
        if (!$this->validateCsrfForRequest()) {
            $this->abortCsrf();
            return;
        }

        $productId = (int) ($_POST['product_id'] ?? 0);
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
        $buyNow = (int) ($_POST['buy_now'] ?? 0) === 1;
        $replaceCart = (int) ($_POST['replace_cart'] ?? 0) === 1;
        $buyNowKeep = (string) ($_POST['buy_now_mode'] ?? '') === 'keep';

        if ($buyNow && $this->wantsJson()) {
            $summary = $this->cartService->summary();
            $hasItems = (int) ($summary['item_count'] ?? 0) > 0;

            if ($hasItems && !$replaceCart && !$buyNowKeep) {
                $this->respondJson([
                    'success' => false,
                    'needs_confirm' => true,
                    'item_count' => (int) $summary['item_count'],
                    'message' => 'Você já tem itens no carrinho.',
                ]);

                return;
            }

            if ($replaceCart) {
                $this->cartService->clearItems();
            }
        }

        $result = $this->cartService->addProduct($productId, $quantity);

        if ($this->wantsJson()) {
            $payload = $this->cartPayload();
            $payload['success'] = $result['success'];
            $payload['message'] = $result['message'];
            $payload['adjusted'] = (bool) ($result['adjusted'] ?? false);
            $payload['out_of_stock'] = (bool) ($result['out_of_stock'] ?? false);
            if (isset($result['available_stock'])) {
                $payload['available_stock'] = (int) $result['available_stock'];
            }
            if ($buyNow && $result['success']) {
                $payload['redirect_url'] = rtrim((string) BASE_URL, '/') . '/checkout';
            }
            $this->respondJson($payload, $result['success'] ? 200 : 422);

            return;
        }

        if ($buyNow && $result['success']) {
            $this->redirect('/checkout');

            return;
        }

        $this->redirectWithMessage('/carrinho', $result['message'], $result['success'] ? 'ok' : 'erro');
    }

    public function update(): void
    {
        if (!$this->validateCsrfForRequest()) {
            $this->abortCsrf();
            return;
        }

        $quantities = isset($_POST['quantities']) && is_array($_POST['quantities']) ? $_POST['quantities'] : [];
        $updateResult = $this->cartService->updateQuantities($quantities);

        if ($this->wantsJson()) {
            $payload = $this->cartPayload();
            $payload['success'] = true;
            $payload['message'] = $updateResult['message'];
            $payload['adjusted'] = $updateResult['adjusted'];
            $this->respondJson($payload);
            return;
        }

        $this->redirectWithMessage(
            '/carrinho',
            $updateResult['message'],
            $updateResult['adjusted'] ? 'neutral' : 'ok'
        );
    }

    public function subscribeStockAlert(): void
    {
        Csrf::requireValidPostOrAbort();

        $result = $this->stockAlertService->subscribe(
            (int) ($_POST['product_id'] ?? 0),
            (string) ($_POST['email'] ?? '')
        );

        if ($this->wantsJson()) {
            $this->respondJson([
                'success' => $result['success'],
                'message' => $result['message'],
            ], $result['success'] ? 200 : 422);

            return;
        }

        $redirect = trim((string) ($_POST['return_to'] ?? '/produtos'));
        if ($redirect === '' || !str_starts_with($redirect, '/')) {
            $redirect = '/produtos';
        }

        $this->redirectWithMessage($redirect, $result['message'], $result['success'] ? 'ok' : 'erro');
    }

    public function remove(): void
    {
        if (!$this->validateCsrfForRequest()) {
            $this->abortCsrf();
            return;
        }

        $this->cartService->removeProduct((int) ($_POST['product_id'] ?? 0));

        if ($this->wantsJson()) {
            $payload = $this->cartPayload();
            $payload['success'] = true;
            $payload['message'] = 'Produto removido do carrinho.';
            $this->respondJson($payload);
            return;
        }

        $this->redirectWithMessage('/carrinho', 'Produto removido do carrinho.', 'neutral');
    }

    public function checkout(): void
    {
        $this->renderCheckout([], []);
    }

    public function lookupPostalCode(): void
    {
        $cep = (string) ($_GET['cep'] ?? '');
        $result = $this->cepLookupService->lookup($cep);
        $status = $result['success'] ? 200 : 422;
        $this->respondJson($result, $status);
    }

    /**
     * Cotação informativa de frete para o checkout (o pedido recalcula no servidor).
     */
    public function quoteShipping(): void
    {
        if (!$this->validateCsrfForRequest()) {
            $this->abortCsrf();
            return;
        }

        $cart = $this->cartService->summary();
        $subtotal = round((float) ($cart['subtotal'] ?? 0), 2);
        $address = BrazilianAddress::parseCheckoutPost($_POST);
        $quote = $this->shippingQuoteService->quote((string) ($_POST['delivery_method'] ?? 'delivery'), $address, $subtotal);
        $total = round($subtotal + (float) $quote['fee'], 2);

        $this->respondJson([
            'success' => true,
            'quote' => $quote,
            'subtotal' => $subtotal,
            'subtotal_label' => formatarPreco($subtotal),
            'shipping_label' => $quote['mode'] === ShippingQuoteService::MODE_TO_ARRANGE
                ? 'A combinar'
                : ((float) $quote['fee'] > 0 ? formatarPreco((float) $quote['fee']) : 'Grátis'),
            'total' => $total,
            'total_label' => formatarPreco($total),
            'installment_options' => PaymentMethod::cardEnabled() ? PaymentMethod::installmentOptions($total) : [],
        ]);
    }

    public function placeOrder(): void
    {
        Csrf::requireValidPostOrAbort();

        try {
            $cart = $this->cartService->summary();
            $commerce = client_config('commerce', []);
            if (!is_array($commerce)) {
                $commerce = [];
            }

            $checkoutContext = [
                'orderReferencePrefix' => (string) ($commerce['orderReferencePrefix'] ?? 'ORD'),
                'strictOwnership' => (bool) ($commerce['strictOwnership'] ?? true),
            ];

            $loggedInCustomerId = $this->customerAuthService->currentCustomerId();
            if ($loggedInCustomerId !== null) {
                $checkoutContext['loggedInCustomerId'] = $loggedInCustomerId;
            }

            $result = $this->orderService->createPublicDraft($_POST, $cart['items'], $checkoutContext);
            if ($result['success']) {
                $_SESSION['commerce_last_order_id'] = (int) ($result['id'] ?? 0);
                $this->cartService->finalizeCurrentCart();
                $this->redirect('/pedido-recebido');
            }

            $this->renderCheckout(
                $result['errors'],
                $result['data'],
                $result['field_errors'] ?? []
            );
        } catch (Throwable $e) {
            error_log('[checkout_place_order] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $this->renderCheckout(
                ['Não foi possível enviar o pedido. Revise os dados ou tente novamente em instantes.'],
                $_POST,
                []
            );
        }
    }

    public function orderReceived(): void
    {
        $orderId = (int) ($_SESSION['commerce_last_order_id'] ?? 0);
        $receipt = $this->orderService->getPublicReceipt($orderId);
        if ($receipt === null) {
            $this->redirect('/produtos');
        }

        $pixCharge = $this->paymentChargeService->findForOrder($orderId);
        $sideEffectsKey = 'commerce_order_side_effects_' . $orderId;

        if (!isset($_SESSION[$sideEffectsKey])) {
            $_SESSION[$sideEffectsKey] = true;
            $pixCharge = $this->orderService->ensureOnlineOrderPixCharge($orderId) ?? $pixCharge;

            register_shutdown_function(function () use ($orderId): void {
                try {
                    (new OrderService())->processOnlineOrderNotifications($orderId);
                } catch (Throwable $e) {
                    error_log('[checkout_order_notifications] order=' . $orderId . ' ' . $e->getMessage());
                }
            });
        }

        $this->view('client::commerce/order-received', [
            'client' => client_config(),
            'order' => $receipt['order'],
            'items' => $receipt['items'],
            'pix_charge' => $pixCharge ?? $this->paymentChargeService->findForOrder($orderId),
            'payment_method' => PaymentMethod::parse((string) ($receipt['order']['payment_method'] ?? PaymentMethod::PIX)),
        ]);
    }

    /**
     * @param list<string> $errors
     * @param array<string, mixed> $data
     */
    /**
     * @param list<string> $errors
     * @param array<string, mixed> $data
     * @param array<string, string> $fieldErrors
     */
    protected function renderCheckout(array $errors, array $data, array $fieldErrors = []): void
    {
        if ($data === [] && $this->customerAuthService->isAuthenticated()) {
            $customerId = $this->customerAuthService->currentCustomerId();
            if ($customerId !== null) {
                $data = $this->customerAccountService->getCheckoutPrefill($customerId);
            }
        }

        $cart = $this->cartService->summary();
        $subtotal = round((float) ($cart['subtotal'] ?? 0), 2);
        $deliveryMethod = (string) ($data['delivery_method'] ?? 'delivery');
        $quote = $this->shippingQuoteService->quote($deliveryMethod, $data, $subtotal);
        $total = round($subtotal + (float) $quote['fee'], 2);

        $this->view('client::commerce/checkout', [
            'client' => client_config(),
            'cart' => $cart,
            'errors' => $errors,
            'field_errors' => $fieldErrors,
            'data' => $data,
            'logged_in' => $this->customerAuthService->isAuthenticated(),
            'shipping_quote' => $quote,
            'shipping_zones_configured' => $this->shippingQuoteService->hasZonesConfigured(),
            'order_total' => $total,
            'card_enabled' => PaymentMethod::cardEnabled(),
            'installment_options' => PaymentMethod::cardEnabled() ? PaymentMethod::installmentOptions($total) : [],
        ]);
    }

    /**
     * @return array{
     *     item_count: int,
     *     product_count: int,
     *     subtotal: float,
     *     subtotal_label: string,
     *     items: list<array<string, mixed>>
     * }
     */
    private function cartPayload(): array
    {
        $cart = $this->cartService->summary();
        $subtotal = round((float) $cart['subtotal'], 2);

        return [
            'item_count' => (int) $cart['item_count'],
            'product_count' => count($cart['items']),
            'subtotal' => $subtotal,
            'subtotal_label' => formatarPreco($subtotal),
            'items' => $this->formatCartItemsForJson($cart['items']),
        ];
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return list<array<string, mixed>>
     */
    private function formatCartItemsForJson(array $items): array
    {
        $formatted = [];

        foreach ($items as $item) {
            $image = trim((string) ($item['image'] ?? ''));
            $formatted[] = [
                'product_id' => (int) ($item['product_id'] ?? 0),
                'name' => (string) ($item['name'] ?? 'Produto'),
                'quantity' => (int) ($item['quantity'] ?? 0),
                'unit_price' => (float) ($item['unit_price'] ?? 0),
                'unit_price_label' => formatarPreco((float) ($item['unit_price'] ?? 0)),
                'total' => (float) ($item['total'] ?? 0),
                'total_label' => formatarPreco((float) ($item['total'] ?? 0)),
                'image_url' => $image !== '' ? client_product_image_url($image) : null,
                'product_url' => BASE_URL . '/produtos/' . rawurlencode((string) ($item['public_slug'] ?? '')),
            ];
        }

        return $formatted;
    }

    private function wantsJson(): bool
    {
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        if (str_contains($accept, 'application/json')) {
            return true;
        }

        return strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    private function validateCsrfForRequest(): bool
    {
        return Csrf::validatePosted();
    }

    private function abortCsrf(): void
    {
        if ($this->wantsJson()) {
            $this->respondJson([
                'success' => false,
                'message' => 'Sessão expirada. Atualize a página e tente novamente.',
            ], 403);
            return;
        }

        Csrf::requireValidPostOrAbort();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function respondJson(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function redirectWithMessage(string $path, string $message, string $type = 'ok'): void
    {
        $_SESSION['commerce_message'] = $message;
        $_SESSION['commerce_message_type'] = $type;
        $this->redirect($path);
    }
}
