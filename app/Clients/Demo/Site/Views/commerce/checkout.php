<?php
$client = $client ?? client_config();
$cart = $cart ?? ['items' => [], 'item_count' => 0, 'subtotal' => 0.0];
$items = $cart['items'] ?? [];
$errors = $errors ?? [];
$field_errors = $field_errors ?? [];
$data = $data ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?= e((string) $client['name']) ?></title>
    <style>
        body { margin: 0; font-family: system-ui, sans-serif; color: #0f172a; background: #f8fafc; }
        main { width: min(960px, calc(100% - 2rem)); margin: 0 auto; padding: 3rem 0; }
        form, .panel { display: grid; gap: 1rem; padding: 1.5rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; }
        input, textarea, button { padding: .75rem 1rem; border-radius: .75rem; border: 1px solid #cbd5e1; }
    </style>
</head>
<body>
    <main>
        <p><a href="<?= BASE_URL ?>/carrinho">Voltar ao carrinho</a></p>
        <h1>Solicitar pedido</h1>
        <?php if ($items === []): ?>
            <div class="panel">Seu carrinho está vazio.</div>
        <?php else: ?>
            <?php foreach ($errors as $error): ?><p><?= e((string) $error) ?></p><?php endforeach; ?>
            <form method="POST" action="<?= BASE_URL ?>/checkout">
                <?= csrf_field() ?>
                <label>Nome <input type="text" name="name" value="<?= e((string) ($data['name'] ?? '')) ?>" required></label>
                <label>Telefone / WhatsApp <input type="text" name="phone" value="<?= e((string) ($data['phone'] ?? '')) ?>" required></label>
                <label>E-mail <input type="email" name="email" value="<?= e((string) ($data['email'] ?? '')) ?>"></label>
                <label>CPF/CNPJ <input type="text" name="document" value="<?= e((string) ($data['document'] ?? '')) ?>"></label>
                <?php
                $fieldErrors = $field_errors ?? [];
                require SHARED_PATH . '/View/site/checkout-delivery-section.php';
                ?>
                <label>Observações <textarea name="notes" rows="4"><?= e((string) ($data['notes'] ?? '')) ?></textarea></label>
                <strong>Total: <?= formatarPreco((float) $cart['subtotal']) ?></strong>
                <button type="submit">Enviar para análise</button>
            </form>
        <?php endif; ?>
    </main>
    <?php commerce_assets(true); ?>
</body>
</html>
