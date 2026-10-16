<?php

declare(strict_types=1);

namespace Erp\Shipping\Services;

use Erp\Fulfillment\Support\DeliveryMethod;
use Erp\Shipping\Repositories\DeliveryZoneRepository;
use Shared\Support\BrazilianAddress;

/**
 * Cotação de frete por zona de entrega própria da loja (bairro → faixa de CEP → cidade inteira).
 *
 * O valor é SEMPRE recalculado no servidor no momento do pedido; a cotação exibida no checkout
 * é apenas informativa.
 */
final class ShippingQuoteService
{
    public const MODE_ZONE = 'zone';
    public const MODE_PICKUP = 'pickup';
    public const MODE_TO_ARRANGE = 'to_arrange';

    public const ERROR_NOT_COVERED = 'not_covered';
    public const ERROR_MIN_ORDER = 'min_order';
    public const ERROR_INCOMPLETE = 'incomplete_address';

    private const SCORE_NEIGHBORHOOD = 3;
    private const SCORE_POSTAL_RANGE = 2;
    private const SCORE_CITY = 1;

    /** Abreviações comuns em nomes de bairro digitados pelo cliente. */
    private const ABBREVIATIONS = [
        'jd' => 'jardim',
        'jdm' => 'jardim',
        'vl' => 'vila',
        'pq' => 'parque',
        'res' => 'residencial',
        'resid' => 'residencial',
        'cj' => 'conjunto',
        'conj' => 'conjunto',
        'nsa' => 'nossa',
        'sra' => 'senhora',
        'sta' => 'santa',
        'sto' => 'santo',
        'sao' => 'sao',
        'chac' => 'chacara',
        'ch' => 'chacara',
        'cond' => 'condominio',
    ];

    /** @var list<array<string, mixed>>|null */
    private ?array $zonesCache = null;

    public function __construct(private ?DeliveryZoneRepository $zoneRepository = null)
    {
        $this->zoneRepository ??= new DeliveryZoneRepository();
    }

    /**
     * @param array<string, mixed> $address shipping_postal_code, shipping_neighborhood, shipping_city, shipping_state
     * @return array{
     *     available: bool,
     *     mode: string,
     *     fee: float,
     *     zone_id: int|null,
     *     zone_name: string|null,
     *     lead_time: string|null,
     *     free_shipping: bool,
     *     free_shipping_min: float|null,
     *     missing_for_free_shipping: float|null,
     *     min_order_amount: float|null,
     *     error: string|null,
     *     message: string
     * }
     */
    public function quote(string $deliveryMethod, array $address, float $subtotal): array
    {
        $subtotal = max(0.0, round($subtotal, 2));

        if (DeliveryMethod::parse($deliveryMethod) === DeliveryMethod::PICKUP) {
            return $this->result(true, self::MODE_PICKUP, 0.0, null, 'Retirada na loja sem custo.');
        }

        $zones = $this->activeZones();
        if ($zones === []) {
            return $this->result(true, self::MODE_TO_ARRANGE, 0.0, null, 'Frete combinado com a loja após o pedido.');
        }

        $city = self::normalize((string) ($address['shipping_city'] ?? ''));
        $state = strtoupper(trim((string) ($address['shipping_state'] ?? '')));
        if ($city === '' || $state === '') {
            return $this->result(
                false,
                self::MODE_ZONE,
                0.0,
                null,
                'Informe o CEP, cidade e bairro para calcular o frete.',
                self::ERROR_INCOMPLETE
            );
        }

        $zone = $this->matchZone($zones, $address);
        if ($zone === null) {
            return $this->result(
                false,
                self::MODE_ZONE,
                0.0,
                null,
                'Ainda não entregamos neste endereço. Escolha retirada na loja ou fale com a gente no WhatsApp.',
                self::ERROR_NOT_COVERED
            );
        }

        $minOrder = self::nullableMoney($zone['min_order_amount'] ?? null);
        if ($minOrder !== null && $subtotal < $minOrder) {
            $result = $this->result(
                false,
                self::MODE_ZONE,
                0.0,
                $zone,
                sprintf(
                    'Pedido mínimo para entrega em %s: %s. Faltam %s.',
                    (string) $zone['name'],
                    self::money($minOrder),
                    self::money($minOrder - $subtotal)
                ),
                self::ERROR_MIN_ORDER
            );
            $result['min_order_amount'] = $minOrder;

            return $result;
        }

        $fee = round(max(0.0, (float) ($zone['fee'] ?? 0)), 2);
        $freeMin = self::nullableMoney($zone['free_shipping_min'] ?? null);
        $isFree = $fee <= 0.0 || ($freeMin !== null && $subtotal >= $freeMin);

        $message = $isFree
            ? 'Frete grátis para ' . (string) $zone['name'] . '.'
            : 'Entrega em ' . (string) $zone['name'] . ': ' . self::money($fee) . '.';

        $result = $this->result(true, self::MODE_ZONE, $isFree ? 0.0 : $fee, $zone, $message);
        $result['free_shipping'] = $isFree;
        $result['free_shipping_min'] = $freeMin;
        $result['min_order_amount'] = $minOrder;
        if (!$isFree && $freeMin !== null) {
            $result['missing_for_free_shipping'] = round($freeMin - $subtotal, 2);
        }

        return $result;
    }

    public function hasZonesConfigured(): bool
    {
        return $this->activeZones() !== [];
    }

    /**
     * @param list<array<string, mixed>> $zones
     * @param array<string, mixed> $address
     * @return array<string, mixed>|null
     */
    public function matchZone(array $zones, array $address): ?array
    {
        $city = self::normalize((string) ($address['shipping_city'] ?? ''));
        $state = strtoupper(trim((string) ($address['shipping_state'] ?? '')));
        $neighborhood = self::normalize((string) ($address['shipping_neighborhood'] ?? ''));
        $postalCode = BrazilianAddress::normalizePostalCode((string) ($address['shipping_postal_code'] ?? ''));

        $best = null;
        $bestScore = 0;

        foreach ($zones as $zone) {
            if (strtoupper(trim((string) ($zone['state'] ?? ''))) !== $state) {
                continue;
            }
            if (self::normalize((string) ($zone['city'] ?? '')) !== $city) {
                continue;
            }

            $score = $this->scoreZone($zone, $neighborhood, $postalCode);
            if ($score > $bestScore) {
                $best = $zone;
                $bestScore = $score;
            }
        }

        return $best;
    }

    /**
     * @param array<string, mixed> $zone
     */
    private function scoreZone(array $zone, string $neighborhood, string $postalCode): int
    {
        $listed = self::parseNeighborhoods((string) ($zone['neighborhoods'] ?? ''));
        $rangeStart = BrazilianAddress::normalizePostalCode((string) ($zone['postal_code_start'] ?? ''));
        $rangeEnd = BrazilianAddress::normalizePostalCode((string) ($zone['postal_code_end'] ?? ''));
        $hasRange = strlen($rangeStart) === 8 && strlen($rangeEnd) === 8;

        if ($listed !== [] && $neighborhood !== '' && in_array($neighborhood, $listed, true)) {
            return self::SCORE_NEIGHBORHOOD;
        }

        if ($hasRange && strlen($postalCode) === 8 && $postalCode >= $rangeStart && $postalCode <= $rangeEnd) {
            return self::SCORE_POSTAL_RANGE;
        }

        if ($listed === [] && !$hasRange) {
            return self::SCORE_CITY;
        }

        return 0;
    }

    /**
     * Uma linha (ou vírgula / ponto e vírgula) por bairro.
     *
     * @return list<string>
     */
    public static function parseNeighborhoods(string $raw): array
    {
        $parts = preg_split('/[\r\n,;]+/', $raw) ?: [];
        $normalized = [];
        foreach ($parts as $part) {
            $value = self::normalize($part);
            if ($value !== '') {
                $normalized[$value] = true;
            }
        }

        return array_keys($normalized);
    }

    public static function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        if ($value === '') {
            return '';
        }

        $ascii = function_exists('iconv') ? @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) : false;
        if (is_string($ascii) && $ascii !== '') {
            $value = $ascii;
        } else {
            $value = strtr($value, [
                'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
                'é' => 'e', 'ê' => 'e', 'è' => 'e', 'í' => 'i', 'î' => 'i',
                'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ò' => 'o', 'ú' => 'u', 'ü' => 'u', 'ç' => 'c',
            ]);
        }

        $value = preg_replace('/[^a-z0-9]+/', ' ', strtolower($value)) ?? '';
        $tokens = array_filter(explode(' ', trim($value)), static fn (string $t): bool => $t !== '');
        $tokens = array_map(static fn (string $t): string => self::ABBREVIATIONS[$t] ?? $t, $tokens);

        return implode(' ', $tokens);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function activeZones(): array
    {
        if ($this->zonesCache === null) {
            try {
                $this->zonesCache = $this->zoneRepository->listActive();
            } catch (\Throwable $e) {
                error_log('[shipping_quote] zones unavailable: ' . $e->getMessage());
                $this->zonesCache = [];
            }
        }

        return $this->zonesCache;
    }

    /**
     * @param array<string, mixed>|null $zone
     * @return array<string, mixed>
     */
    private function result(
        bool $available,
        string $mode,
        float $fee,
        ?array $zone,
        string $message,
        ?string $error = null
    ): array {
        $leadTime = $zone !== null ? trim((string) ($zone['lead_time'] ?? '')) : '';

        return [
            'available' => $available,
            'mode' => $mode,
            'fee' => round($fee, 2),
            'zone_id' => $zone !== null ? (int) ($zone['id'] ?? 0) : null,
            'zone_name' => $zone !== null ? (string) ($zone['name'] ?? '') : null,
            'lead_time' => $leadTime !== '' ? $leadTime : null,
            'free_shipping' => $available && $fee <= 0.0 && $mode === self::MODE_ZONE,
            'free_shipping_min' => null,
            'missing_for_free_shipping' => null,
            'min_order_amount' => null,
            'error' => $error,
            'message' => $message,
        ];
    }

    private static function nullableMoney(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $amount = round((float) $value, 2);

        return $amount > 0 ? $amount : null;
    }

    private static function money(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }
}
