<?php

declare(strict_types=1);

/**
 * Ícones SVG inline da loja (traço, herdam currentColor).
 */
if (!function_exists('bmc_icon')) {
    function bmc_icon(string $name, string $class = 'icon'): string
    {
        static $paths = [
            'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
            'cart' => '<path d="M3 4h2l2.4 11.2a1.5 1.5 0 0 0 1.5 1.2h8.7a1.5 1.5 0 0 0 1.5-1.1L21 8H6.2"/><circle cx="9.5" cy="20" r="1.3"/><circle cx="17.5" cy="20" r="1.3"/>',
            'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/>',
            'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
            'close' => '<path d="M6 6l12 12M18 6 6 18"/>',
            'truck' => '<path d="M2 6h11v10H2zM13 9h4l4 4v3h-8z"/><circle cx="6.5" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>',
            'store' => '<path d="M3 9h18l-1.5-5h-15zM4 9v11h16V9M9 20v-6h6v6"/>',
            'pix' => '<path d="m12 3 4 4-4 4-4-4zM12 13l4 4-4 4-4-4zM3 12l4-4 4 4-4 4zM13 12l4-4 4 4-4 4z"/>',
            'card' => '<rect x="3" y="5.5" width="18" height="13" rx="2"/><path d="M3 10h18M7 15h4"/>',
            'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            'pin' => '<path d="M12 21s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12z"/><circle cx="12" cy="9" r="2.5"/>',
            'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
            'chevron' => '<path d="m9 6 6 6-6 6"/>',
            'check' => '<path d="m5 12 5 5L20 7"/>',
            'plus' => '<path d="M12 5v14M5 12h14"/>',
            'minus' => '<path d="M5 12h14"/>',
            'trash' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
            'filter' => '<path d="M4 6h16M7 12h10M10 18h4"/>',
            'package' => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9z"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9"/>',
            // Pictogramas de departamento
            'dept-bag' => '<path d="M6 5h12l1 3-1 13H6L5 8z"/><path d="M5 8h14M9 12h6M9 15h6"/>',
            'dept-pipe' => '<path d="M3 8h9a4 4 0 0 1 4 4v9M3 13h7a1 1 0 0 1 1 1v7"/><path d="M3 6v9M14 21h7"/>',
            'dept-bolt' => '<path d="M13 2 5 13h6l-1 9 8-12h-6z"/>',
            'dept-hammer' => '<path d="M14 4l6 6-2.5 2.5-6-6zM11.5 6.5 3 15l3 3 8.5-8.5"/>',
            'dept-tiles' => '<rect x="3" y="3" width="8" height="8"/><rect x="13" y="3" width="8" height="8"/><rect x="3" y="13" width="8" height="8"/><rect x="13" y="13" width="8" height="8"/>',
            'dept-roller' => '<rect x="3" y="3" width="15" height="6" rx="1"/><path d="M18 6h3v6h-9v3M11 15h2v6h-2z"/>',
            'dept-brick' => '<path d="M3 6h18v12H3zM3 12h18M9 6v6M15 12v6"/>',
        ];

        if ($name === 'whatsapp') {
            return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.7-1.4.1-.2 0-.3 0-.5l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6a2.7 2.7 0 0 0 1.8-1.2 2.2 2.2 0 0 0 .1-1.2c0-.1-.2-.2-.4-.3z"/></svg>';
        }

        $inner = $paths[$name] ?? $paths['package'];

        return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $inner . '</svg>';
    }
}

if (!function_exists('bmc_department_icon')) {
    /**
     * Pictograma pelo nome do departamento/categoria (fallback: caixa).
     */
    function bmc_department_icon(string $categoryName): string
    {
        $name = mb_strtolower($categoryName, 'UTF-8');
        $map = [
            'dept-bag' => ['cimento', 'argamassa', 'areia', 'brita', 'material básico', 'material basico', 'cal', 'rejunte', 'chapisco', 'imperme', 'aditivo'],
            'dept-pipe' => ['hidráulic', 'hidraulic', 'tubo', 'conex', 'caixa', 'louça', 'louca', 'registro', 'torneira'],
            'dept-bolt' => ['elétric', 'eletric', 'fio', 'cabo', 'disjuntor', 'tomada', 'interruptor', 'quadro', 'lâmpada', 'lampada'],
            'dept-hammer' => ['ferrament', 'epi', 'ferragem', 'ferragens', 'arame', 'prego'],
            'dept-tiles' => ['piso', 'revestimento', 'cerâmic', 'ceramic', 'porcelanato'],
            'dept-roller' => ['tinta', 'acabamento', 'massa', 'verniz', 'pincel'],
            'dept-brick' => ['bloco', 'tijolo', 'telha'],
        ];

        foreach ($map as $icon => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($name, $keyword)) {
                    return $icon;
                }
            }
        }

        return 'package';
    }
}

if (!function_exists('bmc_price_tag')) {
    /**
     * Etiqueta de preço estilo gôndola: "R$" pequeno, reais grandes, centavos sobrescritos.
     */
    function bmc_price_tag(?float $price, string $size = 'md', ?float $compareAt = null): string
    {
        if ($price === null || $price <= 0) {
            return '<span class="price-tag price-tag--' . $size . ' price-tag--ask">Sob consulta</span>';
        }

        $reais = number_format(floor($price), 0, ',', '.');
        $cents = str_pad((string) (int) round(($price - floor($price)) * 100), 2, '0', STR_PAD_LEFT);
        if ($cents === '100') {
            $reais = number_format(floor($price) + 1, 0, ',', '.');
            $cents = '00';
        }

        $old = '';
        if ($compareAt !== null && $compareAt > $price) {
            $old = '<s class="price-tag__old">R$ ' . number_format($compareAt, 2, ',', '.') . '</s>';
        }

        return $old . '<span class="price-tag price-tag--' . $size . '" aria-label="R$ ' . $reais . ',' . $cents . '">'
            . '<span class="price-tag__cur" aria-hidden="true">R$</span>'
            . '<span class="price-tag__int" aria-hidden="true">' . $reais . '</span>'
            . '<span class="price-tag__dec" aria-hidden="true">,' . $cents . '</span>'
            . '</span>';
    }
}
