<?php
$categorias = $categorias ?? [];
$msg = $msg ?? '';
$tipo = $tipo ?? 'ok';
$previousLevel = 0;
?>
<?php partial('page-header', [
    'subtitle' => count($categorias) . ' categoria(s) na hierarquia',
    'actionUrl' => url('/admin/categorias/criar'),
    'actionLabel' => 'Nova categoria',
    'actionIcon' => 'ti-plus',
]); ?>

<?php if ($msg !== ''): ?>
    <div class="alerta alerta-<?= $tipo === 'erro' ? 'erro' : 'ok' ?>"><?= e($msg) ?></div>
<?php endif; ?>

<?php partial('info-bar', [
    'message' => 'Produtos devem ser vinculados às subcategorias (nível 2).',
    'icon' => 'ti-info-circle',
]); ?>

<div class="table-responsive">
    <table class="table-admin">
        <thead>
            <tr>
                <th>Categoria</th>
                <th>Slug</th>
                <th>Nível</th>
                <th>Ordem</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($categorias === []): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Nenhuma categoria cadastrada.</td></tr>
            <?php else: ?>
                <?php foreach ($categorias as $cat): ?>
                    <?php
                        $level = (int) ($cat['level'] ?? 1);
                        $isParent = $level === 1;
                        $rowClass = ($level === 1 && $previousLevel > 1) ? 'category-group-separator' : '';
                        $previousLevel = $level;
                    ?>
                    <tr class="<?= e($rowClass) ?>">
                        <td>
                            <?php if ($isParent): ?>
                                <span class="category-name-parent"><?= e((string) $cat['nome']) ?></span>
                            <?php else: ?>
                                <span class="category-name-child">
                                    <i class="ti ti-corner-down-right" aria-hidden="true"></i>
                                    <?= e((string) $cat['nome']) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><code class="order-meta-ref"><?= e((string) ($cat['slug'] ?? '')) ?></code></td>
                        <td><?= $level ?></td>
                        <td><?= (int) ($cat['sort_order'] ?? 0) ?></td>
                        <td><?= badgeRecordStatus((string) ($cat['status'] ?? 'active')) ?></td>
                        <td>
                            <div class="td-actions">
                                <a class="btn btn-outline-info btn-sm btn-icon" href="<?= url('/admin/categorias/editar/' . (int) $cat['id']) ?>" title="Editar" aria-label="Editar">
                                    <i class="ti ti-edit" aria-hidden="true"></i>
                                </a>
                                <?php if ($level < 3): ?>
                                    <a class="btn btn-outline-info btn-sm btn-icon" href="<?= url('/admin/categorias/criar?parent_id=' . (int) $cat['id']) ?>" title="Adicionar subcategoria" aria-label="Adicionar subcategoria">
                                        <i class="ti ti-plus" aria-hidden="true"></i>
                                    </a>
                                <?php endif; ?>
                                <form method="POST" action="<?= url('/admin/categorias/deletar/' . (int) $cat['id']) ?>" onsubmit="return confirm('Excluir esta categoria?');">
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
