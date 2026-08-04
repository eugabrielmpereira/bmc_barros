<?php

declare(strict_types=1);

namespace Erp\Commerce\Services;

use Shared\Support\BrazilianAddress;

/**
 * Consulta CEP no ViaCEP pelo servidor (evita bloqueio de CSP no navegador).
 */
class CepLookupService
{
    private const LOOKUP_URL = 'https://viacep.com.br/ws/%s/json/';

    private const TIMEOUT_SECONDS = 5;

    /**
     * @return array{success: bool, data?: array<string, string>, error?: string}
     */
    public function lookup(string $postalCode): array
    {
        $digits = BrazilianAddress::normalizePostalCode($postalCode);
        if (strlen($digits) !== 8) {
            return [
                'success' => false,
                'error' => 'CEP deve conter 8 dígitos.',
            ];
        }

        $payload = $this->fetchFromViaCep($digits);
        if ($payload === null) {
            return [
                'success' => false,
                'error' => 'Não foi possível consultar o CEP. Tente novamente ou preencha manualmente.',
            ];
        }

        if (filter_var($payload['erro'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return [
                'success' => false,
                'error' => 'CEP não encontrado.',
            ];
        }

        return [
            'success' => true,
            'data' => [
                'cep' => BrazilianAddress::formatPostalCode($digits),
                'logradouro' => trim((string) ($payload['logradouro'] ?? '')),
                'bairro' => trim((string) ($payload['bairro'] ?? '')),
                'localidade' => trim((string) ($payload['localidade'] ?? '')),
                'uf' => strtoupper(trim((string) ($payload['uf'] ?? ''))),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchFromViaCep(string $digits): ?array
    {
        $url = sprintf(self::LOOKUP_URL, $digits);
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => self::TIMEOUT_SECONDS,
                'header' => "Accept: application/json\r\nUser-Agent: ErpCommerce/1.0\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }
}
