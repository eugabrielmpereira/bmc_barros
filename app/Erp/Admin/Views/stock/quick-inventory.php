<?php
$produtos = $produtos ?? [];
$busca = $busca ?? '';
$msg = $msg ?? '';
$tipo = $tipo ?? 'ok';
?>
<?php partial('page-header', [
    'subtitle' => count($produtos) . ' produto(s) para ajuste em lote',
]); ?>

<?php if ($msg !== ''): ?>
    <div class="alerta alerta-<?= e($tipo) ?>"><?= e($msg) ?></div>
<?php endif; ?>

<?php partial('search-bar', [
    'action' => url('/admin/inventario-rapido'),
    'value' => $busca,
    'placeholder' => 'Buscar produto por nome...',
]); ?>

<?php partial('info-bar', [
    'message' => 'Altere apenas os saldos necessários. O motivo abaixo é registrado no histórico de estoque de cada movimentação gerada.',
    'icon' => 'ti-package',
]); ?>

<form method="POST" action="<?= url('/admin/inventario-rapido') ?>" class="card-admin card-body" data-confirm-submit="Salvar os ajustes de estoque? Produtos alterados gerarão movimentação no histórico.">
    <?= csrf_field() ?>
    <div class="d-flex justify-between align-center mb-3 flex-wrap gap-2">
        <div>
            <h2 style="margin:0;font-size:1.05rem;font-weight:500">Ajuste em lote</h2>
        </div>
        <button type="submit" class="btn btn-approve-solid"><i class="ti ti-device-floppy" aria-hidden="true"></i> Salvar alterações</button>
    </div>

    <label class="mb-3">
        Motivo do ajuste (histórico)
        <input class="form-control" type="text" name="reason" value="Inventário rápido" required>
        <span class="form-hint">Texto salvo em cada movimentação de estoque deste inventário (ex.: contagem física, correção).</span>
    </label>

    <div class="table-responsive">
        <table class="table-admin">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Categoria</th>
                    <th>Status</th>
                    <th>Saldo atual</th>
                    <th>Novo saldo</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($produtos === []): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Nenhum produto encontrado.</td></tr>
            <?php else: ?>
                <?php foreach ($produtos as $produto): ?>
                    <?php $stock = (int) ($produto['estoque_atual'] ?? 0); ?>
                    <tr>
                        <td><strong><?= e((string) ($produto['nome'] ?? '')) ?></strong></td>
                        <td><?= e((string) ($produto['categoria_nome'] ?? '')) ?></td>
                        <td><?= badgeStatus((string) ($produto['status'] ?? 'inativo')) ?></td>
                        <td><?= badgeEstoque($stock) ?></td>
                        <td>
                            <?php partial('qty-stepper', [
                                'name' => 'stocks[' . (int) ($produto['id'] ?? 0) . ']',
                                'value' => $stock,
                                'min' => 0,
                                'inputId' => 'inv-qty-' . (int) ($produto['id'] ?? 0),
                            ]); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</form>
