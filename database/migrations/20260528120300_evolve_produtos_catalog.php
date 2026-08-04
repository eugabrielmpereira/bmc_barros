<?php

declare(strict_types=1);

namespace Database\Migrations;

/**
 * Evolução de produtos: slug, SKU, SEO, marca, unidade e busca FULLTEXT.
 */
final class EvolveProdutosCatalog extends Migration
{
    public function up(): void
    {
        if (!$this->columnExists('produtos', 'slug')) {
            $this->exec(<<<'SQL'
                ALTER TABLE produtos
                    ADD COLUMN slug VARCHAR(200) NOT NULL DEFAULT '' AFTER nome,
                    ADD COLUMN sku VARCHAR(64) NULL DEFAULT NULL AFTER slug,
                    ADD COLUMN short_description VARCHAR(500) NULL DEFAULT NULL AFTER sku,
                    ADD COLUMN long_description MEDIUMTEXT NULL DEFAULT NULL AFTER short_description,
                    ADD COLUMN brand_id INT UNSIGNED NULL DEFAULT NULL AFTER long_description,
                    ADD COLUMN unit_id SMALLINT UNSIGNED NULL DEFAULT NULL AFTER brand_id,
                    ADD COLUMN meta_title VARCHAR(255) NULL DEFAULT NULL AFTER unit_id,
                    ADD COLUMN meta_description VARCHAR(500) NULL DEFAULT NULL AFTER meta_title,
                    ADD COLUMN compare_at_price DECIMAL(12,2) NULL DEFAULT NULL AFTER preco,
                    ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER status,
                    ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at
            SQL);
        }

        CatalogMigrationHelper::copyDescriptionToLong($this->pdo);
        CatalogMigrationHelper::backfillProductSlugs($this->pdo);

        $this->exec('ALTER TABLE produtos MODIFY COLUMN slug VARCHAR(200) NOT NULL');

        if (!$this->indexExists('produtos', 'produtos_slug_unique')) {
            $this->exec('ALTER TABLE produtos ADD UNIQUE KEY produtos_slug_unique (slug)');
        }

        if (!$this->indexExists('produtos', 'produtos_sku_unique')) {
            $this->exec('ALTER TABLE produtos ADD UNIQUE KEY produtos_sku_unique (sku)');
        }

        if (!$this->indexExists('produtos', 'produtos_brand_id')) {
            $this->exec('ALTER TABLE produtos ADD KEY produtos_brand_id (brand_id)');
        }

        if (!$this->indexExists('produtos', 'produtos_unit_id')) {
            $this->exec('ALTER TABLE produtos ADD KEY produtos_unit_id (unit_id)');
        }

        if (CatalogMigrationHelper::tableExists($this->pdo, 'brands') && !$this->foreignKeyExists('produtos', 'fk_produtos_brand')) {
            $this->exec(<<<'SQL'
                ALTER TABLE produtos
                    ADD CONSTRAINT fk_produtos_brand
                    FOREIGN KEY (brand_id) REFERENCES brands (id) ON DELETE SET NULL
            SQL);
        }

        if (CatalogMigrationHelper::tableExists($this->pdo, 'measurement_units') && !$this->foreignKeyExists('produtos', 'fk_produtos_unit')) {
            $this->exec(<<<'SQL'
                ALTER TABLE produtos
                    ADD CONSTRAINT fk_produtos_unit
                    FOREIGN KEY (unit_id) REFERENCES measurement_units (id) ON DELETE SET NULL
            SQL);
        }

        if (!$this->indexExists('produtos', 'ft_produtos_search')) {
            $this->exec(<<<'SQL'
                ALTER TABLE produtos
                    ADD FULLTEXT KEY ft_produtos_search (
                        nome, short_description, long_description, descricao, sku
                    )
            SQL);
        }
    }

    public function down(): void
    {
        if (!$this->columnExists('produtos', 'slug')) {
            return;
        }

        if ($this->foreignKeyExists('produtos', 'fk_produtos_unit')) {
            $this->exec('ALTER TABLE produtos DROP FOREIGN KEY fk_produtos_unit');
        }
        if ($this->foreignKeyExists('produtos', 'fk_produtos_brand')) {
            $this->exec('ALTER TABLE produtos DROP FOREIGN KEY fk_produtos_brand');
        }
        if ($this->indexExists('produtos', 'ft_produtos_search')) {
            $this->exec('ALTER TABLE produtos DROP INDEX ft_produtos_search');
        }
        if ($this->indexExists('produtos', 'produtos_unit_id')) {
            $this->exec('ALTER TABLE produtos DROP INDEX produtos_unit_id');
        }
        if ($this->indexExists('produtos', 'produtos_brand_id')) {
            $this->exec('ALTER TABLE produtos DROP INDEX produtos_brand_id');
        }
        if ($this->indexExists('produtos', 'produtos_sku_unique')) {
            $this->exec('ALTER TABLE produtos DROP INDEX produtos_sku_unique');
        }
        if ($this->indexExists('produtos', 'produtos_slug_unique')) {
            $this->exec('ALTER TABLE produtos DROP INDEX produtos_slug_unique');
        }

        $this->exec(<<<'SQL'
            ALTER TABLE produtos
                DROP COLUMN slug,
                DROP COLUMN sku,
                DROP COLUMN short_description,
                DROP COLUMN long_description,
                DROP COLUMN brand_id,
                DROP COLUMN unit_id,
                DROP COLUMN meta_title,
                DROP COLUMN meta_description,
                DROP COLUMN compare_at_price,
                DROP COLUMN created_at,
                DROP COLUMN updated_at
        SQL);
    }

    private function columnExists(string $table, string $column): bool
    {
        $column = str_replace('`', '', $column);
        $stmt = $this->pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");

        return $stmt !== false && $stmt->fetch() !== false;
    }

    private function indexExists(string $table, string $index): bool
    {
        $stmt = $this->pdo->prepare("SHOW INDEX FROM `{$table}` WHERE Key_name = ?");
        $stmt->execute([$index]);

        return $stmt->fetch() !== false;
    }

    private function foreignKeyExists(string $table, string $name): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM information_schema.table_constraints
             WHERE table_schema = DATABASE() AND table_name = ? AND constraint_name = ? LIMIT 1'
        );
        $stmt->execute([$table, $name]);

        return $stmt->fetchColumn() !== false;
    }
}
