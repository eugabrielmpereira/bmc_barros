<?php

declare(strict_types=1);

namespace Erp\Products\Support;

/**
 * Filtros consolidados da listagem pública de produtos.
 */
final class CatalogProductFilter
{
    /**
     * @param list<int> $attributeOptionIds
     */
    public function __construct(
        public readonly ?int $categoryId = null,
        public readonly string $searchQuery = '',
        public readonly ?int $brandId = null,
        public readonly array $attributeOptionIds = []
    ) {
    }
}
