<?php

declare(strict_types=1);

namespace Erp\Shipping\Services;

use Erp\Shipping\Repositories\DeliveryZoneRepository;
use Shared\Support\BrazilianAddress;

/**
 * CRUD administrativo das zonas de entrega.
 */
final class DeliveryZoneService
{
    public function __construct(private ?DeliveryZoneRepository $repository = null)
    {
        $this->repository ??= new DeliveryZoneRepository();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForAdmin(): array
    {
        return $this->repository->listForAdmin();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        return $id > 0 ? $this->repository->find($id) : null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'name' => '',
            'city' => '',
            'state' => 'SP',
            'neighborhoods' => '',
            'postal_code_start' => '',
            'postal_code_end' => '',
            'fee' => '',
            'free_shipping_min' => '',
            'min_order_amount' => '',
            'lead_time' => '',
            'sort_order' => 0,
            'status' => 'active',
        ];
    }

    /**
     * @param array<string, mixed> $post
     * @return array{success: bool, errors: list<string>, data: array<string, mixed>, id?: int, message?: string}
     */
    public function create(array $post): array
    {
        [$errors, $data, $payload] = $this->validate($post);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors, 'data' => $data];
        }

        $id = $this->repository->createZone($payload);

        return ['success' => true, 'errors' => [], 'data' => $data, 'id' => $id, 'message' => 'Zona de entrega criada.'];
    }

    /**
     * @param array<string, mixed> $post
     * @return array{success: bool, errors: list<string>, data: array<string, mixed>, message?: string}
     */
    public function update(int $id, array $post): array
    {
        if ($this->find($id) === null) {
            return ['success' => false, 'errors' => ['Zona não encontrada.'], 'data' => $post];
        }

        [$errors, $data, $payload] = $this->validate($post);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors, 'data' => $data];
        }

        $this->repository->update($id, $payload);

        return ['success' => true, 'errors' => [], 'data' => $data, 'message' => 'Zona de entrega atualizada.'];
    }

    /**
     * @return array{success: bool, message: string, tipo: string}
     */
    public function delete(int $id): array
    {
        if ($this->find($id) === null) {
            return ['success' => false, 'message' => 'Zona não encontrada.', 'tipo' => 'erro'];
        }

        $this->repository->delete($id);

        return ['success' => true, 'message' => 'Zona de entrega excluída.', 'tipo' => 'ok'];
    }

    /**
     * @param array<string, mixed> $post
     * @return array{0: list<string>, 1: array<string, mixed>, 2: array<string, mixed>}
     */
    public function validate(array $post): array
    {
        $data = [
            'name' => trim((string) ($post['name'] ?? '')),
            'city' => trim((string) ($post['city'] ?? '')),
            'state' => strtoupper(trim((string) ($post['state'] ?? ''))),
            'neighborhoods' => trim((string) ($post['neighborhoods'] ?? '')),
            'postal_code_start' => trim((string) ($post['postal_code_start'] ?? '')),
            'postal_code_end' => trim((string) ($post['postal_code_end'] ?? '')),
            'fee' => trim((string) ($post['fee'] ?? '')),
            'free_shipping_min' => trim((string) ($post['free_shipping_min'] ?? '')),
            'min_order_amount' => trim((string) ($post['min_order_amount'] ?? '')),
            'lead_time' => trim((string) ($post['lead_time'] ?? '')),
            'sort_order' => (int) ($post['sort_order'] ?? 0),
            'status' => ($post['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
        ];

        $errors = [];
        if ($data['name'] === '') {
            $errors[] = 'Nome da zona é obrigatório.';
        }
        if ($data['city'] === '') {
            $errors[] = 'Cidade é obrigatória.';
        }
        if (!preg_match('/^[A-Z]{2}$/', $data['state'])) {
            $errors[] = 'UF deve ter 2 letras.';
        }

        $fee = self::parseMoney($data['fee']);
        if ($fee === null || $fee < 0) {
            $errors[] = 'Valor do frete inválido (use 0 para grátis).';
        }

        $freeMin = $data['free_shipping_min'] === '' ? null : self::parseMoney($data['free_shipping_min']);
        if ($data['free_shipping_min'] !== '' && ($freeMin === null || $freeMin <= 0)) {
            $errors[] = 'Valor para frete grátis inválido.';
        }

        $minOrder = $data['min_order_amount'] === '' ? null : self::parseMoney($data['min_order_amount']);
        if ($data['min_order_amount'] !== '' && ($minOrder === null || $minOrder <= 0)) {
            $errors[] = 'Pedido mínimo inválido.';
        }

        $cepStart = BrazilianAddress::normalizePostalCode($data['postal_code_start']);
        $cepEnd = BrazilianAddress::normalizePostalCode($data['postal_code_end']);
        if (($cepStart === '') !== ($cepEnd === '')) {
            $errors[] = 'Informe o CEP inicial e o final da faixa (ou deixe os dois vazios).';
        } elseif ($cepStart !== '' && (strlen($cepStart) !== 8 || strlen($cepEnd) !== 8 || $cepStart > $cepEnd)) {
            $errors[] = 'Faixa de CEP inválida.';
        }

        $neighborhoods = implode("\n", array_values(array_filter(
            array_map('trim', preg_split('/[\r\n,;]+/', $data['neighborhoods']) ?: []),
            static fn (string $value): bool => $value !== ''
        )));

        $payload = [
            'name' => mb_substr($data['name'], 0, 120),
            'city' => mb_substr($data['city'], 0, 120),
            'state' => $data['state'],
            'neighborhoods' => $neighborhoods !== '' ? $neighborhoods : null,
            'postal_code_start' => $cepStart !== '' ? $cepStart : null,
            'postal_code_end' => $cepEnd !== '' ? $cepEnd : null,
            'fee' => $fee ?? 0.0,
            'free_shipping_min' => $freeMin,
            'min_order_amount' => $minOrder,
            'lead_time' => $data['lead_time'] !== '' ? mb_substr($data['lead_time'], 0, 80) : null,
            'sort_order' => $data['sort_order'],
            'status' => $data['status'],
        ];

        return [$errors, $data, $payload];
    }

    /**
     * Aceita "1.234,56", "1234,56" ou "1234.56".
     */
    public static function parseMoney(string $value): ?float
    {
        $value = trim(str_replace(['R$', ' '], '', $value));
        if ($value === '') {
            return null;
        }

        if (str_contains($value, ',')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? round((float) $value, 2) : null;
    }
}
