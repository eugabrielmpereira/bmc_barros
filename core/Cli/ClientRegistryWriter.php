<?php

declare(strict_types=1);

namespace Core\Cli;

use RuntimeException;

/**
 * Persiste entradas em config/clients.php (registro de clientes).
 */
final class ClientRegistryWriter
{
    public function __construct(private string $projectRoot)
    {
    }

    public function register(string $clientKey, string $folder): void
    {
        $clientKey = strtolower(trim($clientKey));
        $folder = trim($folder);
        if ($clientKey === '' || $folder === '') {
            throw new RuntimeException('clientKey e folder são obrigatórios para registro.');
        }

        $path = $this->projectRoot . '/config/clients.php';
        /** @var array<string, array{config: class-string, folder: string, routes: string}> $clients */
        $clients = is_file($path) ? require $path : [];

        if (isset($clients[$clientKey])) {
            return;
        }

        $clients[$clientKey] = [
            'config' => "Clients\\{$folder}\\Config\\ClientConfig",
            'folder' => $folder,
            'routes' => "/Clients/{$folder}/routes.php",
        ];

        ksort($clients);
        $this->writeClientsFile($path, $clients);
        \Shared\Support\Client::resetRegistryCache();
    }

    public function unregister(string $clientKey): void
    {
        $clientKey = strtolower(trim($clientKey));
        $path = $this->projectRoot . '/config/clients.php';
        if (!is_file($path)) {
            return;
        }

        /** @var array<string, array{config: class-string, folder: string, routes: string}> $clients */
        $clients = require $path;
        if (!isset($clients[$clientKey])) {
            return;
        }

        unset($clients[$clientKey]);
        ksort($clients);
        $this->writeClientsFile($path, $clients);
        \Shared\Support\Client::resetRegistryCache();
    }

    /**
     * @param array<string, array{config: class-string, folder: string, routes: string}> $clients
     */
    private function writeClientsFile(string $path, array $clients): void
    {
        $lines = [
            '<?php',
            '',
            'declare(strict_types=1);',
            '',
            '/**',
            ' * Registro de clientes (branding, rotas, ClientConfig).',
            ' * Novos tenants adicionados via `php tenant.php tenant:create` (scaffold).',
            ' */',
            'return [',
        ];

        foreach ($clients as $key => $entry) {
            $configClass = (string) $entry['config'];
            $folder = (string) $entry['folder'];
            $routes = (string) $entry['routes'];
            $lines[] = "    '{$key}' => [";
            $lines[] = "        'config' => {$configClass}::class,";
            $lines[] = "        'folder' => '{$folder}',";
            $lines[] = "        'routes' => '{$routes}',";
            $lines[] = '    ],';
        }

        $lines[] = '];';
        $lines[] = '';

        if (file_put_contents($path, implode(PHP_EOL, $lines)) === false) {
            throw new RuntimeException('Não foi possível gravar config/clients.php');
        }
    }
}
