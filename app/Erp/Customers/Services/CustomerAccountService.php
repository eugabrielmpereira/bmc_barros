<?php

declare(strict_types=1);

namespace Erp\Customers\Services;

use Erp\Customers\Repositories\CustomerAddressRepository;
use Erp\Customers\Repositories\CustomerRepository;
use Erp\Fulfillment\Support\DeliveryMethod;
use Erp\Fulfillment\Support\DeliveryMethodLabel;
use Erp\Fulfillment\Support\FulfillmentStatusLabel;
use Erp\Orders\Repositories\OrderItemRepository;
use Erp\Orders\Repositories\OrderRepository;
use Shared\Support\BrazilianAddress;
use Shared\Support\TaxId;

final class CustomerAccountService
{
    public function __construct(
        private ?CustomerRepository $customerRepository = null,
        private ?CustomerAddressRepository $addressRepository = null,
        private ?OrderRepository $orderRepository = null,
        private ?OrderItemRepository $orderItemRepository = null
    ) {
        $this->customerRepository ??= new CustomerRepository();
        $this->addressRepository ??= new CustomerAddressRepository();
        $this->orderRepository ??= new OrderRepository();
        $this->orderItemRepository ??= new OrderItemRepository();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getProfile(int $customerId): ?array
    {
        if ($customerId <= 0) {
            return null;
        }

        $customer = $this->customerRepository->find($customerId);
        if ($customer === null) {
            return null;
        }

        unset($customer['password_hash']);

        return $customer;
    }

    /**
     * @return array{success: bool, message: string, field_errors?: array<string, string>}
     */
    public function updateProfile(int $customerId, array $post): array
    {
        $customer = $this->customerRepository->find($customerId);
        if ($customer === null) {
            return ['success' => false, 'message' => 'Conta não encontrada.'];
        }

        $name = trim((string) ($post['name'] ?? ''));
        $phone = trim((string) ($post['phone'] ?? ''));
        $document = trim((string) ($post['document'] ?? ''));

        $fieldErrors = [];
        if ($name === '') {
            $fieldErrors['name'] = 'Nome é obrigatório.';
        }
        if ($phone === '') {
            $fieldErrors['phone'] = 'Telefone é obrigatório.';
        }
        if ($document === '') {
            $fieldErrors['document'] = 'CPF/CNPJ é obrigatório.';
        } elseif (!TaxId::isValidDocument($document)) {
            $fieldErrors['document'] = 'CPF/CNPJ inválido.';
        }

        if ($fieldErrors !== []) {
            return [
                'success' => false,
                'message' => 'Revise os dados informados.',
                'field_errors' => $fieldErrors,
            ];
        }

        $updated = $this->customerRepository->updateProfile($customerId, [
            'name' => $name,
            'phone' => $phone,
            'document' => $document,
        ]);

        if (!$updated) {
            return ['success' => false, 'message' => 'Não foi possível atualizar seus dados.'];
        }

        $_SESSION['user_name'] = $name;

        return ['success' => true, 'message' => 'Dados atualizados com sucesso.'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listOrders(int $customerId): array
    {
        $customer = $this->customerRepository->find($customerId);
        if ($customer === null) {
            return [];
        }

        $orders = $this->orderRepository->listByCommercialClient((int) $customer['commercial_client_id']);

        return array_map(
            static function (array $order): array {
                $status = (string) ($order['status'] ?? '');
                $deliveryMethod = DeliveryMethod::parse((string) ($order['delivery_method'] ?? DeliveryMethod::DELIVERY));
                $fulfillmentStatus = (string) ($order['fulfillment_status'] ?? 'pending');

                return [
                    'id' => (int) ($order['id'] ?? 0),
                    'public_reference' => (string) ($order['public_reference'] ?? ''),
                    'status' => $status,
                    'status_label' => FulfillmentStatusLabel::forPublicTracking($status, $fulfillmentStatus, $deliveryMethod),
                    'delivery_method' => $deliveryMethod,
                    'delivery_method_label' => DeliveryMethodLabel::resolve($deliveryMethod),
                    'total_amount' => (float) ($order['total_amount'] ?? 0),
                    'created_at' => (string) ($order['created_at'] ?? ''),
                    'shipping_city' => (string) ($order['shipping_city'] ?? ''),
                    'shipping_state' => (string) ($order['shipping_state'] ?? ''),
                ];
            },
            $orders
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getOrderForCustomer(int $customerId, string $publicReference): ?array
    {
        $customer = $this->customerRepository->find($customerId);
        if ($customer === null) {
            return null;
        }

        $reference = trim($publicReference);
        if ($reference === '') {
            return null;
        }

        $order = $this->orderRepository->findByPublicReferenceForClient(
            $reference,
            (int) $customer['commercial_client_id']
        );
        if ($order === null) {
            return null;
        }

        $orderId = (int) ($order['id'] ?? 0);
        $items = $this->orderItemRepository->listByOrder($orderId);
        $status = (string) ($order['status'] ?? '');
        $deliveryMethod = DeliveryMethod::parse((string) ($order['delivery_method'] ?? DeliveryMethod::DELIVERY));
        $fulfillmentStatus = (string) ($order['fulfillment_status'] ?? 'pending');

        return [
            'public_reference' => (string) ($order['public_reference'] ?? ''),
            'status' => $status,
            'status_label' => FulfillmentStatusLabel::forPublicTracking($status, $fulfillmentStatus, $deliveryMethod),
            'delivery_method' => $deliveryMethod,
            'delivery_method_label' => DeliveryMethodLabel::resolve($deliveryMethod),
            'total_amount' => (float) ($order['total_amount'] ?? 0),
            'created_at' => (string) ($order['created_at'] ?? ''),
            'shipping_city' => (string) ($order['shipping_city'] ?? ''),
            'shipping_state' => (string) ($order['shipping_state'] ?? ''),
            'shipping_street' => (string) ($order['shipping_street'] ?? ''),
            'shipping_number' => (string) ($order['shipping_number'] ?? ''),
            'shipping_neighborhood' => (string) ($order['shipping_neighborhood'] ?? ''),
            'items' => array_map(
                static fn (array $item): array => [
                    'product_name' => (string) ($item['product_name'] ?? 'Produto'),
                    'quantity' => (int) ($item['quantity'] ?? 0),
                    'total_amount' => (float) ($item['total_amount'] ?? 0),
                ],
                $items
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getCheckoutPrefill(int $customerId): array
    {
        $customer = $this->customerRepository->find($customerId);
        if ($customer === null) {
            return [];
        }

        $data = [
            'name' => (string) ($customer['name'] ?? ''),
            'email' => (string) ($customer['email'] ?? ''),
            'phone' => (string) ($customer['phone'] ?? ''),
            'document' => (string) ($customer['document'] ?? ''),
        ];

        $address = $this->addressRepository->findDefault($customerId);
        if ($address === null) {
            return $data;
        }

        return array_merge($data, [
            'shipping_postal_code' => (string) ($address['postal_code'] ?? ''),
            'shipping_street' => (string) ($address['street'] ?? ''),
            'shipping_number' => (string) ($address['street_number'] ?? ''),
            'shipping_complement' => (string) ($address['complement'] ?? ''),
            'shipping_neighborhood' => (string) ($address['neighborhood'] ?? ''),
            'shipping_city' => (string) ($address['city'] ?? ''),
            'shipping_state' => (string) ($address['state'] ?? ''),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listAddresses(int $customerId): array
    {
        return $this->addressRepository->listByCustomer($customerId);
    }

    /**
     * @return array{success: bool, message: string, field_errors?: array<string, string>, id?: int}
     */
    public function saveAddress(int $customerId, array $post, ?int $addressId = null): array
    {
        $parsed = BrazilianAddress::parseCheckoutPost($post);
        [$addressErrors, $fieldErrors] = BrazilianAddress::validateCheckoutAddress($parsed);

        $label = trim((string) ($post['label'] ?? 'Principal'));
        if ($label === '') {
            $label = 'Principal';
        }

        if ($addressErrors !== []) {
            return [
                'success' => false,
                'message' => $addressErrors[0] ?? 'Endereço inválido.',
                'field_errors' => $fieldErrors,
            ];
        }

        $payload = [
            'label' => $label,
            'postal_code' => $parsed['shipping_postal_code'] ?? null,
            'street' => $parsed['shipping_street'] ?? null,
            'street_number' => $parsed['shipping_number'] ?? null,
            'complement' => $parsed['shipping_complement'] ?? null,
            'neighborhood' => $parsed['shipping_neighborhood'] ?? null,
            'city' => $parsed['shipping_city'] ?? null,
            'state' => $parsed['shipping_state'] ?? null,
        ];

        $makeDefault = (int) ($post['is_default'] ?? 0) === 1
            || $addressId === null && $this->addressRepository->listByCustomer($customerId) === [];

        if ($addressId !== null && $addressId > 0) {
            $existing = $this->addressRepository->findForCustomer($addressId, $customerId);
            if ($existing === null) {
                return ['success' => false, 'message' => 'Endereço não encontrado.'];
            }

            if (!$this->addressRepository->update($addressId, $payload)) {
                return ['success' => false, 'message' => 'Não foi possível atualizar o endereço.'];
            }

            if ($makeDefault) {
                $this->addressRepository->setDefault($addressId, $customerId);
            }

            return ['success' => true, 'message' => 'Endereço atualizado.', 'id' => $addressId];
        }

        $newId = $this->addressRepository->createAddress($customerId, array_merge($payload, [
            'is_default' => $makeDefault ? 1 : 0,
        ]));

        if ($newId <= 0) {
            return ['success' => false, 'message' => 'Não foi possível salvar o endereço.'];
        }

        if ($makeDefault) {
            $this->addressRepository->setDefault($newId, $customerId);
        }

        return ['success' => true, 'message' => 'Endereço salvo.', 'id' => $newId];
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function deleteAddress(int $customerId, int $addressId): array
    {
        $address = $this->addressRepository->findForCustomer($addressId, $customerId);
        if ($address === null) {
            return ['success' => false, 'message' => 'Endereço não encontrado.'];
        }

        if (!$this->addressRepository->delete($addressId)) {
            return ['success' => false, 'message' => 'Não foi possível remover o endereço.'];
        }

        if ((int) ($address['is_default'] ?? 0) === 1) {
            $remaining = $this->addressRepository->listByCustomer($customerId);
            if ($remaining !== []) {
                $this->addressRepository->setDefault((int) $remaining[0]['id'], $customerId);
            }
        }

        return ['success' => true, 'message' => 'Endereço removido.'];
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function setDefaultAddress(int $customerId, int $addressId): array
    {
        $address = $this->addressRepository->findForCustomer($addressId, $customerId);
        if ($address === null) {
            return ['success' => false, 'message' => 'Endereço não encontrado.'];
        }

        if (!$this->addressRepository->setDefault($addressId, $customerId)) {
            return ['success' => false, 'message' => 'Não foi possível definir endereço padrão.'];
        }

        return ['success' => true, 'message' => 'Endereço padrão atualizado.'];
    }

    public function getCommercialClientId(int $customerId): int
    {
        $customer = $this->customerRepository->find($customerId);
        if ($customer === null) {
            return 0;
        }

        return (int) ($customer['commercial_client_id'] ?? 0);
    }
}
