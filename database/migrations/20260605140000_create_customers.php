<?php

declare(strict_types=1);

namespace Database\Migrations;

final class CreateCustomers extends Migration
{
    public function up(): void
    {
        $customersSql = <<<SQL
            CREATE TABLE IF NOT EXISTS customers (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                commercial_client_id BIGINT UNSIGNED NOT NULL,
                email VARCHAR(190) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                name VARCHAR(160) NOT NULL,
                phone VARCHAR(40) NULL DEFAULT NULL,
                document VARCHAR(40) NULL DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY customers_email (email),
                KEY customers_commercial_client (commercial_client_id),
                KEY customers_status (status),
                CONSTRAINT fk_customers_commercial_client FOREIGN KEY (commercial_client_id) REFERENCES commercial_clients (id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL;

        $addressesSql = <<<SQL
            CREATE TABLE IF NOT EXISTS customer_addresses (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                customer_id BIGINT UNSIGNED NOT NULL,
                label VARCHAR(60) NOT NULL DEFAULT 'Principal',
                postal_code VARCHAR(10) NULL DEFAULT NULL,
                street VARCHAR(255) NULL DEFAULT NULL,
                street_number VARCHAR(20) NULL DEFAULT NULL,
                complement VARCHAR(100) NULL DEFAULT NULL,
                neighborhood VARCHAR(100) NULL DEFAULT NULL,
                city VARCHAR(100) NULL DEFAULT NULL,
                state CHAR(2) NULL DEFAULT NULL,
                is_default TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY customer_addresses_customer (customer_id),
                KEY customer_addresses_default (customer_id, is_default),
                CONSTRAINT fk_customer_addresses_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL;

        $this->exec($customersSql);
        $this->exec($addressesSql);
    }

    public function down(): void
    {
        $this->dropIfExists('customer_addresses');
        $this->dropIfExists('customers');
    }
}
