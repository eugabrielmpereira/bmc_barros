<?php

declare(strict_types=1);

namespace Tests\Feature;

use Erp\Auth\Services\AuthService;
use Core\Database;
use PHPUnit\Framework\TestCase;

/**
 * Testes opcionais contra o MySQL do .env (não rodam por padrão).
 *
 * Uso: ERP_RUN_INTEGRATION=1 vendor/bin/phpunit tests/Feature
 */
final class AuthServiceDatabaseTest extends TestCase
{
    private static string $testEmail = 'phpunit_auth@bmc_barros.test';

    /** @var array<string, string|null> */
    private array $originalEnv = [];

    public static function setUpBeforeClass(): void
    {
        if (!self::integrationEnabled()) {
            return;
        }
        $root = dirname(__DIR__, 2);
        if (is_file($root . '/.env')) {
            \Dotenv\Dotenv::createImmutable($root)->safeLoad();
        }
    }

    protected function setUp(): void
    {
        if (!self::integrationEnabled()) {
            $this->markTestSkipped('Defina ERP_RUN_INTEGRATION=1 no ambiente para testes com MySQL real.');
        }
        Database::resetForTesting();
        $this->snapshotEnv(['MULTITENANT_DB']);
        $_ENV['MULTITENANT_DB'] = 'false';
        $_SERVER['MULTITENANT_DB'] = 'false';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    protected function tearDown(): void
    {
        Database::resetForTesting();
        $this->restoreEnv();
        unset($_SERVER['REMOTE_ADDR']);
    }

    public function testLoginFailsForUnknownUser(): void
    {
        $auth = new AuthService();
        $this->assertFalse($auth->attemptLogin('nao_existe_xyz@bmc_barros.test', 'SenhaQualquer123!'));
    }

    public function testLoginFailsWithWrongPassword(): void
    {
        $pdo = Database::getInstance();
        $hash = password_hash('SenhaCerta123!', PASSWORD_DEFAULT);
        $pdo->prepare('DELETE FROM usuarios WHERE email = ?')->execute([self::$testEmail]);
        $pdo->prepare(
            'INSERT INTO usuarios (nome, email, senha, status) VALUES (?, ?, ?, ?)'
        )->execute(['PHPUnit', self::$testEmail, $hash, 'ativo']);

        $auth = new AuthService();
        $this->assertFalse($auth->attemptLogin(self::$testEmail, 'SenhaErrada123!'));

        $pdo->prepare('DELETE FROM usuarios WHERE email = ?')->execute([self::$testEmail]);
    }

    public function testLoginSucceedsWithValidCredentials(): void
    {
        $pdo = Database::getInstance();
        $hash = password_hash('SenhaCerta123!', PASSWORD_DEFAULT);
        $pdo->prepare('DELETE FROM usuarios WHERE email = ?')->execute([self::$testEmail]);
        $pdo->prepare(
            'INSERT INTO usuarios (nome, email, senha, status) VALUES (?, ?, ?, ?)'
        )->execute(['PHPUnit', self::$testEmail, $hash, 'ativo']);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        session_start();
        $_SESSION = [];

        $auth = new AuthService();
        $this->assertTrue($auth->attemptLogin(self::$testEmail, 'SenhaCerta123!'));
        $this->assertArrayHasKey('admin_id', $_SESSION);

        $pdo->prepare('DELETE FROM usuarios WHERE email = ?')->execute([self::$testEmail]);
        $pdo->prepare('DELETE FROM login_attempts WHERE ip_address = ?')->execute(['127.0.0.1']);
    }

    private static function integrationEnabled(): bool
    {
        $v = $_ENV['ERP_RUN_INTEGRATION'] ?? getenv('ERP_RUN_INTEGRATION');

        return $v === '1' || $v === 'true';
    }

    private function snapshotEnv(array $keys): void
    {
        foreach ($keys as $key) {
            $this->originalEnv[$key] = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        }
    }

    private function restoreEnv(): void
    {
        foreach ($this->originalEnv as $key => $value) {
            if ($value === null) {
                unset($_ENV[$key], $_SERVER[$key]);
                continue;
            }
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}
