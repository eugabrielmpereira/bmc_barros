<?php

declare(strict_types=1);

namespace Tests\Support;

use Erp\Finance\Contracts\PaymentGatewayInterface;

final class FakePaymentGateway implements PaymentGatewayInterface
{
    public int $createCallCount = 0;

    /**
     * @param array<string, mixed> $chargeResult
     */
    public function __construct(
        private bool $enabled = true,
        private array $chargeResult = [
            'success' => true,
            'message' => 'OK',
            'external_payment_id' => 'pay_fake_1',
            'external_customer_id' => 'cus_fake_1',
            'status' => 'PENDING',
            'due_date' => '2026-06-20',
            'pix_copy_paste' => '00020126580014BR.GOV.BCB.PIX',
            'invoice_url' => 'https://example.test/invoice/1',
        ]
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function providerKey(): string
    {
        return 'fake';
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function createPixCharge(array $context): array
    {
        $this->createCallCount++;

        return $this->chargeResult;
    }
}
