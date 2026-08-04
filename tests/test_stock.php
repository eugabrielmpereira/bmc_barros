<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Erp\Stock\Services\StockService;

echo "Iniciando teste StockService...\n";

try {
    $service = new StockService();

    $service->addStock(
        produtoId: 1,
        quantidade: 5,
        userId: 1,
        reason: "teste direto no service"
    );

    echo "✔ Entrada realizada com sucesso!\n";

} catch (Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}