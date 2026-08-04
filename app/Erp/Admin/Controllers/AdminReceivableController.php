<?php

declare(strict_types=1);

namespace Erp\Admin\Controllers;

use Core\Controller;
use Core\Security\Csrf;
use Erp\Finance\Services\ReceivableService;

final class AdminReceivableController extends Controller
{
    private ReceivableService $receivableService;

    public function __construct()
    {
        $this->receivableService = new ReceivableService();
    }

    public function index(): void
    {
        $search = trim((string) ($_GET['busca'] ?? ''));
        $summary = $this->receivableService->openSummary();

        $this->view('erp::finance/receivables/index', [
            'tituloPagina' => 'Contas a receber',
            'paginaAtiva' => 'contas-a-receber',
            'receivables' => $this->receivableService->listOpen($search),
            'summary' => $summary,
            'search' => $search,
            'msg' => trim((string) ($_GET['msg'] ?? '')),
            'tipo' => (string) ($_GET['tipo'] ?? 'ok'),
        ], 'erp::layouts/admin');
    }

    public function markPaid(string $id): void
    {
        Csrf::requireValidPostOrAbort();

        $orderId = filter_var($id, FILTER_VALIDATE_INT);
        if ($orderId === false || $orderId <= 0) {
            $this->redirect('/admin/contas-a-receber?' . http_build_query(['msg' => 'Pedido inválido.', 'tipo' => 'erro']));

            return;
        }

        $userId = (int) ($_SESSION['admin_id'] ?? 0);
        $result = $this->receivableService->markAsPaid((int) $orderId, $userId > 0 ? $userId : null);

        $returnTo = (string) ($_POST['return_to'] ?? '');
        if ($returnTo === 'detail') {
            $this->redirect('/admin/pedidos/' . $orderId . '?' . http_build_query([
                'msg' => $result['message'],
                'tipo' => $result['success'] ? 'ok' : 'erro',
            ]));

            return;
        }

        $this->redirect('/admin/contas-a-receber?' . http_build_query([
            'msg' => $result['message'],
            'tipo' => $result['success'] ? 'ok' : 'erro',
        ]));
    }
}
