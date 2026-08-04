<?php

declare(strict_types=1);

namespace Tests\Feature;

use Core\Cli\ClientRegistryWriter;
use Core\Cli\ClientScaffoldGenerator;
use Core\Cli\ClientScaffoldNaming;
use Core\Cli\ComposerAutoloadRefresher;
use PHPUnit\Framework\TestCase;
use Shared\Support\Client;

final class ClientScaffoldTest extends TestCase
{
    private string $projectRoot;
    private string $testSlug = 'scaffoldtest';
    private string $testFolder;

    protected function setUp(): void
    {
        $this->projectRoot = dirname(__DIR__, 2);
        $this->testFolder = ClientScaffoldNaming::slugToFolder($this->testSlug);
        $this->removeScaffoldArtifacts();
    }

    protected function tearDown(): void
    {
        $this->removeScaffoldArtifacts();
    }

    public function testGenerateCreatesExpectedStructureWithReplacements(): void
    {
        $generator = new ClientScaffoldGenerator($this->projectRoot);
        $displayName = 'Scaffold Test Store';

        $created = $generator->generate($this->testSlug, $displayName, 'demo');

        $clientRoot = $this->projectRoot . '/app/Clients/' . $this->testFolder;
        $this->assertDirectoryExists($clientRoot);
        $this->assertFileExists($clientRoot . '/Config/ClientConfig.php');
        $this->assertFileExists($clientRoot . '/routes.php');
        $this->assertFileExists($clientRoot . '/Site/Controllers/HomeController.php');
        $this->assertFileExists($clientRoot . '/Site/Controllers/LegalController.php');
        $this->assertFileExists($clientRoot . '/Site/Views/legal/privacy.php');
        $this->assertDirectoryExists($this->projectRoot . '/public/assets/clients/' . $this->testSlug . '/site');
        $this->assertDirectoryExists($this->projectRoot . '/public/uploads/clients/' . $this->testSlug . '/products');
        $this->assertNotEmpty($created);

        $configContent = (string) file_get_contents($clientRoot . '/Config/ClientConfig.php');
        $this->assertStringContainsString("namespace Clients\\{$this->testFolder}\\Config;", $configContent);
        $this->assertStringContainsString("'key' => '{$this->testSlug}'", $configContent);
        $this->assertStringContainsString("'name' => 'Scaffold Test Store'", $configContent);
        $this->assertStringContainsString("'cnpj' => '[REVISAR]'", $configContent);
        $this->assertStringContainsString(
            "'orderReferencePrefix' => '" . ClientScaffoldNaming::orderReferencePrefix($this->testSlug) . "'",
            $configContent
        );
        $this->assertStringContainsString("/assets/clients/{$this->testSlug}/site", $configContent);
        $this->assertStringContainsString('legalPages', $configContent);

        $homeController = (string) file_get_contents($clientRoot . '/Site/Controllers/HomeController.php');
        $this->assertStringContainsString("namespace Clients\\{$this->testFolder}\\Site\\Controllers;", $homeController);
        $this->assertStringNotContainsString('Clients\\Demo\\', $homeController);

        $routes = (string) file_get_contents($clientRoot . '/routes.php');
        $this->assertStringContainsString("Clients\\{$this->testFolder}\\Site\\Controllers\\LegalController", $routes);
        $this->assertStringContainsString('/politica-de-privacidade', $routes);

        $homeView = (string) file_get_contents($clientRoot . '/Site/Views/home.php');
        $this->assertStringNotContainsString('site público demonstrativo', $homeView);
    }

    public function testGenerateIsIdempotentWhenFolderExists(): void
    {
        $generator = new ClientScaffoldGenerator($this->projectRoot);
        $generator->generate($this->testSlug, 'First', 'demo');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('já existe');
        $generator->generate($this->testSlug, 'Second', 'demo');
    }

    public function testRegistryWriterRegistersAndClientLoadsConfig(): void
    {
        $generator = new ClientScaffoldGenerator($this->projectRoot);
        $generator->generate($this->testSlug, 'Registry Test', 'demo');

        (new ClientRegistryWriter($this->projectRoot))->register($this->testSlug, $this->testFolder);
        ComposerAutoloadRefresher::dumpAutoload($this->projectRoot);
        Client::resetRegistryCache();

        $this->assertTrue(Client::isKnown($this->testSlug));

        $configClass = Client::registry()[$this->testSlug]['config'];
        $config = $configClass::get();
        $this->assertSame($this->testSlug, $config['key']);
        $this->assertSame('Registry Test', $config['name']);
        $this->assertSame('[REVISAR]', $config['cnpj']);
    }

    public function testRoutesFileLoadsForScaffoldedClient(): void
    {
        $generator = new ClientScaffoldGenerator($this->projectRoot);
        $generator->generate($this->testSlug, 'Routes Test', 'demo');
        (new ClientRegistryWriter($this->projectRoot))->register($this->testSlug, $this->testFolder);
        Client::resetRegistryCache();

        $entry = Client::registry()[$this->testSlug];
        $routesPath = $this->projectRoot . '/app' . $entry['routes'];
        $this->assertFileExists($routesPath);
        $routes = require $routesPath;
        $this->assertIsArray($routes);
        $this->assertNotEmpty($routes);
    }

    private function removeScaffoldArtifacts(): void
    {
        Client::resetRegistryCache();
        (new ClientRegistryWriter($this->projectRoot))->unregister($this->testSlug);

        $paths = [
            $this->projectRoot . '/app/Clients/' . $this->testFolder,
            $this->projectRoot . '/public/assets/clients/' . $this->testSlug,
            $this->projectRoot . '/public/uploads/clients/' . $this->testSlug,
        ];

        foreach ($paths as $path) {
            $this->deleteDirectory($path);
        }
    }

    private function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            /** @var \SplFileInfo $item */
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($path);
    }
}
