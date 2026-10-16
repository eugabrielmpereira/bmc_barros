<?php

declare(strict_types=1);

use Erp\Admin\Controllers\AdminAuditController;
use Erp\Admin\Controllers\AdminCommercialClientController;
use Erp\Admin\Controllers\AdminController;
use Erp\Admin\Controllers\AdminOrderController;
use Erp\Admin\Controllers\AdminCategoryController;
use Erp\Admin\Controllers\AdminDeliveryZoneController;
use Erp\Admin\Controllers\AdminProductController;
use Erp\Admin\Controllers\AdminQuickInventoryController;
use Erp\Admin\Controllers\AdminReceivableController;
use Erp\Admin\Controllers\AdminReportController;
use Erp\Admin\Controllers\AdminStockController;
use Erp\Admin\Controllers\AdminStockHistoryController;
use Erp\Admin\Controllers\AdminUserController;
use Erp\Finance\Controllers\AsaasWebhookController;

return [
    ['method' => 'GET', 'path' => '/admin/login', 'handler' => [AdminController::class, 'login'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/login', 'handler' => [AdminController::class, 'authenticate'], 'middleware' => 'admin'],
    ['method' => 'GET', 'path' => '/admin/dashboard', 'handler' => [AdminController::class, 'dashboard'], 'middleware' => 'admin'],
    ['method' => 'GET', 'path' => '/admin/logout', 'handler' => [AdminController::class, 'logout'], 'middleware' => 'admin'],
    ['method' => 'GET', 'path' => '/admin/estoque', 'handler' => [AdminStockHistoryController::class, 'index'], 'middleware' => 'admin'],
    ['method' => 'GET', 'path' => '/admin/inventario-rapido', 'handler' => [AdminQuickInventoryController::class, 'index'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/inventario-rapido', 'handler' => [AdminQuickInventoryController::class, 'update'], 'middleware' => 'admin'],

    ['method' => 'GET', 'path' => '/admin/frete', 'handler' => [AdminDeliveryZoneController::class, 'index'], 'middleware' => 'admin'],
    ['method' => 'GET', 'path' => '/admin/frete/criar', 'handler' => [AdminDeliveryZoneController::class, 'create'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/frete/criar', 'handler' => [AdminDeliveryZoneController::class, 'store'], 'middleware' => 'admin'],
    ['method' => 'GET', 'path' => '/admin/frete/editar/:id', 'handler' => [AdminDeliveryZoneController::class, 'edit'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/frete/editar/:id', 'handler' => [AdminDeliveryZoneController::class, 'update'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/frete/deletar/:id', 'handler' => [AdminDeliveryZoneController::class, 'delete'], 'middleware' => 'admin'],
    ['method' => 'GET', 'path' => '/admin/categorias', 'handler' => [AdminCategoryController::class, 'index'], 'middleware' => 'admin'],
    ['method' => 'GET', 'path' => '/admin/categorias/criar', 'handler' => [AdminCategoryController::class, 'create'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/categorias/criar', 'handler' => [AdminCategoryController::class, 'store'], 'middleware' => 'admin'],
    ['method' => 'GET', 'path' => '/admin/categorias/editar/:id', 'handler' => [AdminCategoryController::class, 'edit'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/categorias/editar/:id', 'handler' => [AdminCategoryController::class, 'update'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/categorias/deletar/:id', 'handler' => [AdminCategoryController::class, 'delete'], 'middleware' => 'admin'],

    ['method' => 'GET', 'path' => '/admin/produtos', 'handler' => [AdminProductController::class, 'index'], 'middleware' => 'admin'],
    ['method' => 'GET', 'path' => '/admin/produtos/criar', 'handler' => [AdminProductController::class, 'create'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/produtos/criar', 'handler' => [AdminProductController::class, 'store'], 'middleware' => 'admin'],
    ['method' => 'GET', 'path' => '/admin/produtos/editar/:id', 'handler' => [AdminProductController::class, 'edit'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/produtos/editar/:id', 'handler' => [AdminProductController::class, 'update'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/produtos/deletar/:id', 'handler' => [AdminProductController::class, 'delete'], 'middleware' => 'admin'],

    ['method' => 'POST', 'path' => '/admin/produtos/:id/entrada', 'handler' => [AdminStockController::class, 'entrada'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/produtos/:id/saida', 'handler' => [AdminStockController::class, 'saida'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/produtos/:id/ajuste', 'handler' => [AdminStockController::class, 'ajuste'], 'middleware' => 'admin'],

    ['method' => 'GET', 'path' => '/admin/usuarios', 'handler' => [AdminUserController::class, 'index'], 'middleware' => 'admin'],
    ['method' => 'GET', 'path' => '/admin/usuarios/criar', 'handler' => [AdminUserController::class, 'create'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/usuarios/criar', 'handler' => [AdminUserController::class, 'store'], 'middleware' => 'admin'],
    ['method' => 'GET', 'path' => '/admin/usuarios/editar/:id', 'handler' => [AdminUserController::class, 'edit'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/usuarios/editar/:id', 'handler' => [AdminUserController::class, 'update'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/usuarios/deletar/:id', 'handler' => [AdminUserController::class, 'delete'], 'middleware' => 'admin'],

    ['method' => 'GET', 'path' => '/admin/clientes', 'handler' => [AdminCommercialClientController::class, 'index'], 'middleware' => 'admin'],
    ['method' => 'GET', 'path' => '/admin/clientes/criar', 'handler' => [AdminCommercialClientController::class, 'create'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/clientes/criar', 'handler' => [AdminCommercialClientController::class, 'store'], 'middleware' => 'admin'],
    ['method' => 'GET', 'path' => '/admin/clientes/editar/:id', 'handler' => [AdminCommercialClientController::class, 'edit'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/clientes/editar/:id', 'handler' => [AdminCommercialClientController::class, 'update'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/clientes/deletar/:id', 'handler' => [AdminCommercialClientController::class, 'delete'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/clientes/:id/desativar', 'handler' => [AdminCommercialClientController::class, 'deactivate'], 'middleware' => 'admin'],

    ['method' => 'GET', 'path' => '/admin/pedidos', 'handler' => [AdminOrderController::class, 'index'], 'middleware' => 'admin'],
    ['method' => 'GET', 'path' => '/admin/pedidos/criar', 'handler' => [AdminOrderController::class, 'create'], 'middleware' => 'admin'],
    ['method' => 'GET', 'path' => '/admin/pedidos/:id', 'handler' => [AdminOrderController::class, 'show'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/pedidos/criar', 'handler' => [AdminOrderController::class, 'store'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/pedidos/:id/aprovar', 'handler' => [AdminOrderController::class, 'approve'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/pedidos/:id/cancelar', 'handler' => [AdminOrderController::class, 'cancel'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/pedidos/:id/reabrir', 'handler' => [AdminOrderController::class, 'reopen'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/pedidos/:id/itens', 'handler' => [AdminOrderController::class, 'addItem'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/pedidos/:id/itens/:itemId', 'handler' => [AdminOrderController::class, 'updateItem'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/pedidos/:id/itens/:itemId/remover', 'handler' => [AdminOrderController::class, 'removeItem'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/pedidos/:id/endereco', 'handler' => [AdminOrderController::class, 'updateShipping'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/pedidos/:id/notas', 'handler' => [AdminOrderController::class, 'updateNotes'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/pedidos/:id/baixa-pagamento', 'handler' => [AdminOrderController::class, 'markPaid'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/pedidos/:id/logistica', 'handler' => [AdminOrderController::class, 'updateFulfillment'], 'middleware' => 'admin'],

    ['method' => 'GET', 'path' => '/admin/contas-a-receber', 'handler' => [AdminReceivableController::class, 'index'], 'middleware' => 'admin'],
    ['method' => 'POST', 'path' => '/admin/contas-a-receber/:id/baixa', 'handler' => [AdminReceivableController::class, 'markPaid'], 'middleware' => 'admin'],

    ['method' => 'GET', 'path' => '/admin/relatorios', 'handler' => [AdminReportController::class, 'index'], 'middleware' => 'admin'],
    ['method' => 'GET', 'path' => '/admin/auditoria', 'handler' => [AdminAuditController::class, 'index'], 'middleware' => 'admin'],

    ['method' => 'POST', 'path' => '/webhooks/asaas', 'handler' => [AsaasWebhookController::class, 'handle'], 'middleware' => 'web'],
];
