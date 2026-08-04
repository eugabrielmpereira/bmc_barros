<?php

declare(strict_types=1);

namespace Tests\Unit;

use Erp\Commerce\Services\CepLookupService;
use PHPUnit\Framework\TestCase;

final class CepLookupServiceTest extends TestCase
{
    private CepLookupService $service;

    protected function setUp(): void
    {
        $this->service = new CepLookupService();
    }

    public function testLookupRejectsInvalidPostalCode(): void
    {
        $result = $this->service->lookup('123');

        $this->assertFalse($result['success']);
        $this->assertSame('CEP deve conter 8 dígitos.', $result['error']);
    }

    public function testLookupRejectsNonExistentCep(): void
    {
        $result = $this->service->lookup('99999999');

        $this->assertFalse($result['success']);
        $this->assertContains(
            $result['error'] ?? '',
            [
                'CEP não encontrado.',
                'Não foi possível consultar o CEP. Tente novamente ou preencha manualmente.',
            ]
        );
    }
}
