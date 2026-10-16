<?php
$zones = $zones ?? [];
$msg = $msg ?? '';
$tipo = $tipo ?? 'ok';
$money = static fn ($value): string => $value === null || $value === '' ? '—' : formatarPreco((float) $value);
?>
<?php partial('page-header', [
    'subtitle' => count($zones) . ' zona(s) de entrega',
    'actionUrl' => url('/admin/frete/criar'),
    'actionLabel' => 'Nova zona',
    'actionIcon' => 'ti-plus',
]); ?>

<?php if ($msg !== ''): ?>
    <div class="alerta alerta-<?= $tipo === 'erro' ? 'erro' : 'ok' ?>"><?= e($msg) ?></div>
<?php endif; ?>

<?php partial('info-bar', [
    'message' => 'O frete é escolhido pela zona mais específica: bairro listado → faixa de CEP → cidade inteira. Sem nenhuma zona ativa, o site aceita entrega com frete "a combinar".',
    'icon' => 'ti-truck-delivery',
]); ?>

<div class="table-responsive">
    <table class="table-admin">
        <thead>
            <tr>
                <th>Zona</th>
                <th>Cidade</th>
                <th>Abrangência</th>
                <th>Frete</th>
                <th>Grátis acima de</th>
                <th>Pedido mínimo</th>
                <th>Prazo</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($zones === []): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">Nenhuma zona cadastrada — o frete está "a combinar" no site.</td></tr>
            <?php else: ?>
                <?php foreach ($zones as $zone): ?>
                    <?php
                        $neighborhoods = array_values(array_filter(explode("\n", (string) ($zone['neighborhoods'] ?? ''))));
                        if ($neighborhoods !== []) {
                            $coverage = count($neighborhoods) . ' bairro(s): ' . implode(', ', array_slice($neighborhoods, 0, 3)) . (count($neighborhoods) > 3 ? '…' : '');
                        } elseif (!empty($zone['postal_code_start'])) {
                            $coverage = 'CEP ' . $zone['postal_code_start'] . ' a ' . $zone['postal_code_end'];
                        } else {
                            $coverage = 'Cidade inteira';
                        }
                    ?>
                    <tr>
                        <td><strong><?= e((string) $zone['name']) ?></strong></td>
                        <td><?= e((string) $zone['city']) ?>/<?= e((string) $zone['state']) ?></td>
                        <td><?= e($coverage) ?></td>
                        <td><?= (float) $zone['fee'] > 0 ? e(formatarPreco((float) $zone['fee'])) : 'Grátis' ?></td>
                        <td><?= e($money($zone['free_shipping_min'] ?? null)) ?></td>
                        <td><?= e($money($zone['min_order_amount'] ?? null)) ?></td>
                        <td><?= e((string) ($zone['lead_time'] ?? '') ?: '—') ?></td>
                        <td><?= badgeRecordStatus((string) ($zone['status'] ?? 'active')) ?></td>
                        <td>
                            <div class="td-actions">
                                <a class="btn btn-outline-info btn-sm btn-icon" href="<?= url('/admin/frete/editar/' . (int) $zone['id']) ?>" title="Editar" aria-label="Editar">
                                    <i class="ti ti-edit" aria-hidden="true"></i>
                                </a>
                                <form method="POST" action="<?= url('/admin/frete/deletar/' . (int) $zone['id']) ?>" onsubmit="return confirm('Excluir esta zona de entrega?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-outline-danger btn-sm btn-icon" title="Excluir" aria-label="Excluir">
                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
