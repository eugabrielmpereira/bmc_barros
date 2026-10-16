<?php
use Erp\Finance\Support\PaymentStatusLabel;
use Erp\Fulfillment\Support\DeliveryMethod;
use Erp\Fulfillment\Support\DeliveryMethodLabel;
use Erp\Fulfillment\Support\FulfillmentStatus;
use Erp\Fulfillment\Support\FulfillmentStatusLabel;
use Erp\Orders\Services\OrderService;
use Shared\Support\BrazilianAddress;

$order = $order ?? [];
$items = $items ?? [];
$products = $products ?? [];
$isAdminRole = (bool) ($isAdminRole ?? false);
$canEdit = $isAdminRole && (string) ($order['status'] ?? '') === 'draft';
$origin = $origin ?? 'admin';
$originLabel = $originLabel ?? 'Admin ERP';
$msg = $msg ?? '';
$tipo = $tipo ?? 'ok';
$orderId = (int) ($order['id'] ?? 0);
$clientId = (int) ($order['commercial_client_id'] ?? 0);
$status = (string) ($order['status'] ?? 'draft');
$statusLabels = ['draft' => 'Pendente', 'confirmed' => 'Faturado', 'cancelled' => 'Cancelado'];
$statusBadgeClass = ['draft' => 'badge-pill-pending', 'confirmed' => 'badge-pill-confirmed', 'cancelled' => 'badge-pill-cancelled'];
$statusIcons = ['draft' => 'ti-clock', 'confirmed' => 'ti-check', 'cancelled' => 'ti-x'];
$paymentStatus = (string) ($order['payment_status'] ?? PaymentStatusLabel::NOT_APPLICABLE);
$canMarkPaid = $status === 'confirmed' && $paymentStatus === PaymentStatusLabel::PENDING;
$paymentCharge = $payment_charge ?? null;
$cancelledByPixOverdue = (bool) ($cancelledByPixOverdue ?? false);
$pixRefundedReview = is_array($pixRefundedReview ?? null) ? $pixRefundedReview : null;
$gatewayPixPaid = is_array($paymentCharge)
    && \Erp\Finance\Services\PaymentChargeService::isPaidStatus((string) ($paymentCharge['status'] ?? ''));

$internalNotes = trim((string) ($order['internal_notes'] ?? ''));
if ($internalNotes === '' && $origin === 'online') {
    $legacyNotes = (string) ($order['notes'] ?? '');
    if (str_starts_with($legacyNotes, OrderService::ONLINE_ORDER_NOTE_PREFIX)) {
        $internalNotes = OrderService::ONLINE_ORDER_NOTE_PREFIX;
    }
}
$customerNotes = trim((string) ($order['customer_notes'] ?? ''));
if ($customerNotes === '' && $origin === 'online' && $internalNotes !== '') {
    $legacyNotes = (string) ($order['notes'] ?? '');
    if (str_starts_with($legacyNotes, OrderService::ONLINE_ORDER_NOTE_PREFIX)) {
        $customerNotes = ltrim(trim(substr($legacyNotes, strlen(OrderService::ONLINE_ORDER_NOTE_PREFIX))), "\r\n");
    }
}
$operatorNotes = trim((string) ($order['notes'] ?? ''));
if ($origin === 'online' && ($internalNotes !== '' || $customerNotes !== '')) {
    $operatorNotes = '';
    $legacyOnly = (string) ($order['notes'] ?? '');
    if ($legacyOnly !== '' && !str_starts_with($legacyOnly, OrderService::ONLINE_ORDER_NOTE_PREFIX)) {
        $operatorNotes = $legacyOnly;
    }
}
$shippingLine = BrazilianAddress::formatSingleLine($order);
$deliveryMethod = DeliveryMethod::parse((string) ($order['delivery_method'] ?? DeliveryMethod::DELIVERY));
$fulfillmentStatus = (string) ($order['fulfillment_status'] ?? FulfillmentStatus::PENDING);
$canManageFulfillment = $status === 'confirmed';
$canEditDeliveryMethod = $canEdit;
$fulfillmentOptions = FulfillmentStatus::allowedForDeliveryMethod($deliveryMethod);
$clientInitials = '';
$clientName = (string) ($order['client_name'] ?? '');
if ($clientName !== '') {
    $parts = preg_split('/\s+/', trim($clientName)) ?: [];
    $clientInitials = strtoupper(substr((string) ($parts[0] ?? ''), 0, 1) . substr((string) ($parts[1] ?? ''), 0, 1));
}
?>
<div class="order-detail-header">
    <div class="order-detail-title">
        <a href="<?= url('/admin/pedidos') ?>" class="btn btn-secondary btn-sm"><i class="ti ti-arrow-left" aria-hidden="true"></i> Pedidos</a>
        <h2>Pedido #<?= $orderId ?></h2>
        <?php if (!empty($order['public_reference'])): ?>
            <span class="order-meta-ref"><?= e((string) $order['public_reference']) ?></span>
        <?php endif; ?>
        <span class="badge-pill <?= e($statusBadgeClass[$status] ?? 'badge-pill-origin') ?>">
            <i class="ti <?= e($statusIcons[$status] ?? 'ti-info-circle') ?>" aria-hidden="true"></i>
            <?= e($statusLabels[$status] ?? $status) ?>
        </span>
        <span class="badge-pill badge-pill-origin"><i class="ti ti-<?= $origin === 'online' ? 'world' : 'settings' ?>" aria-hidden="true"></i> <?= e($originLabel) ?></span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($status === 'draft'): ?>
            <form method="POST" action="<?= url('/admin/pedidos/' . $orderId . '/aprovar') ?>" onsubmit="return confirm('Aprovar este pedido e baixar o estoque?')">
                <?= csrf_field() ?>
                <input type="hidden" name="return_to" value="detail">
                <button type="submit" class="btn btn-approve-solid"><i class="ti ti-check" aria-hidden="true"></i> Aprovar pedido</button>
            </form>
        <?php endif; ?>
        <?php if ($status !== 'cancelled'): ?>
            <form method="POST" action="<?= url('/admin/pedidos/' . $orderId . '/cancelar') ?>" onsubmit="return confirm('Cancelar este pedido? Pedidos faturados terão estoque estornado.')">
                <?= csrf_field() ?>
                <input type="hidden" name="return_to" value="detail">
                <button type="submit" class="btn btn-outline-danger"><i class="ti ti-x" aria-hidden="true"></i> Cancelar pedido</button>
            </form>
        <?php endif; ?>
        <?php if ($status === 'cancelled' && $isAdminRole): ?>
            <form method="POST" action="<?= url('/admin/pedidos/' . $orderId . '/reabrir') ?>" onsubmit="return confirm('Reabrir este pedido como pendente?')">
                <?= csrf_field() ?>
                <input type="hidden" name="return_to" value="detail">
                <button type="submit" class="btn btn-outline-info"><i class="ti ti-refresh" aria-hidden="true"></i> Reabrir pedido</button>
            </form>
        <?php endif; ?>
        <?php if ($canMarkPaid): ?>
            <form method="POST" action="<?= url('/admin/pedidos/' . $orderId . '/baixa-pagamento') ?>" onsubmit="return confirm('Registrar pagamento deste pedido?')">
                <?= csrf_field() ?>
                <input type="hidden" name="return_to" value="detail">
                <button type="submit" class="btn btn-approve-solid"><i class="ti ti-cash" aria-hidden="true"></i> Registrar pagamento</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($msg !== ''): ?><div class="alerta alerta-<?= e($tipo) ?>"><?= e($msg) ?></div><?php endif; ?>

<?php if ($status === 'cancelled' && $cancelledByPixOverdue): ?>
    <div class="alerta alerta-neutral">
        Este pedido foi cancelado automaticamente porque o Pix venceu sem pagamento. A reserva de estoque foi liberada.
    </div>
<?php endif; ?>

<?php if ($pixRefundedReview !== null): ?>
    <div class="alerta alerta-aviso">
        <div>
            <p class="mb-1"><strong>Ação pendente:</strong> Pagamento Pix estornado pelo gateway<?php if ($pixRefundedReview['refunded_at'] !== ''): ?> em <?= e($pixRefundedReview['refunded_at']) ?><?php endif; ?>.</p>
            <?php if ($pixRefundedReview['financial_reverted']): ?>
                <p class="mb-1">Status financeiro já revertido para <?= e(PaymentStatusLabel::resolve(PaymentStatusLabel::PENDING)) ?>. Recebível reaberto em contas a receber.</p>
            <?php else: ?>
                <p class="mb-1">Nenhuma reversão financeira automática foi necessária — o pedido já não estava com pagamento baixado quando o estorno chegou.</p>
            <?php endif; ?>
            <?php if ($pixRefundedReview['stock_likely_reduced']): ?>
                <p class="mb-1"><strong>Estoque já foi baixado para este pedido</strong> (pedido faturado). Avalie estorno manual em <a href="<?= url('/admin/estoque') ?>">/admin/estoque</a>, contato com o cliente e demais ações conforme o contexto operacional.</p>
            <?php endif; ?>
            <p class="mb-0">
                Status do pedido no estorno: <?= e($statusLabels[$pixRefundedReview['order_status_at_refund']] ?? $pixRefundedReview['order_status_at_refund']) ?>
                <?php if ($pixRefundedReview['fulfillment_status_at_refund'] !== ''): ?>
                    · Expedição: <?= e(FulfillmentStatusLabel::resolve($pixRefundedReview['fulfillment_status_at_refund'])) ?>
                <?php endif; ?>
            </p>
        </div>
    </div>
<?php endif; ?>

<div class="order-detail-grid">
    <div>
        <div class="order-card" id="orderItemsCard" data-order-editable="<?= $canEdit ? '1' : '0' ?>">
            <div class="order-card-head">
                <p class="order-section-label"><i class="ti ti-box" aria-hidden="true"></i> Itens do pedido</p>
                <?php if ($canEdit): ?>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-info btn-sm" data-order-edit-toggle="items"><i class="ti ti-edit" aria-hidden="true"></i> Editar</button>
                        <div class="d-flex gap-2" data-order-edit-actions="items" hidden>
                            <button type="button" class="btn btn-secondary btn-sm" data-order-edit-cancel="items" data-confirm-cancel="Descartar as alterações nos itens deste pedido?"><i class="ti ti-x" aria-hidden="true"></i> Cancelar</button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($canEdit): ?>
                <form method="POST" action="<?= url('/admin/pedidos/' . $orderId . '/itens') ?>" class="inline-field-action mb-3 order-edit-panel" data-order-edit-panel="items" data-confirm-submit="Adicionar este produto ao pedido?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="return_to" value="detail">
                    <label class="form-field-inline" style="flex:1;min-width:220px">
                        Adicionar produto
                        <select class="form-control" name="product_id" required>
                            <option value="">Selecione</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= (int) $product['id'] ?>">
                                    <?= e((string) $product['nome']) ?> — <?= formatarPreco($product['preco'] !== null ? (float) $product['preco'] : null) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="form-field-inline">
                        Qtd
                        <?php partial('qty-stepper', ['name' => 'quantity', 'value' => 1, 'min' => 1]); ?>
                    </label>
                    <button type="submit" class="btn btn-outline-info btn-sm"><i class="ti ti-plus" aria-hidden="true"></i> Adicionar</button>
                </form>
            <?php endif; ?>

            <table class="order-items-table">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th style="text-align:center;width:90px">Qtd</th>
                        <th style="text-align:right">Unit.</th>
                        <th style="text-align:right">Subtotal</th>
                        <?php if ($canEdit): ?><th style="width:40px" data-order-remove-col hidden></th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if ($items === []): ?>
                    <tr><td colspan="<?= $canEdit ? 5 : 4 ?>" class="text-center text-muted py-3">Nenhum item registrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <?php $itemId = (int) ($item['id'] ?? 0); ?>
                        <tr>
                            <td><?= e((string) ($item['product_name'] ?? 'Produto')) ?></td>
                            <td style="text-align:center">
                                <span class="qty-readonly order-read-panel" data-order-read-panel="items"><?= (int) ($item['quantity'] ?? 0) ?></span>
                                <?php if ($canEdit): ?>
                                    <form method="POST" action="<?= url('/admin/pedidos/' . $orderId . '/itens/' . $itemId) ?>" class="order-edit-panel" data-order-edit-panel="items" data-qty-auto-submit style="display:flex;justify-content:center">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="return_to" value="detail">
                                        <?php partial('qty-stepper', [
                                            'name' => 'quantity',
                                            'value' => (int) ($item['quantity'] ?? 0),
                                            'min' => 1,
                                            'autoSubmit' => true,
                                            'inputId' => 'order-item-qty-' . $itemId,
                                        ]); ?>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right"><?= formatarPreco((float) ($item['unit_price'] ?? 0)) ?></td>
                            <td style="text-align:right"><strong><?= formatarPreco((float) ($item['total_amount'] ?? 0)) ?></strong></td>
                            <?php if ($canEdit): ?>
                                <td class="order-edit-panel" data-order-edit-panel="items" data-order-remove-col hidden>
                                    <form method="POST" action="<?= url('/admin/pedidos/' . $orderId . '/itens/' . $itemId . '/remover') ?>" onsubmit="return confirm('Remover este item?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="return_to" value="detail">
                                        <button type="submit" class="btn btn-outline-danger btn-sm btn-icon" title="Remover item"><i class="ti ti-trash" aria-hidden="true"></i></button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
            <?php
                $orderShippingFee = (float) ($order['shipping_fee'] ?? 0);
                $orderPaymentMethod = \Erp\Finance\Support\PaymentMethod::parse((string) ($order['payment_method'] ?? 'pix'));
                $orderInstallments = max(1, (int) ($order['installments'] ?? 1));
            ?>
            <?php if ($deliveryMethod === DeliveryMethod::DELIVERY): ?>
                <div class="order-total-row">
                    <span class="text-muted">Frete<?= !empty($order['delivery_zone_name']) ? ' — ' . e((string) $order['delivery_zone_name']) : '' ?></span>
                    <span><?= $orderShippingFee > 0 ? formatarPreco($orderShippingFee) : (!empty($order['delivery_zone_name']) ? 'Grátis' : 'A combinar') ?></span>
                </div>
            <?php endif; ?>
            <div class="order-total-row">
                <span class="text-muted">Pagamento escolhido</span>
                <span><?= e(\Erp\Finance\Support\PaymentMethod::label($orderPaymentMethod, $orderInstallments)) ?></span>
            </div>
            <div class="order-total-row">
                <span class="text-muted">Total</span>
                <strong><?= formatarPreco((float) ($order['total_amount'] ?? 0)) ?></strong>
            </div>
        </div>

        <div class="order-card">
            <p class="order-section-label"><i class="ti ti-notes" aria-hidden="true"></i> Notas</p>
            <?php if ($internalNotes !== ''): ?>
                <p class="text-muted mb-2" style="font-size:.82rem"><strong>Sistema:</strong> <?= e($internalNotes) ?></p>
            <?php endif; ?>
            <?php if ($customerNotes !== ''): ?>
                <p class="mb-2" style="font-size:.85rem;font-style:italic"><strong>Cliente:</strong> <?= e($customerNotes) ?></p>
            <?php endif; ?>
            <?php if ($canEdit): ?>
                <form method="POST" action="<?= url('/admin/pedidos/' . $orderId . '/notas') ?>" data-confirm-submit="Salvar as notas do operador neste pedido?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="return_to" value="detail">
                    <label class="form-field-inline" style="width:100%">
                        Notas do operador
                        <textarea class="form-control" name="notes" rows="3" placeholder="Observações internas sobre este pedido"><?= e($operatorNotes) ?></textarea>
                    </label>
                    <div class="d-flex justify-end mt-2">
                        <button type="submit" class="btn btn-secondary btn-sm"><i class="ti ti-device-floppy" aria-hidden="true"></i> Salvar nota</button>
                    </div>
                </form>
            <?php elseif ($operatorNotes !== ''): ?>
                <p style="white-space:pre-wrap;margin:0"><strong>Operador:</strong> <?= e($operatorNotes) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <div class="order-card">
            <div class="order-card-head">
                <p class="order-section-label"><i class="ti ti-user" aria-hidden="true"></i> Cliente</p>
                <?php if ($clientId > 0): ?>
                    <a href="<?= url('/admin/clientes/editar/' . $clientId) ?>" class="btn btn-outline-info btn-sm"><i class="ti ti-external-link" aria-hidden="true"></i> Ver cadastro</a>
                <?php endif; ?>
            </div>
            <div class="d-flex align-center gap-2 mb-3">
                <span class="client-avatar"><?= e($clientInitials !== '' ? $clientInitials : 'CL') ?></span>
                <div>
                    <p class="order-readonly-value" style="margin:0"><?= e($clientName) ?></p>
                    <?php if (!empty($order['client_email'])): ?>
                        <small class="text-muted"><?= e((string) $order['client_email']) ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($order['client_phone'])): ?>
                <p class="mb-2"><span class="text-muted">Telefone</span><br><span class="order-readonly-value"><?= e((string) $order['client_phone']) ?></span></p>
            <?php endif; ?>
            <?php if (!empty($order['client_document'])): ?>
                <p class="mb-0"><span class="text-muted">Documento</span><br><span class="order-readonly-value"><?= e((string) $order['client_document']) ?></span></p>
            <?php endif; ?>
        </div>

        <div class="order-card" id="orderAddressCard" data-order-editable="<?= $canEdit ? '1' : '0' ?>">
            <div class="order-card-head">
                <p class="order-section-label"><i class="ti ti-map-pin" aria-hidden="true"></i> <?= $deliveryMethod === DeliveryMethod::PICKUP ? 'Retirada na loja' : 'Endereço de entrega' ?></p>
                <?php if ($canEdit): ?>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-info btn-sm" data-order-edit-toggle="address"><i class="ti ti-edit" aria-hidden="true"></i> Editar</button>
                        <div class="d-flex gap-2" data-order-edit-actions="address" hidden>
                            <button type="button" class="btn btn-secondary btn-sm" data-order-edit-cancel="address" data-confirm-cancel="Descartar as alterações no endereço?"><i class="ti ti-x" aria-hidden="true"></i> Cancelar</button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($deliveryMethod === DeliveryMethod::PICKUP && !$canEdit): ?>
                <p class="order-readonly-value order-read-panel" data-order-read-panel="address">Cliente optou por retirada na loja.</p>
            <?php elseif ($shippingLine !== ''): ?>
                <p class="order-readonly-value order-read-panel" data-order-read-panel="address" style="line-height:1.6;font-weight:400"><?= e($shippingLine) ?></p>
            <?php elseif ($deliveryMethod === DeliveryMethod::PICKUP): ?>
                <p class="text-muted order-read-panel" data-order-read-panel="address">Retirada na loja — sem endereço de entrega.</p>
            <?php else: ?>
                <p class="text-muted order-read-panel" data-order-read-panel="address">Endereço não informado.</p>
            <?php endif; ?>
            <?php if ($canEdit): ?>
                <form method="POST" action="<?= url('/admin/pedidos/' . $orderId . '/endereco') ?>" class="order-edit-panel" data-order-edit-panel="address" data-confirm-submit="Salvar o endereço de entrega deste pedido?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="return_to" value="detail">
                    <div class="form-row">
                        <label class="form-field-inline">UF
                            <select class="form-control" name="shipping_state" required>
                                <option value="">Selecione</option>
                                <?php foreach (BrazilianAddress::STATES as $uf => $label): ?>
                                    <option value="<?= e($uf) ?>" <?= strtoupper((string) ($order['shipping_state'] ?? '')) === $uf ? 'selected' : '' ?>><?= e($uf) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="form-field-inline">CEP
                            <input class="form-control" type="text" name="shipping_postal_code" value="<?= e((string) ($order['shipping_postal_code'] ?? '')) ?>" required>
                        </label>
                    </div>
                    <label class="form-field-inline" style="width:100%">Logradouro
                        <input class="form-control" type="text" name="shipping_street" value="<?= e((string) ($order['shipping_street'] ?? '')) ?>" required>
                    </label>
                    <div class="form-row">
                        <label class="form-field-inline">Número
                            <input class="form-control" type="text" name="shipping_number" value="<?= e((string) ($order['shipping_number'] ?? '')) ?>" required>
                        </label>
                        <label class="form-field-inline">Complemento
                            <input class="form-control" type="text" name="shipping_complement" value="<?= e((string) ($order['shipping_complement'] ?? '')) ?>">
                        </label>
                    </div>
                    <div class="form-row">
                        <label class="form-field-inline">Bairro
                            <input class="form-control" type="text" name="shipping_neighborhood" value="<?= e((string) ($order['shipping_neighborhood'] ?? '')) ?>" required>
                        </label>
                        <label class="form-field-inline">Cidade
                            <input class="form-control" type="text" name="shipping_city" value="<?= e((string) ($order['shipping_city'] ?? '')) ?>" required>
                        </label>
                    </div>
                    <button type="submit" class="btn btn-approve-solid btn-sm mt-2"><i class="ti ti-device-floppy" aria-hidden="true"></i> Salvar endereço</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="order-card">
            <p class="order-section-label"><i class="ti ti-truck-delivery" aria-hidden="true"></i> Logística / expedição</p>
            <p class="mb-2">
                <span class="text-muted">Forma de recebimento</span><br>
                <span class="badge-pill badge-pill-origin">
                    <i class="ti <?= e(DeliveryMethodLabel::icon($deliveryMethod)) ?>" aria-hidden="true"></i>
                    <?= e(DeliveryMethodLabel::resolve($deliveryMethod)) ?>
                </span>
            </p>
            <?php if ($status === 'confirmed' || $status === 'cancelled'): ?>
                <p class="mb-2">
                    <span class="text-muted">Status operacional</span><br>
                    <span class="badge-pill <?= e(FulfillmentStatusLabel::badgeClass($fulfillmentStatus)) ?>">
                        <i class="ti <?= e(FulfillmentStatusLabel::icon($fulfillmentStatus)) ?>" aria-hidden="true"></i>
                        <?= e(FulfillmentStatusLabel::resolve($fulfillmentStatus)) ?>
                    </span>
                </p>
            <?php elseif ($status === 'draft'): ?>
                <p class="mb-2 text-muted">Expedição disponível após aprovar o pedido.</p>
            <?php endif; ?>
            <?php if (!empty($order['fulfillment_updated_at'])): ?>
                <p class="mb-2"><span class="text-muted">Atualizado em</span><br><?= e(function_exists('datetime_br') ? datetime_br((string) $order['fulfillment_updated_at']) : (string) $order['fulfillment_updated_at']) ?></p>
            <?php endif; ?>
            <?php if (!empty($order['fulfillment_notes']) && !$canManageFulfillment): ?>
                <p class="mb-0" style="white-space:pre-wrap"><span class="text-muted">Observações</span><br><?= e((string) $order['fulfillment_notes']) ?></p>
            <?php endif; ?>
            <?php if ($canEditDeliveryMethod || $canManageFulfillment): ?>
                <form method="POST" action="<?= url('/admin/pedidos/' . $orderId . '/logistica') ?>" class="mt-3" data-confirm-submit="Salvar dados de logística deste pedido?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="return_to" value="detail">
                    <?php if ($canEditDeliveryMethod): ?>
                        <label class="form-field-inline" style="width:100%">
                            Forma de recebimento
                            <select class="form-control" name="delivery_method" required>
                                <?php foreach (DeliveryMethod::ALL as $method): ?>
                                    <option value="<?= e($method) ?>" <?= $deliveryMethod === $method ? 'selected' : '' ?>><?= e(DeliveryMethodLabel::resolve($method)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    <?php endif; ?>
                    <?php if ($canManageFulfillment): ?>
                        <label class="form-field-inline" style="width:100%">
                            Status de expedição
                            <select class="form-control" name="fulfillment_status" required>
                                <?php foreach ($fulfillmentOptions as $option): ?>
                                    <option value="<?= e($option) ?>" <?= $fulfillmentStatus === $option ? 'selected' : '' ?>><?= e(FulfillmentStatusLabel::resolve($option)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="form-field-inline" style="width:100%">
                            Observações de expedição
                            <textarea class="form-control" name="fulfillment_notes" rows="2" placeholder="Ex.: motorista João, horário combinado"><?= e((string) ($order['fulfillment_notes'] ?? '')) ?></textarea>
                        </label>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-secondary btn-sm mt-2"><i class="ti ti-device-floppy" aria-hidden="true"></i> Salvar logística</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="order-card">
            <p class="order-section-label"><i class="ti ti-cash" aria-hidden="true"></i> Pagamento</p>
            <p class="mb-2">
                <span class="badge-pill <?= e(PaymentStatusLabel::badgeClass($paymentStatus)) ?>">
                    <i class="ti <?= e(PaymentStatusLabel::icon($paymentStatus)) ?>" aria-hidden="true"></i>
                    <?= e(PaymentStatusLabel::resolve($paymentStatus)) ?>
                </span>
            </p>
            <?php if (!empty($order['paid_at'])): ?>
                <p class="mb-0"><span class="text-muted">Pago em</span><br><?= e(function_exists('datetime_br') ? datetime_br((string) $order['paid_at']) : (string) $order['paid_at']) ?></p>
            <?php endif; ?>
            <?php if (is_array($paymentCharge)): ?>
                <hr class="my-3" />
                <p class="mb-1"><span class="text-muted">Gateway Pix (<?= e((string) ($paymentCharge['provider'] ?? 'asaas')) ?>)</span></p>
                <p class="mb-1">
                    <span class="badge-pill <?= $gatewayPixPaid ? 'badge-pill-confirmed' : 'badge-pill-pending' ?>">
                        <?= e(strtoupper((string) ($paymentCharge['status'] ?? 'PENDING'))) ?>
                    </span>
                </p>
                <?php if (!empty($paymentCharge['external_payment_id'])): ?>
                    <p class="mb-1"><span class="text-muted">ID cobrança</span><br><code><?= e((string) $paymentCharge['external_payment_id']) ?></code></p>
                <?php endif; ?>
                <?php if ($gatewayPixPaid && $status === 'draft'): ?>
                    <p class="mb-0 text-success"><i class="ti ti-check" aria-hidden="true"></i> Pix recebido; aguardando aprovação comercial.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="order-card admin-only-section">
            <p class="order-section-label"><i class="ti ti-shield" aria-hidden="true"></i> Dados do pedido <span class="badge-pill badge-pill-cancelled" style="font-size:.62rem">Admin</span></p>
            <p class="mb-2"><span class="text-muted">Criado em</span><br><?= e(function_exists('datetime_br') ? datetime_br((string) ($order['created_at'] ?? '')) : (string) ($order['created_at'] ?? '')) ?></p>
            <p class="mb-2"><span class="text-muted">Registrado por</span><br><?= e((string) ($order['created_by_name'] ?? ($origin === 'online' ? 'Site público' : 'Sistema'))) ?></p>
            <?php if ($origin === 'online' && isset($order['ownership_verified']) && (int) $order['ownership_verified'] === 0): ?>
                <p class="mb-0"><span class="badge-pill badge-pill-pending">Verificação pendente</span></p>
            <?php endif; ?>
        </div>
    </div>
</div>
