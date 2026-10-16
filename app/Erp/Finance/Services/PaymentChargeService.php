<?php

declare(strict_types=1);

namespace Erp\Finance\Services;

use Core\Database;
use Erp\Finance\Contracts\PaymentGatewayInterface;
use Erp\Finance\Gateways\Asaas\AsaasPixGateway;
use Erp\Finance\Gateways\PaymentGatewayFactory;
use Erp\Finance\Repositories\PaymentChargeRepository;
use Erp\Finance\Support\PaymentMethod;
use Erp\Orders\Repositories\OrderRepository;
use PDO;
use PDOException;
use Throwable;

final class PaymentChargeService
{
    public function __construct(
        private ?PDO $pdo = null,
        private ?PaymentChargeRepository $chargeRepository = null,
        private ?OrderRepository $orderRepository = null,
        private ?PaymentGatewayInterface $gateway = null
    ) {
        $this->pdo ??= Database::getInstance();
        $this->chargeRepository ??= new PaymentChargeRepository($this->pdo);
        $this->orderRepository ??= new OrderRepository($this->pdo);
    }

    public function gateway(): ?PaymentGatewayInterface
    {
        if ($this->gateway !== null) {
            return $this->gateway->isEnabled() ? $this->gateway : null;
        }

        return PaymentGatewayFactory::resolve();
    }

    /**
     * @param array<string, mixed> $customerData
     * @return array<string, mixed>|null
     */
    public function createPixForOnlineOrder(int $orderId, array $customerData): ?array
    {
        $gateway = $this->gateway();
        if ($gateway === null) {
            return null;
        }

        $existingCharge = $this->chargeRepository->findByOrderId($orderId);
        if ($existingCharge !== null) {
            return $existingCharge;
        }

        $order = $this->orderRepository->find($orderId);
        if ($order === null) {
            return null;
        }

        $isCard = PaymentMethod::parse((string) ($order['payment_method'] ?? PaymentMethod::PIX)) === PaymentMethod::CREDIT_CARD;

        try {
            $context = [
                'order_id' => $orderId,
                'public_reference' => (string) ($order['public_reference'] ?? ''),
                'amount' => (float) ($order['total_amount'] ?? 0),
                'customer_name' => (string) ($customerData['name'] ?? ''),
                'customer_email' => (string) ($customerData['email'] ?? ''),
                'customer_phone' => (string) ($customerData['phone'] ?? ''),
                'customer_document' => (string) ($customerData['document'] ?? ''),
            ];

            if ($isCard) {
                $context['installments'] = max(1, (int) ($order['installments'] ?? 1));
                $result = $gateway->createCardCharge($context);
            } else {
                $result = $gateway->createPixCharge($context);
            }

            if (!$result['success']) {
                error_log('[payment_charge] order ' . $orderId . ': ' . ($result['message'] ?? 'falha'));

                return null;
            }

            try {
                $chargeId = $this->chargeRepository->createCharge([
                    'order_id' => $orderId,
                    'provider' => $gateway->providerKey(),
                    'external_payment_id' => (string) ($result['external_payment_id'] ?? ''),
                    'external_customer_id' => $result['external_customer_id'] ?? null,
                    'status' => (string) ($result['status'] ?? 'PENDING'),
                    'billing_type' => $isCard ? 'CREDIT_CARD' : 'PIX',
                    'amount' => (float) ($order['total_amount'] ?? 0),
                    'due_date' => (string) ($result['due_date'] ?? date('Y-m-d')),
                    'pix_copy_paste' => $result['pix_copy_paste'] ?? null,
                    'pix_qr_image_base64' => $result['pix_qr_image_base64'] ?? null,
                    'invoice_url' => $result['invoice_url'] ?? null,
                ]);
            } catch (Throwable $insertError) {
                if ($this->isDuplicateOrderChargeException($insertError)) {
                    $raceCharge = $this->chargeRepository->findByOrderId($orderId);
                    if ($raceCharge !== null) {
                        return $raceCharge;
                    }
                }

                throw $insertError;
            }

            return $this->chargeRepository->find($chargeId);
        } catch (Throwable $e) {
            error_log('[payment_charge] exception order ' . $orderId . ': ' . $e->getMessage());

            return null;
        }
    }

    public function findForOrder(int $orderId): ?array
    {
        return $this->chargeRepository->findByOrderId($orderId);
    }

    public function hasPaidCharge(int $orderId): bool
    {
        $charge = $this->findForOrder($orderId);
        if ($charge === null) {
            return false;
        }

        return self::isPaidStatus((string) ($charge['status'] ?? ''));
    }

    public static function isPaidStatus(string $status): bool
    {
        return in_array(strtoupper(trim($status)), AsaasPixGateway::PAID_STATUSES, true);
    }

    private function isDuplicateOrderChargeException(Throwable $exception): bool
    {
        if ($exception instanceof PDOException) {
            $sqlState = (string) ($exception->getCode() ?? '');
            if ($sqlState === '23000') {
                return true;
            }
        }

        $message = strtolower($exception->getMessage());

        return str_contains($message, '1062')
            || str_contains($message, 'unique constraint failed')
            || str_contains($message, 'order_payment_charges_order_id_unique');
    }
}
