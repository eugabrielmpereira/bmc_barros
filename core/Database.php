<?php

declare(strict_types=1);

namespace Core;

use Core\Database\MasterDatabase;
use Core\Database\TenantConnectionResolver;
use Core\Database\TenantSettingsProvider;
use PDO;
use RuntimeException;

class Database
{
    private static ?PDO $instance = null;

    /** @var non-empty-string|null Chave do tenant inicializado no request corrente */
    private static ?string $requestClientKey = null;

    public static function isMultitenantEnabled(): bool
    {
        return filter_var(env('MULTITENANT_DB', 'false'), FILTER_VALIDATE_BOOL);
    }

    /**
     * Inicializa (ou reinicializa) a conexão tenant para o request corrente.
     * Chamado no bootstrap HTTP/CLI quando MULTITENANT_DB=true.
     */
    public static function initializeForRequest(string $clientKey): void
    {
        self::$instance = null;
        self::$requestClientKey = strtolower(trim($clientKey));

        if (!self::isMultitenantEnabled()) {
            return;
        }

        MasterDatabase::resetForRequest();
        TenantSettingsProvider::resetForRequest();
        self::$instance = TenantConnectionResolver::resolve(self::$requestClientKey);
    }

    public static function requestClientKey(): ?string
    {
        return self::$requestClientKey;
    }

    public static function getInstance(): PDO
    {
        if (self::isMultitenantEnabled()) {
            if (self::$instance === null) {
                throw new RuntimeException(
                    'Conexão tenant não inicializada. Chame Database::initializeForRequest() no bootstrap.'
                );
            }

            return self::$instance;
        }

        if (self::$instance === null) {
            $config = require __DIR__ . '/../config/database.php';
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
            self::$instance = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$instance;
    }

    /**
     * Limpa estado estático (testes de integração e simulação multi-request).
     */
    public static function resetForTesting(): void
    {
        self::$instance = null;
        self::$requestClientKey = null;
        MasterDatabase::resetForRequest();
        TenantSettingsProvider::resetForRequest();
    }
}
