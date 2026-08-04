<?php

declare(strict_types=1);

namespace Erp\Users\Services;

use Erp\Users\Repositories\UserRepository;

final class UserService
{
    private const ROLES = ['admin', 'operator'];
    private const STATUSES = ['ativo', 'inativo'];

    public function __construct(
        private ?UserRepository $repository = null
    ) {
        $this->repository ??= new UserRepository();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForAdmin(string $search = ''): array
    {
        return $this->repository->listForAdmin($search);
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
        [$errors, $data] = $this->validate($post, null, true);

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors, 'data' => $data];
        }

        $created = $this->repository->create([
            'nome' => $data['name'],
            'email' => $data['email'],
            'senha' => password_hash((string) $data['password'], PASSWORD_DEFAULT),
            'status' => $data['status'],
            'role' => $data['role'],
        ]);

        if (!$created) {
            return ['success' => false, 'errors' => ['Não foi possível criar o usuário.'], 'data' => $data];
        }

        $user = $this->repository->findByEmail((string) $data['email']);

        return [
            'success' => true,
            'errors' => [],
            'data' => $data,
            'id' => isset($user['id']) ? (int) $user['id'] : null,
            'message' => 'Usuário criado com sucesso.',
        ];
    }

    /**
     * @return array{success: bool, errors: list<string>, data: array<string, mixed>, message?: string}
     */
    public function update(int $id, array $post): array
    {
        $existing = $this->find($id);
        if ($existing === null) {
            return ['success' => false, 'errors' => ['Usuário não encontrado.'], 'data' => []];
        }

        [$errors, $data] = $this->validate($post, $id, false);

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors, 'data' => array_merge($existing, $data)];
        }

        $payload = [
            'nome' => $data['name'],
            'email' => $data['email'],
            'status' => $data['status'],
            'role' => $data['role'],
        ];

        if ((string) $data['password'] !== '') {
            $payload['senha'] = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        }

        if (!$this->repository->update($id, $payload)) {
            return ['success' => false, 'errors' => ['Não foi possível atualizar o usuário.'], 'data' => $data];
        }

        return [
            'success' => true,
            'errors' => [],
            'data' => $data,
            'message' => 'Usuário atualizado com sucesso.',
        ];
    }

    /**
     * @return array{success: bool, message: string, type: string}
     */
    public function delete(int $id, ?int $currentUserId = null): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'ID inválido.', 'type' => 'erro'];
        }

        if ($currentUserId !== null && $id === $currentUserId) {
            return ['success' => false, 'message' => 'Você não pode remover o próprio usuário.', 'type' => 'erro'];
        }

        if ($this->find($id) === null) {
            return ['success' => false, 'message' => 'Usuário não encontrado.', 'type' => 'erro'];
        }

        try {
            if (!$this->repository->delete($id)) {
                return ['success' => false, 'message' => 'Não foi possível remover o usuário.', 'type' => 'erro'];
            }
        } catch (\PDOException) {
            return [
                'success' => false,
                'message' => 'Não é possível excluir este usuário porque há registros vinculados no sistema.',
                'type' => 'erro',
            ];
        }

        return ['success' => true, 'message' => 'Usuário removido com sucesso.', 'type' => 'ok'];
    }

    /**
     * @return array{active: int, inactive: int}
     */
    public function statusSummary(): array
    {
        return [
            'active' => $this->repository->countByStatus('ativo'),
            'inactive' => $this->repository->countByStatus('inativo'),
        ];
    }

    /**
     * @return array{0: list<string>, 1: array<string, mixed>}
     */
    private function validate(array $post, ?int $id, bool $passwordRequired): array
    {
        $data = [
            'name' => trim((string) ($post['name'] ?? $post['nome'] ?? '')),
            'email' => trim((string) ($post['email'] ?? '')),
            'password' => (string) ($post['password'] ?? ''),
            'status' => in_array($post['status'] ?? '', self::STATUSES, true) ? (string) $post['status'] : 'ativo',
            'role' => in_array($post['role'] ?? '', self::ROLES, true) ? (string) $post['role'] : 'operator',
        ];

        $errors = [];
        if ($data['name'] === '') {
            $errors[] = 'Nome é obrigatório.';
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail inválido.';
        } elseif ($this->repository->emailExists((string) $data['email'], $id)) {
            $errors[] = 'Já existe usuário com este e-mail.';
        }
        if ($passwordRequired && $data['password'] === '') {
            $errors[] = 'Senha é obrigatória.';
        }
        if ($data['password'] !== '' && strlen((string) $data['password']) < 8) {
            $errors[] = 'Senha deve ter no mínimo 8 caracteres.';
        }

        return [$errors, $data];
    }
}
