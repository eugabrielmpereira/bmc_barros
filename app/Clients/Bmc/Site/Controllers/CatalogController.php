<?php

declare(strict_types=1);

namespace Clients\Bmc\Site\Controllers;

use Core\Controller;
use Core\Exceptions\NotFoundException;
use Erp\Products\Services\ProductCatalogService;

class CatalogController extends Controller
{
    public function __construct(
        private ?ProductCatalogService $catalogService = null
    ) {
        $this->catalogService ??= new ProductCatalogService();
    }

    public function index(): void
    {
        $search = trim((string) ($_GET['busca'] ?? ''));
        $data = $this->catalogService->getCatalogData(
            (string) ($_GET['categoria'] ?? ''),
            $search,
            (string) ($_GET['marca'] ?? ''),
            max(1, (int) ($_GET['pagina'] ?? 1)),
            ProductCatalogService::parseAttributeSlugsFromRequest(
                isset($_GET['atributo']) ? (array) $_GET['atributo'] : []
            )
        );
        $data['client'] = client_config();

        $this->view('client::catalog/index', $data);
    }

    public function show(string $slug): void
    {
        $product = $this->catalogService->findPublicProduct($slug);
        if ($product === null) {
            throw new NotFoundException('Produto não encontrado.');
        }

        $this->view('client::catalog/show', [
            'client' => client_config(),
            'product' => $product,
        ]);
    }
}
