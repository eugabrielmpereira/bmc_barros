<?php

declare(strict_types=1);

namespace Erp\Commerce\Controllers;

use Core\Controller;
use Core\Security\Csrf;
use Erp\Customers\Services\CustomerAccountService;
use Erp\Customers\Services\CustomerAuthService;

abstract class PublicCustomerAccountController extends Controller
{
    public function __construct(
        private ?CustomerAuthService $authService = null,
        private ?CustomerAccountService $accountService = null
    ) {
        $this->authService ??= new CustomerAuthService();
        $this->accountService ??= new CustomerAccountService();
    }

    public function loginForm(): void
    {
        if ($this->authService->isAuthenticated()) {
            $this->redirect($this->safeRedirect('/meus-pedidos'));
        }

        $this->view('client::account/login', [
            'client' => client_config(),
            'errors' => flash_data('account_errors', []),
            'field_errors' => flash_data('account_field_errors', []),
            'data' => flash_data('account_form', []),
            'redirect' => $this->safeRedirectInput(),
        ]);
    }

    public function login(): void
    {
        Csrf::requireValidPostOrAbort();

        if ($this->authService->isAuthenticated()) {
            $this->redirect($this->safeRedirect('/meus-pedidos'));
        }

        $result = $this->authService->login(
            (string) ($_POST['email'] ?? ''),
            (string) ($_POST['password'] ?? '')
        );

        if (!$result['success']) {
            $this->renderAuthForm('login', $result, [
                'email' => trim((string) ($_POST['email'] ?? '')),
            ]);

            return;
        }

        $this->redirect($this->safeRedirect('/meus-pedidos'));
    }

    public function registerForm(): void
    {
        if ($this->authService->isAuthenticated()) {
            $this->redirect('/meus-pedidos');
        }

        $this->view('client::account/register', [
            'client' => client_config(),
            'errors' => flash_data('account_errors', []),
            'field_errors' => flash_data('account_field_errors', []),
            'data' => flash_data('account_form', []),
        ]);
    }

    public function register(): void
    {
        Csrf::requireValidPostOrAbort();

        if ($this->authService->isAuthenticated()) {
            $this->redirect('/meus-pedidos');
        }

        $result = $this->authService->register($_POST);
        if (!$result['success']) {
            $this->renderAuthForm('register', $result, $_POST);

            return;
        }

        $_SESSION['commerce_message'] = $result['message'];
        $_SESSION['commerce_message_type'] = 'ok';
        $this->redirect('/meus-pedidos');
    }

    public function logout(): void
    {
        Csrf::requireValidPostOrAbort();
        $this->authService->logout();
        $this->redirect('/');
    }

    public function orders(): void
    {
        $customerId = $this->requireCustomerId();
        $this->view('client::account/orders', [
            'client' => client_config(),
            'customer' => $this->accountService->getProfile($customerId),
            'orders' => $this->accountService->listOrders($customerId),
            'msg' => flash('commerce_message'),
            'tipo' => flash('commerce_message_type') ?? 'ok',
        ]);
    }

    public function orderShow(string $reference): void
    {
        $customerId = $this->requireCustomerId();
        $order = $this->accountService->getOrderForCustomer($customerId, $reference);
        if ($order === null) {
            $this->redirect('/meus-pedidos');
        }

        $this->view('client::account/order-show', [
            'client' => client_config(),
            'customer' => $this->accountService->getProfile($customerId),
            'order' => $order,
        ]);
    }

    public function profile(): void
    {
        $customerId = $this->requireCustomerId();
        $this->view('client::account/profile', [
            'client' => client_config(),
            'customer' => $this->accountService->getProfile($customerId),
            'errors' => flash_data('account_errors', []),
            'field_errors' => flash_data('account_field_errors', []),
            'msg' => flash('commerce_message'),
            'tipo' => flash('commerce_message_type') ?? 'ok',
        ]);
    }

    public function updateProfile(): void
    {
        Csrf::requireValidPostOrAbort();
        $customerId = $this->requireCustomerId();

        $result = $this->accountService->updateProfile($customerId, $_POST);
        if (!$result['success']) {
            $_SESSION['account_errors'] = [$result['message']];
            $_SESSION['account_field_errors'] = $result['field_errors'] ?? [];
            $this->redirect('/minha-conta');
        }

        $_SESSION['commerce_message'] = $result['message'];
        $_SESSION['commerce_message_type'] = 'ok';
        $this->redirect('/minha-conta');
    }

    public function addresses(): void
    {
        $customerId = $this->requireCustomerId();
        $this->view('client::account/addresses', [
            'client' => client_config(),
            'customer' => $this->accountService->getProfile($customerId),
            'addresses' => $this->accountService->listAddresses($customerId),
            'errors' => flash_data('account_errors', []),
            'field_errors' => flash_data('account_field_errors', []),
            'data' => flash_data('account_form', []),
            'msg' => flash('commerce_message'),
            'tipo' => flash('commerce_message_type') ?? 'ok',
        ]);
    }

    public function saveAddress(): void
    {
        Csrf::requireValidPostOrAbort();
        $customerId = $this->requireCustomerId();
        $addressId = (int) ($_POST['address_id'] ?? 0);
        $addressId = $addressId > 0 ? $addressId : null;

        $result = $this->accountService->saveAddress($customerId, $_POST, $addressId);
        if (!$result['success']) {
            $_SESSION['account_errors'] = [$result['message']];
            $_SESSION['account_field_errors'] = $result['field_errors'] ?? [];
            $_SESSION['account_form'] = $_POST;
            $this->redirect('/minha-conta/enderecos');
        }

        $_SESSION['commerce_message'] = $result['message'];
        $_SESSION['commerce_message_type'] = 'ok';
        $this->redirect('/minha-conta/enderecos');
    }

    public function deleteAddress(string $id): void
    {
        Csrf::requireValidPostOrAbort();
        $customerId = $this->requireCustomerId();
        $result = $this->accountService->deleteAddress($customerId, (int) $id);

        $_SESSION['commerce_message'] = $result['message'];
        $_SESSION['commerce_message_type'] = $result['success'] ? 'ok' : 'erro';
        $this->redirect('/minha-conta/enderecos');
    }

    public function setDefaultAddress(string $id): void
    {
        Csrf::requireValidPostOrAbort();
        $customerId = $this->requireCustomerId();
        $result = $this->accountService->setDefaultAddress($customerId, (int) $id);

        $_SESSION['commerce_message'] = $result['message'];
        $_SESSION['commerce_message_type'] = $result['success'] ? 'ok' : 'erro';
        $this->redirect('/minha-conta/enderecos');
    }

    private function requireCustomerId(): int
    {
        $customerId = $this->authService->currentCustomerId();
        if ($customerId === null) {
            $this->redirect('/entrar?redirect=' . rawurlencode('/meus-pedidos'));
        }

        return $customerId;
    }

    /**
     * @param array<string, mixed> $result
     * @param array<string, mixed> $formData
     */
    private function renderAuthForm(string $form, array $result, array $formData): void
    {
        $_SESSION['account_errors'] = [$result['message']];
        $_SESSION['account_field_errors'] = $result['field_errors'] ?? [];
        $_SESSION['account_form'] = $formData;

        $path = $form === 'register' ? '/cadastro' : '/entrar';
        $redirect = trim((string) ($_POST['redirect'] ?? ''));
        if ($redirect !== '' && str_starts_with($redirect, '/')) {
            $path .= '?redirect=' . rawurlencode($redirect);
        }

        $this->redirect($path);
    }

    private function safeRedirectInput(): string
    {
        return $this->safeRedirect((string) ($_GET['redirect'] ?? '/meus-pedidos'));
    }

    private function safeRedirect(string $target): string
    {
        $target = trim($target);
        if ($target === '' || !str_starts_with($target, '/') || str_starts_with($target, '//')) {
            return '/meus-pedidos';
        }

        return $target;
    }
}
