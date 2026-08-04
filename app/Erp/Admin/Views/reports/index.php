<?php
use Erp\Finance\Support\PaymentStatusLabel;
use Erp\Stock\Support\StockMovementLabel;

$products = $products ?? [];
$commercialClients = $commercialClients ?? [];
$orders = $orders ?? [];
$orderRows = $orderRows ?? [];
$receivables = $receivables ?? ['count' => 0, 'totalAmount' => 0.0];
$openReceivableRows = $openReceivableRows ?? [];
$stockMovements = $stockMovements ?? [];
$period = $period ?? [
    'startDate' => date('Y-m-01'),
    'endDate' => date('Y-m-d'),
];
$startDate = (string) ($period['startDate'] ?? date('Y-m-01'));
$endDate = (string) ($period['endDate'] ?? date('Y-m-d'));
$periodQuery = ['start_date' => $startDate, 'end_date' => $endDate];
$statusLabels = ['draft' => 'Pendente', 'confirmed' => 'Faturado', 'cancelled' => 'Cancelado'];
?>
<form method="GET" action="<?= url('/admin/relatorios') ?>" class="card-admin card-body mb-4">
    <div class="filter-date-row">
        <label class="form-field-inline">
            Data inicial
            <input class="form-control" type="date" name="start_date" value="<?= e($startDate) ?>">
        </label>
        <label class="form-field-inline">
            Data final
            <input class="form-control" type="date" name="end_date" value="<?= e($endDate) ?>">
        </label>
        <div class="filter-date-row__actions">
            <button type="submit" class="btn btn-approve-solid btn-sm">Aplicar</button>
            <a class="btn btn-secondary btn-sm" href="<?= url('/admin/relatorios') ?>?<?= http_build_query(['start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d')]) ?>">Hoje</a>
            <a class="btn btn-secondary btn-sm" href="<?= url('/admin/relatorios') ?>?<?= http_build_query(['start_date' => date('Y-m-d', strtotime('-6 days')), 'end_date' => date('Y-m-d')]) ?>">Últimos 7 dias</a>
            <a class="btn btn-secondary btn-sm" href="<?= url('/admin/relatorios') ?>?<?= http_build_query(['start_date' => date('Y-m-01'), 'end_date' => date('Y-m-d')]) ?>">Este mês</a>
            <a class="btn btn-secondary btn-sm" href="<?= url('/admin/relatorios') ?>?<?= http_build_query(['start_date' => date('Y-m-01', strtotime('first day of last month')), 'end_date' => date('Y-m-t', strtotime('last month'))]) ?>">Mês passado</a>
        </div>
    </div>
</form>

<div class="d-flex flex-wrap justify-between align-center mb-3 gap-2">
    <p class="text-muted mb-0">Período analisado: <strong><?= e($startDate) ?></strong> até <strong><?= e($endDate) ?></strong>.</p>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-secondary btn-sm" href="<?= url('/admin/relatorios') ?>?<?= http_build_query(array_merge($periodQuery, ['export' => 'csv', 'type' => 'orders'])) ?>">Exportar pedidos CSV</a>
        <a class="btn btn-secondary btn-sm" href="<?= url('/admin/relatorios') ?>?<?= http_build_query(array_merge($periodQuery, ['export' => 'csv', 'type' => 'stock'])) ?>">Exportar estoque CSV</a>
    </div>
</div>

<div class="metric-grid mb-4">
    <div class="card-admin card-body"><div class="metric-number"><?= (int) ($products['total'] ?? 0) ?></div><div class="metric-label">Produtos</div></div>
    <div class="card-admin card-body"><div class="metric-number"><?= (int) ($commercialClients['active'] ?? 0) ?></div><div class="metric-label">Clientes ativos</div></div>
    <div class="card-admin card-body"><div class="metric-number"><?= (int) ($orders['confirmed'] ?? 0) ?></div><div class="metric-label">Pedidos faturados no período</div></div>
    <div class="card-admin card-body"><div class="metric-number"><?= formatarPreco((float) ($orders['totalAmount'] ?? 0)) ?></div><div class="metric-label">Faturamento confirmado</div></div>
    <div class="card-admin card-body"><div class="metric-number"><?= (int) ($receivables['count'] ?? 0) ?></div><div class="metric-label">Recebíveis em aberto</div></div>
    <div class="card-admin card-body"><div class="metric-number"><?= formatarPreco((float) ($receivables['totalAmount'] ?? 0)) ?></div><div class="metric-label">Valor a receber</div></div>
</div>

<section class="mb-4">
    <div class="d-flex flex-wrap justify-between align-center mb-2 gap-2">
        <h2 style="font-size:1.05rem;font-weight:500;margin:0">Recebíveis em aberto</h2>
        <a class="btn btn-secondary btn-sm" href="<?= url('/admin/contas-a-receber') ?>">Gerenciar contas a receber</a>
    </div>
    <div class="card-admin card-body" style="padding:0">
        <div class="table-responsive">
            <table class="table-admin">
                <thead><tr><th>ID</th><th>Cliente</th><th>Valor</th><th>Faturado em</th><th>Pagamento</th></tr></thead>
                <tbody>
                <?php if ($openReceivableRows === []): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Nenhum recebível em aberto.</td></tr>
                <?php else: ?>
                    <?php foreach ($openReceivableRows as $row): ?>
                        <?php $paymentStatus = (string) ($row['payment_status'] ?? PaymentStatusLabel::PENDING); ?>
                        <tr>
                            <td><a href="<?= url('/admin/pedidos/' . (int) ($row['id'] ?? 0)) ?>"><span class="order-meta-ref">#<?= (int) ($row['id'] ?? 0) ?></span></a></td>
                            <td><?= e((string) ($row['client_name'] ?? '')) ?></td>
                            <td class="text-right"><?= formatarPreco((float) ($row['total_amount'] ?? 0)) ?></td>
                            <td><?= e(function_exists('datetime_br') ? datetime_br((string) ($row['created_at'] ?? '')) : (string) ($row['created_at'] ?? '')) ?></td>
                            <td><span class="badge-pill <?= e(PaymentStatusLabel::badgeClass($paymentStatus)) ?>"><?= e(PaymentStatusLabel::resolve($paymentStatus)) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="mb-4">
    <h2 style="font-size:1.05rem;font-weight:500;margin:0 0 .75rem">Pedidos no período</h2>
    <div class="card-admin card-body" style="padding:0">
        <div class="table-responsive">
            <table class="table-admin">
                <thead><tr><th>ID</th><th>Cliente</th><th>Status</th><th>Total</th><th>Data</th></tr></thead>
                <tbody>
                <?php if ($orderRows === []): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Nenhum pedido no período.</td></tr>
                <?php else: ?>
                    <?php foreach ($orderRows as $order): ?>
                        <?php
                        $status = (string) ($order['status'] ?? 'draft');
                        $statusLabel = $statusLabels[$status] ?? $status;
                        ?>
                        <tr>
                            <td><span class="order-meta-ref">#<?= (int) ($order['id'] ?? 0) ?></span></td>
                            <td><?= e((string) ($order['client_name'] ?? '')) ?></td>
                            <td><?= badgeRecordStatus($status) ?></td>
                            <td class="text-right"><?= formatarPreco((float) ($order['total_amount'] ?? 0)) ?></td>
                            <td><?= e(function_exists('datetime_br') ? datetime_br((string) ($order['created_at'] ?? '')) : (string) ($order['created_at'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="mb-4">
    <h2 style="font-size:1.05rem;font-weight:500;margin:0 0 .75rem">Estoque baixo</h2>
    <div class="card-admin card-body" style="padding:0">
        <div class="table-responsive">
            <table class="table-admin">
                <thead><tr><th>Produto</th><th>Categoria</th><th>Estoque</th></tr></thead>
                <tbody>
                <?php if (($products['lowStock'] ?? []) === []): ?>
                    <tr><td colspan="3" class="text-center text-muted py-4">Nenhum produto em alerta.</td></tr>
                <?php else: ?>
                    <?php foreach ($products['lowStock'] as $product): ?>
                        <tr>
                            <td><?= e((string) $product['nome']) ?></td>
                            <td><?= e((string) $product['categoria']) ?></td>
                            <td><?= badgeEstoque((int) ($product['estoque_atual'] ?? 0)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section>
    <h2 style="font-size:1.05rem;font-weight:500;margin:0 0 .75rem">Movimentações de estoque no período</h2>
    <div class="card-admin card-body" style="padding:0">
        <div class="table-responsive">
            <table class="table-admin">
                <thead><tr><th>Produto</th><th>Tipo</th><th>Quantidade</th><th>Data</th></tr></thead>
                <tbody>
                <?php if ($stockMovements === []): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">Nenhuma movimentação registrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($stockMovements as $movement): ?>
                        <tr>
                            <td><?= e((string) ($movement['produto_nome'] ?? '')) ?></td>
                            <td><span class="badge-pill <?= e(StockMovementLabel::badgeClass($movement)) ?>"><?= e(StockMovementLabel::resolve($movement)) ?></span></td>
                            <td><?= (int) ($movement['quantidade'] ?? 0) ?></td>
                            <td><?= e(function_exists('datetime_br') ? datetime_br((string) ($movement['created_at'] ?? '')) : (string) ($movement['created_at'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
