<?php

declare(strict_types=1);

if (!function_exists('config')) {
    function config(string $key, $default = null) {
        global $config;
        return $config[$key] ?? $default;
    }
}

if (!function_exists('env')) {
    function env(string $key, $default = null) {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}

if (!function_exists('dd')) {
    function dd(...$args): never {
        foreach ($args as $arg) {
            var_dump($arg);
        }
        exit;
    }
}

if (!function_exists('dump')) {
    function dump(...$args) {
        foreach ($args as $arg) {
            var_dump($arg);
        }
    }
}

if (!function_exists('abort')) {
    function abort(int $code, string $message = ''): never {
        http_response_code($code);
        echo $message ?: "Error $code";
        exit;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, ?string $message = null): never {
        if ($message) {
            $_SESSION['flash'] = $message;
        }
        header('Location: ' . app_url($url));
        exit;
    }
}

if (!function_exists('back')) {
    function back(string $message = ''): never {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        if ($message) {
            $_SESSION['flash'] = $message;
        }
        header("Location: $referer");
        exit;
    }
}

if (!function_exists('app_url')) {
    /**
     * Monta URL absoluta sem barras duplas (ex.: bmc.local//produtos).
     */
    function app_url(string $path = ''): string {
        $base = rtrim((string) BASE_URL, '/');
        $path = trim($path);

        if ($path === '' || $path === '/') {
            return $base;
        }

        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('route')) {
    function route(string $name, array $params = []): string {
        return app_url($name);
    }
}

if (!function_exists('url')) {
    function url(string $path): string {
        return app_url($path);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string {
        return BASE_URL . '/' . ltrim($path, '/');
    }
}

if (!function_exists('view_exists')) {
    function view_exists(string $view): bool {
        $path = resolve_view_path($view);
        return file_exists($path);
    }
}

if (!function_exists('client_config')) {
    function client_config(?string $key = null, mixed $default = null): mixed {
        $config = \Shared\Support\Client::config();

        if ($key === null) {
            return $config;
        }

        return $config[$key] ?? $default;
    }
}

if (!function_exists('client_asset')) {
    function client_asset(string $path): string {
        return \Shared\Support\Client::asset($path);
    }
}

if (!function_exists('client_whatsapp_url')) {
    function client_whatsapp_url(string $message): string {
        return \Shared\Support\Client::whatsappUrl($message);
    }
}

if (!function_exists('client_product_image_url')) {
    function client_product_image_url(string $filename): string {
        $filename = basename($filename);
        return \Shared\Support\Client::uploadUrl('products', $filename);
    }
}

if (!function_exists('shared_commerce_asset')) {
    function shared_commerce_asset(string $path): string {
        return BASE_URL . '/assets/shared/commerce/' . ltrim($path, '/');
    }
}

if (!function_exists('commerce_cart_nav_count')) {
    function commerce_cart_nav_count(): int {
        static $count = null;
        if ($count !== null) {
            return $count;
        }

        try {
            $summary = (new \Erp\Commerce\Services\CartService())->summary();
            $count = max(0, (int) ($summary['item_count'] ?? 0));
        } catch (\Throwable) {
            $count = 0;
        }

        return $count;
    }
}

if (!function_exists('commerce_cart_nav_link')) {
    function commerce_cart_nav_link(): void {
        $cartNavCount = commerce_cart_nav_count();
        require SHARED_PATH . '/View/site/cart-nav-link.php';
    }
}

if (!function_exists('customer_is_logged_in')) {
    function customer_is_logged_in(): bool
    {
        return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
    }
}

if (!function_exists('customer_display_name')) {
    function customer_display_name(): string
    {
        return trim((string) ($_SESSION['user_name'] ?? ''));
    }
}

if (!function_exists('customer_nav_links')) {
    function customer_nav_links(): void
    {
        require SHARED_PATH . '/View/site/customer-nav-links.php';
    }
}

if (!function_exists('commerce_assets')) {
    function commerce_assets(bool $includeCheckoutAddress = false): void {
        require SHARED_PATH . '/View/site/commerce-assets.php';
        if ($includeCheckoutAddress) {
            echo '<script src="' . e(shared_commerce_asset('tax-id.js')) . '" defer></script>' . "\n";
            echo '<script src="' . e(shared_commerce_asset('delivery-method.js')) . '" defer></script>' . "\n";
            echo '<script src="' . e(shared_commerce_asset('address-cep.js')) . '" defer></script>' . "\n";
        }
    }
}

if (!function_exists('site_topbar')) {
    /**
     * @param array<string, mixed> $options brandHref, whatsappMessage, client
     */
    function site_topbar(array $options = []): void {
        if (isset($options['client'])) {
            $client = $options['client'];
        }
        if (isset($options['brandHref'])) {
            $brandHref = $options['brandHref'];
        }
        if (isset($options['whatsappMessage'])) {
            $whatsappMessage = $options['whatsappMessage'];
        }
        require SHARED_PATH . '/View/site/site-topbar.php';
    }
}

if (!function_exists('site_nav_scripts')) {
    function site_nav_scripts(): void {
        require SHARED_PATH . '/View/site/site-nav-scripts.php';
    }
}

if (!function_exists('commerce_page_styles')) {
    function commerce_page_styles(): void {
        echo '<link rel="stylesheet" href="' . e(shared_commerce_asset('commerce.css')) . '" />' . "\n";
    }
}

if (!function_exists('commerce_footer_minimal')) {
    function commerce_footer_minimal(?string $clientLegalName = null): void {
        if ($clientLegalName === null || $clientLegalName === '') {
            $config = client_config();
            $clientLegalName = (string) ($config['legalName'] ?? $config['name'] ?? 'Cliente');
        }
        require SHARED_PATH . '/View/site/commerce-footer-minimal.php';
    }
}

if (!function_exists('client_legal_page_url')) {
    function client_legal_page_url(string $key): ?string {
        $pages = client_config('legalPages');
        if (!is_array($pages)) {
            return null;
        }

        $path = trim((string) ($pages[$key] ?? ''));
        if ($path === '') {
            return null;
        }

        return app_url($path);
    }
}

if (!function_exists('site_store_footer')) {
    /**
     * Rodapé institucional com identificação do lojista e links legais (quando configurados).
     *
     * @param array<string, mixed>|null $client
     */
    function site_store_footer(?array $client = null, bool $withLogo = true): void {
        $client ??= client_config();
        require SHARED_PATH . '/View/site/store-footer.php';
    }
}

if (!function_exists('commerce_alert_modifier')) {
    function commerce_alert_modifier(string $tipo): string {
        return match ($tipo) {
            'erro' => 'error',
            'neutral' => 'neutral',
            default => 'success',
        };
    }
}

if (!function_exists('commerce_checkout_input_class')) {
    /**
     * @param array<string, string> $fieldErrors
     */
    function commerce_checkout_input_class(array $fieldErrors, string $field, string $base = 'form-control'): string
    {
        return $base . (isset($fieldErrors[$field]) ? ' is-invalid' : '');
    }
}

if (!function_exists('commerce_checkout_field_error_html')) {
    /**
     * @param array<string, string> $fieldErrors
     */
    function commerce_checkout_field_error_html(array $fieldErrors, string $field): string
    {
        if (!isset($fieldErrors[$field])) {
            return '';
        }

        return '<span class="commerce-field-error" role="alert">' . e($fieldErrors[$field]) . '</span>';
    }
}

if (!function_exists('catalog_filter_url')) {
    /**
     * Monta URL do catálogo preservando filtros ativos (categoria, marca, busca).
     *
     * @param array<string, string|null> $overrides
     */
    function catalog_filter_url(array $overrides = []): string
    {
        $params = [
            'categoria' => (string) ($_GET['categoria'] ?? ''),
            'marca' => (string) ($_GET['marca'] ?? ''),
            'busca' => (string) ($_GET['busca'] ?? ''),
            'pagina' => (string) ($_GET['pagina'] ?? ''),
        ];

        $atributo = $_GET['atributo'] ?? [];
        if (!is_array($atributo)) {
            $atributo = [(string) $atributo];
        }

        foreach ($overrides as $key => $value) {
            if ($key === 'atributo') {
                if ($value === null) {
                    $atributo = [];
                } elseif (is_array($value)) {
                    $atributo = $value;
                } else {
                    $atributo = [(string) $value];
                }
                continue;
            }
            if ($value === null) {
                unset($params[$key]);
                continue;
            }
            $params[$key] = (string) $value;
        }

        $filtered = [];
        foreach ($params as $key => $value) {
            $value = trim((string) $value);
            if ($value === '' || ($key === 'pagina' && ($value === '1' || $value === '0'))) {
                continue;
            }
            $filtered[$key] = $value;
        }

        $atributo = array_values(array_unique(array_filter(array_map('trim', $atributo))));
        if ($atributo !== []) {
            $filtered['atributo'] = $atributo;
        }

        $query = http_build_query($filtered);

        return BASE_URL . '/produtos' . ($query !== '' ? '?' . $query : '');
    }
}

if (!function_exists('flash')) {
    function flash(string $key = 'flash'): ?string {
        $message = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);
        return is_string($message) ? $message : null;
    }
}

if (!function_exists('flash_data')) {
    function flash_data(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION[$key] ?? $default;
        unset($_SESSION[$key]);

        return $value;
    }
}

if (!function_exists('has_flash')) {
    function has_flash(string $key = 'flash'): bool {
        return isset($_SESSION[$key]);
    }
}

if (!function_exists('now')) {
    function now(): string {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('today')) {
    function today(): string {
        return date('Y-m-d');
    }
}

if (!function_exists('is_production')) {
    function is_production(): bool {
        return APP_ENV === 'production';
    }
}

if (!function_exists('is_local')) {
    function is_local(): bool {
        return APP_ENV === 'local';
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        return \Core\Security\Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return '<input type="hidden" name="csrf_token" value="'
            . htmlspecialchars(csrf_token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" />';
    }
}

if (!function_exists('gerarTokenCSRF')) {
    function gerarTokenCSRF(): string {
        return csrf_token();
    }
}

if (!function_exists('verificarCSRF')) {
    function verificarCSRF(): void {
        if (!\Core\Security\Csrf::validatePosted()) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Não foi possível processar a solicitação. Atualize a página e tente novamente.';
            exit;
        }
    }
}

require_once __DIR__ . '/view.php';
