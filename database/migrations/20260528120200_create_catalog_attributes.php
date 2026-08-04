<?php

declare(strict_types=1);

namespace Database\Migrations;

/**
 * Definições de atributos e opções para filtros facetados futuros.
 */
final class CreateCatalogAttributes extends Migration
{
    public function up(): void
    {
        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS attribute_definitions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(60) NOT NULL COMMENT 'Machine key, e.g. voltage',
                name VARCHAR(120) NOT NULL,
                type ENUM('text', 'number', 'select', 'boolean') NOT NULL DEFAULT 'select',
                is_filterable TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY attribute_definitions_code_unique (code),
                KEY attribute_definitions_filter_sort (is_filterable, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);

        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS attribute_options (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                attribute_id INT UNSIGNED NOT NULL,
                value VARCHAR(120) NOT NULL,
                slug VARCHAR(160) NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                UNIQUE KEY attribute_options_attr_slug (attribute_id, slug),
                KEY attribute_options_attr_sort (attribute_id, sort_order),
                CONSTRAINT fk_attribute_options_definition
                    FOREIGN KEY (attribute_id) REFERENCES attribute_definitions (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);
    }

    public function down(): void
    {
        $this->dropIfExists('attribute_options');
        $this->dropIfExists('attribute_definitions');
    }
}
