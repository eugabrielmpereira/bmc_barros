<?php

declare(strict_types=1);

namespace Erp\Products\Services;

use Erp\Products\Repositories\CategoryRepository;
use Erp\Products\Support\CatalogSlug;

/**
 * Regras de negócio do CRUD administrativo de categorias.
 */
final class CategoryService
{
    public function __construct(
        private ?CategoryRepository $repository = null
    ) {
        $this->repository ??= new CategoryRepository();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForAdmin(): array
    {
        return $this->repository->listAllOrdered();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listParentOptions(?int $excludeId = null): array
    {
        return $this->repository->listParentOptions($excludeId);
    }

    public function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        return $this->repository->findById($id);
    }

    /**
     * @param array<string, mixed> $post
     * @return array{success: bool, errors: list<string>, data: array<string, mixed>, message?: string}
     */
    public function create(array $post): array
    {
        [$errors, $data] = $this->validate($post);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors, 'data' => $data];
        }

        $id = $this->repository->createCategory($data);
        if ($id <= 0) {
            return [
                'success' => false,
                'errors' => ['Não foi possível salvar a categoria.'],
                'data' => $data,
            ];
        }

        return [
            'success' => true,
            'errors' => [],
            'data' => $data,
            'message' => 'Categoria criada com sucesso.',
        ];
    }

    /**
     * @param array<string, mixed> $post
     * @return array{success: bool, errors: list<string>, data: array<string, mixed>, message?: string}
     */
    public function update(int $id, array $post): array
    {
        $existing = $this->find($id);
        if ($existing === null) {
            return [
                'success' => false,
                'errors' => ['Categoria não encontrada.'],
                'data' => [],
            ];
        }

        [$errors, $data] = $this->validate($post, $id);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors, 'data' => array_merge($existing, $data)];
        }

        if (!$this->repository->update($id, $data)) {
            return [
                'success' => false,
                'errors' => ['Não foi possível atualizar a categoria.'],
                'data' => array_merge($existing, $data),
            ];
        }

        return [
            'success' => true,
            'errors' => [],
            'data' => array_merge($existing, $data),
            'message' => 'Categoria atualizada com sucesso.',
        ];
    }

    /**
     * @return array{success: bool, message: string, tipo: string}
     */
    public function delete(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'ID inválido.', 'tipo' => 'erro'];
        }

        if ($this->repository->findById($id) === null) {
            return ['success' => false, 'message' => 'Categoria não encontrada.', 'tipo' => 'erro'];
        }

        if ($this->repository->countChildren($id) > 0) {
            return [
                'success' => false,
                'message' => 'Remova ou mova as subcategorias antes de excluir.',
                'tipo' => 'erro',
            ];
        }

        if ($this->repository->countProducts($id) > 0) {
            return [
                'success' => false,
                'message' => 'Existem produtos vinculados a esta categoria.',
                'tipo' => 'erro',
            ];
        }

        if (!$this->repository->delete($id)) {
            return ['success' => false, 'message' => 'Não foi possível excluir a categoria.', 'tipo' => 'erro'];
        }

        return ['success' => true, 'message' => 'Categoria excluída com sucesso.', 'tipo' => 'ok'];
    }

    /**
     * @param array<string, mixed> $post
     * @return array{0: list<string>, 1: array<string, mixed>}
     */
    private function validate(array $post, int $excludeId = 0): array
    {
        $errors = [];
        $nome = trim((string) ($post['nome'] ?? ''));
        $slugInput = trim((string) ($post['slug'] ?? ''));
        $parentId = (int) ($post['parent_id'] ?? 0);
        $parentId = $parentId > 0 ? $parentId : null;
        $sortOrder = (int) ($post['sort_order'] ?? 0);
        $status = in_array($post['status'] ?? '', ['active', 'inactive'], true)
            ? (string) $post['status']
            : 'active';
        $description = trim((string) ($post['description'] ?? ''));
        $metaTitle = trim((string) ($post['meta_title'] ?? ''));
        $metaDescription = trim((string) ($post['meta_description'] ?? ''));

        if ($nome === '') {
            $errors[] = 'Nome da categoria é obrigatório.';
        }

        $level = 1;
        if ($parentId !== null) {
            $parent = $this->repository->findById($parentId);
            if ($parent === null) {
                $errors[] = 'Categoria pai inválida.';
            } elseif ($excludeId > 0 && $parentId === $excludeId) {
                $errors[] = 'A categoria não pode ser pai de si mesma.';
            } else {
                $parentLevel = (int) ($parent['level'] ?? 1);
                $level = $parentLevel + 1;
                if ($level > 3) {
                    $errors[] = 'A hierarquia permite no máximo 3 níveis.';
                }
            }
        }

        $slug = $slugInput !== '' ? CatalogSlug::fromText($slugInput) : CatalogSlug::fromText($nome);
        if ($slug === '') {
            $errors[] = 'Slug inválido.';
        } elseif ($this->repository->slugExists($slug, $excludeId)) {
            $errors[] = 'Este slug já está em uso.';
        }

        $data = [
            'parent_id' => $parentId,
            'nome' => $nome,
            'slug' => $slug,
            'description' => $description !== '' ? $description : null,
            'sort_order' => $sortOrder,
            'level' => $level,
            'status' => $status,
            'meta_title' => $metaTitle !== '' ? $metaTitle : $nome,
            'meta_description' => $metaDescription !== '' ? $metaDescription : null,
        ];

        return [$errors, $data];
    }
}
