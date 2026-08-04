<?php

declare(strict_types=1);

// carrega o autoload do Composer
require __DIR__ . '/vendor/autoload.php';

use Erp\Stock\Repositories\StockMovementRepository;

echo "Iniciando teste...\n";

// cria o repository
$repo = new StockMovementRepository();

// cria uma movimentação fake
$repo->createMovement(
    1,             // produto_id (PRECISA EXISTIR NO BANCO)
    'entrada',     // tipo
    5,             // quantidade
    10,            // estoque anterior
    15,            // estoque novo
    'Teste manual',
    null           // user_id opcional
);

echo "Movimento criado com sucesso!";