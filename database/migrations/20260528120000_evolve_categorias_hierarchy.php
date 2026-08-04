<?php

declare(strict_types=1);

namespace Database\Migrations;

/**
 * Evolução de categorias: árvore (parent_id), até 3 níveis, slug e SEO.
 */
final class EvolveCategoriasHierarchy extends Migration
{
    public function up(): void
    {
        if ($this->columnExists('categorias', 'parent_id')) {
            return;
        }

        $this->exec(<<<'SQL'
            ALTER TABLE categorias
                ADD COLUMN parent_id INT UNSIGNED NULL DEFAULT NULL AFTER id,
                ADD COLUMN slug VARCHAR(160) NOT NULL DEFAULT '' AFTER nome,
                ADD COLUMN description TEXT NULL DEFAULT NULL AFTER slug,
                ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER description,
                ADD COLUMN level TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=root, 2=sub, 3=leaf' AFTER sort_order,
                ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active' AFTER level,
                ADD COLUMN meta_title VARCHAR(255) NULL DEFAULT NULL AFTER status,
                ADD COLUMN meta_description VARCHAR(500) NULL DEFAULT NULL AFTER meta_title,
                ADD COLUMN image_path VARCHAR(255) NULL DEFAULT NULL AFTER meta_description,
                ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER image_path,
                ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at
        SQL);

        CatalogMigrationHelper::backfillCategorySlugs($this->pdo);

        if ($this->indexExists('categorias', 'categorias_nome_unique')) {
            $this->exec('ALTER TABLE categorias DROP INDEX categorias_nome_unique');
        }

        $this->exec('ALTER TABLE categorias MODIFY COLUMN slug VARCHAR(160) NOT NULL');

        if (!$this->indexExists('categorias', 'categorias_slug_unique')) {
            $this->exec('ALTER TABLE categorias ADD UNIQUE KEY categorias_slug_unique (slug)');
        }

        if (!$this->indexExists('categorias', 'categorias_parent_sort')) {
            $this->exec('ALTER TABLE categorias ADD KEY categorias_parent_sort (parent_id, sort_order, nome)');
        }

        if (!$this->foreignKeyExists('categorias', 'fk_categorias_parent')) {
            $this->exec(<<<'SQL'
                ALTER TABLE categorias
                    ADD CONSTRAINT fk_categorias_parent
                    FOREIGN KEY (parent_id) REFERENCES categorias (id) ON DELETE RESTRICT
            SQL);
        }

        if (!$this->checkExists('categorias', 'chk_categorias_level')) {
            $this->exec(<<<'SQL'
                ALTER TABLE categorias
                    ADD CONSTRAINT chk_categorias_level CHECK (level BETWEEN 1 AND 3)
            SQL);
        }
    }

    public function down(): void
    {
        if (!$this->columnExists('categorias', 'parent_id')) {
            return;
        }

        if ($this->checkExists('categorias', 'chk_categorias_level')) {
            $this->exec('ALTER TABLE categorias DROP CHECK chk_categorias_level');
        }
        if ($this->foreignKeyExists('categorias', 'fk_categorias_parent')) {
            $this->exec('ALTER TABLE categorias DROP FOREIGN KEY fk_categorias_parent');
        }
        if ($this->indexExists('categorias', 'categorias_slug_unique')) {
            $this->exec('ALTER TABLE categorias DROP INDEX categorias_slug_unique');
        }
        if ($this->indexExists('categorias', 'categorias_parent_sort')) {
            $this->exec('ALTER TABLE categorias DROP INDEX categorias_parent_sort');
        }

        $this->exec(<<<'SQL'
            ALTER TABLE categorias
                DROP COLUMN parent_id,
                DROP COLUMN slug,
                DROP COLUMN description,
                DROP COLUMN sort_order,
                DROP COLUMN level,
                DROP COLUMN status,
                DROP COLUMN meta_title,
                DROP COLUMN meta_description,
                DROP COLUMN image_path,
                DROP COLUMN created_at,
                DROP COLUMN updated_at
        SQL);

        if (!$this->indexExists('categorias', 'categorias_nome_unique')) {
            $this->exec('ALTER TABLE categorias ADD UNIQUE KEY categorias_nome_unique (nome)');
        }
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

    private function checkExists(string $table, string $name): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM information_schema.table_constraints
             WHERE table_schema = DATABASE() AND table_name = ? AND constraint_name = ? LIMIT 1'
        );
        $stmt->execute([$table, $name]);

        return $stmt->fetchColumn() !== false;
    }
}
