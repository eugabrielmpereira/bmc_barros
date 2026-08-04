<?php

declare(strict_types=1);

namespace Database\Migrations;

/**
 * Cobranças Pix via gateway (Asaas) vinculadas a pedidos online.
 */
final class CreateOrderPaymentCharges extends Migration
{
    public function up(): void
    {
        $this->exec(
            "CREATE TABLE IF NOT EXISTS order_payment_charges (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                order_id INT UNSIGNED NOT NULL,
                provider VARCHAR(32) NOT NULL DEFAULT 'asaas',
                external_payment_id VARCHAR(64) NOT NULL,
                external_customer_id VARCHAR(64) DEFAULT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'PENDING',
                billing_type VARCHAR(16) NOT NULL DEFAULT 'PIX',
                amount DECIMAL(10,2) NOT NULL,
                due_date DATE NOT NULL,
                pix_copy_paste TEXT DEFAULT NULL,
                pix_qr_image_base64 MEDIUMTEXT DEFAULT NULL,
                invoice_url VARCHAR(512) DEFAULT NULL,
                paid_at TIMESTAMP NULL DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY order_payment_charges_provider_payment (provider, external_payment_id),
                KEY order_payment_charges_order_id (order_id),
                KEY order_payment_charges_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(): void
    {
        $this->dropIfExists('order_payment_charges');
    }
}
