<?php

declare(strict_types=1);

namespace Shared\Support;

use InvalidArgumentException;

final class Client
{
    /** @var array<string, array{config: class-string, folder: string, routes: string}>|null */
    private static ?array $clients = null;

    /**
     * @return array<string, array{config: class-string, folder: string, routes: string}>
     */
    public static function registry(): array
    {
        if (self::$clients === null) {
            $path = self::projectRoot() . '/config/clients.php';
            if (!is_file($path)) {
                throw new InvalidArgumentException('Arquivo config/clients.php não encontrado.');
            }
            /** @var array<string, array{config: class-string, folder: string, routes: string}> $loaded */
            $loaded = require $path;
            self::$clients = $loaded;
        }

        return self::$clients;
    }

    public static function resetRegistryCache(): void
    {
        self::$clients = null;
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::registry());
    }

    public static function isKnown(string $key): bool
    {
        return array_key_exists(strtolower($key), self::registry());
    }

    public static function key(): string
    {
        $key = strtolower((string) (defined('ACTIVE_CLIENT') ? ACTIVE_CLIENT : 'bmc'));

        return self::isKnown($key) ? $key : 'bmc';
    }

    /**
     * @return array<string, mixed>
     */
    public static function config(): array
    {
        $configClass = self::registry()[self::key()]['config'];

        return $configClass::get();
    }

    public static function folder(): string
    {
        return self::registry()[self::key()]['folder'];
    }

    public static function routesPath(): string
    {
        $routes = self::registry()[self::key()]['routes'];
        $path = self::projectRoot() . '/app' . $routes;
        if (!is_file($path)) {
            throw new InvalidArgumentException('Rotas do cliente ativo não encontradas.');
        }

        return $path;
    }

    public static function clientRootPath(string $clientKey): string
    {
        $entry = self::registry()[strtolower($clientKey)] ?? null;
        if ($entry === null) {
            throw new InvalidArgumentException("Cliente '{$clientKey}' não registrado.");
        }

        return self::projectRoot() . '/app/Clients/' . $entry['folder'];
    }

    public static function asset(string $path): string
    {
        $assetBase = (string) (self::config()['assets']['base'] ?? '');

        return BASE_URL . $assetBase . '/' . ltrim($path, '/');
    }

    public static function uploadUrl(string $context, string $filename): string
    {
        $uploads = self::config()['uploads'] ?? [];
        $base = (string) ($uploads[$context] ?? '');

        return BASE_URL . $base . '/' . rawurlencode(basename($filename));
    }

    public static function whatsappUrl(string $message): string
    {
        $number = (string) (self::config()['whatsappNumber'] ?? '');

        return 'https://wa.me/' . $number . '?text=' . rawurlencode($message);
    }

    private static function projectRoot(): string
    {
        if (defined('BASE_PATH')) {
            return (string) BASE_PATH;
        }

        return dirname(__DIR__, 3);
    }
}
