<?php

declare(strict_types=1);

namespace Erp\Customers\Services;

use Core\Security\LoginRateLimiter;
use Erp\Commercial\Repositories\CommercialClientRepository;
use Erp\Customers\Repositories\CustomerRepository;
use Shared\Support\Phone;
use Shared\Support\TaxId;

final class CustomerAuthService
{
    private const MIN_PASSWORD_LENGTH = 8;

    public function __construct(
        private ?CustomerRepository $customerRepository = null,
        private ?CommercialClientRepository $commercialClientRepository = null,
        private ?LoginRateLimiter $loginRateLimiter = null
    ) {
        $this->customerRepository ??= new CustomerRepository();
        $this->commercialClientRepository ??= new CommercialClientRepository();
    }

    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
    }

    public function currentCustomerId(): ?int
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return (int) $_SESSION['user_id'];
    }

    public function findCurrentCustomer(): ?array
    {
        $customerId = $this->currentCustomerId();
        if ($customerId === null) {
            return null;
        }

        $customer = $this->customerRepository->find($customerId);
        if ($customer === null || (string) ($customer['status'] ?? '') !== 'active') {
            return null;
        }

        return $customer;
    }

    /**
     * @return array{success: bool, message: string, field_errors?: array<string, string>}
     */
    public function register(array $post): array
    {
        $name = trim((string) ($post['name'] ?? ''));
        $email = strtolower(trim((string) ($post['email'] ?? '')));
        $phone = trim((string) ($post['phone'] ?? ''));
        $document = trim((string) ($post['document'] ?? ''));
        $password = (string) ($post['password'] ?? '');
        $passwordConfirm = (string) ($post['password_confirmation'] ?? '');

        $fieldErrors = [];

        if ($name === '') {
            $fieldErrors['name'] = 'Nome é obrigatório.';
        }
        if ($email === '') {
            $fieldErrors['email'] = 'E-mail é obrigatório.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $fieldErrors['email'] = 'E-mail inválido.';
        }
        if ($phone === '') {
            $fieldErrors['phone'] = 'Telefone ou WhatsApp é obrigatório.';
        }
        if ($document === '') {
            $fieldErrors['document'] = 'CPF/CNPJ é obrigatório.';
        } elseif (!TaxId::isValidDocument($document)) {
            $fieldErrors['document'] = 'CPF/CNPJ inválido.';
        }
        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            $fieldErrors['password'] = 'Senha deve ter no mínimo ' . self::MIN_PASSWORD_LENGTH . ' caracteres.';
        }
        if ($password !== $passwordConfirm) {
            $fieldErrors['password_confirmation'] = 'As senhas não conferem.';
        }

        if ($fieldErrors !== []) {
            return [
                'success' => false,
                'message' => 'Revise os dados informados.',
                'field_errors' => $fieldErrors,
            ];
        }

        if ($this->customerRepository->findByEmail($email) !== null) {
            return [
                'success' => false,
                'message' => 'Este e-mail já possui cadastro. Faça login ou recupere o acesso.',
                'field_errors' => ['email' => 'E-mail já cadastrado.'],
            ];
        }

        $commercialClientId = $this->resolveCommercialClientId($name, $email, $phone, $document);
        if ($commercialClientId <= 0) {
            return [
                'success' => false,
                'message' => 'Não foi possível concluir o cadastro. Tente novamente.',
            ];
        }

        if ($this->customerRepository->findByCommercialClientId($commercialClientId) !== null) {
            return [
                'success' => false,
                'message' => 'Já existe uma conta vinculada a estes dados. Faça login.',
                'field_errors' => ['email' => 'Conta já existente para estes dados.'],
            ];
        }

        $customerId = $this->customerRepository->createCustomer([
            'commercial_client_id' => $commercialClientId,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'name' => $name,
            'phone' => $phone,
            'document' => $document,
            'status' => 'active',
        ]);

        if ($customerId <= 0) {
            return [
                'success' => false,
                'message' => 'Não foi possível concluir o cadastro. Tente novamente.',
            ];
        }

        $this->establishSession($customerId, $name);

        return [
            'success' => true,
            'message' => 'Cadastro realizado com sucesso.',
        ];
    }

    /**
     * @return array{success: bool, message: string, field_errors?: array<string, string>}
     */
    public function login(string $email, string $password): array
    {
        $email = strtolower(trim($email));
        if ($email === '' || $password === '') {
            return [
                'success' => false,
                'message' => 'Informe e-mail e senha.',
                'field_errors' => ['email' => 'Informe e-mail e senha.'],
            ];
        }

        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
        if ($ip === '') {
            $ip = '0.0.0.0';
        }

        $limiter = $this->getLimiter();
        if ($limiter->isBlocked($ip)) {
            return [
                'success' => false,
                'message' => 'Muitas tentativas. Aguarde alguns minutos e tente novamente.',
            ];
        }

        $customer = $this->customerRepository->findByEmail($email);
        if (
            $customer === null
            || (string) ($customer['status'] ?? '') !== 'active'
            || !password_verify($password, (string) ($customer['password_hash'] ?? ''))
        ) {
            $limiter->recordFailure($ip);

            return [
                'success' => false,
                'message' => 'E-mail ou senha incorretos.',
                'field_errors' => ['email' => 'E-mail ou senha incorretos.'],
            ];
        }

        $limiter->clearForIp($ip);
        $this->establishSession((int) $customer['id'], (string) ($customer['name'] ?? ''));

        return [
            'success' => true,
            'message' => 'Login realizado com sucesso.',
        ];
    }

    public function logout(): void
    {
        unset($_SESSION['user_id'], $_SESSION['user_name']);
    }

    private function establishSession(int $customerId, string $name): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $customerId;
        $_SESSION['user_name'] = $name;
        $_SESSION['last_activity'] = time();
    }

    private function resolveCommercialClientId(string $name, string $email, string $phone, string $document): int
    {
        $existing = $this->commercialClientRepository->findByDocument($document);
        if ($existing === null) {
            $existing = $this->commercialClientRepository->findByEmail($email);
        }

        if ($existing !== null) {
            return (int) ($existing['id'] ?? 0);
        }

        return $this->commercialClientRepository->createClient([
            'name' => $name,
            'document' => $document,
            'email' => $email,
            'phone' => $phone,
            'status' => 'active',
        ]);
    }

    private function getLimiter(): LoginRateLimiter
    {
        return $this->loginRateLimiter ?? new LoginRateLimiter();
    }
}
