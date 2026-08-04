<?php

declare(strict_types=1);

namespace Database\Migrations;

/**
 * Galeria, categorias M:N, atributos por produto e esqueleto de variações.
 */
final class CreateProductCatalogTables extends Migration
{
    public function up(): void
    {
        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS product_images (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                product_id INT UNSIGNED NOT NULL,
                path VARCHAR(255) NOT NULL,
                alt_text VARCHAR(255) NULL DEFAULT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_primary TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                KEY product_images_product_sort (product_id, sort_order),
                KEY product_images_primary (product_id, is_primary),
                CONSTRAINT fk_product_images_product
                    FOREIGN KEY (product_id) REFERENCES produtos (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);

        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS product_categories (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                product_id INT UNSIGNED NOT NULL,
                category_id INT UNSIGNED NOT NULL,
                is_primary TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY product_categories_product_category (product_id, category_id),
                KEY product_categories_category (category_id),
                KEY product_categories_primary (product_id, is_primary),
                CONSTRAINT fk_product_categories_product
                    FOREIGN KEY (product_id) REFERENCES produtos (id) ON DELETE CASCADE,
                CONSTRAINT fk_product_categories_category
                    FOREIGN KEY (category_id) REFERENCES categorias (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);

        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS product_attribute_values (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                product_id INT UNSIGNED NOT NULL,
                attribute_id INT UNSIGNED NOT NULL,
                attribute_option_id INT UNSIGNED NULL DEFAULT NULL,
                value_text VARCHAR(255) NULL DEFAULT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY product_attribute_values_product_attr (product_id, attribute_id),
                KEY product_attribute_values_option (attribute_option_id),
                CONSTRAINT fk_product_attr_values_product
                    FOREIGN KEY (product_id) REFERENCES produtos (id) ON DELETE CASCADE,
                CONSTRAINT fk_product_attr_values_definition
                    FOREIGN KEY (attribute_id) REFERENCES attribute_definitions (id) ON DELETE CASCADE,
                CONSTRAINT fk_product_attr_values_option
                    FOREIGN KEY (attribute_option_id) REFERENCES attribute_options (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);

        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS product_variants (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                product_id INT UNSIGNED NOT NULL,
                sku VARCHAR(64) NULL DEFAULT NULL,
                slug VARCHAR(200) NULL DEFAULT NULL,
                name VARCHAR(200) NULL DEFAULT NULL,
                price DECIMAL(12,2) NULL DEFAULT NULL,
                compare_at_price DECIMAL(12,2) NULL DEFAULT NULL,
                stock_quantity INT UNSIGNED NOT NULL DEFAULT 0,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                attributes_json JSON NULL COMMENT 'Future variant axes',
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY product_variants_sku_unique (sku),
                KEY product_variants_product_status (product_id, status),
                CONSTRAINT fk_product_variants_product
                    FOREIGN KEY (product_id) REFERENCES produtos (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);

        CatalogMigrationHelper::syncProductPrimaryCategories($this->pdo);
        CatalogMigrationHelper::migratePrimaryImagesToGallery($this->pdo);
    }

    public function down(): void
    {
        $this->dropIfExists('product_variants');
        $this->dropIfExists('product_attribute_values');
        $this->dropIfExists('product_categories');
        $this->dropIfExists('product_images');
    }
}
