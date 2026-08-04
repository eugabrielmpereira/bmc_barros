<?php
$data = $data ?? [];
$errors = $errors ?? [];
$clients = $clients ?? [];
$products = $products ?? [];
?>
<?php if ($errors !== []): ?><div class="alerta alerta-erro"><?php foreach ($errors as $error): ?><p><?= e((string) $error) ?></p><?php endforeach; ?></div><?php endif; ?>
<form method="POST" action="<?= url('/admin/pedidos/criar') ?>" class="card-admin card-body">
    <?= csrf_field() ?>
    <label>
        Cliente
        <select class="form-control" name="commercial_client_id" required>
            <option value="">Selecione</option>
            <?php foreach ($clients as $client): ?>
                <option value="<?= (int) $client['id'] ?>"<?= (int) ($data['commercial_client_id'] ?? 0) === (int) $client['id'] ? ' selected' : '' ?>><?= e((string) $client['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>
        Produto
        <select class="form-control" name="product_id" required>
            <option value="">Selecione</option>
            <?php foreach ($products as $product): ?>
                <option value="<?= (int) $product['id'] ?>"<?= (int) ($data['product_id'] ?? 0) === (int) $product['id'] ? ' selected' : '' ?>>
                    <?= e((string) $product['nome']) ?> - <?= formatarPreco($product['preco'] !== null ? (float) $product['preco'] : null) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>
        Quantidade
        <input class="form-control" type="number" name="quantity" min="1" step="1" value="<?= (int) ($data['quantity'] ?? 1) ?>" required>
    </label>
    <label>
        Status
        <?php $status = (string) ($data['status'] ?? 'draft'); ?>
        <select class="form-control" name="status">
            <option value="draft"<?= $status === 'draft' ? ' selected' : '' ?>>Pendente / Orçamento</option>
            <option value="confirmed"<?= $status === 'confirmed' ? ' selected' : '' ?>>Faturado / Aprovar agora</option>
        </select>
    </label>
    <label>
        Observações
        <textarea class="form-control" name="notes" rows="4"><?= e((string) ($data['notes'] ?? '')) ?></textarea>
    </label>
    <div class="d-flex gap-2 mt-3">
        <button class="btn btn-success" type="submit">Salvar</button>
        <a class="btn btn-secondary" href="<?= url('/admin/pedidos') ?>">Cancelar</a>
    </div>
</form>
