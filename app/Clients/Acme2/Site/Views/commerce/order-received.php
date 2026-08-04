<?php
$client = $client ?? client_config();
$order = $order ?? [];
$items = $items ?? [];
$orderId = (int) ($order['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido recebido - <?= e((string) $client['name']) ?></title>
    <?php commerce_page_styles(); ?>
</head>
<body>
    <main class="commerce-page commerce-layout--single">
        <h1>Pedido recebido #<?= $orderId ?></h1>
        <p>Aguardando análise comercial.</p>
        <p><strong>Total indicativo:</strong> <?= formatarPreco((float) ($order['total_amount'] ?? 0)) ?></p>
        <?php require SHARED_PATH . '/View/site/pix-payment-block.php'; ?>
        <p><a href="<?= BASE_URL ?>/produtos">Continuar comprando</a></p>
    </main>
</body>
</html>
