<?php
$client = $client ?? client_config();
$cart = $cart ?? ['items' => [], 'item_count' => 0, 'subtotal' => 0.0];
$items = $cart['items'] ?? [];
$msg = $msg ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrinho - <?= e((string) $client['name']) ?></title>
    <style>
        body { margin: 0; font-family: system-ui, sans-serif; color: #0f172a; background: #f8fafc; }
        main { width: min(960px, calc(100% - 2rem)); margin: 0 auto; padding: 3rem 0; }
        .panel { display: grid; gap: 1rem; padding: 1.5rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; }
        input, button, a.button { padding: .75rem 1rem; border-radius: .75rem; }
    </style>
</head>
<body>
    <main>
        <p><a href="<?= BASE_URL ?>/produtos">Produtos</a></p>
        <h1>Carrinho</h1>
        <?php if ($msg): ?><p><?= e((string) $msg) ?></p><?php endif; ?>
        <?php if ($items === []): ?>
            <div class="panel">Seu carrinho está vazio.</div>
        <?php else: ?>
            <form method="POST" action="<?= BASE_URL ?>/carrinho/atualizar" class="panel">
                <?= csrf_field() ?>
                <?php foreach ($items as $item): ?>
                    <label>
                        <?= e((string) $item['name']) ?> - <?= formatarPreco((float) $item['unit_price']) ?>
                        <input type="number" name="quantities[<?= (int) $item['product_id'] ?>]" min="0" step="1" value="<?= (int) $item['quantity'] ?>">
                    </label>
                    <form method="POST" action="<?= BASE_URL ?>/carrinho/remover" style="margin:0">
                        <?= csrf_field() ?>
                        <input type="hidden" name="product_id" value="<?= (int) $item['product_id'] ?>">
                        <button type="submit">Remover</button>
                    </form>
                <?php endforeach; ?>
                <strong>Total: <?= formatarPreco((float) $cart['subtotal']) ?></strong>
                <button type="submit">Atualizar</button>
                <a class="button" href="<?= BASE_URL ?>/checkout">Solicitar pedido</a>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>
