<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Shared\Support\BrazilianAddress;

final class BrazilianAddressTest extends TestCase
{
    public function testNormalizeAndFormatPostalCode(): void
    {
        $this->assertSame('01310100', BrazilianAddress::normalizePostalCode('01310-100'));
        $this->assertSame('01310-100', BrazilianAddress::formatPostalCode('01310100'));
    }

    public function testValidateCheckoutAddressRequiresFields(): void
    {
        [$errors, $fieldErrors] = BrazilianAddress::validateCheckoutAddress([
            'shipping_state' => '',
            'shipping_postal_code' => '123',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('shipping_postal_code', $fieldErrors);
        $this->assertArrayHasKey('shipping_street', $fieldErrors);
    }

    public function testParseCheckoutPostBuildsFormattedAddress(): void
    {
        $parsed = BrazilianAddress::parseCheckoutPost([
            'residence_type' => 'building',
            'shipping_state' => 'sp',
            'shipping_postal_code' => '01310100',
            'shipping_street' => 'Avenida Paulista',
            'shipping_number' => '1000',
            'shipping_complement' => 'Sala 1',
            'shipping_neighborhood' => 'Bela Vista',
            'shipping_city' => 'São Paulo',
            'address_reference' => 'Próximo ao MASP',
        ]);

        $this->assertSame('SP', $parsed['shipping_state']);
        $this->assertSame('building', $parsed['residence_type']);
        $this->assertSame('01310-100', $parsed['shipping_postal_code']);
        $this->assertStringContainsString('Prédio:', (string) $parsed['address']);
        $this->assertStringContainsString('Avenida Paulista', (string) $parsed['address']);
    }

    public function testBuildingRequiresMeaningfulComplement(): void
    {
        [$errors, $fieldErrors] = BrazilianAddress::validateCheckoutAddress([
            'residence_type' => BrazilianAddress::RESIDENCE_BUILDING,
            'shipping_state' => 'SP',
            'shipping_postal_code' => '01310-100',
            'shipping_street' => 'Av Paulista',
            'shipping_number' => '100',
            'shipping_neighborhood' => 'Bela Vista',
            'shipping_city' => 'São Paulo',
            'shipping_complement' => '.',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('shipping_complement', $fieldErrors);
    }

    public function testHouseAllowsEmptyComplement(): void
    {
        [$errors, $fieldErrors] = BrazilianAddress::validateCheckoutAddress([
            'residence_type' => BrazilianAddress::RESIDENCE_HOUSE,
            'shipping_state' => 'SP',
            'shipping_postal_code' => '14055-560',
            'shipping_street' => 'Rua Exemplo',
            'shipping_number' => '10',
            'shipping_neighborhood' => 'Centro',
            'shipping_city' => 'Ribeirão Preto',
            'shipping_complement' => '',
        ]);

        $this->assertEmpty($errors);
        $this->assertArrayNotHasKey('shipping_complement', $fieldErrors);
    }

    public function testIsMeaningfulComplementAcceptsApartmentDescription(): void
    {
        $this->assertTrue(BrazilianAddress::isMeaningfulComplement('Apto 12'));
        $this->assertFalse(BrazilianAddress::isMeaningfulComplement('x'));
    }
}
