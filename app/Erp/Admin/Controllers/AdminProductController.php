<?php

declare(strict_types=1);

namespace Erp\Admin\Controllers;

use Core\Controller;
use Core\Security\Csrf;
use Erp\Audit\Services\AuditService;
use Erp\Products\Services\ProductService;

class AdminProductController extends Controller
{
    private ProductService $productService;
    private AuditService $auditService;

    public function __construct()
    {
        $this->productService = new ProductService();
        $this->auditService = new AuditService();
    }

    public function index(): void
    {
        $busca = trim((string) ($_GET['busca'] ?? ''));

        $this->adminProductsView('erp::products/index', 'Produtos', [
            'produtos' => $this->productService->listForAdmin($busca),
            'busca' => $busca,
            'msg' => trim((string) ($_GET['msg'] ?? '')),
            'tipo' => (string) ($_GET['tipo'] ?? 'ok'),
        ]);
    }

    public function create(): void
    {
        $this->adminProductsView('erp::products/create', 'Novo Produto', [
            'categorias' => $this->productService->getCategorias(),
            'dados' => $this->defaultFormData(),
            'erros' => [],
        ]);
    }

    public function store(): void
    {
        Csrf::requireValidPostOrAbort();

        $file = isset($_FILES['imagem']) && is_array($_FILES['imagem']) ? $_FILES['imagem'] : null;
        $result = $this->productService->create($_POST, $file);

        if ($result['success']) {
            $this->auditService->record('create', 'product', null, [
                'name' => (string) ($result['dados']['nome'] ?? ''),
            ]);
            $this->redirectWithFeedback(
                '/admin/produtos',
                (string) ($result['message'] ?? 'Produto criado com sucesso!')
            );
        }

        $this->adminProductsView('erp::products/create', 'Novo Produto', [
            'categorias' => $this->productService->getCategorias(),
            'dados' => $result['dados'],
            'erros' => $result['errors'],
        ]);
    }

    public function edit(string $id): void
    {
        $produto = $this->productService->find((int) $id);
        if ($produto === null) {
            $this->redirectWithFeedback('/admin/produtos', 'Produto não encontrado.', 'erro');
        }

        $this->adminProductsView('erp::products/edit', 'Editar Produto', [
            'categorias' => $this->productService->getCategorias(),
            'dados' => $produto,
            'erros' => [],
            'produtoId' => (int) $id,
        ]);
    }

    public function update(string $id): void
    {
        Csrf::requireValidPostOrAbort();

        $produtoId = (int) $id;
        $file = isset($_FILES['imagem']) && is_array($_FILES['imagem']) ? $_FILES['imagem'] : null;
        $result = $this->productService->update($produtoId, $_POST, $file);

        if ($result['success']) {
            $this->auditService->record('update', 'product', $produtoId);
            $this->redirectWithFeedback(
                '/admin/produtos',
                (string) ($result['message'] ?? 'Produto atualizado com sucesso!')
            );
        }

        if ($result['dados'] === [] && ($result['errors'][0] ?? '') === 'Produto não encontrado.') {
            $this->redirectWithFeedback('/admin/produtos', 'Produto não encontrado.', 'erro');
        }

        $this->adminProductsView('erp::products/edit', 'Editar Produto', [
            'categorias' => $this->productService->getCategorias(),
            'dados' => $result['dados'],
            'erros' => $result['errors'],
            'produtoId' => $produtoId,
        ]);
    }

    public function delete(string $id): void
    {
        Csrf::requireValidPostOrAbort();

        $result = $this->productService->delete((int) $id);
        if ($result['success']) {
            $this->auditService->record('delete', 'product', (int) $id);
        }
        $this->redirectWithFeedback('/admin/produtos', $result['message'], $result['tipo']);
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultFormData(): array
    {
        return [
            'nome' => '',
            'descricao' => '',
            'preco' => '',
            'estoque_atual' => 0,
            'status' => 'ativo',
            'categoria_id' => '',
        ];
    }

    private function redirectWithFeedback(string $path, string $message, string $tipo = 'ok'): void
    {
        $separator = str_contains($path, '?') ? '&' : '?';
        $this->redirect(
            $path . $separator . 'msg=' . rawurlencode($message) . '&tipo=' . rawurlencode($tipo)
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function adminProductsView(string $view, string $tituloPagina, array $data = []): void
    {
        $this->view($view, array_merge($data, [
            'tituloPagina' => $tituloPagina,
            'paginaAtiva' => 'produtos',
        ]), 'erp::layouts/admin');
    }
}
