<?php

declare(strict_types=1);

namespace Erp\Admin\Controllers;

use Core\Controller;
use Core\Security\Csrf;
use Erp\Audit\Services\AuditService;
use Erp\Finance\Services\PaymentChargeService;
use Erp\Finance\Services\ReceivableService;
use Erp\Fulfillment\Services\FulfillmentService;
use Erp\Orders\Services\OrderService;

final class AdminOrderController extends Controller
{
    private OrderService $orderService;
    private ReceivableService $receivableService;
    private PaymentChargeService $paymentChargeService;
    private AuditService $auditService;
    private FulfillmentService $fulfillmentService;

    public function __construct()
    {
        $this->orderService = new OrderService();
        $this->receivableService = new ReceivableService();
        $this->paymentChargeService = new PaymentChargeService();
        $this->auditService = new AuditService();
        $this->fulfillmentService = new FulfillmentService();
    }

    public function index(): void
    {
        $search = trim((string) ($_GET['busca'] ?? ''));

        $this->view('erp::orders/index', [
            'tituloPagina' => 'Pedidos',
            'paginaAtiva' => 'pedidos',
            'orders' => $this->orderService->listForAdmin($search),
            'search' => $search,
            'isAdminRole' => $this->isAdminRole(),
            'msg' => trim((string) ($_GET['msg'] ?? '')),
            'tipo' => (string) ($_GET['tipo'] ?? 'ok'),
        ], 'erp::layouts/admin');
    }

    public function show(string $id): void
    {
        $orderId = $this->parseOrderId($id);
        if ($orderId === null) {
            $this->redirect('/admin/pedidos?' . http_build_query(['msg' => 'Pedido inválido.', 'tipo' => 'erro']));

            return;
        }

        $detail = $this->orderService->getDetailForAdmin($orderId);
        if ($detail === null) {
            $this->redirect('/admin/pedidos?' . http_build_query(['msg' => 'Pedido não encontrado.', 'tipo' => 'erro']));

            return;
        }

        $this->view('erp::orders/show', [
            'tituloPagina' => 'Pedido #' . $orderId,
            'paginaAtiva' => 'pedidos',
            'order' => $detail['order'],
            'items' => $detail['items'],
            'origin' => $detail['origin'],
            'originLabel' => $detail['origin_label'],
            'products' => $this->orderService->activeProducts(),
            'isAdminRole' => $this->isAdminRole(),
            'payment_charge' => $this->paymentChargeService->findForOrder($orderId),
            'cancelledByPixOverdue' => $this->auditService->hasActionForEntity(
                'cancel_pix_overdue_auto',
                'order',
                $orderId
            ),
            'pixRefundedReview' => $this->buildPixRefundedReviewContext($orderId),
            'msg' => trim((string) ($_GET['msg'] ?? '')),
            'tipo' => (string) ($_GET['tipo'] ?? 'ok'),
        ], 'erp::layouts/admin');
    }

    public function create(): void
    {
        $this->viewForm(['data' => [], 'errors' => []]);
    }

    public function store(): void
    {
        Csrf::requireValidPostOrAbort();
        $result = $this->orderService->create($_POST, $this->currentUserId());
        if ($result['success']) {
            $this->auditService->record('create', 'order', isset($result['id']) ? (int) $result['id'] : null);
            $this->redirect('/admin/pedidos?' . http_build_query(['msg' => $result['message'], 'tipo' => 'ok']));
        }

        $this->viewForm(['data' => $result['data'], 'errors' => $result['errors']]);
    }

    public function approve(string $id): void
    {
        Csrf::requireValidPostOrAbort();

        $orderId = $this->parseOrderId($id);
        if ($orderId === null) {
            $this->redirect('/admin/pedidos?' . http_build_query(['msg' => 'Pedido inválido.', 'tipo' => 'erro']));

            return;
        }

        $result = $this->orderService->approve($orderId, $this->currentUserId());
        if ($result['success']) {
            $this->auditService->record('approve', 'order', $orderId);
        }

        $this->redirectAfterAction($orderId, $result);
    }

    public function cancel(string $id): void
    {
        Csrf::requireValidPostOrAbort();

        $orderId = $this->parseOrderId($id);
        if ($orderId === null) {
            $this->redirect('/admin/pedidos?' . http_build_query(['msg' => 'Pedido inválido.', 'tipo' => 'erro']));

            return;
        }

        $result = $this->orderService->cancel($orderId, $this->currentUserId());
        if ($result['success']) {
            $this->auditService->record('cancel', 'order', $orderId);
        }

        $this->redirectAfterAction($orderId, $result);
    }

    public function reopen(string $id): void
    {
        Csrf::requireValidPostOrAbort();

        if (!$this->isAdminRole()) {
            $this->redirect('/admin/pedidos?' . http_build_query(['msg' => 'Somente administradores podem reabrir pedidos.', 'tipo' => 'erro']));

            return;
        }

        $orderId = $this->parseOrderId($id);
        if ($orderId === null) {
            $this->redirect('/admin/pedidos?' . http_build_query(['msg' => 'Pedido inválido.', 'tipo' => 'erro']));

            return;
        }

        $result = $this->orderService->reopenCancelled($orderId, $this->currentUserId());
        $this->redirectAfterAction($orderId, $result);
    }

    public function addItem(string $id): void
    {
        Csrf::requireValidPostOrAbort();
        $orderId = $this->parseOrderId($id);
        if ($orderId === null) {
            $this->redirect('/admin/pedidos?' . http_build_query(['msg' => 'Pedido inválido.', 'tipo' => 'erro']));

            return;
        }

        if (!$this->requireAdminForEdit($orderId)) {
            return;
        }

        $result = $this->orderService->addDraftItem($orderId, $_POST, $this->currentUserId());
        $this->redirectAfterAction($orderId, $result);
    }

    public function updateItem(string $id, string $itemId): void
    {
        Csrf::requireValidPostOrAbort();
        $orderId = $this->parseOrderId($id);
        $orderItemId = filter_var($itemId, FILTER_VALIDATE_INT);
        if ($orderId === null || $orderItemId === false || $orderItemId <= 0) {
            $this->redirect('/admin/pedidos?' . http_build_query(['msg' => 'Pedido ou item inválido.', 'tipo' => 'erro']));

            return;
        }

        if (!$this->requireAdminForEdit($orderId)) {
            return;
        }

        $result = $this->orderService->updateDraftItem($orderId, (int) $orderItemId, $_POST, $this->currentUserId());
        $this->redirectAfterAction($orderId, $result);
    }

    public function removeItem(string $id, string $itemId): void
    {
        Csrf::requireValidPostOrAbort();
        $orderId = $this->parseOrderId($id);
        $orderItemId = filter_var($itemId, FILTER_VALIDATE_INT);
        if ($orderId === null || $orderItemId === false || $orderItemId <= 0) {
            $this->redirect('/admin/pedidos?' . http_build_query(['msg' => 'Pedido ou item inválido.', 'tipo' => 'erro']));

            return;
        }

        if (!$this->requireAdminForEdit($orderId)) {
            return;
        }

        $result = $this->orderService->removeDraftItem($orderId, (int) $orderItemId, $this->currentUserId());
        $this->redirectAfterAction($orderId, $result);
    }

    public function updateShipping(string $id): void
    {
        Csrf::requireValidPostOrAbort();
        $orderId = $this->parseOrderId($id);
        if ($orderId === null) {
            $this->redirect('/admin/pedidos?' . http_build_query(['msg' => 'Pedido inválido.', 'tipo' => 'erro']));

            return;
        }

        if (!$this->requireAdminForEdit($orderId)) {
            return;
        }

        $result = $this->orderService->updateDraftShipping($orderId, $_POST, $this->currentUserId());
        $this->redirectAfterAction($orderId, $result);
    }

    public function updateNotes(string $id): void
    {
        Csrf::requireValidPostOrAbort();
        $orderId = $this->parseOrderId($id);
        if ($orderId === null) {
            $this->redirect('/admin/pedidos?' . http_build_query(['msg' => 'Pedido inválido.', 'tipo' => 'erro']));

            return;
        }

        if (!$this->requireAdminForEdit($orderId)) {
            return;
        }

        $result = $this->orderService->updateOperatorNotes($orderId, $_POST, $this->currentUserId());
        $this->redirectAfterAction($orderId, $result);
    }

    public function markPaid(string $id): void
    {
        Csrf::requireValidPostOrAbort();

        $orderId = $this->parseOrderId($id);
        if ($orderId === null) {
            $this->redirect('/admin/pedidos?' . http_build_query(['msg' => 'Pedido inválido.', 'tipo' => 'erro']));

            return;
        }

        $result = $this->receivableService->markAsPaid($orderId, $this->currentUserId());
        $this->redirectAfterAction($orderId, $result);
    }

    public function updateFulfillment(string $id): void
    {
        Csrf::requireValidPostOrAbort();

        $orderId = $this->parseOrderId($id);
        if ($orderId === null) {
            $this->redirect('/admin/pedidos?' . http_build_query(['msg' => 'Pedido inválido.', 'tipo' => 'erro']));

            return;
        }

        $order = $this->orderService->find($orderId);
        if ($order === null) {
            $this->redirect('/admin/pedidos?' . http_build_query(['msg' => 'Pedido não encontrado.', 'tipo' => 'erro']));

            return;
        }

        $status = (string) ($order['status'] ?? '');
        if ($status === 'draft') {
            if (!$this->requireAdminForEdit($orderId)) {
                return;
            }
            $result = $this->fulfillmentService->updateDraftDeliveryMethod($orderId, $_POST, $this->currentUserId());
        } elseif ($status === 'confirmed') {
            $result = $this->fulfillmentService->updateConfirmedFulfillment($orderId, $_POST, $this->currentUserId());
        } else {
            $result = ['success' => false, 'message' => 'Logística não pode ser alterada neste status.'];
        }

        $this->redirectAfterAction($orderId, $result);
    }

    /**
     * @param array{success: bool, message: string} $result
     */
    private function redirectAfterAction(int $orderId, array $result): void
    {
        $params = [
            'msg' => $result['message'],
            'tipo' => $result['success'] ? 'ok' : 'erro',
        ];

        if ((string) ($_POST['return_to'] ?? '') === 'detail') {
            $this->redirect('/admin/pedidos/' . $orderId . '?' . http_build_query($params));

            return;
        }

        $this->redirect('/admin/pedidos?' . http_build_query($params));
    }

    private function viewForm(array $data): void
    {
        $this->view('erp::orders/create', array_merge($data, [
            'tituloPagina' => 'Novo Pedido',
            'paginaAtiva' => 'pedidos',
            'clients' => $this->orderService->activeClients(),
            'products' => $this->orderService->activeProducts(),
        ]), 'erp::layouts/admin');
    }

    private function currentUserId(): ?int
    {
        $id = (int) ($_SESSION['admin_id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    private function parseOrderId(string $id): ?int
    {
        $orderId = filter_var($id, FILTER_VALIDATE_INT);

        return $orderId !== false && $orderId > 0 ? $orderId : null;
    }

    private function isAdminRole(): bool
    {
        return ($_SESSION['admin_role'] ?? '') === 'admin';
    }

    private function requireAdminForEdit(int $orderId): bool
    {
        if ($this->isAdminRole()) {
            return true;
        }

        $this->redirect('/admin/pedidos/' . $orderId . '?' . http_build_query([
            'msg' => 'Somente administradores podem editar pedidos.',
            'tipo' => 'erro',
        ]));

        return false;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildPixRefundedReviewContext(int $orderId): ?array
    {
        if (!$this->auditService->hasActionForEntity('gateway_pix_refunded_requires_review', 'order', $orderId)) {
            return null;
        }

        $audit = $this->auditService->latestActionForEntity('gateway_pix_refunded_requires_review', 'order', $orderId);
        $metadata = [];
        if (is_array($audit) && !empty($audit['metadata'])) {
            $decoded = json_decode((string) $audit['metadata'], true);
            if (is_array($decoded)) {
                $metadata = $decoded;
            }
        }

        return [
            'refunded_at' => (string) ($metadata['refunded_at'] ?? ($audit['created_at'] ?? '')),
            'financial_reverted' => $this->auditService->hasActionForEntity('payment_reverted_pix_refunded', 'order', $orderId),
            'order_status_at_refund' => (string) ($metadata['order_status'] ?? ''),
            'fulfillment_status_at_refund' => (string) ($metadata['fulfillment_status'] ?? ''),
            'stock_likely_reduced' => (bool) ($metadata['stock_likely_reduced'] ?? false),
            'gateway_event' => (string) ($metadata['gateway_event'] ?? 'PAYMENT_REFUNDED'),
        ];
    }
}
