<?php

declare(strict_types=1);

namespace Erp\Finance\Gateways\Asaas;

use Erp\Finance\Contracts\PaymentGatewayInterface;
use Shared\Support\Phone;

final class AsaasPixGateway implements PaymentGatewayInterface
{
    /** Status Asaas considerados como pagamento recebido. */
    public const PAID_STATUSES = ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'];

    public function __construct(private ?AsaasHttpClient $httpClient = null)
    {
        $this->httpClient ??= new AsaasHttpClient();
    }

    public function isEnabled(): bool
    {
        return AsaasConfig::isEnabled();
    }

    public function providerKey(): string
    {
        return 'asaas';
    }

    public function createPixCharge(array $context): array
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => 'Gateway Asaas desabilitado.'];
        }

        $orderId = (int) ($context['order_id'] ?? 0);
        $amount = round((float) ($context['amount'] ?? 0), 2);
        if ($orderId <= 0 || $amount <= 0) {
            return ['success' => false, 'message' => 'Pedido ou valor inválido para cobrança Pix.'];
        }

        $document = $this->onlyDigits((string) ($context['customer_document'] ?? ''));
        if ($document === '') {
            return ['success' => false, 'message' => 'CPF/CNPJ obrigatório para gerar Pix.'];
        }

        $customerId = $this->findOrCreateCustomer($context, $document);
        if ($customerId === null) {
            return ['success' => false, 'message' => 'Não foi possível registrar o cliente no gateway.'];
        }

        $reference = trim((string) ($context['public_reference'] ?? ''));
        $description = $reference !== ''
            ? 'Pedido ' . $reference
            : 'Pedido #' . $orderId;

        $dueDate = (new \DateTimeImmutable('today'))
            ->modify('+' . AsaasConfig::pixDueDays() . ' days')
            ->format('Y-m-d');

        $paymentResponse = $this->httpClient->post('/payments', [
            'customer' => $customerId,
            'billingType' => 'PIX',
            'value' => $amount,
            'dueDate' => $dueDate,
            'description' => $description,
            'externalReference' => (string) $orderId,
        ]);

        if (!$paymentResponse['success']) {
            error_log('[asaas_pix] create payment failed: ' . $paymentResponse['message']);

            return [
                'success' => false,
                'message' => $paymentResponse['message'],
            ];
        }

        /** @var array<string, mixed> $payment */
        $payment = $paymentResponse['data'];
        $paymentId = (string) ($payment['id'] ?? '');
        if ($paymentId === '') {
            return ['success' => false, 'message' => 'Gateway não retornou identificador da cobrança.'];
        }

        $qrResponse = $this->httpClient->get('/payments/' . rawurlencode($paymentId) . '/pixQrCode');
        $pixPayload = '';
        $pixImage = '';
        if ($qrResponse['success'] && is_array($qrResponse['data'])) {
            $pixPayload = (string) ($qrResponse['data']['payload'] ?? '');
            $pixImage = (string) ($qrResponse['data']['encodedImage'] ?? '');
        }

        return [
            'success' => true,
            'message' => 'Cobrança Pix criada.',
            'external_payment_id' => $paymentId,
            'external_customer_id' => $customerId,
            'status' => (string) ($payment['status'] ?? 'PENDING'),
            'due_date' => $dueDate,
            'pix_copy_paste' => $pixPayload,
            'pix_qr_image_base64' => $pixImage,
            'invoice_url' => (string) ($payment['invoiceUrl'] ?? ''),
        ];
    }

    public function createCardCharge(array $context): array
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => 'Gateway Asaas desabilitado.'];
        }

        $orderId = (int) ($context['order_id'] ?? 0);
        $amount = round((float) ($context['amount'] ?? 0), 2);
        if ($orderId <= 0 || $amount <= 0) {
            return ['success' => false, 'message' => 'Pedido ou valor inválido para cobrança no cartão.'];
        }

        $document = $this->onlyDigits((string) ($context['customer_document'] ?? ''));
        if ($document === '') {
            return ['success' => false, 'message' => 'CPF/CNPJ obrigatório para cobrança no cartão.'];
        }

        $customerId = $this->findOrCreateCustomer($context, $document);
        if ($customerId === null) {
            return ['success' => false, 'message' => 'Não foi possível registrar o cliente no gateway.'];
        }

        $reference = trim((string) ($context['public_reference'] ?? ''));
        $installments = max(1, min((int) ($context['installments'] ?? 1), 12));
        $dueDate = (new \DateTimeImmutable('today'))->modify('+1 day')->format('Y-m-d');

        $payload = [
            'customer' => $customerId,
            'billingType' => 'CREDIT_CARD',
            'dueDate' => $dueDate,
            'description' => $reference !== '' ? 'Pedido ' . $reference : 'Pedido #' . $orderId,
            'externalReference' => (string) $orderId,
        ];

        if ($installments > 1) {
            // Asaas divide o total em N parcelas; o cliente informa o cartão na fatura hospedada.
            $payload['installmentCount'] = $installments;
            $payload['totalValue'] = $amount;
        } else {
            $payload['value'] = $amount;
        }

        $response = $this->httpClient->post('/payments', $payload);
        if (!$response['success'] || !is_array($response['data'])) {
            error_log('[asaas_card] create payment failed: ' . $response['message']);

            return ['success' => false, 'message' => $response['message']];
        }

        $payment = $response['data'];
        $paymentId = (string) ($payment['id'] ?? '');
        if ($paymentId === '') {
            return ['success' => false, 'message' => 'Gateway não retornou identificador da cobrança.'];
        }

        return [
            'success' => true,
            'message' => 'Cobrança no cartão criada.',
            'external_payment_id' => $paymentId,
            'external_customer_id' => $customerId,
            'status' => (string) ($payment['status'] ?? 'PENDING'),
            'due_date' => $dueDate,
            'invoice_url' => (string) ($payment['invoiceUrl'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $context
     */
    private function findOrCreateCustomer(array $context, string $document): ?string
    {
        $lookup = $this->httpClient->get('/customers', ['cpfCnpj' => $document]);
        if ($lookup['success'] && is_array($lookup['data'])) {
            $items = $lookup['data']['data'] ?? null;
            if (is_array($items) && isset($items[0]) && is_array($items[0])) {
                $existingId = (string) ($items[0]['id'] ?? '');
                if ($existingId !== '') {
                    return $existingId;
                }
            }
        }

        $phone = Phone::normalize((string) ($context['customer_phone'] ?? ''));
        $mobilePhone = strlen($phone) >= 10 ? $phone : null;

        $create = $this->httpClient->post('/customers', array_filter([
            'name' => trim((string) ($context['customer_name'] ?? 'Cliente')),
            'cpfCnpj' => $document,
            'email' => trim((string) ($context['customer_email'] ?? '')),
            'mobilePhone' => $mobilePhone,
            'externalReference' => (string) ((int) ($context['order_id'] ?? 0)),
        ], static fn ($value): bool => $value !== null && $value !== ''));

        if (!$create['success'] || !is_array($create['data'])) {
            error_log('[asaas_pix] create customer failed: ' . $create['message']);

            return null;
        }

        $customerId = (string) ($create['data']['id'] ?? '');

        return $customerId !== '' ? $customerId : null;
    }

    private function onlyDigits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }
}
