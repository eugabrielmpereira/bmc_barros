<?php

declare(strict_types=1);

namespace Erp\Admin\Controllers;

use Core\Controller;
use Core\Security\Csrf;
use Erp\Audit\Services\AuditService;
use Erp\Users\Services\UserService;

final class AdminUserController extends Controller
{
    private UserService $userService;
    private AuditService $auditService;

    public function __construct()
    {
        $this->userService = new UserService();
        $this->auditService = new AuditService();
    }

    public function index(): void
    {
        $search = trim((string) ($_GET['busca'] ?? ''));

        $this->view('erp::users/index', [
            'tituloPagina' => 'Usuários',
            'paginaAtiva' => 'usuarios',
            'users' => $this->userService->listForAdmin($search),
            'search' => $search,
            'msg' => trim((string) ($_GET['msg'] ?? '')),
            'tipo' => (string) ($_GET['tipo'] ?? 'ok'),
        ], 'erp::layouts/admin');
    }

    public function create(): void
    {
        $this->viewForm('Novo Usuário', 'erp::users/create', [
            'data' => $this->defaultFormData(),
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        Csrf::requireValidPostOrAbort();

        $result = $this->userService->create($_POST);
        if ($result['success']) {
            $this->auditService->record('create', 'user', isset($result['id']) ? (int) $result['id'] : null);
            $this->redirectWithFeedback('/admin/usuarios', (string) $result['message']);
        }

        $this->viewForm('Novo Usuário', 'erp::users/create', [
            'data' => $result['data'],
            'errors' => $result['errors'],
        ]);
    }

    public function edit(string $id): void
    {
        $user = $this->userService->find((int) $id);
        if ($user === null) {
            $this->redirectWithFeedback('/admin/usuarios', 'Usuário não encontrado.', 'erro');
        }

        $this->viewForm('Editar Usuário', 'erp::users/edit', [
            'data' => $user,
            'errors' => [],
            'userId' => (int) $id,
        ]);
    }

    public function update(string $id): void
    {
        Csrf::requireValidPostOrAbort();

        $userId = (int) $id;
        $result = $this->userService->update($userId, $_POST);
        if ($result['success']) {
            $this->auditService->record('update', 'user', $userId);
            $this->redirectWithFeedback('/admin/usuarios', (string) $result['message']);
        }

        if ($result['data'] === [] && ($result['errors'][0] ?? '') === 'Usuário não encontrado.') {
            $this->redirectWithFeedback('/admin/usuarios', 'Usuário não encontrado.', 'erro');
        }

        $this->viewForm('Editar Usuário', 'erp::users/edit', [
            'data' => $result['data'],
            'errors' => $result['errors'],
            'userId' => $userId,
        ]);
    }

    public function delete(string $id): void
    {
        Csrf::requireValidPostOrAbort();

        $userId = (int) $id;
        $result = $this->userService->delete($userId, $this->currentUserId());
        if ($result['success']) {
            $this->auditService->record('delete', 'user', $userId);
        }

        $this->redirectWithFeedback('/admin/usuarios', $result['message'], $result['type']);
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultFormData(): array
    {
        return [
            'name' => '',
            'email' => '',
            'status' => 'ativo',
            'role' => 'operator',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function viewForm(string $title, string $view, array $data): void
    {
        $this->view($view, array_merge($data, [
            'tituloPagina' => $title,
            'paginaAtiva' => 'usuarios',
        ]), 'erp::layouts/admin');
    }

    private function redirectWithFeedback(string $path, string $message, string $tipo = 'ok'): void
    {
        $separator = str_contains($path, '?') ? '&' : '?';
        $this->redirect($path . $separator . http_build_query(['msg' => $message, 'tipo' => $tipo]));
    }

    private function currentUserId(): ?int
    {
        $id = (int) ($_SESSION['admin_id'] ?? 0);

        return $id > 0 ? $id : null;
    }
}
