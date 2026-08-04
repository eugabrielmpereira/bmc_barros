<?php
$clients = $clients ?? [];
$search = $search ?? '';
$msg = $msg ?? '';
$tipo = $tipo ?? 'ok';
?>
<?php partial('page-header', [
    'subtitle' => count($clients) . ' cliente(s) cadastrado(s)',
    'actionUrl' => url('/admin/clientes/criar'),
    'actionLabel' => 'Novo cliente',
    'actionIcon' => 'ti-plus',
]); ?>

<?php if ($msg !== ''): ?><div class="alerta alerta-<?= e($tipo) ?>"><?= e($msg) ?></div><?php endif; ?>

<?php partial('search-bar', [
    'action' => url('/admin/clientes'),
    'value' => $search,
    'placeholder' => 'Buscar por nome, documento ou e-mail...',
]); ?>

<div class="table-responsive">
    <table class="table-admin">
        <thead><tr><th>Nome</th><th>Documento</th><th>Contato</th><th>Status</th><th>Ações</th></tr></thead>
        <tbody>
        <?php if ($clients === []): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Nenhum cliente encontrado.</td></tr>
        <?php else: ?>
            <?php foreach ($clients as $client): ?>
                <tr>
                    <td><strong><?= e((string) $client['name']) ?></strong><br><small class="text-muted"><?= e((string) ($client['address'] ?? '')) ?></small></td>
                    <td><span class="order-meta-ref"><?= e((string) ($client['document'] ?? '')) ?></span></td>
                    <td><?= e((string) ($client['email'] ?? '')) ?><br><small class="text-muted"><?= e((string) ($client['phone'] ?? '')) ?></small></td>
                    <td><?= badgeRecordStatus((string) ($client['status'] ?? 'inactive')) ?></td>
                    <td><div class="td-actions">
                        <a class="btn btn-outline-info btn-sm btn-icon" href="<?= url('/admin/clientes/editar/' . (int) $client['id']) ?>" title="Editar" aria-label="Editar">
                            <i class="ti ti-edit" aria-hidden="true"></i>
                        </a>
                        <?php if ((string) ($client['status'] ?? '') === 'active'): ?>
                            <form method="POST" action="<?= url('/admin/clientes/' . (int) $client['id'] . '/desativar') ?>" onsubmit="return confirm('Desativar este cliente?')">
                                <?= csrf_field() ?>
                                <button class="btn btn-outline-danger btn-sm btn-icon" type="submit" title="Desativar" aria-label="Desativar">
                                    <i class="ti ti-ban" aria-hidden="true"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" action="<?= url('/admin/clientes/deletar/' . (int) $client['id']) ?>" onsubmit="return confirm('Remover este cliente? Só é possível se não houver pedidos vinculados.')">
                            <?= csrf_field() ?>
                            <button class="btn btn-outline-danger btn-sm btn-icon" type="submit" title="Remover" aria-label="Remover">
                                <i class="ti ti-trash" aria-hidden="true"></i>
                            </button>
                        </form>
                    </div></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
