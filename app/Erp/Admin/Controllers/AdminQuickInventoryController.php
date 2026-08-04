<?php

declare(strict_types=1);

namespace Erp\Admin\Controllers;

use Core\Controller;
use Core\Security\Csrf;
use Erp\Audit\Services\AuditService;
use Erp\Products\Services\ProductService;
use Erp\Stock\Services\StockService;
use Throwable;

final class AdminQuickInventoryController extends Controller
{
    public function __construct(
        private ?ProductService $productService = null,
        private ?StockService $stockService = null,
        private ?AuditService $auditService = null
    ) {
        $this->productService ??= new ProductService();
        $this->stockService ??= new StockService();
        $this->auditService ??= new AuditService();
    }

    public function index(): void
    {
        $this->view('erp::stock/quick-inventory', [
            'tituloPagina' => 'Inventário Rápido',
            'paginaAtiva' => 'inventario',
            'produtos' => $this->productService->listForAdmin(trim((string) ($_GET['busca'] ?? ''))),
            'busca' => trim((string) ($_GET['busca'] ?? '')),
            'msg' => trim((string) ($_GET['msg'] ?? '')),
            'tipo' => (string) ($_GET['tipo'] ?? 'ok'),
        ], 'erp::layouts/admin');
    }

    public function update(): void
    {
        Csrf::requireValidPostOrAbort();

        $stocks = isset($_POST['stocks']) && is_array($_POST['stocks']) ? $_POST['stocks'] : [];
        $reason = trim((string) ($_POST['reason'] ?? ''));

        try {
            $result = $this->stockService->adjustMany($stocks, $this->currentUserId(), $reason);
            $this->auditService->record('bulk_stock_adjust', 'product', null, $result);
            $this->redirect('/admin/inventario-rapido?' . http_build_query([
                'msg' => $result['updated'] . ' produto(s) atualizado(s).',
                'tipo' => 'ok',
            ]));
        } catch (Throwable) {
            $this->redirect('/admin/inventario-rapido?' . http_build_query([
                'msg' => 'Não foi possível salvar os ajustes de inventário.',
                'tipo' => 'erro',
            ]));
        }
    }

    private function currentUserId(): ?int
    {
        $id = (int) ($_SESSION['admin_id'] ?? 0);

        return $id > 0 ? $id : null;
    }
}
