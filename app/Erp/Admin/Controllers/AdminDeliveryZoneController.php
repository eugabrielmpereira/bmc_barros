<?php

declare(strict_types=1);

namespace Erp\Admin\Controllers;

use Core\Controller;
use Core\Security\Csrf;
use Erp\Audit\Services\AuditService;
use Erp\Shipping\Services\DeliveryZoneService;

final class AdminDeliveryZoneController extends Controller
{
    private DeliveryZoneService $zoneService;
    private AuditService $auditService;

    public function __construct()
    {
        $this->zoneService = new DeliveryZoneService();
        $this->auditService = new AuditService();
    }

    public function index(): void
    {
        $this->view('erp::delivery-zones/index', [
            'tituloPagina' => 'Frete e entregas',
            'paginaAtiva' => 'frete',
            'zones' => $this->zoneService->listForAdmin(),
            'msg' => trim((string) ($_GET['msg'] ?? '')),
            'tipo' => (string) ($_GET['tipo'] ?? 'ok'),
        ], 'erp::layouts/admin');
    }

    public function create(): void
    {
        $this->viewForm('Nova zona de entrega', [
            'data' => DeliveryZoneService::defaults(),
            'errors' => [],
            'action' => url('/admin/frete/criar'),
        ]);
    }

    public function store(): void
    {
        Csrf::requireValidPostOrAbort();
        $result = $this->zoneService->create($_POST);

        if ($result['success']) {
            $this->auditService->record('create', 'delivery_zone', $result['id'] ?? null, ['name' => (string) $result['data']['name']]);
            $this->redirectWithFeedback('/admin/frete', (string) ($result['message'] ?? 'Zona criada.'));
        }

        $this->viewForm('Nova zona de entrega', [
            'data' => $result['data'],
            'errors' => $result['errors'],
            'action' => url('/admin/frete/criar'),
        ]);
    }

    public function edit(string $id): void
    {
        $zone = $this->zoneService->find((int) $id);
        if ($zone === null) {
            $this->redirectWithFeedback('/admin/frete', 'Zona não encontrada.', 'erro');
        }

        $this->viewForm('Editar zona de entrega', [
            'data' => $zone,
            'errors' => [],
            'action' => url('/admin/frete/editar/' . (int) $id),
        ]);
    }

    public function update(string $id): void
    {
        Csrf::requireValidPostOrAbort();
        $zoneId = (int) $id;
        $result = $this->zoneService->update($zoneId, $_POST);

        if ($result['success']) {
            $this->auditService->record('update', 'delivery_zone', $zoneId);
            $this->redirectWithFeedback('/admin/frete', (string) ($result['message'] ?? 'Zona atualizada.'));
        }

        $this->viewForm('Editar zona de entrega', [
            'data' => $result['data'],
            'errors' => $result['errors'],
            'action' => url('/admin/frete/editar/' . $zoneId),
        ]);
    }

    public function delete(string $id): void
    {
        Csrf::requireValidPostOrAbort();
        $zoneId = (int) $id;
        $result = $this->zoneService->delete($zoneId);

        if ($result['success']) {
            $this->auditService->record('delete', 'delivery_zone', $zoneId);
        }

        $this->redirectWithFeedback('/admin/frete', $result['message'], $result['tipo']);
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function viewForm(string $title, array $extra): void
    {
        $this->view('erp::delivery-zones/form', array_merge([
            'tituloPagina' => $title,
            'paginaAtiva' => 'frete',
        ], $extra), 'erp::layouts/admin');
    }

    private function redirectWithFeedback(string $path, string $message, string $tipo = 'ok'): void
    {
        $this->redirect($path . '?msg=' . urlencode($message) . '&tipo=' . urlencode($tipo));
    }
}
