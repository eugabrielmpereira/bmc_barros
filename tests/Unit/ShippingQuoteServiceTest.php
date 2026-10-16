<?php

declare(strict_types=1);

namespace Tests\Unit;

use Erp\Shipping\Repositories\DeliveryZoneRepository;
use Erp\Shipping\Services\DeliveryZoneService;
use Erp\Shipping\Services\ShippingQuoteService;
use PDO;
use PHPUnit\Framework\TestCase;

final class ShippingQuoteServiceTest extends TestCase
{
    private PDO $pdo;
    private ShippingQuoteService $service;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->pdo->exec(
            "CREATE TABLE delivery_zones (
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
                status TEXT NOT NULL DEFAULT 'active'
            )"
        );
        $this->service = new ShippingQuoteService(new DeliveryZoneRepository($this->pdo));
    }

    private function zone(array $fields): void
    {
        $fields += ['state' => 'SP', 'status' => 'active', 'fee' => 0];
        $columns = implode(', ', array_keys($fields));
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));
        $this->pdo->prepare("INSERT INTO delivery_zones ({$columns}) VALUES ({$placeholders})")->execute(array_values($fields));
    }

    private function address(string $neighborhood, string $city = 'Rincão', string $cep = '14830-000'): array
    {
        return [
            'shipping_postal_code' => $cep,
            'shipping_neighborhood' => $neighborhood,
            'shipping_city' => $city,
            'shipping_state' => 'SP',
        ];
    }

    public function testWithoutZonesShippingIsToArrange(): void
    {
        $quote = $this->service->quote('delivery', $this->address('Centro'), 100);

        $this->assertTrue($quote['available']);
        $this->assertSame(ShippingQuoteService::MODE_TO_ARRANGE, $quote['mode']);
        $this->assertSame(0.0, $quote['fee']);
    }

    public function testPickupIsAlwaysFree(): void
    {
        $this->zone(['name' => 'Rincão', 'city' => 'Rincão', 'fee' => 20]);

        $quote = $this->service->quote('pickup', [], 10);

        $this->assertTrue($quote['available']);
        $this->assertSame(ShippingQuoteService::MODE_PICKUP, $quote['mode']);
        $this->assertSame(0.0, $quote['fee']);
    }

    public function testNeighborhoodZoneWinsOverCityWideZone(): void
    {
        $this->zone(['name' => 'Rincão (cidade)', 'city' => 'Rincão', 'fee' => 25, 'sort_order' => 0]);
        $this->zone(['name' => 'Centro', 'city' => 'Rincão', 'neighborhoods' => "Centro\nJardim Primavera", 'fee' => 10, 'sort_order' => 5]);

        $quote = $this->service->quote('delivery', $this->address('Jd. Primavera'), 80);

        $this->assertTrue($quote['available']);
        $this->assertSame('Centro', $quote['zone_name']);
        $this->assertSame(10.0, $quote['fee']);

        $other = $this->service->quote('delivery', $this->address('Vila Nova'), 80);
        $this->assertSame('Rincão (cidade)', $other['zone_name']);
        $this->assertSame(25.0, $other['fee']);
    }

    public function testCityMatchIgnoresAccentsAndCase(): void
    {
        $this->zone(['name' => 'Taquaral', 'city' => 'TAQUARAL', 'fee' => 30]);

        $quote = $this->service->quote('delivery', $this->address('Sítio', 'taquaral'), 50);

        $this->assertTrue($quote['available']);
        $this->assertSame(30.0, $quote['fee']);
    }

    public function testPostalCodeRangeZone(): void
    {
        $this->zone(['name' => 'Faixa', 'city' => 'Araraquara', 'postal_code_start' => '14800000', 'postal_code_end' => '14809999', 'fee' => 40]);

        $inside = $this->service->quote('delivery', $this->address('Qualquer', 'Araraquara', '14801-100'), 50);
        $outside = $this->service->quote('delivery', $this->address('Qualquer', 'Araraquara', '14815-000'), 50);

        $this->assertTrue($inside['available']);
        $this->assertSame(40.0, $inside['fee']);
        $this->assertFalse($outside['available']);
        $this->assertSame(ShippingQuoteService::ERROR_NOT_COVERED, $outside['error']);
    }

    public function testFreeShippingAboveThreshold(): void
    {
        $this->zone(['name' => 'Rincão', 'city' => 'Rincão', 'fee' => 20, 'free_shipping_min' => 300]);

        $below = $this->service->quote('delivery', $this->address('Centro'), 250);
        $above = $this->service->quote('delivery', $this->address('Centro'), 300);

        $this->assertSame(20.0, $below['fee']);
        $this->assertSame(50.0, $below['missing_for_free_shipping']);
        $this->assertSame(0.0, $above['fee']);
        $this->assertTrue($above['free_shipping']);
    }

    public function testMinimumOrderBlocksDelivery(): void
    {
        $this->zone(['name' => 'Taquaral', 'city' => 'Taquaral', 'fee' => 30, 'min_order_amount' => 150]);

        $quote = $this->service->quote('delivery', $this->address('Centro', 'Taquaral'), 100);

        $this->assertFalse($quote['available']);
        $this->assertSame(ShippingQuoteService::ERROR_MIN_ORDER, $quote['error']);
        $this->assertStringContainsString('R$ 50,00', $quote['message']);
    }

    public function testInactiveZonesAreIgnored(): void
    {
        $this->zone(['name' => 'Rincão', 'city' => 'Rincão', 'fee' => 20, 'status' => 'inactive']);

        $quote = $this->service->quote('delivery', $this->address('Centro'), 100);

        $this->assertSame(ShippingQuoteService::MODE_TO_ARRANGE, $quote['mode']);
    }

    public function testIncompleteAddressAsksForCity(): void
    {
        $this->zone(['name' => 'Rincão', 'city' => 'Rincão', 'fee' => 20]);

        $quote = $this->service->quote('delivery', ['shipping_neighborhood' => 'Centro'], 100);

        $this->assertFalse($quote['available']);
        $this->assertSame(ShippingQuoteService::ERROR_INCOMPLETE, $quote['error']);
    }

    public function testZoneServiceValidatesAndNormalizesInput(): void
    {
        $service = new DeliveryZoneService(new DeliveryZoneRepository($this->pdo));

        $invalid = $service->create(['name' => '', 'city' => 'Rincão', 'state' => 'S', 'fee' => 'abc', 'postal_code_start' => '14830000']);
        $this->assertFalse($invalid['success']);
        $this->assertCount(4, $invalid['errors']);

        $valid = $service->create([
            'name' => 'Centro',
            'city' => 'Rincão',
            'state' => 'sp',
            'neighborhoods' => "Centro, Vila Nova;\n\nJardim",
            'fee' => '12,50',
            'free_shipping_min' => '1.000,00',
        ]);
        $this->assertTrue($valid['success']);

        $row = $service->find((int) $valid['id']);
        $this->assertSame('SP', $row['state']);
        $this->assertSame("Centro\nVila Nova\nJardim", $row['neighborhoods']);
        $this->assertSame(12.5, (float) $row['fee']);
        $this->assertSame(1000.0, (float) $row['free_shipping_min']);
    }
}
