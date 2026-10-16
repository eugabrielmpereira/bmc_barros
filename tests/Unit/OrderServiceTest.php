<?php

declare(strict_types=1);

namespace Tests\Unit;

use Erp\Audit\Repositories\AuditLogRepository;
use Erp\Audit\Services\AuditService;
use Erp\Commercial\Repositories\CommercialClientRepository;
use Erp\Orders\Repositories\OrderItemRepository;
use Erp\Orders\Repositories\OrderRepository;
use Erp\Orders\Services\OrderService;
use Erp\Products\Repositories\ProductRepository;
use Erp\Stock\Repositories\StockMovementRepository;
use Erp\Stock\Services\StockService;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeCepLookupService;
use Tests\Support\UnavailableCepLookupService;

final class OrderServiceTest extends TestCase
{
    private PDO $pdo;
    private OrderService $service;
    private ProductRepository $productRepository;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $this->pdo->exec(
            'CREATE TABLE commercial_clients (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                document TEXT,
                email TEXT,
                phone TEXT,
                address TEXT,
                postal_code TEXT,
                street TEXT,
                street_number TEXT,
                complement TEXT,
                neighborhood TEXT,
                city TEXT,
                state TEXT,
                status TEXT NOT NULL DEFAULT \'active\'
             );
             CREATE TABLE produtos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                categoria_id INTEGER NOT NULL,
                nome TEXT NOT NULL,
                descricao TEXT,
                preco REAL,
                imagem TEXT,
                estoque_atual INTEGER DEFAULT 0,
                status TEXT DEFAULT \'ativo\'
             );
             CREATE TABLE orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                commercial_client_id INTEGER NOT NULL,
                status TEXT NOT NULL DEFAULT \'draft\',
                payment_status TEXT NOT NULL DEFAULT \'not_applicable\',
                paid_at TEXT,
                total_amount REAL NOT NULL DEFAULT 0,
                shipping_fee REAL NOT NULL DEFAULT 0,
                delivery_zone_id INTEGER,
                delivery_zone_name TEXT,
                payment_method TEXT NOT NULL DEFAULT \'pix\',
                installments INTEGER NOT NULL DEFAULT 1,
                notes TEXT,
                customer_notes TEXT,
                internal_notes TEXT,
                public_reference TEXT UNIQUE,
                ownership_verified INTEGER NOT NULL DEFAULT 1,
                shipping_postal_code TEXT,
                shipping_street TEXT,
                shipping_number TEXT,
                shipping_complement TEXT,
                shipping_neighborhood TEXT,
                shipping_city TEXT,
                shipping_state TEXT,
                delivery_method TEXT NOT NULL DEFAULT \'delivery\',
                fulfillment_status TEXT NOT NULL DEFAULT \'pending\',
                fulfillment_notes TEXT,
                fulfillment_updated_at TEXT,
                created_by INTEGER,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
             );
             CREATE TABLE delivery_zones (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                city TEXT NOT NULL,
                state TEXT NOT NULL,
                neighborhoods TEXT,
                postal_code_start TEXT,
                postal_code_end TEXT,
                fee REAL NOT NULL DEFAULT 0,
                free_shipping_min REAL,
                min_order_amount REAL,
                lead_time TEXT,
                sort_order INTEGER NOT NULL DEFAULT 0,
                status TEXT NOT NULL DEFAULT \'active\'
             );
             CREATE TABLE order_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                quantity INTEGER NOT NULL,
                unit_price REAL NOT NULL DEFAULT 0,
                total_amount REAL NOT NULL DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
             );
             CREATE TABLE stock_movements (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                produto_id INTEGER NOT NULL,
                type TEXT NOT NULL,
                quantity INTEGER NOT NULL,
                previous_stock INTEGER NOT NULL,
                new_stock INTEGER NOT NULL,
                reason TEXT,
                user_id INTEGER,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
             );
             CREATE TABLE usuarios (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL
             );
             CREATE TABLE audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                action TEXT NOT NULL,
                entity TEXT NOT NULL,
                entity_id INTEGER,
                user_id INTEGER,
                metadata TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
             );
             CREATE TABLE stock_reservations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                quantity INTEGER NOT NULL,
                status TEXT NOT NULL DEFAULT \'active\',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                released_at TEXT NULL
             );
             CREATE TABLE order_payment_charges (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL UNIQUE,
                provider TEXT NOT NULL,
                external_payment_id TEXT NOT NULL,
                external_customer_id TEXT,
                status TEXT NOT NULL,
                billing_type TEXT NOT NULL,
                amount REAL NOT NULL,
                due_date TEXT NOT NULL,
                pix_copy_paste TEXT,
                pix_qr_image_base64 TEXT,
                invoice_url TEXT,
                paid_at TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
             );'
        );
        $this->pdo->exec("INSERT INTO commercial_clients (id, name, status) VALUES (1, 'Cliente Teste', 'active')");
        $this->pdo->exec("INSERT INTO produtos (id, categoria_id, nome, preco, estoque_atual, status) VALUES (1, 1, 'Cimento', 25.50, 10, 'ativo')");

        $this->productRepository = new ProductRepository($this->pdo);
        $movementRepository = new StockMovementRepository($this->pdo);
        $stockService = new StockService($this->pdo, $this->productRepository, $movementRepository);

        $this->service = new OrderService(
            $this->pdo,
            new OrderRepository($this->pdo),
            new OrderItemRepository($this->pdo),
            new CommercialClientRepository($this->pdo),
            $this->productRepository,
            $stockService,
            new AuditService(new AuditLogRepository($this->pdo)),
            cepLookupService: new FakeCepLookupService()
        );
    }

    public function testCreateConfirmedOrderDecreasesStock(): void
    {
        $result = $this->service->create([
            'commercial_client_id' => 1,
            'product_id' => 1,
            'quantity' => 3,
            'status' => 'confirmed',
            'notes' => '',
        ], 7);

        $product = $this->productRepository->find(1);
        $movement = $this->pdo->query('SELECT * FROM stock_movements')->fetch(PDO::FETCH_ASSOC);

        $this->assertTrue($result['success']);
        $this->assertSame(7, (int) $product['estoque_atual']);
        $this->assertSame('saida', $movement['type']);
        $this->assertSame(10, (int) $movement['previous_stock']);
        $this->assertSame(7, (int) $movement['new_stock']);
    }

    public function testApproveDraftOrderDecreasesStock(): void
    {
        $result = $this->service->create([
            'commercial_client_id' => 1,
            'product_id' => 1,
            'quantity' => 2,
            'status' => 'draft',
            'notes' => '',
        ]);

        $approve = $this->service->approve((int) $result['id'], 7);
        $order = $this->pdo->query('SELECT * FROM orders WHERE id = ' . (int) $result['id'])->fetch(PDO::FETCH_ASSOC);
        $product = $this->productRepository->find(1);

        $this->assertTrue($approve['success']);
        $this->assertSame('confirmed', $order['status']);
        $this->assertSame(8, (int) $product['estoque_atual']);
    }

    public function testCancelConfirmedOrderRestoresStock(): void
    {
        $result = $this->service->create([
            'commercial_client_id' => 1,
            'product_id' => 1,
            'quantity' => 4,
            'status' => 'confirmed',
            'notes' => '',
        ]);

        $cancel = $this->service->cancel((int) $result['id'], 7);
        $order = $this->pdo->query('SELECT * FROM orders WHERE id = ' . (int) $result['id'])->fetch(PDO::FETCH_ASSOC);
        $product = $this->productRepository->find(1);

        $this->assertTrue($cancel['success']);
        $this->assertSame('cancelled', $order['status']);
        $this->assertSame(10, (int) $product['estoque_atual']);
    }

    public function testCreateDraftRejectsWhenReservationExceedsAvailable(): void
    {
        $this->pdo->exec('UPDATE produtos SET estoque_atual = 1 WHERE id = 1');
        $result = $this->service->create([
            'commercial_client_id' => 1,
            'product_id' => 1,
            'quantity' => 2,
            'status' => 'draft',
            'notes' => '',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn());
    }

    public function testApproveRejectsInsufficientPhysicalStockAndKeepsDraft(): void
    {
        $this->pdo->exec('UPDATE produtos SET estoque_atual = 1 WHERE id = 1');
        $result = $this->service->create([
            'commercial_client_id' => 1,
            'product_id' => 1,
            'quantity' => 1,
            'status' => 'draft',
            'notes' => '',
        ]);

        $this->pdo->exec('UPDATE produtos SET estoque_atual = 0 WHERE id = 1');

        $approve = $this->service->approve((int) $result['id']);
        $order = $this->pdo->query('SELECT * FROM orders WHERE id = ' . (int) $result['id'])->fetch(PDO::FETCH_ASSOC);
        $product = $this->productRepository->find(1);

        $this->assertTrue($result['success']);
        $this->assertFalse($approve['success']);
        $this->assertSame('draft', $order['status']);
        $this->assertSame(0, (int) $product['estoque_atual']);
    }

    public function testCreatePublicDraftCreatesClientOrderAndItems(): void
    {
        $this->pdo->exec("INSERT INTO produtos (id, categoria_id, nome, preco, estoque_atual, status) VALUES (2, 1, 'Areia', 18.00, 5, 'ativo')");

        $result = $this->service->createPublicDraft($this->checkoutCustomer([
            'notes' => 'Entregar de manhã',
        ]), [
            [
                'product_id' => 1,
                'quantity' => 2,
                'unit_price' => 25.50,
                'name' => 'Cimento',
            ],
            [
                'product_id' => 2,
                'quantity' => 1,
                'unit_price' => 18.00,
                'name' => 'Areia',
            ],
        ], [
            'orderReferencePrefix' => 'TST',
            'strictOwnership' => true,
        ]);

        $this->assertTrue($result['success']);
        $orderId = (int) $result['id'];
        $order = $this->pdo->query('SELECT * FROM orders WHERE id = ' . $orderId)->fetch(PDO::FETCH_ASSOC);
        $items = $this->pdo->query('SELECT COUNT(*) FROM order_items WHERE order_id = ' . $orderId)->fetchColumn();
        $client = $this->pdo->query('SELECT * FROM commercial_clients WHERE id = ' . (int) $order['commercial_client_id'])->fetch(PDO::FETCH_ASSOC);

        $this->assertSame('draft', $order['status']);
        $this->assertSame(69.0, (float) $order['total_amount']);
        $this->assertNull($order['notes']);
        $this->assertStringStartsWith('Pedido online recebido pelo site público.', (string) $order['internal_notes']);
        $this->assertStringContainsString('Frete a combinar', (string) $order['internal_notes']);
        $this->assertSame(0.0, (float) $order['shipping_fee']);
        $this->assertSame('pix', $order['payment_method']);
        $this->assertSame('Entregar de manhã', $order['customer_notes']);
        $this->assertStringStartsWith('TST-', (string) $order['public_reference']);
        $this->assertSame('01310-100', $order['shipping_postal_code']);
        $this->assertSame('Avenida Paulista', $order['shipping_street']);
        $this->assertSame('100', $order['shipping_number']);
        $this->assertSame('SP', $order['shipping_state']);
        $this->assertSame(1, (int) $order['ownership_verified']);
        $this->assertSame(2, (int) $items);
        $this->assertSame('João Silva', $client['name']);
        $this->assertSame('online', OrderService::resolveOrigin($order));
    }

    public function testApprovePublicDraftOrderDecreasesStock(): void
    {
        $draft = $this->service->createPublicDraft($this->checkoutCustomer([
            'name' => 'Maria Souza',
            'phone' => '(11) 98888-0000',
        ]), [
            ['product_id' => 1, 'quantity' => 3, 'unit_price' => 25.50],
        ]);

        $approve = $this->service->approve((int) $draft['id'], 7);
        $product = $this->productRepository->find(1);

        $this->assertTrue($approve['success']);
        $this->assertSame(7, (int) $product['estoque_atual']);
    }

    public function testCreatePublicDraftUsesFrozenCartPriceWhenProductPriceChanged(): void
    {
        $this->pdo->exec("UPDATE produtos SET preco = 99.99 WHERE id = 1");

        $result = $this->service->createPublicDraft($this->checkoutCustomer([
            'name' => 'Cliente Preço',
            'phone' => '(11) 90000-0000',
        ]), [
            ['product_id' => 1, 'quantity' => 2, 'unit_price' => 25.50],
        ]);

        $this->assertTrue($result['success']);
        $order = $this->pdo->query('SELECT * FROM orders WHERE id = ' . (int) $result['id'])->fetch(PDO::FETCH_ASSOC);
        $item = $this->pdo->query('SELECT * FROM order_items WHERE order_id = ' . (int) $result['id'])->fetch(PDO::FETCH_ASSOC);

        $this->assertSame(51.0, (float) $order['total_amount']);
        $this->assertSame(25.50, (float) $item['unit_price']);
    }

    public function testCreatePublicDraftReusesExistingClientWhenEmailMatchesWithoutUpdatingMaster(): void
    {
        $this->pdo->exec(
            "INSERT INTO commercial_clients (id, name, email, phone, status)
             VALUES (9, 'Cliente Antigo', 'joao@example.com', '(11) 91111-1111', 'active')"
        );

        $result = $this->service->createPublicDraft($this->checkoutCustomer([
            'name' => 'João Atualizado',
            'email' => 'joao@example.com',
            'phone' => '(11) 92222-2222',
        ]), [
            ['product_id' => 1, 'quantity' => 1, 'unit_price' => 25.50],
        ]);

        $this->assertTrue($result['success']);
        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM commercial_clients')->fetchColumn();
        $client = $this->pdo->query('SELECT * FROM commercial_clients WHERE id = 9')->fetch(PDO::FETCH_ASSOC);

        $this->assertSame(2, $count);
        $this->assertSame('Cliente Antigo', $client['name']);
        $this->assertSame('(11) 91111-1111', $client['phone']);
        $this->assertSame('joao@example.com', $client['email']);
    }

    public function testCreatePublicDraftBlocksOwnershipMismatchWhenStrict(): void
    {
        $this->pdo->exec(
            "INSERT INTO commercial_clients (id, name, document, email, phone, status)
             VALUES (9, 'Vitima', '529.982.247-25', 'vitima@example.com', '(11) 91111-1111', 'active')"
        );

        $result = $this->service->createPublicDraft($this->checkoutCustomer([
            'name' => 'Atacante',
            'document' => '529.982.247-25',
            'email' => 'atacante@example.com',
            'phone' => '(11) 99999-9999',
        ]), [
            ['product_id' => 1, 'quantity' => 1, 'unit_price' => 25.50],
        ], [
            'strictOwnership' => true,
        ]);

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['errors']);
        $this->assertSame([], $result['field_errors']);
        $this->assertSame('Atacante', $result['data']['name']);
        $this->assertSame('atacante@example.com', $result['data']['email']);
        $this->assertSame('(11) 99999-9999', $result['data']['phone']);

        $client = $this->pdo->query('SELECT * FROM commercial_clients WHERE id = 9')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('Vitima', $client['name']);
        $this->assertSame('vitima@example.com', $client['email']);
        $this->assertSame('(11) 91111-1111', $client['phone']);

        $orderCount = (int) $this->pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
        $this->assertSame(0, $orderCount);
    }

    public function testGetPublicReceiptReturnsOnlineOrderOnly(): void
    {
        $draft = $this->service->createPublicDraft($this->checkoutCustomer([
            'name' => 'Cliente Online',
            'phone' => '(11) 97777-0000',
        ]), [
            ['product_id' => 1, 'quantity' => 1, 'unit_price' => 25.50],
        ]);

        $receipt = $this->service->getPublicReceipt((int) $draft['id']);
        $this->assertNotNull($receipt);
        $this->assertSame((int) $draft['id'], (int) $receipt['order']['id']);

        $adminOrder = $this->service->create([
            'commercial_client_id' => 1,
            'product_id' => 1,
            'quantity' => 1,
            'status' => 'draft',
            'notes' => 'Pedido interno',
        ]);

        $this->assertNull($this->service->getPublicReceipt((int) $adminOrder['id']));
    }

    public function testGetDetailForAdminReturnsItemsAndOrigin(): void
    {
        $draft = $this->service->createPublicDraft($this->checkoutCustomer([
            'name' => 'Cliente Online',
            'phone' => '(11) 97777-0000',
        ]), [
            ['product_id' => 1, 'quantity' => 1, 'unit_price' => 25.50],
        ]);

        $detail = $this->service->getDetailForAdmin((int) $draft['id']);

        $this->assertNotNull($detail);
        $this->assertSame('online', $detail['origin']);
        $this->assertSame('Site público', $detail['origin_label']);
        $this->assertCount(1, $detail['items']);
        $this->assertSame('Cliente Online', $detail['order']['client_name']);
    }

    public function testResolveOriginUsesInternalNotesForNewOrders(): void
    {
        $order = [
            'notes' => null,
            'internal_notes' => OrderService::ONLINE_ORDER_NOTE_PREFIX,
        ];

        $this->assertSame('online', OrderService::resolveOrigin($order));
    }

    public function testCreatePublicDraftRejectsBuildingWithoutComplement(): void
    {
        $result = $this->service->createPublicDraft($this->checkoutCustomer([
            'residence_type' => 'building',
            'shipping_complement' => '',
        ]), [
            ['product_id' => 1, 'quantity' => 1, 'unit_price' => 25.50],
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('shipping_complement', $result['field_errors']);
    }

    public function testCreatePublicDraftRejectsInvalidDocumentChecksum(): void
    {
        $result = $this->service->createPublicDraft($this->checkoutCustomer([
            'document' => '123.456.789-00',
        ]), [
            ['product_id' => 1, 'quantity' => 1, 'unit_price' => 25.50],
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('document', $result['field_errors']);
    }

    public function testCreatePublicDraftRejectsMismatchedCityForCep(): void
    {
        $result = $this->service->createPublicDraft($this->checkoutCustomer([
            'shipping_postal_code' => '01310-100',
            'shipping_city' => 'Cidade Inventada',
        ]), [
            ['product_id' => 1, 'quantity' => 1, 'unit_price' => 25.50],
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('shipping_city', $result['field_errors']);
    }

    public function testCreatePublicDraftRejectsInvalidCepEvenWhenClientClaimsValidated(): void
    {
        $result = $this->service->createPublicDraft($this->checkoutCustomer([
            'shipping_postal_code' => '00000-000',
            'shipping_city' => 'Cidade Inventada',
            'shipping_street' => 'Rua Qualquer',
            'shipping_neighborhood' => 'Centro',
            'cep_validated' => '1',
        ]), [
            ['product_id' => 1, 'quantity' => 1, 'unit_price' => 25.50],
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('shipping_postal_code', $result['field_errors']);
    }

    public function testCreatePublicDraftAllowsCheckoutWhenCepLookupUnavailable(): void
    {
        $service = new OrderService(
            $this->pdo,
            new OrderRepository($this->pdo),
            new OrderItemRepository($this->pdo),
            new CommercialClientRepository($this->pdo),
            $this->productRepository,
            new StockService($this->pdo, $this->productRepository, new StockMovementRepository($this->pdo)),
            new AuditService(new AuditLogRepository($this->pdo)),
            cepLookupService: new UnavailableCepLookupService()
        );

        $result = $service->createPublicDraft($this->checkoutCustomer([
            'cep_validated' => '1',
        ]), [
            ['product_id' => 1, 'quantity' => 1, 'unit_price' => 25.50],
        ]);

        $this->assertTrue($result['success']);

        $orderId = (int) $result['id'];
        $order = $this->pdo->query('SELECT internal_notes FROM orders WHERE id = ' . $orderId)->fetch(PDO::FETCH_ASSOC);
        $this->assertStringContainsString('CEP não validado no servidor', (string) $order['internal_notes']);

        $audit = $this->pdo->query(
            "SELECT action FROM audit_logs WHERE entity = 'order' AND entity_id = {$orderId}"
        )->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('checkout_cep_lookup_unavailable', $audit['action']);
    }

    public function testCreatePublicDraftRejectsMissingShippingFields(): void
    {
        $result = $this->service->createPublicDraft([
            'name' => 'Sem Endereco',
            'document' => '987.654.321-00',
            'email' => 'sem@example.com',
            'phone' => '(11) 90000-1111',
            'residence_type' => 'house',
        ], [
            ['product_id' => 1, 'quantity' => 1, 'unit_price' => 25.50],
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('shipping_postal_code', $result['field_errors']);
    }

    public function testCreatePublicDraftAcceptsPickupWithoutAddress(): void
    {
        $result = $this->service->createPublicDraft([
            'name' => 'Retirada Loja',
            'document' => '987.654.321-00',
            'email' => 'retirada@example.com',
            'phone' => '(11) 90000-1111',
            'delivery_method' => 'pickup',
            'notes' => '',
        ], [
            ['product_id' => 1, 'quantity' => 1, 'unit_price' => 25.50],
        ]);

        $this->assertTrue($result['success']);
        $order = $this->pdo->query('SELECT delivery_method, shipping_city FROM orders WHERE id = ' . (int) $result['id'])->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('pickup', $order['delivery_method']);
        $this->assertNull($order['shipping_city']);
    }

    public function testReopenCancelledOrderSetsDraftStatus(): void
    {
        $created = $this->service->create([
            'commercial_client_id' => 1,
            'product_id' => 1,
            'quantity' => 1,
            'status' => 'draft',
            'notes' => '',
        ]);
        $orderId = (int) $created['id'];
        $this->service->cancel($orderId);

        $reopen = $this->service->reopenCancelled($orderId);
        $order = $this->pdo->query('SELECT status FROM orders WHERE id = ' . $orderId)->fetch(PDO::FETCH_ASSOC);

        $this->assertTrue($reopen['success']);
        $this->assertSame('draft', $order['status']);
    }

    public function testAddAndRemoveDraftItemRecalculatesTotal(): void
    {
        $created = $this->service->create([
            'commercial_client_id' => 1,
            'product_id' => 1,
            'quantity' => 1,
            'status' => 'draft',
            'notes' => '',
        ]);
        $orderId = (int) $created['id'];

        $add = $this->service->addDraftItem($orderId, ['product_id' => 1, 'quantity' => 2]);
        $this->assertTrue($add['success']);

        $items = $this->pdo->query('SELECT id FROM order_items WHERE order_id = ' . $orderId)->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(2, $items);

        $order = $this->pdo->query('SELECT total_amount FROM orders WHERE id = ' . $orderId)->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(76.5, (float) $order['total_amount']);

        $remove = $this->service->removeDraftItem($orderId, (int) $items[0]['id']);
        $this->assertTrue($remove['success']);

        $orderAfter = $this->pdo->query('SELECT total_amount FROM orders WHERE id = ' . $orderId)->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(51.0, (float) $orderAfter['total_amount']);
    }

    public function testUpdateDraftShippingPersistsFields(): void
    {
        $draft = $this->service->createPublicDraft($this->checkoutCustomer(), [
            ['product_id' => 1, 'quantity' => 1, 'unit_price' => 25.50],
        ]);
        $orderId = (int) $draft['id'];

        $result = $this->service->updateDraftShipping($orderId, [
            'shipping_state' => 'RJ',
            'shipping_postal_code' => '20040-020',
            'shipping_street' => 'Rua Nova',
            'shipping_number' => '50',
            'shipping_neighborhood' => 'Centro',
            'shipping_city' => 'Rio de Janeiro',
        ]);

        $this->assertTrue($result['success']);
        $order = $this->pdo->query('SELECT shipping_city, shipping_state FROM orders WHERE id = ' . $orderId)->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('Rio de Janeiro', $order['shipping_city']);
        $this->assertSame('RJ', $order['shipping_state']);
    }

    public function testCreatePublicDraftReservesStockUntilCancel(): void
    {
        $draft = $this->service->createPublicDraft($this->checkoutCustomer(), [
            ['product_id' => 1, 'quantity' => 4, 'unit_price' => 25.50],
        ]);

        $this->assertTrue($draft['success']);

        $reserved = (int) $this->pdo->query("SELECT SUM(quantity) FROM stock_reservations WHERE status = 'active'")->fetchColumn();
        $this->assertSame(4, $reserved);

        $second = $this->service->createPublicDraft($this->checkoutCustomer([
            'name' => 'Outro Cliente',
            'email' => 'outro@example.com',
            'phone' => '(11) 98888-1111',
        ]), [
            ['product_id' => 1, 'quantity' => 7, 'unit_price' => 25.50],
        ]);

        $this->assertFalse($second['success']);

        $cancel = $this->service->cancel((int) $draft['id']);
        $this->assertTrue($cancel['success']);

        $activeAfterCancel = (int) $this->pdo->query("SELECT COUNT(*) FROM stock_reservations WHERE status = 'active'")->fetchColumn();
        $this->assertSame(0, $activeAfterCancel);
    }

    public function testCreatePublicDraftAddsZoneShippingFeeToTotal(): void
    {
        $this->pdo->exec("INSERT INTO delivery_zones (name, city, state, neighborhoods, fee, status) VALUES ('Centro SP', 'São Paulo', 'SP', 'Bela Vista', 15.00, 'active')");

        $result = $this->service->createPublicDraft($this->checkoutCustomer(), [
            ['product_id' => 1, 'quantity' => 2, 'unit_price' => 25.50, 'name' => 'Cimento'],
        ], ['orderReferencePrefix' => 'TST']);

        $this->assertTrue($result['success'], implode(' ', $result['errors']));
        $order = $this->pdo->query('SELECT * FROM orders WHERE id = ' . (int) $result['id'])->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(15.0, (float) $order['shipping_fee']);
        $this->assertSame(66.0, (float) $order['total_amount']);
        $this->assertSame('Centro SP', $order['delivery_zone_name']);
        $this->assertStringNotContainsString('Frete a combinar', (string) $order['internal_notes']);
    }

    public function testCreatePublicDraftRejectsAddressOutsideDeliveryZones(): void
    {
        $this->pdo->exec("INSERT INTO delivery_zones (name, city, state, fee, status) VALUES ('Rincão', 'Rincão', 'SP', 10.00, 'active')");

        $result = $this->service->createPublicDraft($this->checkoutCustomer(), [
            ['product_id' => 1, 'quantity' => 1, 'unit_price' => 25.50, 'name' => 'Cimento'],
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('shipping', $result['field_errors']);
        $this->assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn());
    }

    public function testCreatePublicDraftPickupIgnoresZonesAndHasNoFee(): void
    {
        $this->pdo->exec("INSERT INTO delivery_zones (name, city, state, fee, status) VALUES ('Rincão', 'Rincão', 'SP', 10.00, 'active')");

        $result = $this->service->createPublicDraft($this->checkoutCustomer(['delivery_method' => 'pickup']), [
            ['product_id' => 1, 'quantity' => 1, 'unit_price' => 25.50, 'name' => 'Cimento'],
        ]);

        $this->assertTrue($result['success'], implode(' ', $result['errors']));
        $order = $this->pdo->query('SELECT * FROM orders WHERE id = ' . (int) $result['id'])->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(0.0, (float) $order['shipping_fee']);
        $this->assertSame(25.5, (float) $order['total_amount']);
    }

    public function testCreatePublicDraftFallsBackToPixWhenCardUnavailable(): void
    {
        $result = $this->service->createPublicDraft($this->checkoutCustomer([
            'payment_method' => 'credit_card',
            'installments' => '6',
        ]), [
            ['product_id' => 1, 'quantity' => 1, 'unit_price' => 25.50, 'name' => 'Cimento'],
        ]);

        $this->assertTrue($result['success']);
        $order = $this->pdo->query('SELECT * FROM orders WHERE id = ' . (int) $result['id'])->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('pix', $order['payment_method']);
        $this->assertSame(1, (int) $order['installments']);
    }

    /**
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    private function checkoutCustomer(array $override = []): array
    {
        return array_merge([
            'name' => 'João Silva',
            'document' => '111.444.777-35',
            'email' => 'joao@example.com',
            'phone' => '(11) 99999-0000',
            'shipping_state' => 'SP',
            'shipping_postal_code' => '01310-100',
            'shipping_street' => 'Avenida Paulista',
            'shipping_number' => '100',
            'shipping_neighborhood' => 'Bela Vista',
            'shipping_city' => 'São Paulo',
            'residence_type' => 'house',
            'notes' => '',
        ], $override);
    }
}
