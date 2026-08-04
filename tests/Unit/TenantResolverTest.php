<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Shared\Support\TenantResolver;

final class TenantResolverTest extends TestCase
{
    /** @var list<string> */
    private array $knownClients = ['bmc', 'demo'];

    /** @var array<string, string> */
    private array $hostMap = [
        'localhost' => 'bmc',
        'bmc.local' => 'bmc',
        'demo.local' => 'demo',
    ];

    public function testNormalizeHostStripsPortAndLowercases(): void
    {
        $this->assertSame('demo.local', TenantResolver::normalizeHost('Demo.Local:8080'));
    }

    public function testEnvResolutionIgnoresHostMap(): void
    {
        $key = TenantResolver::resolveClientKey(
            'env',
            'demo.local',
            'bmc',
            $this->hostMap,
            'bmc',
            $this->knownClients
        );

        $this->assertSame('bmc', $key);
    }

    public function testDomainResolutionUsesHostMap(): void
    {
        $key = TenantResolver::resolveClientKey(
            'domain',
            'demo.local:443',
            'bmc',
            $this->hostMap,
            'bmc',
            $this->knownClients
        );

        $this->assertSame('demo', $key);
    }

    public function testDomainResolutionFallsBackToEnvClient(): void
    {
        $key = TenantResolver::resolveClientKey(
            'domain',
            'unknown.test',
            'demo',
            $this->hostMap,
            'bmc',
            $this->knownClients
        );

        $this->assertSame('demo', $key);
    }

    public function testDomainResolutionRejectsInvalidMappedClient(): void
    {
        $key = TenantResolver::resolveClientKey(
            'domain',
            'bad.local',
            'bmc',
            ['bad.local' => 'invalid'],
            'bmc',
            $this->knownClients
        );

        $this->assertSame('bmc', $key);
    }

    public function testResolveBaseUrlUsesConfiguredClientUrl(): void
    {
        $url = TenantResolver::resolveBaseUrl(
            'domain',
            'demo',
            null,
            'http://localhost/bmc_barros/public',
            ['demo' => 'https://demo.exemplo.com.br/']
        );

        $this->assertSame('https://demo.exemplo.com.br', $url);
    }

    public function testResolveBaseUrlDerivesFromRequestInDomainMode(): void
    {
        $url = TenantResolver::resolveBaseUrl(
            'domain',
            'demo',
            [
                'HTTP_HOST' => 'demo.local',
                'HTTPS' => 'off',
                'SCRIPT_NAME' => '/bmc_barros/public/index.php',
            ],
            'http://localhost/bmc_barros/public',
            []
        );

        $this->assertSame('http://demo.local/bmc_barros/public', $url);
    }

    public function testResolveBaseUrlUsesEnvInEnvMode(): void
    {
        $url = TenantResolver::resolveBaseUrl(
            'env',
            'bmc',
            [
                'HTTP_HOST' => 'demo.local',
                'SCRIPT_NAME' => '/index.php',
            ],
            'http://localhost/bmc_barros/public',
            []
        );

        $this->assertSame('http://localhost/bmc_barros/public', $url);
    }
}
