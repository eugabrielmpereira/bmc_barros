<?php

declare(strict_types=1);

namespace Database\Migrations;

/**
 * Garante no máximo uma cobrança Pix por pedido (proteção contra race condition no checkout).
 */
final class AddUniqueOrderIdToOrderPaymentCharges extends Migration
{
    public function up(): void
    {
        $duplicates = $this->pdo->query(
            'SELECT order_id, COUNT(*) AS row_count
             FROM order_payment_charges
             GROUP BY order_id
             HAVING row_count > 1'
        )->fetchAll(\PDO::FETCH_ASSOC);

        if ($duplicates !== []) {
            throw new \RuntimeException(
                'Migration aborted: duplicate order_id in order_payment_charges — resolve manually before retry. '
                . json_encode($duplicates, JSON_UNESCAPED_UNICODE)
            );
        }

        $indexStmt = $this->pdo->query("SHOW INDEX FROM order_payment_charges WHERE Key_name = 'order_payment_charges_order_id'");
        if ($indexStmt !== false && $indexStmt->fetch() !== false) {
            $this->exec('ALTER TABLE order_payment_charges DROP INDEX order_payment_charges_order_id');
        }

        $uniqueStmt = $this->pdo->query(
            "SHOW INDEX FROM order_payment_charges WHERE Key_name = 'order_payment_charges_order_id_unique'"
        );
        if ($uniqueStmt !== false && $uniqueStmt->fetch() === false) {
            $this->exec(
                'ALTER TABLE order_payment_charges
                 ADD UNIQUE KEY order_payment_charges_order_id_unique (order_id)'
            );
        }
    }

    public function down(): void
    {
        $uniqueStmt = $this->pdo->query(
            "SHOW INDEX FROM order_payment_charges WHERE Key_name = 'order_payment_charges_order_id_unique'"
        );
        if ($uniqueStmt !== false && $uniqueStmt->fetch() !== false) {
            $this->exec('ALTER TABLE order_payment_charges DROP INDEX order_payment_charges_order_id_unique');
        }

        $indexStmt = $this->pdo->query("SHOW INDEX FROM order_payment_charges WHERE Key_name = 'order_payment_charges_order_id'");
        if ($indexStmt !== false && $indexStmt->fetch() === false) {
            $this->exec('ALTER TABLE order_payment_charges ADD KEY order_payment_charges_order_id (order_id)');
        }
    }
}
