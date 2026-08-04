<?php
$users = $users ?? [];
$search = $search ?? '';
$msg = $msg ?? '';
$tipo = $tipo ?? 'ok';
?>
<?php partial('page-header', [
    'subtitle' => count($users) . ' usuário(s) cadastrado(s)',
    'actionUrl' => url('/admin/usuarios/criar'),
    'actionLabel' => 'Novo usuário',
    'actionIcon' => 'ti-plus',
]); ?>

<?php if ($msg !== ''): ?>
    <div class="alerta alerta-<?= e($tipo) ?>"><?= e($msg) ?></div>
<?php endif; ?>

<?php partial('search-bar', [
    'action' => url('/admin/usuarios'),
    'value' => $search,
    'placeholder' => 'Buscar por nome ou e-mail...',
]); ?>

<div class="table-responsive">
    <table class="table-admin">
        <thead>
            <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Papel</th>
                <th>Status</th>
                <th>Criado em</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($users === []): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Nenhum usuário encontrado.</td></tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><strong><?= e((string) $user['nome']) ?></strong></td>
                        <td><?= e((string) $user['email']) ?></td>
                        <td><?= e((string) ($user['role'] ?? 'operator')) ?></td>
                        <td><?= badgeStatus((string) ($user['status'] ?? 'inativo')) ?></td>
                        <td><?= e(function_exists('datetime_br') ? datetime_br((string) ($user['created_at'] ?? '')) : (string) ($user['created_at'] ?? '')) ?></td>
                        <td>
                            <div class="td-actions">
                                <a href="<?= url('/admin/usuarios/editar/' . (int) $user['id']) ?>" class="btn btn-outline-info btn-sm btn-icon" title="Editar" aria-label="Editar">
                                    <i class="ti ti-edit" aria-hidden="true"></i>
                                </a>
                                <form method="POST" action="<?= url('/admin/usuarios/deletar/' . (int) $user['id']) ?>" onsubmit="return confirm('Tem certeza que deseja remover este usuário?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-outline-danger btn-sm btn-icon" title="Remover" aria-label="Remover">
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
