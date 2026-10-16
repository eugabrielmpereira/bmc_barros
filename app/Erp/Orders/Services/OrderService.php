<?php

declare(strict_types=1);

namespace Erp\Orders\Services;

use Core\Database;
use Core\Exceptions\ValidationException;
use Erp\Commerce\Services\CepLookupService;
use Shared\Support\BrazilianAddress;
use Shared\Support\TaxId;
use Erp\Audit\Services\AuditService;
use Erp\Finance\Services\PaymentChargeService;
use Erp\Finance\Services\ReceivableService;
use Erp\Finance\Support\PaymentMethod;
use Erp\Shipping\Repositories\DeliveryZoneRepository;
use Erp\Shipping\Services\ShippingQuoteService;
use Erp\Fulfillment\Support\DeliveryMethod;
use Erp\Fulfillment\Services\FulfillmentService;
use Erp\Commercial\Repositories\CommercialClientRepository;
use Erp\Customers\Repositories\CustomerRepository;
use Erp\Notifications\Services\OrderNotificationService;
use Erp\Orders\Contracts\PublicOrderReceiptReader;
use Erp\Orders\Repositories\OrderItemRepository;
use Erp\Orders\Repositories\OrderRepository;
use Erp\Products\Repositories\ProductRepository;
use Erp\Stock\Services\StockAvailabilityService;
use Erp\Stock\Services\StockReservationService;
use Erp\Stock\Services\StockService;
use PDO;
use Shared\Support\Phone;
use Throwable;

final class OrderService implements PublicOrderReceiptReader
{
    private const CREATE_STATUSES = ['draft', 'confirmed'];
    public const ONLINE_ORDER_NOTE_PREFIX = 'Pedido online recebido pelo site público.';
    public const CANCEL_SOURCE_PIX_OVERDUE_AUTO = 'pix_overdue_auto';
    private const OWNERSHIP_MISMATCH_MESSAGE = 'Não foi possível validar seus dados. Confirme o telefone ou e-mail cadastrado ou entre em contato com a loja.';

    public function __construct(
        private ?PDO $pdo = null,
        private ?OrderRepository $orderRepository = null,
        private ?OrderItemRepository $itemRepository = null,
        private ?CommercialClientRepository $clientRepository = null,
        private ?ProductRepository $productRepository = null,
        private ?StockService $stockService = null,
        private ?AuditService $auditService = null,
        private ?CepLookupService $cepLookupService = null,
        private ?OrderNotificationService $orderNotificationService = null,
        private ?StockReservationService $stockReservationService = null,
        private ?StockAvailabilityService $stockAvailabilityService = null,
        private ?ReceivableService $receivableService = null,
        private ?PaymentChargeService $paymentChargeService = null,
        private ?FulfillmentService $fulfillmentService = null,
        private ?ShippingQuoteService $shippingQuoteService = null
    ) {
        $this->pdo ??= Database::getInstance();
        $this->orderRepository ??= new OrderRepository($this->pdo);
        $this->itemRepository ??= new OrderItemRepository($this->pdo);
        $this->clientRepository ??= new CommercialClientRepository($this->pdo);
        $this->productRepository ??= new ProductRepository($this->pdo);
        $this->stockService ??= new StockService($this->pdo, $this->productRepository);
        $this->auditService ??= new AuditService();
        $this->cepLookupService ??= new CepLookupService();
        $this->orderNotificationService ??= new OrderNotificationService($this);
        $this->stockReservationService ??= new StockReservationService($this->pdo);
        $this->stockAvailabilityService ??= new StockAvailabilityService($this->pdo);
        $this->receivableService ??= new ReceivableService($this->pdo, $this->orderRepository, $this->auditService);
        $this->paymentChargeService ??= new PaymentChargeService($this->pdo);
        $this->fulfillmentService ??= new FulfillmentService($this->orderRepository);
        $this->shippingQuoteService ??= new ShippingQuoteService(new DeliveryZoneRepository($this->pdo));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForAdmin(string $search = ''): array
    {
        return $this->orderRepository->listForAdmin($search);
    }

    public function find(int $id): ?array
    {
        return $id > 0 ? $this->orderRepository->find($id) : null;
    }

    /**
     * @return array{order: array<string, mixed>, items: list<array<string, mixed>>, origin: string, origin_label: string}|null
     */
    public function getDetailForAdmin(int $orderId): ?array
    {
        $order = $this->orderRepository->findForAdmin($orderId);
        if ($order === null) {
            return null;
        }

        $origin = self::resolveOrigin($order);

        return [
            'order' => $order,
            'items' => $this->itemRepository->listByOrder($orderId),
            'origin' => $origin,
            'origin_label' => $origin === 'online' ? 'Site público' : 'Admin ERP',
        ];
    }

    /**
     * @param array<string, mixed> $order
     */
    public static function resolveOrigin(array $order): string
    {
        $internalNotes = trim((string) ($order['internal_notes'] ?? ''));
        if ($internalNotes !== '' && str_starts_with($internalNotes, self::ONLINE_ORDER_NOTE_PREFIX)) {
            return 'online';
        }

        $notes = (string) ($order['notes'] ?? '');

        return str_starts_with($notes, self::ONLINE_ORDER_NOTE_PREFIX) ? 'online' : 'admin';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function activeClients(): array
    {
        return $this->clientRepository->activeOptions();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function activeProducts(): array
    {
        return $this->productRepository->allActive();
    }

    /**
     * @return array{success: bool, errors: list<string>, data: array<string, mixed>, id?: int, message?: string}
     */
    public function create(array $post, ?int $userId = null): array
    {
        [$errors, $data] = $this->validate($post);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors, 'data' => $data];
        }

        $product = $this->productRepository->find((int) $data['product_id']);
        if ($product === null || (string) ($product['status'] ?? '') !== 'ativo') {
            return ['success' => false, 'errors' => ['Produto inválido.'], 'data' => $data];
        }

        $unitPrice = (float) ($product['preco'] ?? 0);
        $total = $unitPrice * (int) $data['quantity'];

        $this->pdo->beginTransaction();
        try {
            $orderId = $this->orderRepository->createOrder(
                (int) $data['commercial_client_id'],
                (string) $data['status'],
                $total,
                $data['notes'] !== '' ? (string) $data['notes'] : null,
                $userId
            );
            $this->itemRepository->createItem($orderId, (int) $data['product_id'], (int) $data['quantity'], $unitPrice);

            if ($data['status'] === 'draft') {
                $this->stockReservationService->syncForDraftOrder($orderId);
            }

            if ($data['status'] === 'confirmed') {
                $this->stockService->removeStock(
                    (int) $data['product_id'],
                    (int) $data['quantity'],
                    $userId,
                    'Pedido #' . $orderId . ' confirmado'
                );
                $this->receivableService->setPendingOnConfirm($orderId);
            }

            $this->pdo->commit();
        } catch (ValidationException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return ['success' => false, 'errors' => $this->validationMessages($e), 'data' => $data];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }

        return [
            'success' => true,
            'errors' => [],
            'data' => $data,
            'id' => $orderId,
            'message' => 'Pedido criado com sucesso.',
        ];
    }

    /**
     * @param list<array<string, mixed>> $cartItems
     * @param array<string, mixed> $checkoutContext orderReferencePrefix, strictOwnership
     * @return array{success: bool, errors: list<string>, field_errors: array<string, string>, data: array<string, mixed>, id?: int, message?: string}
     */
    public function createPublicDraft(array $post, array $cartItems, array $checkoutContext = []): array
    {
        [$errors, $customerData, $fieldErrors] = $this->validatePublicCustomer($post);
        $items = $this->validatePublicItems($cartItems, $errors);

        if ($errors !== []) {
            return [
                'success' => false,
                'errors' => $errors,
                'data' => $customerData,
                'field_errors' => $fieldErrors,
            ];
        }

        $referencePrefix = strtoupper(trim((string) ($checkoutContext['orderReferencePrefix'] ?? 'ORD')));
        if ($referencePrefix === '') {
            $referencePrefix = 'ORD';
        }
        $strictOwnership = (bool) ($checkoutContext['strictOwnership'] ?? true);
        $loggedInCustomerId = (int) ($checkoutContext['loggedInCustomerId'] ?? 0);

        if ($loggedInCustomerId > 0) {
            $sessionCustomer = (new CustomerRepository($this->pdo))->find($loggedInCustomerId);
            if ($sessionCustomer === null || (string) ($sessionCustomer['status'] ?? '') !== 'active') {
                return [
                    'success' => false,
                    'errors' => ['Sua sessão expirou. Faça login novamente para concluir o pedido.'],
                    'data' => $customerData,
                    'field_errors' => [],
                ];
            }

            $clientId = (int) ($sessionCustomer['commercial_client_id'] ?? 0);
            $clientErrors = [];
            $ownershipVerified = true;
            $clientFieldErrors = [];
        } else {
            [$clientId, $clientErrors, $ownershipVerified, $clientFieldErrors] = $this->resolvePublicClientForCheckout(
                $customerData,
                $strictOwnership
            );
        }

        if ($clientErrors !== []) {
            return [
                'success' => false,
                'errors' => $clientErrors,
                'data' => $customerData,
                'field_errors' => $clientFieldErrors,
            ];
        }

        $subtotal = round(array_reduce(
            $items,
            fn (float $sum, array $item): float => $sum + ((float) $item['unit_price'] * (int) $item['quantity']),
            0.0
        ), 2);

        // Frete sempre recalculado no servidor — nunca confiar em valor enviado pelo navegador.
        $shippingQuote = $this->shippingQuoteService->quote(
            (string) ($customerData['delivery_method'] ?? DeliveryMethod::DELIVERY),
            $customerData,
            $subtotal
        );
        if (!$shippingQuote['available']) {
            return [
                'success' => false,
                'errors' => [$shippingQuote['message']],
                'data' => $customerData,
                'field_errors' => ['shipping' => $shippingQuote['message']],
            ];
        }

        $shippingFee = (float) $shippingQuote['fee'];
        $total = round($subtotal + $shippingFee, 2);

        $paymentMethod = PaymentMethod::parse((string) ($post['payment_method'] ?? PaymentMethod::PIX));
        if ($paymentMethod === PaymentMethod::CREDIT_CARD && !PaymentMethod::cardEnabled()) {
            $paymentMethod = PaymentMethod::PIX;
        }
        $installments = 1;
        if ($paymentMethod === PaymentMethod::CREDIT_CARD) {
            $installments = max(1, min(
                (int) ($post['installments'] ?? 1),
                PaymentMethod::allowedInstallments($total)
            ));
        }
        $customerData['payment_method'] = $paymentMethod;
        $customerData['installments'] = $installments;

        $customerNotes = trim((string) ($customerData['notes'] ?? ''));
        $internalNotes = self::ONLINE_ORDER_NOTE_PREFIX;
        if (!empty($customerData['cep_server_validation_deferred'])) {
            $internalNotes .= "\nCEP não validado no servidor — revisar endereço antes da expedição.";
        }
        if ($shippingQuote['mode'] === ShippingQuoteService::MODE_TO_ARRANGE) {
            $internalNotes .= "\nFrete a combinar — nenhuma zona de entrega cadastrada.";
        }
        $shippingComplement = BrazilianAddress::mergeComplementAndReference(
            (string) ($customerData['shipping_complement'] ?? ''),
            (string) ($customerData['address_reference'] ?? '')
        );

        $this->pdo->beginTransaction();
        try {
            if ($clientId <= 0) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }

                return [
                    'success' => false,
                    'errors' => ['Não foi possível registrar o cliente do pedido.'],
                    'data' => $customerData,
                    'field_errors' => [],
                ];
            }

            $orderId = $this->orderRepository->createPublicOrder([
                'commercial_client_id' => $clientId,
                'status' => 'draft',
                'total_amount' => $total,
                'shipping_fee' => $shippingFee,
                'delivery_zone_id' => $shippingQuote['zone_id'],
                'delivery_zone_name' => $shippingQuote['zone_name'],
                'payment_method' => $paymentMethod,
                'installments' => $installments,
                'notes' => null,
                'internal_notes' => $internalNotes,
                'customer_notes' => $customerNotes !== '' ? $customerNotes : null,
                'public_reference' => $this->generatePublicReference($referencePrefix),
                'ownership_verified' => $ownershipVerified ? 1 : 0,
                'shipping_postal_code' => $customerData['shipping_postal_code'] ?? null,
                'shipping_street' => $customerData['shipping_street'] ?? null,
                'shipping_number' => $customerData['shipping_number'] ?? null,
                'shipping_complement' => $shippingComplement,
                'shipping_neighborhood' => $customerData['shipping_neighborhood'] ?? null,
                'shipping_city' => $customerData['shipping_city'] ?? null,
                'shipping_state' => $customerData['shipping_state'] ?? null,
                'delivery_method' => $customerData['delivery_method'] ?? DeliveryMethod::DELIVERY,
                'created_by' => null,
            ]);

            foreach ($items as $item) {
                $this->itemRepository->createItem(
                    $orderId,
                    (int) $item['product_id'],
                    (int) $item['quantity'],
                    (float) $item['unit_price']
                );
            }

            $this->stockReservationService->syncForDraftOrder($orderId);

            if (!empty($customerData['cep_server_validation_deferred'])) {
                $this->auditService->record('checkout_cep_lookup_unavailable', 'order', $orderId, [
                    'shipping_postal_code' => $customerData['shipping_postal_code'] ?? null,
                ]);
            }

            $this->pdo->commit();
        } catch (ValidationException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return [
                'success' => false,
                'errors' => $this->validationMessages($e),
                'data' => $customerData,
                'field_errors' => [],
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }

        return [
            'success' => true,
            'errors' => [],
            'field_errors' => [],
            'data' => $customerData,
            'id' => $orderId,
            'message' => 'Pedido online criado com sucesso.',
        ];
    }

    /**
     * E-mails de confirmação (cliente + admin). Idempotente por pedido via checagem externa.
     */
    public function processOnlineOrderNotifications(int $orderId): void
    {
        if ($orderId <= 0) {
            return;
        }

        $this->orderNotificationService->notifyOnlineOrderCreated($orderId);
    }

    /**
     * Garante cobrança Pix no gateway para pedido online; retorna registro local ou null.
     *
     * @return array<string, mixed>|null
     */
    public function ensureOnlineOrderPixCharge(int $orderId): ?array
    {
        if ($orderId <= 0) {
            return null;
        }

        $existing = $this->paymentChargeService->findForOrder($orderId);
        if ($existing !== null) {
            return $existing;
        }

        $customerData = $this->customerDataForOnlineOrder($orderId);
        if ($customerData === null) {
            return null;
        }

        return $this->paymentChargeService->createPixForOnlineOrder($orderId, $customerData);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function customerDataForOnlineOrder(int $orderId): ?array
    {
        $order = $this->orderRepository->findForAdmin($orderId);
        if ($order === null) {
            return null;
        }

        return [
            'name' => (string) ($order['client_name'] ?? ''),
            'email' => (string) ($order['client_email'] ?? ''),
            'phone' => (string) ($order['client_phone'] ?? ''),
            'document' => (string) ($order['client_document'] ?? ''),
        ];
    }

    /**
     * @return array{success: bool, message: string, errors: list<string>}
     */
    public function approve(int $orderId, ?int $userId = null): array
    {
        $order = $this->orderRepository->find($orderId);
        if ($order === null) {
            return ['success' => false, 'message' => 'Pedido não encontrado.', 'errors' => ['Pedido não encontrado.']];
        }

        $status = (string) ($order['status'] ?? '');
        if ($status === 'confirmed') {
            return ['success' => false, 'message' => 'Pedido já está confirmado.', 'errors' => ['Pedido já está confirmado.']];
        }
        if ($status === 'cancelled') {
            return ['success' => false, 'message' => 'Pedido cancelado não pode ser aprovado.', 'errors' => ['Pedido cancelado não pode ser aprovado.']];
        }

        $items = $this->itemRepository->listByOrder($orderId);
        if ($items === []) {
            return ['success' => false, 'message' => 'Pedido sem itens não pode ser aprovado.', 'errors' => ['Pedido sem itens não pode ser aprovado.']];
        }

        $this->pdo->beginTransaction();
        try {
            foreach ($items as $item) {
                $this->stockService->removeStock(
                    (int) $item['product_id'],
                    (int) $item['quantity'],
                    $userId,
                    'Pedido #' . $orderId . ' aprovado'
                );
            }

            $this->stockReservationService->consumeByOrder($orderId);
            $this->orderRepository->updateStatus($orderId, 'confirmed');
            $gatewayPaid = $this->paymentChargeService->hasPaidCharge($orderId);
            $this->receivableService->applyPaymentStatusOnConfirm($orderId, $gatewayPaid);
            $this->fulfillmentService->ensurePendingOnConfirm($orderId);
            $this->pdo->commit();
        } catch (ValidationException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return ['success' => false, 'message' => $e->getMessage(), 'errors' => $this->validationMessages($e)];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }

        return ['success' => true, 'message' => 'Pedido aprovado e estoque baixado com sucesso.', 'errors' => []];
    }

    /**
     * @param string|null $cancelSource Origem do cancelamento (ex.: {@see self::CANCEL_SOURCE_PIX_OVERDUE_AUTO}).
     * @return array{success: bool, message: string, errors: list<string>}
     */
    public function cancel(int $orderId, ?int $userId = null, ?string $cancelSource = null): array
    {
        $order = $this->orderRepository->find($orderId);
        if ($order === null) {
            return ['success' => false, 'message' => 'Pedido não encontrado.', 'errors' => ['Pedido não encontrado.']];
        }

        $status = (string) ($order['status'] ?? '');
        if ($status === 'cancelled') {
            return ['success' => false, 'message' => 'Pedido já está cancelado.', 'errors' => ['Pedido já está cancelado.']];
        }

        $items = $this->itemRepository->listByOrder($orderId);

        $ownsTransaction = !$this->pdo->inTransaction();
        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            if ($status === 'draft') {
                $this->stockReservationService->releaseByOrder($orderId);
            }

            if ($status === 'confirmed') {
                foreach ($items as $item) {
                    $this->stockService->addStock(
                        (int) $item['product_id'],
                        (int) $item['quantity'],
                        $userId,
                        'Pedido #' . $orderId . ' cancelado'
                    );
                }
            }

            $this->orderRepository->updateStatus($orderId, 'cancelled');
            $this->receivableService->syncPaymentStatusForOrder($orderId, 'cancelled');
            $this->fulfillmentService->markCancelled($orderId);

            if ($ownsTransaction) {
                $this->pdo->commit();
            }
        } catch (Throwable $e) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }

        if ($cancelSource === self::CANCEL_SOURCE_PIX_OVERDUE_AUTO) {
            $this->auditService->record('cancel_pix_overdue_auto', 'order', $orderId, [
                'source' => self::CANCEL_SOURCE_PIX_OVERDUE_AUTO,
                'user_id' => $userId,
            ]);
        }

        return ['success' => true, 'message' => 'Pedido cancelado com sucesso.', 'errors' => []];
    }

    /**
     * @return array{success: bool, message: string, errors: list<string>}
     */
    public function reopenCancelled(int $orderId, ?int $userId = null): array
    {
        $order = $this->orderRepository->find($orderId);
        if ($order === null) {
            return ['success' => false, 'message' => 'Pedido não encontrado.', 'errors' => ['Pedido não encontrado.']];
        }

        if ((string) ($order['status'] ?? '') !== 'cancelled') {
            return ['success' => false, 'message' => 'Somente pedidos cancelados podem ser reabertos.', 'errors' => ['Status inválido para reabertura.']];
        }

        $this->pdo->beginTransaction();
        try {
            $this->orderRepository->updateStatus($orderId, 'draft');
            $this->receivableService->resetPaymentOnReopen($orderId);
            $this->stockReservationService->syncForDraftOrder($orderId);
            $this->fulfillmentService->resetAfterReopen($orderId);
            $this->pdo->commit();
        } catch (ValidationException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $this->validationMessages($e),
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }

        $this->auditService->record('reopen', 'order', $orderId, ['user_id' => $userId]);

        return ['success' => true, 'message' => 'Pedido reaberto como pendente.', 'errors' => []];
    }

    /**
     * @return array{success: bool, message: string, errors: list<string>}
     */
    public function addDraftItem(int $orderId, array $post, ?int $userId = null): array
    {
        $guard = $this->guardDraftOrder($orderId);
        if (!$guard['success']) {
            return $guard;
        }

        $productId = (int) ($post['product_id'] ?? 0);
        $quantity = (int) ($post['quantity'] ?? 0);
        if ($productId <= 0) {
            return ['success' => false, 'message' => 'Produto é obrigatório.', 'errors' => ['Produto é obrigatório.']];
        }
        if ($quantity <= 0) {
            return ['success' => false, 'message' => 'Quantidade deve ser maior que zero.', 'errors' => ['Quantidade inválida.']];
        }

        $product = $this->productRepository->find($productId);
        if ($product === null || (string) ($product['status'] ?? '') !== 'ativo') {
            return ['success' => false, 'message' => 'Produto inválido ou inativo.', 'errors' => ['Produto inválido.']];
        }

        $unitPrice = (float) ($product['preco'] ?? 0);

        $this->pdo->beginTransaction();
        try {
            $this->itemRepository->createItem($orderId, $productId, $quantity, $unitPrice);
            $this->stockReservationService->syncForDraftOrder($orderId);
            $this->recalculateOrderTotal($orderId);
            $this->pdo->commit();
        } catch (ValidationException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return ['success' => false, 'message' => $e->getMessage(), 'errors' => $this->validationMessages($e)];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }

        $this->auditService->record('order_item_add', 'order', $orderId, [
            'product_id' => $productId,
            'quantity' => $quantity,
            'user_id' => $userId,
        ]);

        return ['success' => true, 'message' => 'Item adicionado ao pedido.', 'errors' => []];
    }

    /**
     * @return array{success: bool, message: string, errors: list<string>}
     */
    public function updateDraftItem(int $orderId, int $itemId, array $post, ?int $userId = null): array
    {
        $guard = $this->guardDraftOrder($orderId);
        if (!$guard['success']) {
            return $guard;
        }

        $item = $this->itemRepository->findByIdForOrder($itemId, $orderId);
        if ($item === null) {
            return ['success' => false, 'message' => 'Item não encontrado.', 'errors' => ['Item não encontrado.']];
        }

        $quantity = (int) ($post['quantity'] ?? 0);
        if ($quantity <= 0) {
            return ['success' => false, 'message' => 'Quantidade deve ser maior que zero.', 'errors' => ['Quantidade inválida.']];
        }

        $before = [
            'product_id' => (int) ($item['product_id'] ?? 0),
            'quantity' => (int) ($item['quantity'] ?? 0),
            'unit_price' => (float) ($item['unit_price'] ?? 0),
        ];

        $this->pdo->beginTransaction();
        try {
            $this->itemRepository->updateQuantityAndTotal($itemId, $quantity, (float) ($item['unit_price'] ?? 0));
            $this->stockReservationService->syncForDraftOrder($orderId);
            $this->recalculateOrderTotal($orderId);
            $this->pdo->commit();
        } catch (ValidationException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return ['success' => false, 'message' => $e->getMessage(), 'errors' => $this->validationMessages($e)];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }

        $this->auditService->record('order_item_update', 'order', $orderId, [
            'item_id' => $itemId,
            'before' => $before,
            'after' => [
                'product_id' => $before['product_id'],
                'quantity' => $quantity,
                'unit_price' => $before['unit_price'],
            ],
            'user_id' => $userId,
        ]);

        return ['success' => true, 'message' => 'Quantidade atualizada.', 'errors' => []];
    }

    /**
     * @return array{success: bool, message: string, errors: list<string>}
     */
    public function removeDraftItem(int $orderId, int $itemId, ?int $userId = null): array
    {
        $guard = $this->guardDraftOrder($orderId);
        if (!$guard['success']) {
            return $guard;
        }

        $item = $this->itemRepository->findByIdForOrder($itemId, $orderId);
        if ($item === null) {
            return ['success' => false, 'message' => 'Item não encontrado.', 'errors' => ['Item não encontrado.']];
        }

        $this->pdo->beginTransaction();
        try {
            $this->itemRepository->deleteById($itemId);
            $this->stockReservationService->syncForDraftOrder($orderId);
            $this->recalculateOrderTotal($orderId);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }

        $this->auditService->record('order_item_remove', 'order', $orderId, [
            'item_id' => $itemId,
            'product_id' => (int) ($item['product_id'] ?? 0),
            'quantity' => (int) ($item['quantity'] ?? 0),
            'user_id' => $userId,
        ]);

        return ['success' => true, 'message' => 'Item removido do pedido.', 'errors' => []];
    }

    /**
     * @return array{success: bool, message: string, errors: list<string>}
     */
    public function updateDraftShipping(int $orderId, array $post, ?int $userId = null): array
    {
        $guard = $this->guardDraftOrder($orderId);
        if (!$guard['success']) {
            return $guard;
        }

        $order = $this->orderRepository->findForAdmin($orderId);
        if ($order === null) {
            return ['success' => false, 'message' => 'Pedido não encontrado.', 'errors' => ['Pedido não encontrado.']];
        }

        $shipping = BrazilianAddress::parseAdminOrderPost($post);
        [$errors] = BrazilianAddress::validateAdminShippingAddress($shipping);
        if ($errors !== []) {
            return ['success' => false, 'message' => $errors[0], 'errors' => $errors];
        }

        $before = $this->extractShippingSnapshot($order);
        $this->orderRepository->updateShippingFields($orderId, $shipping);

        $this->auditService->record('order_shipping_update', 'order', $orderId, [
            'before' => $before,
            'after' => $this->extractShippingSnapshot(array_merge($order, $shipping)),
            'user_id' => $userId,
        ]);

        return ['success' => true, 'message' => 'Endereço de entrega atualizado.', 'errors' => []];
    }

    /**
     * @return array{success: bool, message: string, errors: list<string>}
     */
    public function updateOperatorNotes(int $orderId, array $post, ?int $userId = null): array
    {
        $order = $this->orderRepository->find($orderId);
        if ($order === null) {
            return ['success' => false, 'message' => 'Pedido não encontrado.', 'errors' => ['Pedido não encontrado.']];
        }

        if ((string) ($order['status'] ?? '') !== 'draft') {
            return ['success' => false, 'message' => 'Notas do operador só podem ser editadas em pedidos pendentes.', 'errors' => ['Status inválido.']];
        }

        $notes = trim((string) ($post['notes'] ?? ''));
        $before = trim((string) ($order['notes'] ?? ''));
        $this->orderRepository->updateOperatorNotes($orderId, $notes !== '' ? $notes : null);

        if ($before !== $notes) {
            $this->auditService->record('order_notes_update', 'order', $orderId, [
                'before' => $before,
                'after' => $notes,
                'user_id' => $userId,
            ]);
        }

        return ['success' => true, 'message' => 'Notas do operador salvas.', 'errors' => []];
    }

    /**
     * @return array{success: bool, message: string, errors: list<string>}
     */
    private function guardDraftOrder(int $orderId): array
    {
        $order = $this->orderRepository->find($orderId);
        if ($order === null) {
            return ['success' => false, 'message' => 'Pedido não encontrado.', 'errors' => ['Pedido não encontrado.']];
        }

        if ((string) ($order['status'] ?? '') !== 'draft') {
            return ['success' => false, 'message' => 'Alteração permitida apenas em pedidos pendentes.', 'errors' => ['Status inválido.']];
        }

        return ['success' => true, 'message' => '', 'errors' => []];
    }

    private function recalculateOrderTotal(int $orderId): void
    {
        $order = $this->orderRepository->find($orderId);
        $shippingFee = (float) ($order['shipping_fee'] ?? 0);
        $total = round($this->itemRepository->sumTotalByOrder($orderId) + $shippingFee, 2);
        $this->orderRepository->updateTotalAmount($orderId, $total);
    }

    /**
     * @param array<string, mixed> $order
     * @return array<string, mixed>
     */
    private function extractShippingSnapshot(array $order): array
    {
        return [
            'shipping_postal_code' => $order['shipping_postal_code'] ?? null,
            'shipping_street' => $order['shipping_street'] ?? null,
            'shipping_number' => $order['shipping_number'] ?? null,
            'shipping_complement' => $order['shipping_complement'] ?? null,
            'shipping_neighborhood' => $order['shipping_neighborhood'] ?? null,
            'shipping_city' => $order['shipping_city'] ?? null,
            'shipping_state' => $order['shipping_state'] ?? null,
        ];
    }

    /**
     * @return array{draft: int, confirmed: int, cancelled: int, totalAmount: float}
     */
    public function summary(): array
    {
        return [
            'draft' => $this->orderRepository->countByStatus('draft'),
            'confirmed' => $this->orderRepository->countByStatus('confirmed'),
            'cancelled' => $this->orderRepository->countByStatus('cancelled'),
            'totalAmount' => $this->orderRepository->sumTotal(),
        ];
    }

    /**
     * @return array{draft: int, confirmed: int, cancelled: int, totalAmount: float}
     */
    public function summaryByPeriod(string $startDateTime, string $endDateTime): array
    {
        return [
            'draft' => $this->orderRepository->countByStatusBetween('draft', $startDateTime, $endDateTime),
            'confirmed' => $this->orderRepository->countByStatusBetween('confirmed', $startDateTime, $endDateTime),
            'cancelled' => $this->orderRepository->countByStatusBetween('cancelled', $startDateTime, $endDateTime),
            'totalAmount' => $this->orderRepository->sumTotalByStatusBetween('confirmed', $startDateTime, $endDateTime),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listByPeriod(string $startDateTime, string $endDateTime): array
    {
        return $this->orderRepository->listByPeriod($startDateTime, $endDateTime);
    }

    /**
     * @return array{order: array<string, mixed>, items: list<array<string, mixed>>}|null
     */
    public function getPublicReceipt(int $orderId): ?array
    {
        if ($orderId <= 0) {
            return null;
        }

        $detail = $this->getDetailForAdmin($orderId);
        if ($detail === null || $detail['origin'] !== 'online') {
            return null;
        }

        return [
            'order' => $detail['order'],
            'items' => $detail['items'],
        ];
    }

    /**
     * @return array{0: list<string>, 1: array<string, mixed>}
     */
    private function validate(array $post): array
    {
        $data = [
            'commercial_client_id' => (int) ($post['commercial_client_id'] ?? 0),
            'product_id' => (int) ($post['product_id'] ?? 0),
            'quantity' => (int) ($post['quantity'] ?? 0),
            'status' => in_array($post['status'] ?? '', self::CREATE_STATUSES, true) ? (string) $post['status'] : 'draft',
            'notes' => trim((string) ($post['notes'] ?? '')),
        ];

        $errors = [];
        if ($data['commercial_client_id'] <= 0 || $this->clientRepository->find($data['commercial_client_id']) === null) {
            $errors[] = 'Cliente comercial inválido.';
        }
        if ($data['product_id'] <= 0) {
            $errors[] = 'Produto é obrigatório.';
        }
        if ($data['quantity'] <= 0) {
            $errors[] = 'Quantidade deve ser maior que zero.';
        }

        return [$errors, $data];
    }

    /**
     * @param array<string, mixed> $customerData
     * @return array{0: int, 1: list<string>, 2: bool, 3: array<string, string>} clientId, errors, ownershipVerified, fieldErrors
     */
    private function resolvePublicClientForCheckout(array $customerData, bool $strictOwnership): array
    {
        $document = is_string($customerData['document'] ?? null) ? (string) $customerData['document'] : '';
        $email = is_string($customerData['email'] ?? null) ? strtolower(trim((string) $customerData['email'])) : '';
        $phone = Phone::normalize((string) ($customerData['phone'] ?? ''));

        $existing = null;
        if ($document !== '') {
            $existing = $this->clientRepository->findByDocument($document);
        }
        if ($existing === null && $email !== '') {
            $existing = $this->clientRepository->findByEmail($email);
        }

        if ($existing === null) {
            $clientId = $this->clientRepository->createClient($this->buildCommercialClientPayload($customerData));

            return [$clientId, [], true, []];
        }

        $clientId = (int) ($existing['id'] ?? 0);
        $registeredPhone = Phone::normalize((string) ($existing['phone'] ?? ''));
        $registeredEmail = strtolower(trim((string) ($existing['email'] ?? '')));

        $phoneMatch = $registeredPhone !== '' && $phone !== '' && $registeredPhone === $phone;
        $emailMatch = $registeredEmail !== '' && $email !== '' && $registeredEmail === $email;

        if ($phoneMatch || $emailMatch) {
            // Checkout não altera cadastro mestre (nome, telefone, endereço) — só vincula o pedido.
            return [$clientId, [], true, []];
        }

        $this->recordCheckoutOwnershipMismatch($clientId, $document);

        if ($strictOwnership) {
            $message = self::OWNERSHIP_MISMATCH_MESSAGE;

            return [
                0,
                [$message],
                false,
                [],
            ];
        }

        return [$clientId, [], false, []];
    }

    private function recordCheckoutOwnershipMismatch(int $clientId, string $document): void
    {
        $documentNormalized = CommercialClientRepository::normalizeDocument($document);
        $metadata = [
            'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            'document_hash' => $documentNormalized !== ''
                ? substr(hash('sha256', $documentNormalized), 0, 16)
                : null,
        ];

        try {
            $this->auditService->record(
                'checkout_ownership_mismatch',
                'commercial_client',
                $clientId > 0 ? $clientId : null,
                $metadata
            );
        } catch (Throwable $e) {
            error_log('[checkout_mismatch] client_id=' . $clientId . ' ip=' . ($metadata['ip'] ?? '-') . ' ' . $e->getMessage());
        }
    }

    private function generatePublicReference(string $prefix): string
    {
        $date = date('Ymd');

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $suffix = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $reference = $prefix . '-' . $date . '-' . $suffix;
            if (!$this->orderRepository->publicReferenceExists($reference)) {
                return $reference;
            }
        }

        throw new \RuntimeException('Não foi possível gerar referência pública única para o pedido.');
    }

    /**
     * @return array{0: list<string>, 1: array<string, mixed>, 2: array<string, string>}
     */
    private function validatePublicCustomer(array $post): array
    {
        $data = array_merge(
            [
                'name' => trim((string) ($post['name'] ?? '')),
                'document' => trim((string) ($post['document'] ?? '')) ?: null,
                'email' => trim((string) ($post['email'] ?? '')) ?: null,
                'phone' => trim((string) ($post['phone'] ?? '')) ?: null,
                'notes' => trim((string) ($post['notes'] ?? '')),
                'delivery_method' => DeliveryMethod::parse((string) ($post['delivery_method'] ?? '')),
            ],
            BrazilianAddress::parseCheckoutPost($post)
        );

        $errors = [];
        $fieldErrors = [];

        if ($data['name'] === '') {
            $errors[] = 'Nome é obrigatório.';
            $fieldErrors['name'] = 'Nome é obrigatório.';
        }
        if ($data['phone'] === null) {
            $errors[] = 'Telefone ou WhatsApp é obrigatório.';
            $fieldErrors['phone'] = 'Telefone ou WhatsApp é obrigatório.';
        }
        if ($data['document'] === null) {
            $errors[] = 'CPF/CNPJ é obrigatório.';
            $fieldErrors['document'] = 'CPF/CNPJ é obrigatório.';
        } else {
            $documentDigits = preg_replace('/\D+/', '', (string) $data['document']) ?? '';
            $digitLength = strlen($documentDigits);
            if ($digitLength !== 11 && $digitLength !== 14) {
                $errors[] = 'CPF/CNPJ incompleto. Verifique os números digitados.';
                $fieldErrors['document'] = 'CPF/CNPJ incompleto. Verifique os números digitados.';
            } elseif (!TaxId::isValidDocument((string) $data['document'])) {
                $errors[] = 'CPF/CNPJ inválido. Verifique os dígitos verificadores.';
                $fieldErrors['document'] = 'CPF/CNPJ inválido. Verifique os dígitos verificadores.';
            }
        }
        if ($data['email'] === null) {
            $errors[] = 'E-mail é obrigatório.';
            $fieldErrors['email'] = 'E-mail é obrigatório.';
        } elseif (!filter_var((string) $data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail inválido.';
            $fieldErrors['email'] = 'E-mail inválido.';
        }

        if ($data['delivery_method'] === DeliveryMethod::DELIVERY) {
            [$addressErrors, $addressFieldErrors] = BrazilianAddress::validateCheckoutAddress($data);
            $errors = array_merge($errors, $addressErrors);
            $fieldErrors = array_merge($fieldErrors, $addressFieldErrors);

            if ($addressErrors === [] && $addressFieldErrors === []) {
                try {
                    $cepLookup = $this->cepLookupService->lookup((string) ($data['shipping_postal_code'] ?? ''));
                    if ($this->isCepLookupUnavailable($cepLookup)) {
                        $data['cep_server_validation_deferred'] = true;
                    } else {
                        [$cepErrors, $cepFieldErrors] = BrazilianAddress::validateAgainstCepLookup($data, $cepLookup);
                        $errors = array_merge($errors, $cepErrors);
                        $fieldErrors = array_merge($fieldErrors, $cepFieldErrors);
                    }
                } catch (Throwable $e) {
                    error_log('[checkout_cep] ' . $e->getMessage());
                    $data['cep_server_validation_deferred'] = true;
                }
            }
        } else {
            $data['shipping_postal_code'] = null;
            $data['shipping_street'] = null;
            $data['shipping_number'] = null;
            $data['shipping_complement'] = null;
            $data['shipping_neighborhood'] = null;
            $data['shipping_city'] = null;
            $data['shipping_state'] = null;
            $data['residence_type'] = null;
            $data['address_reference'] = null;
        }

        return [$errors, $data, $fieldErrors];
    }

    /**
     * @param array<string, mixed> $lookupResult
     */
    private function isCepLookupUnavailable(array $lookupResult): bool
    {
        if (($lookupResult['success'] ?? false) === true) {
            return false;
        }

        $error = mb_strtolower(trim((string) ($lookupResult['error'] ?? '')));
        if ($error === '') {
            return true;
        }

        if (str_contains($error, 'não foi possível consultar') || str_contains($error, 'nao foi possivel consultar')) {
            return true;
        }

        return false;
    }

    /**
     * @param array<string, mixed> $customerData
     * @return array<string, mixed>
     */
    private function buildCommercialClientPayload(array $customerData): array
    {
        $payload = [
            'name' => $customerData['name'],
            'document' => $customerData['document'],
            'email' => $customerData['email'],
            'phone' => $customerData['phone'],
            'address' => $customerData['address'],
            'status' => 'active',
        ];

        $structured = [
            'postal_code' => $customerData['postal_code'] ?? null,
            'street' => $customerData['street'] ?? null,
            'street_number' => $customerData['street_number'] ?? null,
            'complement' => BrazilianAddress::mergeComplementAndReference(
                (string) ($customerData['shipping_complement'] ?? ''),
                (string) ($customerData['address_reference'] ?? '')
            ),
            'neighborhood' => $customerData['neighborhood'] ?? null,
            'city' => $customerData['city'] ?? null,
            'state' => $customerData['state'] ?? null,
        ];

        foreach ($structured as $column => $value) {
            if ($value === null || (is_string($value) && trim($value) === '')) {
                continue;
            }
            $payload[$column] = $value;
        }

        return $payload;
    }

    /**
     * @param list<array<string, mixed>> $cartItems
     * @param list<string> $errors
     * @return list<array{product_id: int, quantity: int, unit_price: float}>
     */
    private function validatePublicItems(array $cartItems, array &$errors): array
    {
        if ($cartItems === []) {
            $errors[] = 'Carrinho vazio.';

            return [];
        }

        $items = [];
        foreach ($cartItems as $cartItem) {
            $productId = (int) ($cartItem['product_id'] ?? 0);
            $quantity = (int) ($cartItem['quantity'] ?? 0);
            if ($productId <= 0 || $quantity <= 0) {
                $errors[] = 'Item inválido no carrinho.';
                continue;
            }

            $product = $this->productRepository->find($productId);
            if ($product === null || (string) ($product['status'] ?? '') !== 'ativo') {
                $errors[] = 'Produto indisponível no carrinho.';
                continue;
            }

            $available = $this->stockAvailabilityService->getAvailableQuantity($productId);
            if ($quantity > $available) {
                $errors[] = 'Estoque insuficiente para "' . (string) ($product['nome'] ?? 'produto') . '".';
                continue;
            }

            $frozenPrice = (float) ($cartItem['unit_price'] ?? 0);
            $items[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $frozenPrice > 0 ? $frozenPrice : (float) ($product['preco'] ?? 0),
            ];
        }

        if ($items === [] && !in_array('Carrinho vazio.', $errors, true)) {
            $errors[] = 'Carrinho vazio.';
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    private function validationMessages(ValidationException $exception): array
    {
        $messages = [];
        foreach ($exception->errors() as $fieldErrors) {
            foreach ($fieldErrors as $message) {
                $messages[] = $message;
            }
        }

        return $messages !== [] ? $messages : [$exception->getMessage()];
    }
}
