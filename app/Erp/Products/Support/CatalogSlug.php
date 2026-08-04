<?php

declare(strict_types=1);

namespace Erp\Products\Support;

/**
 * Geração consistente de slugs para catálogo (produtos e categorias).
 */
final class CatalogSlug
{
    public static function fromText(string $value): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT', $value) ?: $value;
        $value = preg_replace('/[^A-Za-z0-9]+/', '-', $value);
        $value = trim((string) $value, '-');

        return strtolower($value);
    }

    public static function forProduct(int $id, string $name): string
    {
        $base = $id . '-' . self::fromText($name);

        return $base !== '-' ? $base : (string) $id;
    }
}
