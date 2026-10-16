<?php

declare(strict_types=1);

namespace Erp\Commerce\Controllers;

use Core\Controller;
use Erp\Products\Repositories\CategoryRepository;
use Erp\Products\Repositories\ProductRepository;
use Throwable;

/**
 * sitemap.xml e robots.txt da loja pública (genérico para qualquer cliente).
 */
class PublicSeoController extends Controller
{
    public function sitemap(): void
    {
        $urls = [
            ['loc' => app_url('/'), 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => app_url('/produtos'), 'priority' => '0.9', 'changefreq' => 'daily'],
        ];

        try {
            foreach ((new CategoryRepository())->getCatalogTree() as $root) {
                $urls[] = ['loc' => app_url('/produtos?categoria=' . rawurlencode((string) $root['slug'])), 'priority' => '0.8', 'changefreq' => 'weekly'];
                foreach (($root['children'] ?? []) as $child) {
                    $urls[] = ['loc' => app_url('/produtos?categoria=' . rawurlencode((string) $child['slug'])), 'priority' => '0.7', 'changefreq' => 'weekly'];
                }
            }

            $products = new ProductRepository();
            foreach ($products->allActive() as $product) {
                $entry = ['loc' => app_url('/produtos/' . rawurlencode($products->publicSlug($product))), 'priority' => '0.6', 'changefreq' => 'weekly'];
                $updated = (string) ($product['updated_at'] ?? '');
                if ($updated !== '' && ($ts = strtotime($updated)) !== false) {
                    $entry['lastmod'] = date('Y-m-d', $ts);
                }
                $urls[] = $entry;
            }
        } catch (Throwable $e) {
            error_log('[sitemap] ' . $e->getMessage());
        }

        foreach (['privacy', 'terms'] as $legal) {
            $url = client_legal_page_url($legal);
            if ($url !== null) {
                $urls[] = ['loc' => $url, 'priority' => '0.2', 'changefreq' => 'yearly'];
            }
        }

        header('Content-Type: application/xml; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $url) {
            echo '  <url><loc>' . htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>';
            if (isset($url['lastmod'])) {
                echo '<lastmod>' . $url['lastmod'] . '</lastmod>';
            }
            echo '<changefreq>' . $url['changefreq'] . '</changefreq><priority>' . $url['priority'] . '</priority></url>' . "\n";
        }
        echo '</urlset>' . "\n";
        exit;
    }

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /carrinho',
            'Disallow: /checkout',
            'Disallow: /pedido-recebido',
            'Disallow: /minha-conta',
            'Disallow: /meus-pedidos',
            'Disallow: /webhooks',
            'Disallow: /*?busca=',
            '',
            'Sitemap: ' . app_url('/sitemap.xml'),
        ];
        if (defined('APP_ENV') && APP_ENV !== 'production') {
            $lines = ['User-agent: *', 'Disallow: /'];
        }
        echo implode("\n", $lines) . "\n";
        exit;
    }
}
