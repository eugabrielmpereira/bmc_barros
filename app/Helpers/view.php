<?php

declare(strict_types=1);

if (!function_exists('resolve_view_path')) {
    function resolve_view_path(string $view): string
    {
        $roots = [
            'erp' => ERP_PATH . '/Admin/Views',
            'client' => CLIENTS_PATH . '/' . \Shared\Support\Client::folder() . '/Site/Views',
            'shared' => SHARED_PATH . '/View',
        ];

        if (str_contains($view, '::')) {
            [$root, $relative] = explode('::', $view, 2);
            $base = $roots[$root] ?? null;

            if ($base === null) {
                throw new InvalidArgumentException("Raiz de view \"{$root}\" não registrada.");
            }

            return $base . '/' . ltrim($relative, '/') . '.php';
        }

        throw new InvalidArgumentException("View \"{$view}\" deve informar um contexto registrado.");
    }
}

if (!function_exists('partial')) {
    function partial(string $name, array $viewData = []): void
    {
        $view = str_contains($name, '::') ? $name : 'erp::partials/' . $name;
        $path = resolve_view_path($view);

        if (!is_file($path)) {
            throw new InvalidArgumentException("Partial \"{$name}\" não encontrada.");
        }

        extract($viewData, EXTR_SKIP);
        require $path;
    }
}

if (!function_exists('renderView')) {
    /**
     * Renderiza views por contexto: erp::, client:: ou shared::.
     */
    function renderView(string $view, array $viewData = [], ?string $layout = null): void
    {
        $viewPath = resolve_view_path($view);

        if (!is_file($viewPath)) {
            throw new InvalidArgumentException("View \"{$view}\" não encontrada.");
        }

        extract($viewData, EXTR_SKIP);

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        if ($layout === null) {
            echo $content;

            return;
        }

        $layoutPath = resolve_view_path($layout);

        if (!is_file($layoutPath)) {
            throw new InvalidArgumentException("Layout \"{$layout}\" não encontrado.");
        }

        $tituloPagina = $viewData['tituloPagina'] ?? 'Painel';
        $paginaAtiva = $viewData['paginaAtiva'] ?? '';

        extract($viewData, EXTR_SKIP);
        require $layoutPath;
    }
}
