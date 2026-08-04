<?php

declare(strict_types=1);

namespace Erp\Admin\Controllers;

use Core\Controller;
use Core\Security\Csrf;
use Erp\Audit\Services\AuditService;
use Erp\Products\Services\CategoryService;

final class AdminCategoryController extends Controller
{
    private CategoryService $categoryService;
    private AuditService $auditService;

    public function __construct()
    {
        $this->categoryService = new CategoryService();
        $this->auditService = new AuditService();
    }

    public function index(): void
    {
        $this->view('erp::categories/index', [
            'tituloPagina' => 'Categorias',
            'paginaAtiva' => 'categorias',
            'categorias' => $this->categoryService->listForAdmin(),
            'msg' => trim((string) ($_GET['msg'] ?? '')),
            'tipo' => (string) ($_GET['tipo'] ?? 'ok'),
        ], 'erp::layouts/admin');
    }

    public function create(): void
    {
        $this->viewForm('Nova Categoria', 'erp::categories/create', [
            'data' => $this->defaultFormData(),
            'errors' => [],
            'parents' => $this->categoryService->listParentOptions(),
        ]);
    }

    public function store(): void
    {
        Csrf::requireValidPostOrAbort();
        $result = $this->categoryService->create($_POST);

        if ($result['success']) {
            $this->auditService->record('create', 'category', null, [
                'name' => (string) ($result['data']['nome'] ?? ''),
            ]);
            $this->redirectWithFeedback('/admin/categorias', (string) ($result['message'] ?? 'Categoria criada.'));
        }

        $this->viewForm('Nova Categoria', 'erp::categories/create', [
            'data' => $result['data'],
            'errors' => $result['errors'],
            'parents' => $this->categoryService->listParentOptions(),
        ]);
    }

    public function edit(string $id): void
    {
        $category = $this->categoryService->find((int) $id);
        if ($category === null) {
            $this->redirectWithFeedback('/admin/categorias', 'Categoria não encontrada.', 'erro');
        }

        $this->viewForm('Editar Categoria', 'erp::categories/edit', [
            'data' => $category,
            'errors' => [],
            'parents' => $this->categoryService->listParentOptions((int) $id),
            'categoryId' => (int) $id,
        ]);
    }

    public function update(string $id): void
    {
        Csrf::requireValidPostOrAbort();
        $categoryId = (int) $id;
        $result = $this->categoryService->update($categoryId, $_POST);

        if ($result['success']) {
            $this->auditService->record('update', 'category', $categoryId);
            $this->redirectWithFeedback('/admin/categorias', (string) ($result['message'] ?? 'Categoria atualizada.'));
        }

        $this->viewForm('Editar Categoria', 'erp::categories/edit', [
            'data' => $result['data'],
            'errors' => $result['errors'],
            'parents' => $this->categoryService->listParentOptions($categoryId),
            'categoryId' => $categoryId,
        ]);
    }

    public function delete(string $id): void
    {
        Csrf::requireValidPostOrAbort();
        $categoryId = (int) $id;
        $result = $this->categoryService->delete($categoryId);

        if ($result['success']) {
            $this->auditService->record('delete', 'category', $categoryId);
        }

        $this->redirectWithFeedback(
            '/admin/categorias',
            $result['message'],
            $result['tipo']
        );
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function viewForm(string $title, string $view, array $extra): void
    {
        $this->view($view, array_merge([
            'tituloPagina' => $title,
            'paginaAtiva' => 'categorias',
        ], $extra), 'erp::layouts/admin');
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultFormData(): array
    {
        return [
            'nome' => '',
            'slug' => '',
            'parent_id' => (int) ($_GET['parent_id'] ?? 0) ?: null,
            'sort_order' => 0,
            'status' => 'active',
            'description' => '',
            'meta_title' => '',
            'meta_description' => '',
        ];
    }

    private function redirectWithFeedback(string $path, string $message, string $tipo = 'ok'): void
    {
        $separator = str_contains($path, '?') ? '&' : '?';
        $this->redirect($path . $separator . 'msg=' . urlencode($message) . '&tipo=' . urlencode($tipo));
    }
}
