<?php
$client = $client ?? client_config();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e((string) $client['name']) ?></title>
    <style>
        body { margin: 0; font-family: system-ui, sans-serif; color: #0f172a; background: #f8fafc; }
        main { width: min(960px, calc(100% - 2rem)); margin: 0 auto; padding: 4rem 0; }
        a { color: #1d4ed8; font-weight: 700; }
        .card { padding: 2rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; }
    </style>
</head>
<body>
    <main>
        <div class="card">
            <p>Cliente ativo: <?= e((string) $client['key']) ?></p>
            <h1><?= e((string) $client['name']) ?></h1>
            <p><?= e((string) $client['tagline']) ?></p>
            <p>Bem-vindo à loja online. Personalize esta página conforme o branding do tenant.</p>
            <p><a href="<?= BASE_URL ?>/produtos">Ver catálogo</a></p>
        </div>
    </main>
</body>
</html>
