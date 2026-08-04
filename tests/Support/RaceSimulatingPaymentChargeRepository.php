<?php

declare(strict_types=1);

namespace Tests\Support;

use Erp\Finance\Repositories\PaymentChargeRepository;
use PDO;

/**
 * Simula corrida: primeira leitura não enxerga a charge; leituras seguintes sim.
 */
final class RaceSimulatingPaymentChargeRepository extends PaymentChargeRepository
{
    private int $findByOrderCalls = 0;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    public function findByOrderId(int $orderId): ?array
    {
        $this->findByOrderCalls++;
        if ($this->findByOrderCalls === 1) {
            return null;
        }

        return parent::findByOrderId($orderId);
    }
}
