<?php

declare(strict_types=1);

namespace Erp\Finance\Contracts;

/**
 * Contrato genérico para gateways de pagamento (Pix, boleto, etc.).
 */
interface PaymentGatewayInterface
{
    public function isEnabled(): bool;

    public function providerKey(): string;

    /**
     * @param array{
     *     order_id: int,
     *     public_reference: string,
     *     amount: float,
     *     customer_name: string,
     *     customer_email: string,
     *     customer_phone: string,
     *     customer_document: string
     * } $context
     * @return array{
     *     success: bool,
     *     message: string,
     *     external_payment_id?: string,
     *     external_customer_id?: string,
     *     status?: string,
     *     due_date?: string,
     *     pix_copy_paste?: string,
     *     pix_qr_image_base64?: string,
     *     invoice_url?: string
     * }
     */
    public function createPixCharge(array $context): array;

    /**
     * Cobrança de cartão de crédito paga na fatura hospedada do gateway (PCI fora do servidor).
     *
     * @param array{
     *     order_id: int,
     *     public_reference: string,
     *     amount: float,
     *     installments: int,
     *     customer_name: string,
     *     customer_email: string,
     *     customer_phone: string,
     *     customer_document: string
     * } $context
     * @return array{
     *     success: bool,
     *     message: string,
     *     external_payment_id?: string,
     *     external_customer_id?: string,
     *     status?: string,
     *     due_date?: string,
     *     invoice_url?: string
     * }
     */
    public function createCardCharge(array $context): array;
}
