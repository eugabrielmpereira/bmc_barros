<?php

declare(strict_types=1);

namespace Tests\Support;

use Erp\Commerce\Services\CepLookupService;
use Shared\Support\BrazilianAddress;

/**
 * Retorno fixo de CEP para testes unitários sem rede.
 */
final class FakeCepLookupService extends CepLookupService
{
    public function lookup(string $postalCode): array
    {
        $digits = BrazilianAddress::normalizePostalCode($postalCode);
        if (strlen($digits) !== 8) {
            return [
                'success' => false,
                'error' => 'CEP deve conter 8 dígitos.',
            ];
        }

        if ($digits === '00000000') {
            return [
                'success' => false,
                'error' => 'CEP não encontrado.',
            ];
        }

        return [
            'success' => true,
            'data' => [
                'cep' => BrazilianAddress::formatPostalCode($digits),
                'logradouro' => 'Avenida Paulista',
                'bairro' => 'Bela Vista',
                'localidade' => 'São Paulo',
                'uf' => 'SP',
            ],
        ];
    }
}
