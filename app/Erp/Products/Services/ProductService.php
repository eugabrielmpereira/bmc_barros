<?php

declare(strict_types=1);

namespace Erp\Products\Services;

use Core\Database;
use Erp\Products\Repositories\CategoryRepository;
use Erp\Products\Repositories\ProductRepository;

/**
 * Regras de negócio do módulo admin de produtos.
 */
class ProductService
{
    public function __construct(
        private ?ProductRepository $repository = null
    ) {
        $this->repository ??= new ProductRepository();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForAdmin(string $busca = ''): array
    {
        return $this->repository->listForAdmin($busca);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getCategorias(): array
    {
        return (new CategoryRepository())->listLeafCategoriesForProductForm();
    }

    public function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        return $this->repository->find($id);
    }

    /**
     * @param array<string, mixed> $post
     * @param array<string, mixed>|null $file $_FILES['imagem'] ou null
     * @return array{success: bool, errors: list<string>, dados: array<string, mixed>, message?: string}
     */
    public function create(array $post, ?array $file = null): array
    {
        [$errors, $dados] = $this->validateFormData($post);
        $imagem = $this->resolveImageOnCreate($file, $errors);

        if ($errors !== []) {
            return [
                'success' => false,
                'errors' => $errors,
                'dados' => $dados,
            ];
        }

        $descricao = (string) $dados['descricao'];
        $persisted = $this->repository->create([
            'categoria_id' => (int) $dados['categoria_id'],
            'nome' => $dados['nome'],
            'descricao' => $descricao,
            'short_description' => $this->shortDescription($descricao),
            'long_description' => $descricao !== '' ? $descricao : null,
            'preco' => $this->normalizePreco($dados['preco']),
            'imagem' => $imagem,
            'estoque_atual' => (int) $dados['estoque_atual'],
            'status' => $dados['status'],
        ]);

        if (!$persisted) {
            return [
                'success' => false,
                'errors' => ['Não foi possível salvar o produto.'],
                'dados' => $dados,
            ];
        }

        $productId = (int) $this->repository->getConnection()->lastInsertId();
        if ($productId > 0) {
            $this->repository->assignSlugAfterCreate($productId, $dados['nome']);
            $this->repository->syncPrimaryCategory($productId, (int) $dados['categoria_id']);
        }

        return [
            'success' => true,
            'errors' => [],
            'dados' => $dados,
            'message' => 'Produto criado com sucesso!',
        ];
    }

    /**
     * @param array<string, mixed> $post
     * @param array<string, mixed>|null $file
     * @return array{success: bool, errors: list<string>, dados: array<string, mixed>, message?: string}
     */
    public function update(int $id, array $post, ?array $file = null): array
    {
        $produto = $this->find($id);
        if ($produto === null) {
            return [
                'success' => false,
                'errors' => ['Produto não encontrado.'],
                'dados' => [],
            ];
        }

        [$errors, $dados] = $this->validateFormData($post);
        $imagem = $this->resolveImageOnUpdate(
            $produto,
            $file,
            isset($post['remover_imagem']),
            $errors
        );

        if ($errors !== []) {
            $dados['imagem'] = $produto['imagem'] ?? null;

            return [
                'success' => false,
                'errors' => $errors,
                'dados' => array_merge($produto, $dados),
            ];
        }

        $descricao = (string) $dados['descricao'];
        $persisted = $this->repository->update($id, [
            'categoria_id' => (int) $dados['categoria_id'],
            'nome' => $dados['nome'],
            'descricao' => $descricao,
            'short_description' => $this->shortDescription($descricao),
            'long_description' => $descricao !== '' ? $descricao : null,
            'preco' => $this->normalizePreco($dados['preco']),
            'imagem' => $imagem,
            'estoque_atual' => (int) $dados['estoque_atual'],
            'status' => $dados['status'],
        ]);

        if (!$persisted) {
            return [
                'success' => false,
                'errors' => ['Não foi possível atualizar o produto.'],
                'dados' => array_merge($produto, $dados),
            ];
        }

        $this->repository->assignSlugAfterCreate($id, $dados['nome']);
        $this->repository->syncPrimaryCategory($id, (int) $dados['categoria_id']);

        return [
            'success' => true,
            'errors' => [],
            'dados' => array_merge($produto, $dados),
            'message' => 'Produto atualizado com sucesso!',
        ];
    }

    /**
     * @return array{success: bool, message: string, tipo: string}
     */
    public function delete(int $id): array
    {
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'ID inválido.',
                'tipo' => 'erro',
            ];
        }

        $produto = $this->find($id);
        if ($produto === null) {
            return [
                'success' => false,
                'message' => 'Produto não encontrado.',
                'tipo' => 'erro',
            ];
        }

        $this->deleteImageFile(isset($produto['imagem']) ? (string) $produto['imagem'] : null);

        if (!$this->repository->delete($id)) {
            return [
                'success' => false,
                'message' => 'Não foi possível deletar o produto.',
                'tipo' => 'erro',
            ];
        }

        return [
            'success' => true,
            'message' => 'Produto deletado com sucesso.',
            'tipo' => 'ok',
        ];
    }

    /**
     * @param array<string, mixed> $post
     * @return array{0: list<string>, 1: array<string, mixed>}
     */
    private function validateFormData(array $post): array
    {
        $errors = [];
        $dados = [
            'nome' => trim((string) ($post['nome'] ?? '')),
            'descricao' => trim((string) ($post['descricao'] ?? '')),
            'preco' => (string) ($post['preco'] ?? ''),
            'estoque_atual' => (int) ($post['estoque_atual'] ?? 0),
            'status' => in_array($post['status'] ?? '', ['ativo', 'inativo'], true)
                ? (string) $post['status']
                : 'ativo',
            'categoria_id' => (int) ($post['categoria_id'] ?? 0),
        ];

        if ($dados['nome'] === '') {
            $errors[] = 'Nome do produto é obrigatório.';
        }

        if (!$this->repository->categoriaExists((int) $dados['categoria_id'])) {
            $errors[] = 'Selecione uma categoria.';
        }

        if ($dados['preco'] !== '' && (!is_numeric($dados['preco']) || (float) $dados['preco'] < 0)) {
            $errors[] = 'Preço inválido.';
        }

        if ($dados['estoque_atual'] < 0) {
            $errors[] = 'Estoque não pode ser negativo.';
        }

        return [$errors, $dados];
    }

    /**
     * @param list<string> $errors
     */
    private function resolveImageOnCreate(?array $file, array &$errors): ?string
    {
        if ($file === null || ($file['name'] ?? '') === '') {
            return null;
        }

        $nomeImagem = $this->uploadImage($file);
        if ($nomeImagem === null) {
            $errors[] = 'Imagem inválida. Use JPG, PNG ou WEBP com até 3MB.';
        }

        return $nomeImagem;
    }

    /**
     * @param array<string, mixed> $produto
     * @param list<string> $errors
     */
    private function resolveImageOnUpdate(
        array $produto,
        ?array $file,
        bool $removerImagem,
        array &$errors
    ): ?string {
        $imagemAtual = isset($produto['imagem']) && $produto['imagem'] !== ''
            ? (string) $produto['imagem']
            : null;

        if ($removerImagem && $imagemAtual !== null) {
            $this->deleteImageFile($imagemAtual);
            $imagemAtual = null;
        }

        if ($file === null || ($file['name'] ?? '') === '') {
            return $imagemAtual;
        }

        $novaImagem = $this->uploadImage($file);
        if ($novaImagem === null) {
            $errors[] = 'Imagem inválida. Use JPG, PNG ou WEBP com até 3MB.';

            return $imagemAtual;
        }

        if ($imagemAtual !== null) {
            $this->deleteImageFile($imagemAtual);
        }

        return $novaImagem;
    }

    private function normalizePreco(string $preco): ?float
    {
        return $preco !== '' ? (float) $preco : null;
    }

    private function shortDescription(string $descricao): ?string
    {
        $descricao = trim($descricao);
        if ($descricao === '') {
            return null;
        }

        if (strlen($descricao) <= 500) {
            return $descricao;
        }

        return substr($descricao, 0, 497) . '...';
    }

    private function uploadImage(array $file): ?string
    {
        $this->loadUploadHelper();

        $dir = $this->imageUploadDir();
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }

        return uploadImagem($file, $dir);
    }

    private function deleteImageFile(?string $filename): void
    {
        if ($filename === null || $filename === '') {
            return;
        }

        $path = $this->imageUploadDir() . '/' . basename($filename);
        if (is_file($path)) {
            unlink($path);
        }
    }

    private function imageUploadDir(): string
    {
        return PUBLIC_PATH . '/uploads/clients/' . ACTIVE_CLIENT . '/products';
    }

    private function loadUploadHelper(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }

        require_once APP_PATH . '/Services/functions/helpers.php';
        $loaded = true;
    }
}
