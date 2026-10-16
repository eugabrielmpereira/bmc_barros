<?php

declare(strict_types=1);

namespace Erp\Orders\Repositories;

use Core\Database\Repository;
use PDO;

final class OrderRepository extends Repository
{
    public function __construct(?PDO $pdo = null)
    {
        parent::__construct('orders', $pdo);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForAdmin(string $search = ''): array
    {
        $sql = "SELECT o.*, cc.name AS client_name, u.nome AS created_by_name
                FROM {$this->table} o
                INNER JOIN commercial_clients cc ON cc.id = o.commercial_client_id
                LEFT JOIN usuarios u ON u.id = o.created_by
                WHERE 1=1";
        $params = [];

        $search = trim($search);
        if (str_starts_with($search, '#')) {
            $search = ltrim($search, '#');
        }

        if ($search !== '') {
            $like = '%' . $search . '%';
            $documentDigits = preg_replace('/\D+/', '', $search) ?? '';
            $conditions = [
                'cc.name LIKE ?',
                'cc.email LIKE ?',
                'cc.document LIKE ?',
                'cc.phone LIKE ?',
                'o.public_reference LIKE ?',
            ];
            $params = [$like, $like, $like, $like, $like];

            if ($documentDigits !== '') {
                $conditions[] = "REPLACE(REPLACE(REPLACE(REPLACE(cc.document, '.', ''), '-', ''), '/', ''), ' ', '') LIKE ?";
                $params[] = '%' . $documentDigits . '%';
            }

            if (ctype_digit($search)) {
                $conditions[] = 'o.id = ?';
                $params[] = (int) $search;
            }

            $sql .= ' AND (' . implode(' OR ', $conditions) . ')';
        }

        $sql .= ' ORDER BY o.created_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findForAdmin(int $orderId): ?array
    {
        if ($orderId <= 0) {
            return null;
        }

        $sql = "SELECT o.*,
                       cc.name AS client_name,
                       cc.document AS client_document,
                       cc.email AS client_email,
                       cc.phone AS client_phone,
                       cc.address AS client_address,
                       u.nome AS created_by_name
                FROM {$this->table} o
                INNER JOIN commercial_clients cc ON cc.id = o.commercial_client_id
                LEFT JOIN usuarios u ON u.id = o.created_by
                WHERE o.id = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public function createOrder(int $clientId, string $status, float $total, ?string $notes, ?int $userId): int
    {
        $this->create([
            'commercial_client_id' => $clientId,
            'status' => $status,
            'total_amount' => $total,
            'notes' => $notes,
            'created_by' => $userId,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createPublicOrder(array $data): int
    {
        $payload = [
            'commercial_client_id' => (int) ($data['commercial_client_id'] ?? 0),
            'status' => (string) ($data['status'] ?? 'draft'),
            'total_amount' => (float) ($data['total_amount'] ?? 0),
            'shipping_fee' => (float) ($data['shipping_fee'] ?? 0),
            'delivery_zone_id' => $data['delivery_zone_id'] ?? null,
            'delivery_zone_name' => $data['delivery_zone_name'] ?? null,
            'payment_method' => (string) ($data['payment_method'] ?? 'pix'),
            'installments' => max(1, (int) ($data['installments'] ?? 1)),
            'notes' => $data['notes'] ?? null,
            'internal_notes' => $data['internal_notes'] ?? null,
            'customer_notes' => $data['customer_notes'] ?? null,
            'public_reference' => $data['public_reference'] ?? null,
            'ownership_verified' => (int) ($data['ownership_verified'] ?? 1),
            'shipping_postal_code' => $data['shipping_postal_code'] ?? null,
            'shipping_street' => $data['shipping_street'] ?? null,
            'shipping_number' => $data['shipping_number'] ?? null,
            'shipping_complement' => $data['shipping_complement'] ?? null,
            'shipping_neighborhood' => $data['shipping_neighborhood'] ?? null,
            'shipping_city' => $data['shipping_city'] ?? null,
            'shipping_state' => $data['shipping_state'] ?? null,
            'delivery_method' => $data['delivery_method'] ?? 'delivery',
            'fulfillment_status' => $data['fulfillment_status'] ?? 'pending',
            'created_by' => $data['created_by'] ?? null,
        ];

        $this->create($payload);

        return (int) $this->db->lastInsertId();
    }

    public function findByPublicReference(string $reference): ?array
    {
        $reference = trim($reference);
        if ($reference === '') {
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE public_reference = ? LIMIT 1");
        $stmt->execute([$reference]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public function publicReferenceExists(string $reference): bool
    {
        return $this->findByPublicReference($reference) !== null;
    }

    public function updateStatus(int $orderId, string $status): bool
    {
        return $this->update($orderId, ['status' => $status]);
    }

    public function updatePaymentStatus(
        int $orderId,
        string $paymentStatus,
        ?string $paidAt = null,
        bool $clearPaidAt = false
    ): bool {
        $payload = ['payment_status' => $paymentStatus];
        if ($clearPaidAt) {
            $payload['paid_at'] = null;
        } elseif ($paidAt !== null) {
            $payload['paid_at'] = $paidAt;
        }

        return $this->update($orderId, $payload);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listOpenReceivables(string $search = ''): array
    {
        $sql = "SELECT o.*, cc.name AS client_name, cc.phone AS client_phone, cc.email AS client_email
                FROM {$this->table} o
                INNER JOIN commercial_clients cc ON cc.id = o.commercial_client_id
                WHERE o.status = 'confirmed' AND o.payment_status = 'pending'";
        $params = [];

        $search = trim($search);
        if (str_starts_with($search, '#')) {
            $search = ltrim($search, '#');
        }

        if ($search !== '') {
            $like = '%' . $search . '%';
            $conditions = [
                'cc.name LIKE ?',
                'cc.email LIKE ?',
                'cc.phone LIKE ?',
                'o.public_reference LIKE ?',
            ];
            $params = [$like, $like, $like, $like];

            if (ctype_digit($search)) {
                $conditions[] = 'o.id = ?';
                $params[] = (int) $search;
            }

            $sql .= ' AND (' . implode(' OR ', $conditions) . ')';
        }

        $sql .= ' ORDER BY o.created_at ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countOpenReceivables(): int
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM {$this->table} WHERE status = 'confirmed' AND payment_status = 'pending'"
        );

        return (int) $stmt->fetchColumn();
    }

    public function sumOpenReceivables(): float
    {
        $stmt = $this->db->query(
            "SELECT COALESCE(SUM(total_amount), 0) FROM {$this->table} WHERE status = 'confirmed' AND payment_status = 'pending'"
        );

        return (float) $stmt->fetchColumn();
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE status = ?");
        $stmt->execute([$status]);

        return (int) $stmt->fetchColumn();
    }

    public function countByStatusBetween(string $status, string $startDateTime, string $endDateTime): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE status = ? AND created_at BETWEEN ? AND ?"
        );
        $stmt->execute([$status, $startDateTime, $endDateTime]);

        return (int) $stmt->fetchColumn();
    }

    public function sumTotal(): float
    {
        $stmt = $this->db->query("SELECT COALESCE(SUM(total_amount), 0) FROM {$this->table}");

        return (float) $stmt->fetchColumn();
    }

    public function sumTotalByStatusBetween(string $status, string $startDateTime, string $endDateTime): float
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(total_amount), 0) FROM {$this->table} WHERE status = ? AND created_at BETWEEN ? AND ?"
        );
        $stmt->execute([$status, $startDateTime, $endDateTime]);

        return (float) $stmt->fetchColumn();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listByPeriod(string $startDateTime, string $endDateTime): array
    {
        $sql = "SELECT o.id, o.status, o.total_amount, o.created_at, cc.name AS client_name
                FROM {$this->table} o
                INNER JOIN commercial_clients cc ON cc.id = o.commercial_client_id
                WHERE o.created_at BETWEEN ? AND ?
                ORDER BY o.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDateTime, $endDateTime]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function updateTotalAmount(int $orderId, float $total): bool
    {
        return $this->update($orderId, ['total_amount' => $total]);
    }

    /**
     * @param array<string, mixed> $shipping
     */
    public function updateShippingFields(int $orderId, array $shipping): bool
    {
        return $this->update($orderId, [
            'shipping_postal_code' => $shipping['shipping_postal_code'] ?? null,
            'shipping_street' => $shipping['shipping_street'] ?? null,
            'shipping_number' => $shipping['shipping_number'] ?? null,
            'shipping_complement' => $shipping['shipping_complement'] ?? null,
            'shipping_neighborhood' => $shipping['shipping_neighborhood'] ?? null,
            'shipping_city' => $shipping['shipping_city'] ?? null,
            'shipping_state' => $shipping['shipping_state'] ?? null,
        ]);
    }

    public function updateOperatorNotes(int $orderId, ?string $notes): bool
    {
        return $this->update($orderId, ['notes' => $notes !== null && trim($notes) !== '' ? trim($notes) : null]);
    }

    /**
     * @param array<string, mixed> $fields
     */
    public function updateFulfillmentFields(int $orderId, array $fields): bool
    {
        $allowed = [
            'delivery_method',
            'fulfillment_status',
            'fulfillment_notes',
            'fulfillment_updated_at',
        ];
        $payload = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $fields)) {
                $payload[$key] = $fields[$key];
            }
        }

        return $payload !== [] ? $this->update($orderId, $payload) : false;
    }

    public function countByCommercialClient(int $clientId): int
    {
        if ($clientId <= 0) {
            return 0;
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE commercial_client_id = ?");
        $stmt->execute([$clientId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listByCommercialClient(int $clientId, int $limit = 50): array
    {
        if ($clientId <= 0) {
            return [];
        }

        $limit = max(1, min(100, $limit));
        $sql = "SELECT id, status, total_amount, public_reference, created_at,
                       shipping_city, shipping_state, delivery_method, fulfillment_status
                FROM {$this->table}
                WHERE commercial_client_id = ?
                ORDER BY created_at DESC
                LIMIT {$limit}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clientId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findByPublicReferenceForClient(string $reference, int $commercialClientId): ?array
    {
        $reference = trim($reference);
        if ($reference === '' || $commercialClientId <= 0) {
            return null;
        }

        $sql = "SELECT o.*
                FROM {$this->table} o
                WHERE o.public_reference = ? AND o.commercial_client_id = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$reference, $commercialClientId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public function findForPublicTracking(string $reference): ?array
    {
        $reference = trim($reference);
        if ($reference === '') {
            return null;
        }

        $sql = "SELECT o.id, o.status, o.total_amount, o.public_reference, o.created_at,
                       o.shipping_city, o.shipping_state, o.delivery_method, o.fulfillment_status,
                       cc.phone AS client_phone
                FROM {$this->table} o
                INNER JOIN commercial_clients cc ON cc.id = o.commercial_client_id
                WHERE o.public_reference = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$reference]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }
}
