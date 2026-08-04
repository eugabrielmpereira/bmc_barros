<?php

declare(strict_types=1);

namespace Erp\Commercial\Services;

use Erp\Commercial\Repositories\CommercialClientRepository;
use Erp\Orders\Repositories\OrderRepository;
use PDOException;

final class CommercialClientService
{
    private const STATUSES = ['active', 'inactive'];

    public function __construct(
        private ?CommercialClientRepository $repository = null,
        private ?OrderRepository $orderRepository = null
    ) {
        $this->repository ??= new CommercialClientRepository();
        $this->orderRepository ??= new OrderRepository();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForAdmin(string $search = ''): array
    {
        return $this->repository->listForAdmin($search);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function activeOptions(): array
    {
        return $this->repository->activeOptions();
    }

    public function find(int $id): ?array
    {
        return $id > 0 ? $this->repository->find($id) : null;
    }

    /**
     * @return array{success: bool, errors: list<string>, data: array<string, mixed>, id?: int, message?: string}
     */
    public function create(array $post): array
    {
        [$errors, $data] = $this->validate($post);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors, 'data' => $data];
        }

        if (!$this->repository->create($data)) {
            return ['success' => false, 'errors' => ['Não foi possível criar o cliente.'], 'data' => $data];
        }

        return ['success' => true, 'errors' => [], 'data' => $data, 'message' => 'Cliente criado com sucesso.'];
    }

    /**
     * @return array{success: bool, errors: list<string>, data: array<string, mixed>, message?: string}
     */
    public function update(int $id, array $post): array
    {
        $existing = $this->find($id);
        if ($existing === null) {
            return ['success' => false, 'errors' => ['Cliente não encontrado.'], 'data' => []];
        }

        [$errors, $data] = $this->validate($post);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors, 'data' => array_merge($existing, $data)];
        }

        if (!$this->repository->update($id, $data)) {
            return ['success' => false, 'errors' => ['Não foi possível atualizar o cliente.'], 'data' => $data];
        }

        return ['success' => true, 'errors' => [], 'data' => $data, 'message' => 'Cliente atualizado com sucesso.'];
    }

    /**
     * @return array{success: bool, message: string, type: string}
     */
    public function delete(int $id): array
    {
        $existing = $this->find($id);
        if ($existing === null) {
            return ['success' => false, 'message' => 'Cliente não encontrado.', 'type' => 'erro'];
        }

        $orderCount = $this->orderRepository->countByCommercialClient($id);
        if ($orderCount > 0) {
            return [
                'success' => false,
                'message' => 'Não é possível excluir: existem ' . $orderCount . ' pedido(s) vinculado(s). Desative o cadastro em vez de remover.',
                'type' => 'erro',
            ];
        }

        try {
            if (!$this->repository->delete($id)) {
                return ['success' => false, 'message' => 'Não foi possível remover o cliente.', 'type' => 'erro'];
            }
        } catch (PDOException) {
            return [
                'success' => false,
                'message' => 'Não é possível excluir este cliente porque há registros vinculados. Desative o cadastro em vez de remover.',
                'type' => 'erro',
            ];
        }

        return ['success' => true, 'message' => 'Cliente removido com sucesso.', 'type' => 'ok'];
    }

    /**
     * @return array{success: bool, message: string, type: string}
     */
    public function deactivate(int $id): array
    {
        $existing = $this->find($id);
        if ($existing === null) {
            return ['success' => false, 'message' => 'Cliente não encontrado.', 'type' => 'erro'];
        }

        if (!$this->repository->update($id, ['status' => 'inactive'])) {
            return ['success' => false, 'message' => 'Não foi possível desativar o cliente.', 'type' => 'erro'];
        }

        return ['success' => true, 'message' => 'Cliente desativado com sucesso.', 'type' => 'ok'];
    }

    /**
     * @return array{active: int, inactive: int}
     */
    public function statusSummary(): array
    {
        return [
            'active' => $this->repository->countByStatus('active'),
            'inactive' => $this->repository->countByStatus('inactive'),
        ];
    }

    /**
     * @return array{0: list<string>, 1: array<string, mixed>}
     */
    private function validate(array $post): array
    {
        $data = [
            'name' => trim((string) ($post['name'] ?? '')),
            'document' => trim((string) ($post['document'] ?? '')) ?: null,
            'email' => trim((string) ($post['email'] ?? '')) ?: null,
            'phone' => trim((string) ($post['phone'] ?? '')) ?: null,
            'address' => trim((string) ($post['address'] ?? '')) ?: null,
            'status' => in_array($post['status'] ?? '', self::STATUSES, true) ? (string) $post['status'] : 'active',
        ];

        $errors = [];
        if ($data['name'] === '') {
            $errors[] = 'Nome é obrigatório.';
        }
        if ($data['email'] !== null && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail inválido.';
        }

        return [$errors, $data];
    }
}
