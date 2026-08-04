<?php

declare(strict_types=1);

namespace Core\Cli;

use Core\Database\MasterDatabase;
use Core\Database\TenantMasterRepository;
use Core\Database\TenantSettingsRepository;
use RuntimeException;
use Shared\Support\Client;

final class TenantCommandHandler
{
    public function __construct(
        private TenantMasterRepository $master = new TenantMasterRepository(),
        private TenantSettingsRepository $settings = new TenantSettingsRepository()
    ) {
    }

    /**
     * @param list<string> $argv
     */
    public function run(array $argv): int
    {
        $command = $argv[1] ?? 'help';
        $args = $this->parseArgs($argv);

        return match ($command) {
            'tenant:create' => $this->createTenant($args),
            'tenant:domain:add' => $this->addDomain($args),
            'tenant:suspend' => $this->suspend($args),
            'tenant:activate' => $this->activate($args),
            'tenant:list' => $this->listTenants(),
            'tenant:config:asaas' => $this->configAsaas($args),
            'tenant:config:mail' => $this->configMail($args),
            'help', '--help', '-h' => $this->help(),
            default => $this->unknownCommand($command),
        };
    }

    /**
     * @param array<string, string> $args
     */
    private function createTenant(array $args): int
    {
        $slug = TenantMasterRepository::sanitizeSlug($args['slug'] ?? '');
        if ($slug === '') {
            $this->error(
                'Uso: php tenant.php tenant:create {slug} --domain=host '
                . '[--display-name=Nome] [--client-config=key] [--scaffold-client=demo] [--no-scaffold] '
                . '[--admin-email=] [--admin-password=]'
            );

            return 1;
        }

        $domain = trim($args['domain'] ?? '');
        if ($domain === '') {
            $this->error('Informe --domain=host');

            return 1;
        }

        $projectRoot = defined('BASE_PATH') ? (string) BASE_PATH : dirname(__DIR__, 2);
        $displayName = trim($args['display-name'] ?? ucfirst($slug));
        $explicitClientConfig = array_key_exists('client-config', $args);
        $clientConfig = strtolower(trim($args['client-config'] ?? $slug));
        $noScaffold = filter_var($args['no-scaffold'] ?? 'false', FILTER_VALIDATE_BOOL);
        $folder = ClientScaffoldNaming::slugToFolder($slug);

        try {
            if (!$noScaffold) {
                $scaffold = new ClientScaffoldGenerator($projectRoot);
                if ($scaffold->exists($slug)) {
                    $this->warn("app/Clients/{$folder}/ já existe — scaffold ignorado (idempotente).");
                } else {
                    $templateKey = strtolower(trim($args['scaffold-client'] ?? 'demo'));
                    $scaffold->generate($slug, $displayName, $templateKey);
                    (new ClientRegistryWriter($projectRoot))->register($slug, $folder);
                    ComposerAutoloadRefresher::dumpAutoload($projectRoot);
                    if (!$explicitClientConfig) {
                        $clientConfig = $slug;
                    }
                    $this->info("Scaffold client criado: app/Clients/{$folder}/ (template: {$templateKey})");
                }
            }

            if (!Client::isKnown($clientConfig)) {
                $this->warn(
                    "ClientConfig '{$clientConfig}' não registrado em config/clients.php. "
                    . 'Branding/rotas exigem scaffold ou registro manual antes do go-live.'
                );
            }

            $adminEmail = trim($args['admin-email'] ?? '');
            $adminPassword = (string) ($args['admin-password'] ?? '');

            MasterDatabase::ensureDatabaseExists();
            $created = $this->master->createTenant(
                $slug,
                $clientConfig,
                $displayName,
                $domain
            );

            $this->info("Registro mestre criado: {$slug} → {$created['database_name']}");

            $this->master->provisionDatabase($created['database_name']);
            $this->info("Migrations aplicadas em {$created['database_name']}");

            if ($adminEmail !== '' && $adminPassword !== '') {
                $pdo = MasterDatabase::openTenantConnection($created['database_name']);
                TenantAdminSeeder::create($adminEmail, $adminPassword, $pdo);
                $this->info("Admin criado: {$adminEmail}");
            } else {
                $this->warn('Admin não criado. Use --admin-email e --admin-password ou rode criar_admin.php.');
            }

            $tenantId = $this->master->requireTenantId($slug);
            $this->applyOptionalAsaasFlags($tenantId, $args);
            $this->applyOptionalMailFlags($tenantId, $args);

            $this->master->markActive($slug);
            $this->info("Tenant '{$slug}' ativo. Domínio: {$domain}");

            return 0;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return 1;
        }
    }

    /**
     * @param array<string, string> $args
     */
    private function addDomain(array $args): int
    {
        $slug = TenantMasterRepository::sanitizeSlug($args['slug'] ?? '');
        $host = trim($args['host'] ?? ($args['domain'] ?? ''));
        if ($slug === '' || $host === '') {
            $this->error('Uso: php tenant.php tenant:domain:add {slug} {host}');

            return 1;
        }

        try {
            $this->master->addDomain($slug, $host, filter_var($args['primary'] ?? 'false', FILTER_VALIDATE_BOOL));
            $this->info("Domínio '{$host}' adicionado ao tenant '{$slug}'.");

            return 0;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return 1;
        }
    }

    /**
     * @param array<string, string> $args
     */
    private function suspend(array $args): int
    {
        $slug = TenantMasterRepository::sanitizeSlug($args['slug'] ?? '');
        if ($slug === '') {
            $this->error('Uso: php tenant.php tenant:suspend {slug} [--reason=motivo]');

            return 1;
        }

        try {
            $this->master->setStatus($slug, 'suspended', $args['reason'] ?? 'manual_operator');
            $this->info("Tenant '{$slug}' suspenso.");

            return 0;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return 1;
        }
    }

    /**
     * @param array<string, string> $args
     */
    private function activate(array $args): int
    {
        $slug = TenantMasterRepository::sanitizeSlug($args['slug'] ?? '');
        if ($slug === '') {
            $this->error('Uso: php tenant.php tenant:activate {slug}');

            return 1;
        }

        try {
            $this->master->markActive($slug);
            $this->info("Tenant '{$slug}' ativado.");

            return 0;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return 1;
        }
    }

    private function listTenants(): int
    {
        try {
            $rows = $this->master->listTenants();
            if ($rows === []) {
                $this->info('Nenhum tenant registrado.');

                return 0;
            }

            foreach ($rows as $row) {
                echo sprintf(
                    "- %-12s %-10s %-20s %s\n",
                    (string) ($row['slug'] ?? ''),
                    (string) ($row['status'] ?? ''),
                    (string) ($row['database_name'] ?? ''),
                    (string) ($row['domains'] ?? '')
                );
            }

            return 0;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return 1;
        }
    }

    /**
     * @param array<string, string> $args
     */
    private function configAsaas(array $args): int
    {
        $slug = TenantMasterRepository::sanitizeSlug($args['slug'] ?? '');
        if ($slug === '') {
            $this->error('Uso: php tenant.php tenant:config:asaas {slug} [--enabled=true] [--sandbox=true] [--api-key=] [--webhook-token=] [--pix-due-days=3]');

            return 1;
        }

        try {
            $tenantId = $this->master->requireTenantId($slug);
            $current = $this->settings->findAsaasByClientKey($this->master->getClientConfigKey($slug)) ?? [];

            $payload = [
                'enabled' => $args['enabled'] ?? ($current['enabled'] ?? false),
                'sandbox' => $args['sandbox'] ?? ($current['sandbox'] ?? true),
                'api_key' => $args['api-key'] ?? ($current['api_key'] ?? ''),
                'webhook_token' => $args['webhook-token'] ?? ($current['webhook_token'] ?? ''),
                'pix_due_days' => $args['pix-due-days'] ?? ($current['pix_due_days'] ?? 3),
            ];

            $this->settings->upsertAsaas($tenantId, $payload);
            $this->info("Config Asaas atualizada para tenant '{$slug}'.");

            return 0;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return 1;
        }
    }

    /**
     * @param array<string, string> $args
     */
    private function configMail(array $args): int
    {
        $slug = TenantMasterRepository::sanitizeSlug($args['slug'] ?? '');
        if ($slug === '') {
            $this->error('Uso: php tenant.php tenant:config:mail {slug} [--enabled=true] [--host=] [--port=] [--username=] [--password=] [--from=] [--from-name=] [--admin-to=]');

            return 1;
        }

        try {
            $tenantId = $this->master->requireTenantId($slug);
            $current = $this->settings->findMailByClientKey($this->master->getClientConfigKey($slug)) ?? [];

            $payload = [
                'enabled' => $args['enabled'] ?? ($current['enabled'] ?? false),
                'driver' => $args['driver'] ?? ($current['driver'] ?? 'smtp'),
                'host' => $args['host'] ?? ($current['host'] ?? ''),
                'port' => $args['port'] ?? ($current['port'] ?? 2525),
                'username' => $args['username'] ?? ($current['username'] ?? ''),
                'password' => $args['password'] ?? ($current['password'] ?? ''),
                'encryption' => $args['encryption'] ?? ($current['encryption'] ?? 'tls'),
                'from_address' => $args['from'] ?? ($current['from_address'] ?? ''),
                'from_name' => $args['from-name'] ?? ($current['from_name'] ?? ''),
                'admin_to' => $args['admin-to'] ?? ($current['admin_to'] ?? ''),
            ];

            $this->settings->upsertMail($tenantId, $payload);
            $this->info("Config Mail atualizada para tenant '{$slug}'.");

            return 0;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return 1;
        }
    }

    /**
     * @param array<string, string> $args
     */
    private function applyOptionalAsaasFlags(int $tenantId, array $args): void
    {
        if (!isset($args['asaas-api-key']) && !isset($args['asaas-webhook-token']) && !isset($args['asaas-enabled'])) {
            return;
        }

        $this->settings->upsertAsaas($tenantId, [
            'enabled' => $args['asaas-enabled'] ?? 'true',
            'sandbox' => $args['asaas-sandbox'] ?? 'true',
            'api_key' => $args['asaas-api-key'] ?? '',
            'webhook_token' => $args['asaas-webhook-token'] ?? '',
            'pix_due_days' => $args['asaas-pix-due-days'] ?? 3,
        ]);
    }

    /**
     * @param array<string, string> $args
     */
    private function applyOptionalMailFlags(int $tenantId, array $args): void
    {
        if (!isset($args['mail-host']) && !isset($args['mail-username'])) {
            return;
        }

        $this->settings->upsertMail($tenantId, [
            'enabled' => $args['mail-enabled'] ?? 'true',
            'driver' => 'smtp',
            'host' => $args['mail-host'] ?? '',
            'port' => $args['mail-port'] ?? 2525,
            'username' => $args['mail-username'] ?? '',
            'password' => $args['mail-password'] ?? '',
            'encryption' => $args['mail-encryption'] ?? 'tls',
            'from_address' => $args['mail-from'] ?? '',
            'from_name' => $args['mail-from-name'] ?? '',
            'admin_to' => $args['mail-admin-to'] ?? '',
        ]);
    }

    /**
     * @param list<string> $argv
     * @return array<string, string>
     */
    private function parseArgs(array $argv): array
    {
        $parsed = ['slug' => '', 'host' => ''];

        if (isset($argv[2]) && !str_starts_with((string) $argv[2], '--')) {
            $parsed['slug'] = (string) $argv[2];
        }

        if (isset($argv[3]) && !str_starts_with((string) $argv[3], '--')) {
            $parsed['host'] = (string) $argv[3];
        }

        foreach ($argv as $index => $arg) {
            if ($index < 2 || !str_starts_with($arg, '--')) {
                continue;
            }

            $parts = explode('=', substr($arg, 2), 2);
            $key = $parts[0];
            $value = $parts[1] ?? 'true';
            $parsed[$key] = $value;
        }

        return $parsed;
    }

    private function help(): int
    {
        echo <<<'HELP'
Comandos de onboarding multi-tenant (banco mestre erp_master):

  php tenant.php tenant:list
  php tenant.php tenant:create {slug} --domain=host [--display-name=Nome]
      [--client-config=key] [--scaffold-client=demo] [--no-scaffold]
      [--admin-email=] [--admin-password=]
      [--asaas-enabled=true] [--asaas-api-key=] [--asaas-webhook-token=]
      [--mail-host=] [--mail-username=] [--mail-password=] [--mail-from=]
  php tenant.php tenant:domain:add {slug} {host}
  php tenant.php tenant:suspend {slug} [--reason=motivo]
  php tenant.php tenant:activate {slug}
  php tenant.php tenant:config:asaas {slug} [--enabled=] [--api-key=] [--webhook-token=]
  php tenant.php tenant:config:mail {slug} [--enabled=] [--host=] [--username=] [--password=]

Requer: MySQL, erp_master migrado, APP_KEY para criptografia de segredos.

HELP;

        return 0;
    }

    private function unknownCommand(string $command): int
    {
        $this->error("Comando desconhecido: {$command}");
        $this->help();

        return 1;
    }

    private function info(string $message): void
    {
        echo '✅ ' . $message . "\n";
    }

    private function warn(string $message): void
    {
        echo '⚠️  ' . $message . "\n";
    }

    private function error(string $message): void
    {
        fwrite(STDERR, '❌ ' . $message . "\n");
    }
}
