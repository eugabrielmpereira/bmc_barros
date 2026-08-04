<?php

declare(strict_types=1);

namespace Shared\Support;

/**
 * Validação e formatação de endereço brasileiro (checkout público).
 */
final class BrazilianAddress
{
    public const RESIDENCE_HOUSE = 'house';

    public const RESIDENCE_BUILDING = 'building';

    /** @var list<string> */
    private const JUNK_COMPLEMENT_VALUES = [
        '.', '..', '...', '-', '--', 'x', 'xx', 'xxx',
        'na', 'n/a', 'n.a', 'não tem', 'nao tem', 'sem', 'nenhum', 'nada',
        'ok', 'sim', 'nao', 'não', 'teste', 'test', 'asdf', 'qwerty', 'abc',
        'ap', 'apt', 'apto', 'bl', 'sl',
    ];

    /** @var array<string, string> */
    public const STATES = [
        'AC' => 'Acre',
        'AL' => 'Alagoas',
        'AP' => 'Amapá',
        'AM' => 'Amazonas',
        'BA' => 'Bahia',
        'CE' => 'Ceará',
        'DF' => 'Distrito Federal',
        'ES' => 'Espírito Santo',
        'GO' => 'Goiás',
        'MA' => 'Maranhão',
        'MT' => 'Mato Grosso',
        'MS' => 'Mato Grosso do Sul',
        'MG' => 'Minas Gerais',
        'PA' => 'Pará',
        'PB' => 'Paraíba',
        'PR' => 'Paraná',
        'PE' => 'Pernambuco',
        'PI' => 'Piauí',
        'RJ' => 'Rio de Janeiro',
        'RN' => 'Rio Grande do Norte',
        'RS' => 'Rio Grande do Sul',
        'RO' => 'Rondônia',
        'RR' => 'Roraima',
        'SC' => 'Santa Catarina',
        'SP' => 'São Paulo',
        'SE' => 'Sergipe',
        'TO' => 'Tocantins',
    ];

    public static function normalizePostalCode(string $postalCode): string
    {
        return preg_replace('/\D+/', '', trim($postalCode)) ?? '';
    }

    public static function formatPostalCode(string $postalCode): string
    {
        $digits = self::normalizePostalCode($postalCode);
        if (strlen($digits) !== 8) {
            return $digits;
        }

        return substr($digits, 0, 5) . '-' . substr($digits, 5);
    }

    public static function isValidPostalCode(string $postalCode): bool
    {
        return strlen(self::normalizePostalCode($postalCode)) === 8;
    }

    public static function isValidState(string $state): bool
    {
        $uf = strtoupper(trim($state));

        return $uf !== '' && isset(self::STATES[$uf]);
    }

    /**
     * Extrai campos de endereço do POST do checkout.
     *
     * @return array<string, mixed>
     */
    public static function parseCheckoutPost(array $post): array
    {
        $postalDigits = self::normalizePostalCode((string) ($post['shipping_postal_code'] ?? ''));
        $complement = trim((string) ($post['shipping_complement'] ?? ''));
        $reference = trim((string) ($post['address_reference'] ?? ''));

        $residenceType = self::normalizeResidenceType((string) ($post['residence_type'] ?? ''));

        $shipping = [
            'residence_type' => $residenceType,
            'shipping_state' => strtoupper(trim((string) ($post['shipping_state'] ?? ''))),
            'shipping_postal_code' => $postalDigits !== '' ? self::formatPostalCode($postalDigits) : '',
            'shipping_street' => trim((string) ($post['shipping_street'] ?? '')),
            'shipping_number' => trim((string) ($post['shipping_number'] ?? '')),
            'shipping_complement' => $complement !== '' ? $complement : null,
            'shipping_neighborhood' => trim((string) ($post['shipping_neighborhood'] ?? '')),
            'shipping_city' => trim((string) ($post['shipping_city'] ?? '')),
            'address_reference' => $reference !== '' ? $reference : null,
        ];

        $shippingForLine = $shipping;
        $shippingForLine['shipping_complement'] = self::mergeComplementAndReference($complement, $reference);

        return array_merge($shipping, [
            'address' => self::formatSingleLine($shippingForLine),
            'postal_code' => $shipping['shipping_postal_code'],
            'street' => $shipping['shipping_street'],
            'street_number' => $shipping['shipping_number'],
            'complement' => $shipping['shipping_complement'],
            'neighborhood' => $shipping['shipping_neighborhood'],
            'city' => $shipping['shipping_city'],
            'state' => $shipping['shipping_state'],
        ]);
    }

    /**
     * @param array<string, mixed> $address
     * @return array{0: list<string>, 1: array<string, string>}
     */
    public static function validateCheckoutAddress(array $address): array
    {
        $errors = [];
        $fieldErrors = [];

        $residenceType = (string) ($address['residence_type'] ?? '');
        if ($residenceType === '') {
            $message = 'Selecione se o imóvel é casa ou prédio.';
            $errors[] = $message;
            $fieldErrors['residence_type'] = $message;
        } elseif (!in_array($residenceType, [self::RESIDENCE_HOUSE, self::RESIDENCE_BUILDING], true)) {
            $message = 'Tipo de imóvel inválido.';
            $errors[] = $message;
            $fieldErrors['residence_type'] = $message;
        }

        $complement = trim((string) ($address['shipping_complement'] ?? ''));
        if ($residenceType === self::RESIDENCE_BUILDING) {
            if ($complement === '') {
                $message = 'Em prédio, informe apartamento, bloco, sala ou similar.';
                $errors[] = $message;
                $fieldErrors['shipping_complement'] = $message;
            } elseif (!self::isMeaningfulComplement($complement)) {
                $message = 'Complemento inválido. Ex.: Apto 12, Bloco B, Sala 3.';
                $errors[] = $message;
                $fieldErrors['shipping_complement'] = $message;
            }
        } elseif ($complement !== '' && !self::isMeaningfulComplement($complement)) {
            $message = 'Complemento inválido. Use descrição real ou deixe em branco.';
            $errors[] = $message;
            $fieldErrors['shipping_complement'] = $message;
        }

        $required = [
            'shipping_state' => 'UF é obrigatória.',
            'shipping_postal_code' => 'CEP é obrigatório.',
            'shipping_street' => 'Logradouro é obrigatório.',
            'shipping_number' => 'Número é obrigatório.',
            'shipping_neighborhood' => 'Bairro é obrigatório.',
            'shipping_city' => 'Cidade é obrigatória.',
        ];

        foreach ($required as $field => $message) {
            $value = trim((string) ($address[$field] ?? ''));
            if ($value === '') {
                $errors[] = $message;
                $fieldErrors[$field] = $message;
            }
        }

        $postal = self::normalizePostalCode((string) ($address['shipping_postal_code'] ?? ''));
        if ($postal !== '' && strlen($postal) !== 8) {
            $message = 'CEP deve conter 8 dígitos.';
            $errors[] = $message;
            $fieldErrors['shipping_postal_code'] = $message;
        }

        $uf = strtoupper(trim((string) ($address['shipping_state'] ?? '')));
        if ($uf !== '' && !self::isValidState($uf)) {
            $message = 'UF inválida.';
            $errors[] = $message;
            $fieldErrors['shipping_state'] = $message;
        }

        return [$errors, $fieldErrors];
    }

    /**
     * Extrai campos de endereço do POST do admin (edição de pedido).
     *
     * @return array<string, mixed>
     */
    public static function parseAdminOrderPost(array $post): array
    {
        $postalDigits = self::normalizePostalCode((string) ($post['shipping_postal_code'] ?? ''));
        $complement = trim((string) ($post['shipping_complement'] ?? ''));

        return [
            'shipping_state' => strtoupper(trim((string) ($post['shipping_state'] ?? ''))),
            'shipping_postal_code' => $postalDigits !== '' ? self::formatPostalCode($postalDigits) : '',
            'shipping_street' => trim((string) ($post['shipping_street'] ?? '')),
            'shipping_number' => trim((string) ($post['shipping_number'] ?? '')),
            'shipping_complement' => $complement !== '' ? $complement : null,
            'shipping_neighborhood' => trim((string) ($post['shipping_neighborhood'] ?? '')),
            'shipping_city' => trim((string) ($post['shipping_city'] ?? '')),
        ];
    }

    /**
     * @param array<string, mixed> $address
     * @return array{0: list<string>, 1: array<string, string>}
     */
    public static function validateAdminShippingAddress(array $address): array
    {
        $errors = [];
        $fieldErrors = [];

        $required = [
            'shipping_state' => 'UF é obrigatória.',
            'shipping_postal_code' => 'CEP é obrigatório.',
            'shipping_street' => 'Logradouro é obrigatório.',
            'shipping_number' => 'Número é obrigatório.',
            'shipping_neighborhood' => 'Bairro é obrigatório.',
            'shipping_city' => 'Cidade é obrigatória.',
        ];

        foreach ($required as $field => $message) {
            $value = trim((string) ($address[$field] ?? ''));
            if ($value === '') {
                $errors[] = $message;
                $fieldErrors[$field] = $message;
            }
        }

        $postal = self::normalizePostalCode((string) ($address['shipping_postal_code'] ?? ''));
        if ($postal !== '' && strlen($postal) !== 8) {
            $message = 'CEP deve conter 8 dígitos.';
            $errors[] = $message;
            $fieldErrors['shipping_postal_code'] = $message;
        }

        $uf = strtoupper(trim((string) ($address['shipping_state'] ?? '')));
        if ($uf !== '' && !self::isValidState($uf)) {
            $message = 'UF inválida.';
            $errors[] = $message;
            $fieldErrors['shipping_state'] = $message;
        }

        return [$errors, $fieldErrors];
    }

    /**
     * Confere CEP na base dos Correios (ViaCEP) e coerência de UF/cidade com o formulário.
     *
     * @param array<string, mixed> $address
     * @param array{success: bool, data?: array<string, string>, error?: string} $lookupResult
     * @return array{0: list<string>, 1: array<string, string>}
     */
    public static function validateAgainstCepLookup(array $address, array $lookupResult): array
    {
        $errors = [];
        $fieldErrors = [];

        if (!$lookupResult['success']) {
            $message = trim((string) ($lookupResult['error'] ?? 'CEP inválido.'));
            if ($message === '') {
                $message = 'CEP inválido.';
            }

            return [[$message], ['shipping_postal_code' => $message]];
        }

        $lookup = $lookupResult['data'] ?? [];
        $ufLookup = strtoupper(trim((string) ($lookup['uf'] ?? '')));
        $ufForm = strtoupper(trim((string) ($address['shipping_state'] ?? '')));
        if ($ufLookup !== '' && $ufForm !== '' && $ufLookup !== $ufForm) {
            $message = 'UF não confere com o CEP informado.';
            $errors[] = $message;
            $fieldErrors['shipping_state'] = $message;
        }

        $cityLookup = self::normalizeComparable((string) ($lookup['localidade'] ?? ''));
        $cityForm = self::normalizeComparable((string) ($address['shipping_city'] ?? ''));
        if ($cityLookup !== '' && $cityForm !== '' && $cityLookup !== $cityForm) {
            $message = 'Cidade não confere com o CEP informado.';
            $errors[] = $message;
            $fieldErrors['shipping_city'] = $message;
        }

        $districtLookup = self::normalizeComparable((string) ($lookup['bairro'] ?? ''));
        $districtForm = self::normalizeComparable((string) ($address['shipping_neighborhood'] ?? ''));
        if ($districtLookup !== '' && $districtForm !== '' && $districtLookup !== $districtForm) {
            $message = 'Bairro não confere com o CEP informado.';
            $errors[] = $message;
            $fieldErrors['shipping_neighborhood'] = $message;
        }

        $streetLookup = self::normalizeComparable((string) ($lookup['logradouro'] ?? ''));
        $streetForm = self::normalizeComparable((string) ($address['shipping_street'] ?? ''));
        if ($streetLookup !== '' && $streetForm !== '' && !str_starts_with($streetForm, $streetLookup) && !str_starts_with($streetLookup, $streetForm)) {
            $message = 'Logradouro não confere com o CEP informado.';
            $errors[] = $message;
            $fieldErrors['shipping_street'] = $message;
        }

        return [$errors, $fieldErrors];
    }

    public static function normalizeComparable(string $value): string
    {
        $value = self::lowerAscii(trim($value));
        $value = strtr($value, self::accentMap());

        return preg_replace('/[^a-z0-9]/', '', $value) ?? '';
    }

    private static function lowerAscii(string $value): string
    {
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($value, 'UTF-8');
        }

        return strtolower($value);
    }

    /**
     * @return array<string, string>
     */
    private static function accentMap(): array
    {
        return [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
        ];
    }

    /**
     * Monta endereço legível a partir de snapshot do pedido ou dados do checkout.
     *
     * @param array<string, mixed> $row
     */
    public static function formatSingleLine(array $row): string
    {
        $street = trim((string) ($row['shipping_street'] ?? $row['street'] ?? ''));
        $number = trim((string) ($row['shipping_number'] ?? $row['street_number'] ?? ''));
        $complement = trim((string) ($row['shipping_complement'] ?? $row['complement'] ?? ''));
        $neighborhood = trim((string) ($row['shipping_neighborhood'] ?? $row['neighborhood'] ?? ''));
        $city = trim((string) ($row['shipping_city'] ?? $row['city'] ?? ''));
        $state = strtoupper(trim((string) ($row['shipping_state'] ?? $row['state'] ?? '')));
        $postal = trim((string) ($row['shipping_postal_code'] ?? $row['postal_code'] ?? ''));

        if ($street === '' && $city === '' && $postal === '') {
            return trim((string) ($row['address'] ?? ''));
        }

        $line1 = $street;
        if ($number !== '') {
            $line1 .= ($line1 !== '' ? ', ' : '') . $number;
        }
        if ($complement !== '') {
            $line1 .= ($line1 !== '' ? ' — ' : '') . $complement;
        }

        $residenceType = (string) ($row['residence_type'] ?? '');
        if ($residenceType === self::RESIDENCE_BUILDING) {
            $line1 = ($line1 !== '' ? 'Prédio: ' : 'Prédio') . $line1;
        } elseif ($residenceType === self::RESIDENCE_HOUSE && $line1 !== '') {
            $line1 = 'Casa: ' . $line1;
        }

        $parts = array_filter([
            $line1 !== '' ? $line1 : null,
            $neighborhood !== '' ? $neighborhood : null,
            ($city !== '' || $state !== '') ? trim($city . ($city !== '' && $state !== '' ? '/' : '') . $state) : null,
            $postal !== '' ? 'CEP ' . $postal : null,
        ]);

        return implode(' · ', $parts);
    }

    public static function mergeComplementAndReference(string $complement, string $reference): ?string
    {
        $complement = trim($complement);
        $reference = trim($reference);
        if ($reference !== '') {
            $refLine = 'Ref.: ' . $reference;
            $complement = $complement !== '' ? $complement . ' — ' . $refLine : $refLine;
        }

        return $complement !== '' ? $complement : null;
    }

    public static function normalizeResidenceType(string $value): ?string
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'house', 'casa' => self::RESIDENCE_HOUSE,
            'building', 'predio', 'prédio', 'apartamento' => self::RESIDENCE_BUILDING,
            default => $normalized === '' ? null : null,
        };
    }

    public static function isMeaningfulComplement(string $complement): bool
    {
        $trimmed = trim($complement);
        if ($trimmed === '') {
            return false;
        }

        $lower = mb_strtolower($trimmed, 'UTF-8');
        if (mb_strlen($lower, 'UTF-8') < 3) {
            return false;
        }

        if (in_array($lower, self::JUNK_COMPLEMENT_VALUES, true)) {
            return false;
        }

        if (preg_match('/^(.)\1+$/u', $lower) === 1) {
            return false;
        }

        $alphanumeric = preg_replace('/[^a-z0-9]/u', '', $lower) ?? '';
        if (strlen($alphanumeric) < 2) {
            return false;
        }

        if (preg_match('/[a-z]/', $alphanumeric) === 1) {
            return true;
        }

        return preg_match(
            '/(ap|apt|apto|apart|bloco|bl|sala|sl|conj|cj|andar|torre|fundos|portaria|garagem|loja)/u',
            $lower
        ) === 1;
    }
}
