<?php
use Erp\Stock\Support\StockMovementLabel;

$movimentos = $movimentos ?? [];
?>
<?php partial('page-header', [
    'subtitle' => count($movimentos) . ' movimentação(ões) registrada(s)',
]); ?>

<div class="card-admin card-body" style="padding:0">
    <div class="table-responsive">
        <table class="table-admin">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Tipo</th>
                    <th>Quantidade</th>
                    <th>Motivo</th>
                    <th>Usuário</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($movimentos === []): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            Nenhuma movimentação de estoque registrada até o momento.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($movimentos as $movimento): ?>
                        <?php
                        $tipoLabel = StockMovementLabel::resolve($movimento);
                        $badgeClass = StockMovementLabel::badgeClass($movimento);
                        $createdAt = (string) ($movimento['created_at'] ?? '');
                        ?>
                        <tr>
                            <td>
                                <strong><?= e((string) ($movimento['produto_nome'] ?? '')) ?></strong>
                                <br>
                                <small class="text-muted order-meta-ref">ID <?= (int) ($movimento['produto_id'] ?? 0) ?></small>
                            </td>
                            <td>
                                <span class="badge-pill <?= e($badgeClass) ?>"><?= e($tipoLabel) ?></span>
                            </td>
                            <td><?= (int) ($movimento['quantidade'] ?? 0) ?></td>
                            <td><?= e((string) ($movimento['motivo'] ?? '—')) ?></td>
                            <td>
                                <?= isset($movimento['user_id']) && $movimento['user_id'] !== null
                                    ? 'Usuário #' . (int) $movimento['user_id']
                                    : 'Sistema' ?>
                            </td>
                            <td><?= e(function_exists('datetime_br') ? datetime_br($createdAt) : $createdAt) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
