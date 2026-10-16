<?php

declare(strict_types=1);

namespace Clients\Bmc\Site\Controllers;

use Core\Controller;
use Erp\Products\Services\ProductCatalogService;
use Erp\Shipping\Repositories\DeliveryZoneRepository;
use Throwable;

class HomeController extends Controller
{
    private const FEATURED_LIMIT = 8;

    public function index(): void
    {
        $featured = [];
        $departments = [];
        try {
            $catalog = (new ProductCatalogService())->getCatalogData('', '', '', 1);
            $inStock = array_values(array_filter(
                $catalog['produtos'] ?? [],
                static fn (array $product): bool => (int) ($product['estoque_atual'] ?? 0) > 0
            ));
            $featured = array_slice($inStock !== [] ? $inStock : ($catalog['produtos'] ?? []), 0, self::FEATURED_LIMIT);
            $departments = $catalog['categoryTree'] ?? [];
        } catch (Throwable $e) {
            error_log('[home_catalog] ' . $e->getMessage());
        }

        $zones = [];
        try {
            $zones = (new DeliveryZoneRepository())->listActive();
        } catch (Throwable $e) {
            error_log('[home_zones] ' . $e->getMessage());
        }

        $this->view('client::home', [
            'client' => client_config(),
            'featured' => $featured,
            'departments' => $departments,
            'zones' => $zones,
        ]);
    }
}
