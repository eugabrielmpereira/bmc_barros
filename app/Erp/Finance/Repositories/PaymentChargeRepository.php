<?php

declare(strict_types=1);

namespace Erp\Finance\Repositories;

use Core\Database\Repository;
use PDO;

class PaymentChargeRepository extends Repository
{
    public function __construct(?PDO $pdo = null)
    {
        parent::__construct('order_payment_charges', $pdo);
    }

    public function findByOrderId(int $orderId): ?array
    {
        if ($orderId <= 0) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE order_id = ? ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public function findByExternalPaymentId(string $provider, string $externalPaymentId): ?array
    {
        $provider = trim($provider);
        $externalPaymentId = trim($externalPaymentId);
        if ($provider === '' || $externalPaymentId === '') {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table}
             WHERE provider = ? AND external_payment_id = ?
             LIMIT 1"
        );
        $stmt->execute([$provider, $externalPaymentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createCharge(array $data): int
    {
        $this->create([
            'order_id' => (int) ($data['order_id'] ?? 0),
            'provider' => (string) ($data['provider'] ?? 'asaas'),
            'external_payment_id' => (string) ($data['external_payment_id'] ?? ''),
            'external_customer_id' => $data['external_customer_id'] ?? null,
            'status' => (string) ($data['status'] ?? 'PENDING'),
            'billing_type' => (string) ($data['billing_type'] ?? 'PIX'),
            'amount' => (float) ($data['amount'] ?? 0),
            'due_date' => (string) ($data['due_date'] ?? date('Y-m-d')),
            'pix_copy_paste' => $data['pix_copy_paste'] ?? null,
            'pix_qr_image_base64' => $data['pix_qr_image_base64'] ?? null,
            'invoice_url' => $data['invoice_url'] ?? null,
            'paid_at' => $data['paid_at'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * @param array<string, mixed> $fields
     */
    public function updateCharge(int $chargeId, array $fields): bool
    {
        return $this->update($chargeId, $fields);
    }
}
