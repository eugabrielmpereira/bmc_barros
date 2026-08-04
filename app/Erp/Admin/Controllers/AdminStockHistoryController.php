<?php

declare(strict_types=1);

namespace Erp\Admin\Controllers;

use Core\Controller;
use Erp\Stock\Services\StockHistoryService;

/**
 * Exibe o histórico administrativo de movimentações de estoque.
 */
class AdminStockHistoryController extends Controller
{
    private StockHistoryService $stockHistoryService;

    public function __construct()
    {
        $this->stockHistoryService = new StockHistoryService();
    }

    public function index(): void
    {
        $this->view('erp::stock/index', [
            'tituloPagina' => 'Histórico de Estoque',
            'paginaAtiva' => 'estoque',
            'movimentos' => $this->stockHistoryService->getLatest(),
        ], 'erp::layouts/admin');
    }
}
