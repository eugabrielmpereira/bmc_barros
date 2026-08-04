<?php

declare(strict_types=1);

namespace Database\Migrations;

/**
 * Marcas e unidades de medida para catálogo e filtros futuros.
 */
final class CreateCatalogBrandsAndUnits extends Migration
{
    public function up(): void
    {
        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS brands (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                slug VARCHAR(160) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                meta_title VARCHAR(255) NULL DEFAULT NULL,
                meta_description VARCHAR(500) NULL DEFAULT NULL,
                logo_path VARCHAR(255) NULL DEFAULT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY brands_slug_unique (slug),
                KEY brands_status_sort (status, sort_order, name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);

        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS measurement_units (
                id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(10) NOT NULL COMMENT 'UN, KG, M2, etc.',
                name VARCHAR(60) NOT NULL,
                UNIQUE KEY measurement_units_code_unique (code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);

        $this->exec(<<<'SQL'
            INSERT INTO measurement_units (code, name) VALUES
                ('UN', 'Unidade'),
                ('KG', 'Quilograma'),
                ('M', 'Metro'),
                ('M2', 'Metro quadrado'),
                ('M3', 'Metro cúbico'),
                ('L', 'Litro'),
                ('SC', 'Saco'),
                ('CX', 'Caixa'),
                ('PC', 'Peça'),
                ('RL', 'Rolo')
            ON DUPLICATE KEY UPDATE name = VALUES(name)
        SQL);
    }

    public function down(): void
    {
        $this->dropIfExists('measurement_units');
        $this->dropIfExists('brands');
    }
}
