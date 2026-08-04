<?php

declare(strict_types=1);

namespace Erp\Admin\Controllers;

use Core\Controller;
use Core\Exceptions\NotFoundException;
use Core\Exceptions\ValidationException;
use Core\Security\Csrf;
use Erp\Audit\Services\AuditService;
use Erp\Stock\Services\StockService;

/**
 * Orquestra movimentação de estoque no admin (POST apenas).
 * Regras de negócio permanecem no StockService.
 */
class AdminStockController extends Controller
{
    private StockService $stockService;
    private AuditService $auditService;

    public function __construct()
    {
        $this->stockService = new StockService();
        $this->auditService = new AuditService();
    }

    /** POST — espera `quantidade` (inteiro > 0), opcional `motivo`. */
    public function entrada(string $id): void
    {
        Csrf::requireValidPostOrAbort();

        $produtoId = $this->parseProdutoId($id);
        if ($produtoId === null) {
            $this->redirectListaProdutosErro();

            return;
        }

        $quantidade = $this->parseQuantidadePost();
        if ($quantidade === null) {
            $this->redirectListaProdutosErro();

            return;
        }

        try {
            $this->stockService->addStock(
                $produtoId,
                $quantidade,
                $this->currentAdminUserId(),
                $this->parseMotivoPost()
            );
            $this->auditService->record('stock_entry', 'product', $produtoId, ['quantity' => $quantidade]);
            $this->redirectListaProdutosSucesso();
        } catch (ValidationException | NotFoundException) {
            $this->redirectListaProdutosErro();
        }
    }

    /** POST — espera `quantidade` (inteiro > 0), opcional `motivo`. */
    public function saida(string $id): void
    {
        Csrf::requireValidPostOrAbort();

        $produtoId = $this->parseProdutoId($id);
        if ($produtoId === null) {
            $this->redirectListaProdutosErro();

            return;
        }

        $quantidade = $this->parseQuantidadePost();
        if ($quantidade === null) {
            $this->redirectListaProdutosErro();

            return;
        }

        try {
            $this->stockService->removeStock(
                $produtoId,
                $quantidade,
                $this->currentAdminUserId(),
                $this->parseMotivoPost()
            );
            $this->auditService->record('stock_exit', 'product', $produtoId, ['quantity' => $quantidade]);
            $this->redirectListaProdutosSucesso();
        } catch (ValidationException | NotFoundException) {
            $this->redirectListaProdutosErro();
        }
    }

    /** POST — espera `novo_estoque` (inteiro ≥ 0), opcional `motivo`. */
    public function ajuste(string $id): void
    {
        Csrf::requireValidPostOrAbort();

        $produtoId = $this->parseProdutoId($id);
        if ($produtoId === null) {
            $this->redirectListaProdutosErro();

            return;
        }

        $novoEstoque = $this->parseNovoEstoquePost();
        if ($novoEstoque === null) {
            $this->redirectListaProdutosErro();

            return;
        }

        try {
            $this->stockService->adjustStock(
                $produtoId,
                $novoEstoque,
                $this->currentAdminUserId(),
                $this->parseMotivoPost()
            );
            $this->auditService->record('stock_adjust', 'product', $produtoId, ['new_stock' => $novoEstoque]);
            $this->redirectListaProdutosSucesso();
        } catch (ValidationException | NotFoundException) {
            $this->redirectListaProdutosErro();
        }
    }

    private function redirectListaProdutosSucesso(): void
    {
        $this->redirect('/admin/produtos?' . http_build_query(['msg' => 'sucesso']));
    }

    private function redirectListaProdutosErro(): void
    {
        $this->redirect('/admin/produtos?' . http_build_query(['msg' => 'erro']));
    }

    private function parseProdutoId(string $id): ?int
    {
        $produtoId = filter_var($id, FILTER_VALIDATE_INT);

        return $produtoId !== false && $produtoId > 0 ? $produtoId : null;
    }

    /** Quantidade para entrada/saída: inteiro estritamente positivo. */
    private function parseQuantidadePost(): ?int
    {
        $raw = $_POST['quantidade'] ?? null;
        $quantidade = filter_var($raw, FILTER_VALIDATE_INT);

        return $quantidade !== false && $quantidade > 0 ? $quantidade : null;
    }

    /** Estoque alvo no ajuste: inteiro não negativo. */
    private function parseNovoEstoquePost(): ?int
    {
        $raw = $_POST['novo_estoque'] ?? null;
        $valor = filter_var($raw, FILTER_VALIDATE_INT);

        return $valor !== false && $valor >= 0 ? $valor : null;
    }

    private function parseMotivoPost(): ?string
    {
        if (!isset($_POST['motivo'])) {
            return null;
        }

        $motivo = trim((string) $_POST['motivo']);

        return $motivo === '' ? null : $motivo;
    }

    private function currentAdminUserId(): ?int
    {
        if (!isset($_SESSION['admin_id'])) {
            return null;
        }

        $uid = (int) $_SESSION['admin_id'];

        return $uid > 0 ? $uid : null;
    }
}
