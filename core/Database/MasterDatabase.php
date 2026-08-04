<?php

declare(strict_types=1);

namespace Core\Database;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Conexão PDO dedicada ao banco mestre (erp_master).
 *
 * Isolada de Core\Database (tenant) para evitar que lookups no mestre
 * reutilizem ou sobrescrevam a conexão do tenant ativo.
 */
final class MasterDatabase
{
    private static ?PDO $connection = null;

    /**
     * @return array{host: string, database: string, username: string, password: string, charset: string}
     */
    public static function config(): array
    {
        /** @var array{host: string, database: string, username: string, password: string, charset: string} $config */
        $config = require dirname(__DIR__, 2) . '/config/master_database.php';

        return $config;
    }

    public static function ensureDatabaseExists(): void
    {
        $config = self::config();
        $dsn = "mysql:host={$config['host']};charset={$config['charset']}";

        $pdo = new PDO($dsn, $config['username'], $config['password'], self::pdoOptions());
        $dbName = str_replace('`', '``', $config['database']);
        $pdo->exec(
            "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    }

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $config = self::config();
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
            self::$connection = new PDO($dsn, $config['username'], $config['password'], self::pdoOptions());
        }

        return self::$connection;
    }

    /**
     * Descarta a conexão mestre ao fim de cada request (PHP-FPM worker reuse).
     */
    public static function resetForRequest(): void
    {
        self::$connection = null;
    }

    /**
     * Abre PDO para um database de tenant (sem cache global).
     */
    public static function openTenantConnection(string $databaseName, string $charset = 'utf8mb4'): PDO
    {
        $config = self::config();
        $safeDb = str_replace('`', '``', $databaseName);
        $dsn = "mysql:host={$config['host']};dbname={$safeDb};charset={$charset}";

        try {
            return new PDO($dsn, $config['username'], $config['password'], self::pdoOptions());
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Não foi possível conectar ao banco do tenant: ' . $databaseName,
                0,
                $e
            );
        }
    }

    /**
     * Cria database de tenant se ainda não existir (privilégio CREATE do operador).
     */
    public static function ensureTenantDatabaseExists(string $databaseName): void
    {
        $config = self::config();
        $dsn = "mysql:host={$config['host']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['username'], $config['password'], self::pdoOptions());
        $safeDb = str_replace('`', '``', $databaseName);
        $pdo->exec(
            "CREATE DATABASE IF NOT EXISTS `{$safeDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    }

    /**
     * @return array<int, mixed>
     */
    private static function pdoOptions(): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }
}
