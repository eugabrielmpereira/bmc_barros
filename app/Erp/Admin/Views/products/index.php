<?php
// Listagem administrativa de produtos.

$produtos = $produtos ?? [];
$busca = $busca ?? '';
$msg = $msg ?? '';
$tipo = $tipo ?? 'ok';
?>

<?php partial('page-header', [
    'subtitle' => count($produtos) . ' produto(s) listado(s)',
    'actionUrl' => url('/admin/produtos/criar'),
    'actionLabel' => 'Novo produto',
    'actionIcon' => 'ti-plus',
]); ?>

<?php if ($msg !== ''): ?>
    <div class="alerta alerta-<?= e($tipo) ?>"><?= e($msg) ?></div>
<?php endif; ?>

<?php partial('search-bar', [
    'action' => url('/admin/produtos'),
    'value' => $busca,
    'placeholder' => 'Buscar produto por nome...',
]); ?>

<div class="table-responsive">
    <table class="table-admin">
        <thead>
            <tr>
                <th style="width:3rem"></th>
                <th>Foto</th>
                <th>Produto</th>
                <th>Categoria</th>
                <th>Preço</th>
                <th>Estoque</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($produtos === []): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Nenhum produto encontrado.</td></tr>
            <?php else: ?>
                <?php foreach ($produtos as $p): ?>
                <?php
                $productId = (int) $p['id'];
                $productName = (string) $p['nome'];
                $currentStock = (int) ($p['estoque_atual'] ?? 0);
                $deleteConfirm = 'Tem certeza que quer deletar "' . $productName . '"? Esta ação não pode ser desfeita.';
                ?>
                <tr>
                    <td class="td-actions-menu">
                        <?php partial('actions-menu', [
                            'items' => [
                                [
                                    'type' => 'button',
                                    'label' => 'Gerenciar estoque',
                                    'icon' => 'ti-package',
                                    'attrs' => [
                                        'data-stock-drawer-open' => '',
                                        'data-stock-drawer-target' => 'stock-drawer-' . $productId,
                                    ],
                                ],
                                [
                                    'type' => 'link',
                                    'label' => 'Editar produto',
                                    'icon' => 'ti-edit',
                                    'href' => url('/admin/produtos/editar/' . $productId),
                                ],
                                [
                                    'type' => 'submit',
                                    'label' => 'Excluir produto',
                                    'icon' => 'ti-trash',
                                    'confirm' => $deleteConfirm,
                                    'attrs' => ['form' => 'delete-product-' . $productId],
                                ],
                            ],
                        ]); ?>
                    </td>
                    <td>
                        <?php if (!empty($p['imagem'])): ?>
                            <img class="thumb" src="<?= e(client_product_image_url((string) $p['imagem'])) ?>" alt="<?= e($productName) ?>" />
                        <?php else: ?>
                            <div class="sem-foto">📦</div>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= e($productName) ?></strong></td>
                    <td><?= e((string) ($p['categoria_nome'] ?? '')) ?></td>
                    <td class="text-right"><?= formatarPreco($p['preco'] !== null && $p['preco'] !== '' ? (float) $p['preco'] : null) ?></td>
                    <td><?= badgeEstoque($currentStock) ?></td>
                    <td><?= badgeStatus((string) ($p['status'] ?? 'inativo')) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($produtos !== []): ?>
    <?php foreach ($produtos as $p): ?>
        <?php $productId = (int) $p['id']; ?>
        <form id="delete-product-<?= $productId ?>" method="POST" action="<?= url('/admin/produtos/deletar/' . $productId) ?>" hidden>
            <?= csrf_field() ?>
        </form>
    <?php endforeach; ?>
<?php endif; ?>

<?php if ($produtos !== []): ?>
    <?php foreach ($produtos as $p): ?>
        <?php
        $productId = (int) $p['id'];
        $productName = (string) $p['nome'];
        $currentStock = (int) ($p['estoque_atual'] ?? 0);
        $categoryName = (string) ($p['categoria_nome'] ?? 'Sem categoria');
        $price = $p['preco'] !== null && $p['preco'] !== '' ? (float) $p['preco'] : null;
        ?>
        <div class="stock-drawer-shell" id="stock-drawer-<?= $productId ?>" aria-hidden="true">
            <button class="stock-drawer-overlay" type="button" aria-label="Fechar gaveta de estoque" data-stock-drawer-close></button>
            <aside class="stock-drawer-panel" role="dialog" aria-modal="true" aria-labelledby="stock-drawer-title-<?= $productId ?>">
                <header class="stock-drawer-header">
                    <div>
                        <p class="stock-drawer-eyebrow">Gerenciamento de estoque</p>
                        <h2 id="stock-drawer-title-<?= $productId ?>"><?= e($productName) ?></h2>
                    </div>
                    <button class="stock-drawer-close" type="button" aria-label="Fechar gaveta" data-stock-drawer-close>&times;</button>
                </header>

                <div class="stock-drawer-summary">
                    <div>
                        <span>Categoria</span>
                        <strong><?= e($categoryName) ?></strong>
                    </div>
                    <div>
                        <span>Preço</span>
                        <strong><?= formatarPreco($price) ?></strong>
                    </div>
                    <div>
                        <span>Estoque atual</span>
                        <strong><?= $currentStock ?> un.</strong>
                    </div>
                </div>

                <div class="stock-drawer-tabs" role="tablist" aria-label="Tipo de movimentação">
                    <button
                        type="button"
                        class="stock-drawer-tab is-active"
                        role="tab"
                        aria-selected="true"
                        aria-controls="stock-entry-<?= $productId ?>"
                        data-stock-tab-target="stock-entry-<?= $productId ?>"
                    >
                        Entrada
                    </button>
                    <button
                        type="button"
                        class="stock-drawer-tab"
                        role="tab"
                        aria-selected="false"
                        aria-controls="stock-exit-<?= $productId ?>"
                        data-stock-tab-target="stock-exit-<?= $productId ?>"
                    >
                        Saída
                    </button>
                    <button
                        type="button"
                        class="stock-drawer-tab"
                        role="tab"
                        aria-selected="false"
                        aria-controls="stock-adjust-<?= $productId ?>"
                        data-stock-tab-target="stock-adjust-<?= $productId ?>"
                    >
                        Ajuste
                    </button>
                </div>

                <section class="stock-drawer-tab-panel is-active" id="stock-entry-<?= $productId ?>" role="tabpanel">
                    <form method="POST" action="<?= url('/admin/produtos/' . $productId . '/entrada') ?>" class="stock-drawer-form" data-confirm-submit="Confirmar entrada de estoque para este produto?">
                        <?= csrf_field() ?>
                        <label>
                            Quantidade de entrada
                            <?php partial('qty-stepper', ['name' => 'quantidade', 'value' => 1, 'min' => 1]); ?>
                        </label>
                        <label>
                            Motivo
                            <input class="form-control" type="text" name="motivo" placeholder="Ex.: compra, devolução, correção" />
                        </label>
                        <button type="submit" class="btn btn-success">Confirmar Entrada</button>
                    </form>
                </section>

                <section class="stock-drawer-tab-panel" id="stock-exit-<?= $productId ?>" role="tabpanel" hidden>
                    <form method="POST" action="<?= url('/admin/produtos/' . $productId . '/saida') ?>" class="stock-drawer-form" data-confirm-submit="Confirmar saída de estoque para este produto?">
                        <?= csrf_field() ?>
                        <label>
                            Quantidade de saída
                            <?php partial('qty-stepper', ['name' => 'quantidade', 'value' => 1, 'min' => 1]); ?>
                        </label>
                        <label>
                            Motivo
                            <input class="form-control" type="text" name="motivo" placeholder="Ex.: venda, perda, transferência" />
                        </label>
                        <button type="submit" class="btn btn-warning">Confirmar Saída</button>
                    </form>
                </section>

                <section class="stock-drawer-tab-panel" id="stock-adjust-<?= $productId ?>" role="tabpanel" hidden>
                    <form method="POST" action="<?= url('/admin/produtos/' . $productId . '/ajuste') ?>" class="stock-drawer-form" data-confirm-submit="Confirmar ajuste de estoque para este produto?">
                        <?= csrf_field() ?>
                        <label>
                            Novo saldo de estoque
                            <?php partial('qty-stepper', ['name' => 'novo_estoque', 'value' => $currentStock, 'min' => 0]); ?>
                        </label>
                        <label>
                            Motivo do ajuste
                            <input class="form-control" type="text" name="motivo" placeholder="Ex.: inventário, contagem física" required />
                        </label>
                        <button type="submit" class="btn btn-primary">Confirmar Ajuste</button>
                    </form>
                </section>
            </aside>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
