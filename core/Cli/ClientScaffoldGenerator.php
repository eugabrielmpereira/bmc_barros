<?php

declare(strict_types=1);

namespace Core\Cli;

use RuntimeException;

/**
 * Gera app/Clients/{Folder}/ a partir do template Demo + páginas legais do Bmc.
 */
final class ClientScaffoldGenerator
{
    private const LEGAL_SOURCE_FOLDER = 'Bmc';

    public function __construct(private string $projectRoot)
    {
    }

    public function clientRootPath(string $slug): string
    {
        return $this->projectRoot . '/app/Clients/' . ClientScaffoldNaming::slugToFolder($slug);
    }

    public function exists(string $slug): bool
    {
        return is_dir($this->clientRootPath($slug));
    }

    /**
     * @return list<string> caminhos relativos ao project root criados
     */
    public function generate(string $slug, string $displayName, string $templateKey = 'demo'): array
    {
        $slug = strtolower(trim($slug));
        $folder = ClientScaffoldNaming::slugToFolder($slug);
        $templateKey = strtolower(trim($templateKey));
        $templateFolder = ClientScaffoldNaming::slugToFolder($templateKey);

        $targetRoot = $this->clientRootPath($slug);
        if (is_dir($targetRoot)) {
            throw new RuntimeException("Pasta app/Clients/{$folder}/ já existe — scaffold abortado.");
        }

        $templateRoot = $this->projectRoot . '/app/Clients/' . $templateFolder;
        if (!is_dir($templateRoot)) {
            throw new RuntimeException("Template app/Clients/{$templateFolder}/ não encontrado.");
        }

        $created = [];

        $this->copyTemplateTree($templateRoot, $targetRoot, $templateKey, $slug, $folder, $displayName, $created);
        $this->writeClientConfig($targetRoot, $slug, $displayName, $folder, $created);
        $this->copyLegalArtifacts($targetRoot, $slug, $folder, $created);
        $this->patchRoutesWithLegal($targetRoot, $folder, $created);
        $this->copyPublicAssets($templateKey, $slug, $created);
        $this->ensureUploadsDirectory($slug, $created);

        return $created;
    }

    /**
     * @param list<string> $created
     */
    private function copyTemplateTree(
        string $sourceRoot,
        string $targetRoot,
        string $templateKey,
        string $slug,
        string $folder,
        string $displayName,
        array &$created
    ): void {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            /** @var \SplFileInfo $item */
            $relative = substr($item->getPathname(), strlen($sourceRoot) + 1);
            $relative = str_replace('\\', '/', $relative);

            if ($relative === 'Config/ClientConfig.php') {
                continue;
            }

            $targetPath = $targetRoot . '/' . $relative;

            if ($item->isDir()) {
                if (!is_dir($targetPath) && !mkdir($targetPath, 0755, true) && !is_dir($targetPath)) {
                    throw new RuntimeException("Não foi possível criar diretório: {$targetPath}");
                }

                continue;
            }

            $parent = dirname($targetPath);
            if (!is_dir($parent) && !mkdir($parent, 0755, true) && !is_dir($parent)) {
                throw new RuntimeException("Não foi possível criar diretório: {$parent}");
            }

            $content = (string) file_get_contents($item->getPathname());
            $content = $this->transformContent($content, $templateKey, $slug, $folder, $displayName, $relative);
            if (file_put_contents($targetPath, $content) === false) {
                throw new RuntimeException("Não foi possível gravar: {$targetPath}");
            }

            $created[] = $this->relativeProjectPath($targetPath);
        }
    }

    /**
     * @param list<string> $created
     */
    private function writeClientConfig(
        string $targetRoot,
        string $slug,
        string $displayName,
        string $folder,
        array &$created
    ): void {
        $prefix = ClientScaffoldNaming::orderReferencePrefix($slug);
        $nameExport = var_export($displayName, true);
        $assetBase = "/assets/clients/{$slug}/site";
        $uploadBase = "/uploads/clients/{$slug}/products";

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace Clients\\{$folder}\\Config;

final class ClientConfig
{
    /**
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        return [
            'key' => '{$slug}',
            'name' => {$nameExport},
            'legalName' => {$nameExport},
            // [REVISAR] Substituir pelo CNPJ real da loja antes do go-live.
            'cnpj' => '[REVISAR]',
            'tagline' => 'Loja online',
            'adminName' => 'ERP Admin',
            'emailPlaceholder' => 'admin@example.com',
            'phone' => '[REVISAR]',
            'phoneHref' => '+5500000000000',
            'whatsappNumber' => '5500000000000',
            // [REVISAR] Endereço comercial completo.
            'address' => '[REVISAR]',
            'businessHours' => [
                'weekday' => '08h às 18h',
                'saturday' => '08h às 12h',
                'sunday' => 'Fechado',
            ],
            'assets' => [
                'base' => '{$assetBase}',
                'logo' => '{$assetBase}/img/logo-placeholder.svg',
            ],
            'uploads' => [
                'products' => '{$uploadBase}',
            ],
            'commerce' => [
                'orderReferencePrefix' => '{$prefix}',
                'strictOwnership' => true,
            ],
            'legalPages' => [
                'privacy' => '/politica-de-privacidade',
                'terms' => '/termos-de-uso',
            ],
        ];
    }
}

PHP;

        $path = $targetRoot . '/Config/ClientConfig.php';
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException("Não foi possível criar diretório: {$dir}");
        }

        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException("Não foi possível gravar ClientConfig: {$path}");
        }

        $created[] = $this->relativeProjectPath($path);
    }

    /**
     * @param list<string> $created
     */
    private function copyLegalArtifacts(string $targetRoot, string $slug, string $folder, array &$created): void
    {
        $bmcRoot = $this->projectRoot . '/app/Clients/' . self::LEGAL_SOURCE_FOLDER;

        $legalControllerSource = $bmcRoot . '/Site/Controllers/LegalController.php';
        if (!is_file($legalControllerSource)) {
            throw new RuntimeException('LegalController do template Bmc não encontrado.');
        }

        $legalControllerTarget = $targetRoot . '/Site/Controllers/LegalController.php';
        $content = (string) file_get_contents($legalControllerSource);
        $content = str_replace(
            'Clients\\' . self::LEGAL_SOURCE_FOLDER . '\\',
            "Clients\\{$folder}\\",
            $content
        );

        if (file_put_contents($legalControllerTarget, $content) === false) {
            throw new RuntimeException('Não foi possível gravar LegalController.');
        }
        $created[] = $this->relativeProjectPath($legalControllerTarget);

        foreach (['privacy.php', 'terms.php'] as $viewFile) {
            $source = $bmcRoot . '/Site/Views/legal/' . $viewFile;
            $target = $targetRoot . '/Site/Views/legal/' . $viewFile;
            if (!is_file($source)) {
                throw new RuntimeException("View legal {$viewFile} não encontrada no Bmc.");
            }

            if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0755, true) && !is_dir(dirname($target))) {
                throw new RuntimeException('Não foi possível criar diretório legal views.');
            }

            if (!copy($source, $target)) {
                throw new RuntimeException("Não foi possível copiar view legal {$viewFile}.");
            }
            $created[] = $this->relativeProjectPath($target);
        }
    }

    /**
     * @param list<string> $created
     */
    private function patchRoutesWithLegal(string $targetRoot, string $folder, array &$created): void
    {
        $routesFile = $targetRoot . '/routes.php';
        if (!is_file($routesFile)) {
            throw new RuntimeException('routes.php não encontrado após cópia do template.');
        }

        $content = (string) file_get_contents($routesFile);
        if (str_contains($content, 'LegalController')) {
            return;
        }

        $content = preg_replace(
            '/(use Clients\\\\[^;]+OrderTrackingController;)/',
            "$1\nuse Clients\\{$folder}\\Site\\Controllers\\LegalController;",
            $content,
            1
        ) ?? $content;

        $legalRoutes = <<<'ROUTES'

    ['method' => 'GET', 'path' => '/politica-de-privacidade', 'handler' => [LegalController::class, 'privacy'], 'middleware' => 'web'],
    ['method' => 'GET', 'path' => '/termos-de-uso', 'handler' => [LegalController::class, 'terms'], 'middleware' => 'web'],
ROUTES;

        $content = preg_replace(
            "/(\['method' => 'GET', 'path' => '\/', 'handler' => \[HomeController::class, 'index'\], 'middleware' => 'web'\],)/",
            '$1' . $legalRoutes,
            $content,
            1
        ) ?? $content;

        if (file_put_contents($routesFile, $content) === false) {
            throw new RuntimeException('Não foi possível atualizar routes.php com rotas legais.');
        }
        $created[] = $this->relativeProjectPath($routesFile);
    }

    /**
     * @param list<string> $created
     */
    private function copyPublicAssets(string $templateKey, string $slug, array &$created): void
    {
        $templateSlug = strtolower($templateKey);
        $source = $this->projectRoot . "/public/assets/clients/{$templateSlug}/site";
        $target = $this->projectRoot . "/public/assets/clients/{$slug}/site";

        if (!is_dir($source)) {
            if (!is_dir($target) && !mkdir($target, 0755, true) && !is_dir($target)) {
                throw new RuntimeException("Não foi possível criar diretório de assets: {$target}");
            }

            $readme = $target . '/README.md';
            file_put_contents(
                $readme,
                "# Assets do cliente {$slug}\n\nSubstitua logo, CSS e imagens conforme branding do tenant.\n"
            );
            $created[] = $this->relativeProjectPath($readme);

            return;
        }

        $this->copyDirectoryRecursive($source, $target, $created);

        $readme = $target . '/README.md';
        file_put_contents(
            $readme,
            "# Assets do cliente {$slug}\n\nArquivos copiados do template `{$templateSlug}`. Personalize logo e CSS antes do go-live.\n"
        );
        $created[] = $this->relativeProjectPath($readme);
    }

    /**
     * @param list<string> $created
     */
    private function ensureUploadsDirectory(string $slug, array &$created): void
    {
        $path = $this->projectRoot . "/public/uploads/clients/{$slug}/products";
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new RuntimeException("Não foi possível criar diretório de uploads: {$path}");
        }

        $gitkeep = $path . '/.gitkeep';
        if (!is_file($gitkeep)) {
            file_put_contents($gitkeep, '');
            $created[] = $this->relativeProjectPath($gitkeep);
        }
    }

    /**
     * @param list<string> $created
     */
    private function copyDirectoryRecursive(string $source, string $target, array &$created): void
    {
        if (!is_dir($target) && !mkdir($target, 0755, true) && !is_dir($target)) {
            throw new RuntimeException("Não foi possível criar diretório: {$target}");
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            /** @var \SplFileInfo $item */
            $relative = substr($item->getPathname(), strlen($source) + 1);
            $targetPath = $target . '/' . str_replace('\\', '/', $relative);

            if ($item->isDir()) {
                if (!is_dir($targetPath) && !mkdir($targetPath, 0755, true) && !is_dir($targetPath)) {
                    throw new RuntimeException("Não foi possível criar diretório: {$targetPath}");
                }

                continue;
            }

            $parent = dirname($targetPath);
            if (!is_dir($parent) && !mkdir($parent, 0755, true) && !is_dir($parent)) {
                throw new RuntimeException("Não foi possível criar diretório: {$parent}");
            }

            if (!copy($item->getPathname(), $targetPath)) {
                throw new RuntimeException("Não foi possível copiar asset: {$targetPath}");
            }

            $created[] = $this->relativeProjectPath($targetPath);
        }
    }

    private function transformContent(
        string $content,
        string $templateKey,
        string $slug,
        string $folder,
        string $displayName,
        string $relativePath
    ): string {
        $templateFolder = ClientScaffoldNaming::slugToFolder($templateKey);
        $templateSlug = strtolower($templateKey);

        $content = str_replace("Clients\\{$templateFolder}\\", "Clients\\{$folder}\\", $content);
        $content = str_replace("/assets/clients/{$templateSlug}/", "/assets/clients/{$slug}/", $content);
        $content = str_replace("/uploads/clients/{$templateSlug}/", "/uploads/clients/{$slug}/", $content);

        if ($relativePath === 'Site/Views/home.php') {
            $content = str_replace(
                'Este é um site público demonstrativo para validar multi-cliente sem alterar o ERP.',
                'Bem-vindo à loja online. Personalize esta página conforme o branding do tenant.',
                $content
            );
        }

        return $content;
    }

    private function relativeProjectPath(string $absolutePath): string
    {
        $root = rtrim(str_replace('\\', '/', $this->projectRoot), '/');
        $path = str_replace('\\', '/', $absolutePath);

        if (str_starts_with($path, $root . '/')) {
            return substr($path, strlen($root) + 1);
        }

        return $path;
    }
}
