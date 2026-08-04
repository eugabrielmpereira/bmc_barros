<?php

declare(strict_types=1);

namespace Erp\Admin\Controllers;

use Core\Controller;
use Core\Security\Csrf;
use Erp\Audit\Services\AuditService;
use Erp\Commercial\Services\CommercialClientService;

final class AdminCommercialClientController extends Controller
{
    private CommercialClientService $clientService;
    private AuditService $auditService;

    public function __construct()
    {
        $this->clientService = new CommercialClientService();
        $this->auditService = new AuditService();
    }

    public function index(): void
    {
        $search = trim((string) ($_GET['busca'] ?? ''));
        $this->view('erp::commercial-clients/index', [
            'tituloPagina' => 'Clientes Comerciais',
            'paginaAtiva' => 'clientes',
            'clients' => $this->clientService->listForAdmin($search),
            'search' => $search,
            'msg' => trim((string) ($_GET['msg'] ?? '')),
            'tipo' => (string) ($_GET['tipo'] ?? 'ok'),
        ], 'erp::layouts/admin');
    }

    public function create(): void
    {
        $this->viewForm('Novo Cliente', 'erp::commercial-clients/create', ['data' => [], 'errors' => []]);
    }

    public function store(): void
    {
        Csrf::requireValidPostOrAbort();
        $result = $this->clientService->create($_POST);
        if ($result['success']) {
            $this->auditService->record('create', 'commercial_client');
            $this->redirectWithFeedback('/admin/clientes', (string) $result['message']);
        }

        $this->viewForm('Novo Cliente', 'erp::commercial-clients/create', [
            'data' => $result['data'],
            'errors' => $result['errors'],
        ]);
    }

    public function edit(string $id): void
    {
        $client = $this->clientService->find((int) $id);
        if ($client === null) {
            $this->redirectWithFeedback('/admin/clientes', 'Cliente não encontrado.', 'erro');
        }

        $this->viewForm('Editar Cliente', 'erp::commercial-clients/edit', [
            'data' => $client,
            'errors' => [],
            'clientId' => (int) $id,
        ]);
    }

    public function update(string $id): void
    {
        Csrf::requireValidPostOrAbort();
        $clientId = (int) $id;
        $result = $this->clientService->update($clientId, $_POST);
        if ($result['success']) {
            $this->auditService->record('update', 'commercial_client', $clientId);
            $this->redirectWithFeedback('/admin/clientes', (string) $result['message']);
        }

        $this->viewForm('Editar Cliente', 'erp::commercial-clients/edit', [
            'data' => $result['data'],
            'errors' => $result['errors'],
            'clientId' => $clientId,
        ]);
    }

    public function delete(string $id): void
    {
        Csrf::requireValidPostOrAbort();
        $clientId = (int) $id;
        $result = $this->clientService->delete($clientId);
        if ($result['success']) {
            $this->auditService->record('delete', 'commercial_client', $clientId);
        }

        $this->redirectWithFeedback('/admin/clientes', $result['message'], $result['type']);
    }

    public function deactivate(string $id): void
    {
        Csrf::requireValidPostOrAbort();
        $clientId = (int) $id;
        $result = $this->clientService->deactivate($clientId);
        if ($result['success']) {
            $this->auditService->record('deactivate', 'commercial_client', $clientId);
        }

        $this->redirectWithFeedback('/admin/clientes', $result['message'], $result['type']);
    }

    private function viewForm(string $title, string $view, array $data): void
    {
        $this->view($view, array_merge($data, [
            'tituloPagina' => $title,
            'paginaAtiva' => 'clientes',
        ]), 'erp::layouts/admin');
    }

    private function redirectWithFeedback(string $path, string $message, string $tipo = 'ok'): void
    {
        $this->redirect($path . '?' . http_build_query(['msg' => $message, 'tipo' => $tipo]));
    }
}
