<?php

declare(strict_types=1);

namespace Erp\Orders\Contracts;

interface PublicOrderReceiptReader
{
    /**
     * @return array{order: array<string, mixed>, items: list<array<string, mixed>>}|null
     */
    public function getPublicReceipt(int $orderId): ?array;
}
