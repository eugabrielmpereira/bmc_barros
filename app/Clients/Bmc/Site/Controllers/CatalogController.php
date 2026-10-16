<?php

declare(strict_types=1);

namespace Clients\Bmc\Site\Controllers;

use Core\Controller;
use Core\Exceptions\NotFoundException;
use Erp\Products\Services\ProductCatalogService;
use Erp\Shipping\Repositories\DeliveryZoneRepository;
use Throwable;

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

        $related = [];
        $categorySlug = (string) ($product['categoria_slug'] ?? '');
        if ($categorySlug !== '') {
            try {
                $sameCategory = $this->catalogService->getCatalogData($categorySlug, '', '', 1)['produtos'] ?? [];
                $related = array_slice(array_values(array_filter(
                    $sameCategory,
                    static fn (array $item): bool => (int) ($item['id'] ?? 0) !== (int) ($product['id'] ?? 0)
                )), 0, 4);
            } catch (Throwable $e) {
                error_log('[pdp_related] ' . $e->getMessage());
            }
        }

        $zones = [];
        try {
            $zones = (new DeliveryZoneRepository())->listActive();
        } catch (Throwable $e) {
            error_log('[pdp_zones] ' . $e->getMessage());
        }

        $this->view('client::catalog/show', [
            'client' => client_config(),
            'product' => $product,
            'related' => $related,
            'zones' => $zones,
        ]);
    }
}
