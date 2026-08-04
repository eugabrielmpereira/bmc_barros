<?php

declare(strict_types=1);

namespace Tests\Support;

use Erp\Commerce\Services\CepLookupService;

/**
 * Simula indisponibilidade do ViaCEP para testes de fallback no checkout.
 */
final class UnavailableCepLookupService extends CepLookupService
{
    public function lookup(string $postalCode): array
    {
        return [
            'success' => false,
            'error' => 'Não foi possível consultar o CEP. Tente novamente ou preencha manualmente.',
        ];
    }
}
