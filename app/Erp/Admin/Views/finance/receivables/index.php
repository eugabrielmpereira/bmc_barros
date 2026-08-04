<?php
use Erp\Finance\Support\PaymentStatusLabel;

$receivables = $receivables ?? [];
$summary = $summary ?? ['count' => 0, 'totalAmount' => 0.0];
$search = $search ?? '';
$msg = $msg ?? '';
$tipo = $tipo ?? 'ok';
$openCount = (int) ($summary['count'] ?? 0);
$openTotal = (float) ($summary['totalAmount'] ?? 0);
$subtitle = $search !== ''
    ? 'Busca: "' . $search . '" · ' . count($receivables) . ' resultado(s)'
    : $openCount . ' título(s) em aberto · total ' . formatarPreco($openTotal);
?>
<?php partial('page-header', [
    'subtitle' => $subtitle,
]); ?>

<?php if ($msg !== ''): ?><div class="alerta alerta-<?= e($tipo) ?>"><?= e($msg) ?></div><?php endif; ?>

<div class="metric-grid mb-4">
    <div class="card-admin card-body">
        <div class="metric-number"><?= $openCount ?></div>
        <div class="metric-label">Recebíveis em aberto</div>
    </div>
    <div class="card-admin card-body">
        <div class="metric-number"><?= formatarPreco($openTotal) ?></div>
        <div class="metric-label">Valor total a receber</div>
    </div>
</div>

<?php partial('search-bar', [
    'action' => url('/admin/contas-a-receber'),
    'value' => $search,
    'placeholder' => 'Buscar por ID, cliente ou referência...',
]); ?>

<div class="table-responsive">
    <table class="table-admin">
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Referência</th>
                <th class="text-right">Valor</th>
                <th>Faturado em</th>
                <th>Pagamento</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($receivables === []): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Nenhum recebível em aberto.</td></tr>
        <?php else: ?>
            <?php foreach ($receivables as $row): ?>
                <?php
                $orderId = (int) ($row['id'] ?? 0);
                $paymentStatus = (string) ($row['payment_status'] ?? PaymentStatusLabel::PENDING);
                ?>
                <tr>
                    <td><span class="order-meta-ref">#<?= $orderId ?></span></td>
                    <td><strong><?= e((string) ($row['client_name'] ?? '')) ?></strong></td>
                    <td><?= !empty($row['public_reference']) ? e((string) $row['public_reference']) : '—' ?></td>
                    <td class="text-right"><strong><?= formatarPreco((float) ($row['total_amount'] ?? 0)) ?></strong></td>
                    <td><?= e(function_exists('datetime_br') ? datetime_br((string) ($row['created_at'] ?? '')) : (string) ($row['created_at'] ?? '')) ?></td>
                    <td>
                        <span class="badge-pill <?= e(PaymentStatusLabel::badgeClass($paymentStatus)) ?>">
                            <i class="ti <?= e(PaymentStatusLabel::icon($paymentStatus)) ?>" aria-hidden="true"></i>
                            <?= e(PaymentStatusLabel::resolve($paymentStatus)) ?>
                        </span>
                    </td>
                    <td>
                        <div class="td-actions">
                            <form method="POST" action="<?= url('/admin/contas-a-receber/' . $orderId . '/baixa') ?>" onsubmit="return confirm('Registrar pagamento deste pedido?')">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-approve-solid btn-sm btn-icon" title="Registrar pagamento" aria-label="Registrar pagamento">
                                    <i class="ti ti-cash" aria-hidden="true"></i>
                                </button>
                            </form>
                            <a href="<?= url('/admin/pedidos/' . $orderId) ?>" class="btn btn-outline-info btn-sm btn-icon" title="Ver pedido" aria-label="Ver pedido">
                                <i class="ti ti-eye" aria-hidden="true"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
