<?php

declare(strict_types=1);

namespace Tests\Support;

use Erp\Orders\Contracts\PublicOrderReceiptReader;

final class FakePublicOrderReceiptReader implements PublicOrderReceiptReader
{
    /** @var array{order: array<string, mixed>, items: list<array<string, mixed>>}|null */
    private ?array $receipt;

    /**
     * @param array{order: array<string, mixed>, items: list<array<string, mixed>>}|null $receipt
     */
    public function __construct(?array $receipt = null)
    {
        $this->receipt = $receipt;
    }

    public function getPublicReceipt(int $orderId): ?array
    {
        return $this->receipt;
    }
}
