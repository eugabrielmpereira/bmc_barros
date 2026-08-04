<?php

declare(strict_types=1);

namespace Tests\Unit;

use Erp\Commercial\Repositories\CommercialClientRepository;
use Erp\Customers\Repositories\CustomerRepository;
use Erp\Customers\Services\CustomerAuthService;
use PDO;
use PHPUnit\Framework\TestCase;

final class CustomerAuthServiceTest extends TestCase
{
    private PDO $pdo;
    private CustomerAuthService $service;

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $this->pdo->exec(
            'CREATE TABLE commercial_clients (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                document TEXT,
                email TEXT,
                phone TEXT,
                address TEXT,
                status TEXT NOT NULL DEFAULT \'active\'
             );
             CREATE TABLE customers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                commercial_client_id INTEGER NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                name TEXT NOT NULL,
                phone TEXT,
                document TEXT,
                status TEXT NOT NULL DEFAULT \'active\'
             );'
        );

        $this->service = new CustomerAuthService(
            new CustomerRepository($this->pdo),
            new CommercialClientRepository($this->pdo)
        );
    }

    public function testRegisterCreatesCustomerAndSession(): void
    {
        $result = $this->service->register([
            'name' => 'Cliente Site',
            'email' => 'cliente@example.com',
            'phone' => '(11) 98888-1234',
            'document' => '390.533.447-05',
            'password' => 'senha1234',
            'password_confirmation' => 'senha1234',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(1, (int) $_SESSION['user_id']);
        $this->assertSame('Cliente Site', $_SESSION['user_name']);

        $customer = (new CustomerRepository($this->pdo))->findByEmail('cliente@example.com');
        $this->assertNotNull($customer);
        $this->assertSame('Cliente Site', $customer['name']);
    }

    public function testLoginWithValidCredentials(): void
    {
        $this->service->register([
            'name' => 'Cliente Site',
            'email' => 'cliente@example.com',
            'phone' => '(11) 98888-1234',
            'document' => '390.533.447-05',
            'password' => 'senha1234',
            'password_confirmation' => 'senha1234',
        ]);

        $_SESSION = [];

        $result = $this->service->login('cliente@example.com', 'senha1234');

        $this->assertTrue($result['success']);
        $this->assertSame(1, (int) $_SESSION['user_id']);
    }

    public function testRegisterRejectsDuplicateEmail(): void
    {
        $payload = [
            'name' => 'Cliente Site',
            'email' => 'cliente@example.com',
            'phone' => '(11) 98888-1234',
            'document' => '390.533.447-05',
            'password' => 'senha1234',
            'password_confirmation' => 'senha1234',
        ];

        $this->service->register($payload);
        $_SESSION = [];

        $result = $this->service->register($payload);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('field_errors', $result);
    }
}
