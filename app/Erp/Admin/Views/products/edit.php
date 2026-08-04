<?php
// Formulário administrativo de edição de produto.

$categorias = $categorias ?? [];
$dados = $dados ?? [];
$erros = $erros ?? [];
$produtoId = (int) ($produtoId ?? ($dados['id'] ?? 0));
?>

<div class="mb-3">
    <a href="<?= url('/admin/produtos') ?>" class="btn btn-secondary">← Voltar</a>
</div>

<?php if ($erros !== []): ?>
    <div class="alerta alerta-erro">
        <?php foreach ($erros as $erro): ?>
            <div>⚠️ <?= e($erro) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card-admin card-body">
    <form method="POST" action="<?= url('/admin/produtos/editar/' . $produtoId) ?>" enctype="multipart/form-data" data-confirm-submit="Salvar as alterações deste produto?">
        <?= csrf_field() ?>

        <div class="form-row">
            <div class="form-grupo">
                <label for="nome">Nome do Produto *</label>
                <input class="form-control" type="text" id="nome" name="nome" value="<?= e((string) ($dados['nome'] ?? '')) ?>" required />
            </div>
            <div class="form-grupo">
                <label for="categoria_id">Categoria *</label>
                <select class="form-control" id="categoria_id" name="categoria_id" required>
                    <option value="">Selecione a subcategoria...</option>
                    <?php
                    $groupedCategories = [];
                    foreach ($categorias as $cat) {
                        $parentLabel = (string) ($cat['parent_nome'] ?? 'Outras');
                        $groupedCategories[$parentLabel][] = $cat;
                    }
                    foreach ($groupedCategories as $parentLabel => $items): ?>
                        <optgroup label="<?= e($parentLabel) ?>">
                            <?php foreach ($items as $cat): ?>
                                <option value="<?= (int) $cat['id'] ?>" <?= (string) ($dados['categoria_id'] ?? '') === (string) $cat['id'] ? 'selected' : '' ?>>
                                    <?= e((string) $cat['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-grupo">
            <label for="descricao">Descrição</label>
            <textarea class="form-control" id="descricao" name="descricao"><?= e((string) ($dados['descricao'] ?? '')) ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-grupo">
                <label for="preco">Preço (R$)</label>
                <input class="form-control" type="number" id="preco" name="preco" value="<?= e((string) ($dados['preco'] ?? '')) ?>" step="0.01" min="0" placeholder="0.00" />
            </div>
            <div class="form-grupo">
                <label for="estoque_atual">Quantidade em Estoque *</label>
                <input class="form-control" type="number" id="estoque_atual" name="estoque_atual" value="<?= (int) ($dados['estoque_atual'] ?? 0) ?>" min="0" required />
            </div>
        </div>

        <?php if (!empty($dados['imagem'])): ?>
            <div class="form-grupo">
                <label>Imagem Atual</label>
                <div class="d-flex align-center flex-wrap gap-3">
                    <img class="image-preview" src="<?= e(client_product_image_url((string) $dados['imagem'])) ?>" alt="Imagem atual" />
                    <label class="d-flex align-center gap-1 text-muted">
                        <input type="checkbox" name="remover_imagem" value="1" />
                        Remover imagem atual
                    </label>
                </div>
            </div>
        <?php endif; ?>

        <div class="form-row">
            <div class="form-grupo">
                <label for="imagem"><?= !empty($dados['imagem']) ? 'Trocar Imagem' : 'Imagem do Produto' ?></label>
                <input class="form-control" type="file" id="imagem" name="imagem" accept="image/jpeg,image/png,image/webp" />
                <span class="form-hint">JPG, PNG ou WEBP · Máx. 3MB</span>
            </div>
            <div class="form-grupo">
                <label for="status">Status</label>
                <select class="form-control" id="status" name="status">
                    <option value="ativo"   <?= ($dados['status'] ?? '') === 'ativo' ? 'selected' : '' ?>>✅ Ativo (aparece no site)</option>
                    <option value="inativo" <?= ($dados['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>❌ Inativo (oculto no site)</option>
                </select>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-2">
            <button type="submit" class="btn btn-success">💾 Salvar Alterações</button>
            <a href="<?= url('/admin/produtos') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
