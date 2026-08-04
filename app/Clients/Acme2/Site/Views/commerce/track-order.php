<?php
$client = $client ?? client_config();
$result = $result ?? null;
$error = $error ?? null;
$reference = $reference ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acompanhar pedido - <?= e((string) $client['name']) ?></title>
</head>
<body>
    <main style="max-width:720px;margin:2rem auto;font-family:system-ui,sans-serif;">
        <h1>Acompanhar pedido</h1>
        <?php if ($error): ?><p style="color:#b91c1c"><?= e((string) $error) ?></p><?php endif; ?>
        <?php if ($result === null): ?>
            <form method="POST" action="<?= BASE_URL ?>/acompanhar-pedido">
                <?= csrf_field() ?>
                <p><label>Referência<br><input type="text" name="public_reference" value="<?= e($reference) ?>" required></label></p>
                <p><label>4 últimos dígitos do telefone<br><input type="text" name="phone_last_digits" maxlength="4" required></label></p>
                <button type="submit">Consultar</button>
            </form>
        <?php else: ?>
            <p><strong>Pedido:</strong> <?= e((string) ($result['public_reference'] ?? '')) ?></p>
            <p><strong>Status:</strong> <?= e((string) ($result['status_label'] ?? '')) ?></p>
            <p><strong>Total:</strong> <?= formatarPreco((float) ($result['total_amount'] ?? 0)) ?></p>
            <p><a href="<?= BASE_URL ?>/acompanhar-pedido">Nova consulta</a></p>
        <?php endif; ?>
        <p><a href="<?= BASE_URL ?>/produtos">Voltar aos produtos</a></p>
    </main>
</body>
</html>
