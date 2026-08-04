<?php
use Erp\Finance\Support\PaymentStatusLabel;
use Erp\Fulfillment\Support\DeliveryMethod;
use Erp\Fulfillment\Support\DeliveryMethodLabel;
use Erp\Fulfillment\Support\FulfillmentStatus;
use Erp\Fulfillment\Support\FulfillmentStatusLabel;
use Erp\Orders\Services\OrderService;

$orders = $orders ?? [];
$search = $search ?? '';
$isAdminRole = (bool) ($isAdminRole ?? false);
$msg = $msg ?? '';
$tipo = $tipo ?? 'ok';
$statusLabels = [
    'draft' => 'Pendente',
    'confirmed' => 'Faturado',
    'cancelled' => 'Cancelado',
];
$statusBadgeClass = [
    'draft' => 'badge-pill-pending',
    'confirmed' => 'badge-pill-confirmed',
    'cancelled' => 'badge-pill-cancelled',
];
$statusIcons = [
    'draft' => 'ti-clock',
    'confirmed' => 'ti-check',
    'cancelled' => 'ti-x',
];
$pendingCount = count(array_filter($orders, static fn (array $o): bool => ($o['status'] ?? '') === 'draft'));
$subtitle = $search !== ''
    ? 'Busca: "' . $search . '" · ' . count($orders) . ' resultado(s)'
    : count($orders) . ' pedido(s) · ' . $pendingCount . ' pendente(s)';
?>
<?php partial('page-header', [
    'subtitle' => $subtitle,
    'actionUrl' => url('/admin/pedidos/criar'),
    'actionLabel' => 'Novo pedido',
    'actionIcon' => 'ti-plus',
]); ?>

<?php if ($msg !== ''): ?><div class="alerta alerta-<?= e($tipo) ?>"><?= e($msg) ?></div><?php endif; ?>

<?php partial('search-bar', [
    'action' => url('/admin/pedidos'),
    'value' => $search,
    'placeholder' => 'Buscar por ID, cliente ou referência...',
]); ?>

<div class="table-responsive">
    <table class="table-admin">
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Origem</th>
                <th>Status</th>
                <th>Pagamento</th>
                <th>Expedição</th>
                <th class="text-right">Total</th>
                <th>Data</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($orders === []): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">Nenhum pedido encontrado.</td></tr>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <?php
                $orderId = (int) ($order['id'] ?? 0);
                $status = (string) ($order['status'] ?? 'draft');
                $statusLabel = $statusLabels[$status] ?? $status;
                $origin = OrderService::resolveOrigin($order);
                $originLabel = $origin === 'online' ? 'Site' : 'Admin';
                $originIcon = $origin === 'online' ? 'ti-world' : 'ti-settings';
                $isPending = $status === 'draft';
                $paymentStatus = (string) ($order['payment_status'] ?? PaymentStatusLabel::NOT_APPLICABLE);
                $fulfillmentStatus = (string) ($order['fulfillment_status'] ?? FulfillmentStatus::PENDING);
                $deliveryMethod = DeliveryMethod::parse((string) ($order['delivery_method'] ?? DeliveryMethod::DELIVERY));
                ?>
                <tr class="<?= $isPending ? 'order-pending-row' : '' ?>">
                    <td><span class="order-meta-ref">#<?= $orderId ?></span></td>
                    <td><strong><?= e((string) $order['client_name']) ?></strong></td>
                    <td><span class="badge-pill badge-pill-origin"><i class="ti <?= e($originIcon) ?>" aria-hidden="true"></i> <?= e($originLabel) ?></span></td>
                    <td><span class="badge-pill <?= e($statusBadgeClass[$status] ?? 'badge-pill-origin') ?>"><i class="ti <?= e($statusIcons[$status] ?? 'ti-info-circle') ?>" aria-hidden="true"></i> <?= e($statusLabel) ?></span></td>
                    <td>
                        <?php if ($status === 'confirmed' || $paymentStatus === PaymentStatusLabel::PAID): ?>
                            <span class="badge-pill <?= e(PaymentStatusLabel::badgeClass($paymentStatus)) ?>">
                                <i class="ti <?= e(PaymentStatusLabel::icon($paymentStatus)) ?>" aria-hidden="true"></i>
                                <?= e(PaymentStatusLabel::resolve($paymentStatus)) ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($status === 'confirmed'): ?>
                            <span class="badge-pill <?= e(FulfillmentStatusLabel::badgeClass($fulfillmentStatus)) ?>">
                                <i class="ti <?= e(FulfillmentStatusLabel::icon($fulfillmentStatus)) ?>" aria-hidden="true"></i>
                                <?= e(FulfillmentStatusLabel::resolve($fulfillmentStatus)) ?>
                            </span>
                            <br><small class="text-muted"><?= e(DeliveryMethodLabel::resolve($deliveryMethod)) ?></small>
                        <?php elseif ($status === 'draft'): ?>
                            <span class="badge-pill badge-pill-origin">
                                <i class="ti <?= e(DeliveryMethodLabel::icon($deliveryMethod)) ?>" aria-hidden="true"></i>
                                <?= e(DeliveryMethodLabel::resolve($deliveryMethod)) ?>
                            </span>
                            <br><small class="text-muted">Expedição após aprovar</small>
                        <?php elseif ($status === 'cancelled'): ?>
                            <span class="text-muted">—</span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right"><strong><?= formatarPreco((float) $order['total_amount']) ?></strong></td>
                    <td><?= e(function_exists('datetime_br') ? datetime_br((string) ($order['created_at'] ?? '')) : (string) ($order['created_at'] ?? '')) ?></td>
                    <td>
                        <div class="td-actions">
                            <?php if ($isPending): ?>
                                <form method="POST" action="<?= url('/admin/pedidos/' . $orderId . '/aprovar') ?>" onsubmit="return confirm('Aprovar este pedido e baixar o estoque?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-approve-solid btn-sm btn-icon" title="Aprovar pedido" aria-label="Aprovar pedido">
                                        <i class="ti ti-check" aria-hidden="true"></i>
                                    </button>
                                </form>
                                <form method="POST" action="<?= url('/admin/pedidos/' . $orderId . '/cancelar') ?>" onsubmit="return confirm('Cancelar este pedido?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-outline-danger btn-sm btn-icon" title="Cancelar pedido" aria-label="Cancelar pedido">
                                        <i class="ti ti-x" aria-hidden="true"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <a href="<?= url('/admin/pedidos/' . $orderId) ?>" class="btn btn-outline-info btn-sm btn-icon" title="<?= $isPending && $isAdminRole ? 'Editar pedido' : 'Ver detalhes' ?>" aria-label="<?= $isPending && $isAdminRole ? 'Editar pedido' : 'Ver detalhes' ?>">
                                <i class="ti <?= $isPending && $isAdminRole ? 'ti-edit' : 'ti-eye' ?>" aria-hidden="true"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
