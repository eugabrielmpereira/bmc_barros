<?php
// Conteúdo do painel ERP Admin.

$totalProdutos = (int) ($totalProdutos ?? 0);
$semEstoque = (int) ($semEstoque ?? 0);
$estoqueAlerta = (int) ($estoqueAlerta ?? 0);
$monthlyRevenue = (float) ($monthlyRevenue ?? 0);
$pendingOrders = (int) ($pendingOrders ?? 0);
$newClientsWeek = (int) ($newClientsWeek ?? 0);
$auditorias = (int) ($auditorias ?? 0);
$produtosAlerta = $produtosAlerta ?? [];
$movimentacoesRecentes = $movimentacoesRecentes ?? [];
$revenueTrend = $revenueTrend ?? [];
$maxTrend = 0.0;
foreach ($revenueTrend as $trendItem) {
    $maxTrend = max($maxTrend, (float) ($trendItem['total'] ?? 0));
}
?>

<div class="metric-grid mb-4">
    <div class="card-admin card-body">
        <div class="metric-number"><?= formatarPreco($monthlyRevenue) ?></div>
        <div class="metric-label">Faturamento Confirmado no Mês</div>
    </div>
    <div class="card-admin card-body border-success">
        <div class="metric-number"><?= $pendingOrders ?></div>
        <div class="metric-label">Pedidos Pendentes</div>
    </div>
    <div class="card-admin card-body border-warning">
        <div class="metric-number"><?= $estoqueAlerta ?></div>
        <div class="metric-label">Itens com Estoque Baixo</div>
    </div>
    <div class="card-admin card-body border-danger">
        <div class="metric-number"><?= $semEstoque ?></div>
        <div class="metric-label">Itens Esgotados</div>
    </div>
    <div class="card-admin card-body">
        <div class="metric-number"><?= $newClientsWeek ?></div>
        <div class="metric-label">Novos Clientes na Semana</div>
    </div>
    <div class="card-admin card-body">
        <div class="metric-number"><?= $totalProdutos ?></div>
        <div class="metric-label">Produtos Cadastrados</div>
    </div>
</div>

<section class="card-admin card-body mb-4">
    <div class="d-flex justify-between align-center mb-3">
        <h2>Tendência de Faturamento</h2>
        <span class="text-muted">Últimos 6 meses</span>
    </div>
    <div class="d-flex align-end gap-2" style="min-height:180px">
        <?php foreach ($revenueTrend as $trend): ?>
            <?php
            $total = (float) ($trend['total'] ?? 0);
            $height = $maxTrend > 0 ? max(8, (int) round(($total / $maxTrend) * 150)) : 8;
            ?>
            <div style="flex:1;text-align:center">
                <div title="<?= e(formatarPreco($total)) ?>" style="height:<?= $height ?>px;background:#315cfd;border-radius:8px 8px 0 0"></div>
                <small><?= e((string) ($trend['label'] ?? '')) ?></small>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php if (!empty($produtosAlerta)): ?>
    <div class="alerta alerta-aviso">
        ⚠️ <strong><?= count($produtosAlerta) ?> produto(s)</strong> com estoque baixo ou zerado. Verifique abaixo.
    </div>

    <div class="table-responsive">
        <table class="table-admin">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Categoria</th>
                    <th>Estoque</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produtosAlerta as $p): ?>
                <tr>
                    <td><?= e((string) $p['nome']) ?></td>
                    <td><?= e((string) $p['categoria']) ?></td>
                    <td><?= badgeEstoque((int) $p['estoque_atual']) ?></td>
                    <td>
                        <a href="<?= url('/admin/produtos') ?>?busca=<?= urlencode((string) $p['nome']) ?>" class="btn btn-secondary btn-sm">Ver</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="alerta alerta-ok">Todos os produtos com estoque adequado. Tudo certo por aqui!</div>
<?php endif; ?>

<section class="mt-4">
    <h2>Movimentações Recentes</h2>
    <div class="table-responsive">
        <table class="table-admin">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Tipo</th>
                    <th>Quantidade</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($movimentacoesRecentes === []): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">Nenhuma movimentação recente.</td></tr>
                <?php else: ?>
                    <?php foreach ($movimentacoesRecentes as $movimento): ?>
                        <tr>
                            <td><?= e((string) ($movimento['produto_nome'] ?? '')) ?></td>
                            <td><?= e((string) ($movimento['type'] ?? '')) ?></td>
                            <td><?= (int) ($movimento['quantity'] ?? 0) ?></td>
                            <td><?= e(function_exists('datetime_br') ? datetime_br((string) ($movimento['created_at'] ?? '')) : (string) ($movimento['created_at'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
