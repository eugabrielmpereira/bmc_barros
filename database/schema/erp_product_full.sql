-- =============================================================================
-- ERP Produto — Schema completo (MySQL / XAMPP)
-- Banco: erp_product
--
-- Uso: importar no phpMyAdmin (aba SQL) ou: mysql -u root < erp_product_full.sql
-- Após importar:
--   1. Ajuste DB_NAME=erp_product no .env
--   2. php database/seeds/criar_admin.php "email@exemplo.com" "SuaSenha123"
--   3. php migrate.php run  (se importar schema antigo; fresh install já inclui batch 2)
--   4. php database/seeds/seed_catalog_mvp.php
-- =============================================================================

CREATE DATABASE IF NOT EXISTS erp_product
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE erp_product;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS carts;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS product_variants;
DROP TABLE IF EXISTS product_attribute_values;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS product_categories;
DROP TABLE IF EXISTS attribute_options;
DROP TABLE IF EXISTS attribute_definitions;
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS commercial_clients;
DROP TABLE IF EXISTS produtos;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS brands;
DROP TABLE IF EXISTS measurement_units;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS migrations;

SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- Núcleo: usuários e rate limit de login
-- -----------------------------------------------------------------------------

CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'ativo',
    role VARCHAR(40) NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY usuarios_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    created_at DATETIME NOT NULL,
    KEY login_attempts_ip_created (ip_address, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Catálogo
-- -----------------------------------------------------------------------------

CREATE TABLE categorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id INT UNSIGNED NULL DEFAULT NULL,
    nome VARCHAR(120) NOT NULL,
    slug VARCHAR(160) NOT NULL,
    description TEXT NULL DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    level TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=root, 2=sub, 3=leaf',
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    meta_title VARCHAR(255) NULL DEFAULT NULL,
    meta_description VARCHAR(500) NULL DEFAULT NULL,
    image_path VARCHAR(255) NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY categorias_slug_unique (slug),
    KEY categorias_parent_sort (parent_id, sort_order, nome),
    CONSTRAINT fk_categorias_parent FOREIGN KEY (parent_id) REFERENCES categorias (id) ON DELETE RESTRICT,
    CONSTRAINT chk_categorias_level CHECK (level BETWEEN 1 AND 3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE brands (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE measurement_units (
    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL,
    name VARCHAR(60) NOT NULL,
    UNIQUE KEY measurement_units_code_unique (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO measurement_units (code, name) VALUES
    ('UN', 'Unidade'), ('KG', 'Quilograma'), ('M', 'Metro'), ('M2', 'Metro quadrado'),
    ('M3', 'Metro cúbico'), ('L', 'Litro'), ('SC', 'Saco'), ('CX', 'Caixa'),
    ('PC', 'Peça'), ('RL', 'Rolo');

CREATE TABLE attribute_definitions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(60) NOT NULL,
    name VARCHAR(120) NOT NULL,
    type ENUM('text', 'number', 'select', 'boolean') NOT NULL DEFAULT 'select',
    is_filterable TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY attribute_definitions_code_unique (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE attribute_options (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attribute_id INT UNSIGNED NOT NULL,
    value VARCHAR(120) NOT NULL,
    slug VARCHAR(160) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    UNIQUE KEY attribute_options_attr_slug (attribute_id, slug),
    CONSTRAINT fk_attribute_options_definition
        FOREIGN KEY (attribute_id) REFERENCES attribute_definitions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE produtos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT UNSIGNED NOT NULL,
    nome VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL,
    sku VARCHAR(64) NULL DEFAULT NULL,
    short_description VARCHAR(500) NULL DEFAULT NULL,
    long_description MEDIUMTEXT NULL DEFAULT NULL,
    descricao TEXT NULL DEFAULT NULL,
    brand_id INT UNSIGNED NULL DEFAULT NULL,
    unit_id SMALLINT UNSIGNED NULL DEFAULT NULL,
    meta_title VARCHAR(255) NULL DEFAULT NULL,
    meta_description VARCHAR(500) NULL DEFAULT NULL,
    preco DECIMAL(12,2) NULL DEFAULT NULL,
    compare_at_price DECIMAL(12,2) NULL DEFAULT NULL,
    imagem VARCHAR(255) NULL DEFAULT NULL,
    estoque_atual INT UNSIGNED NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'ativo',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY produtos_slug_unique (slug),
    UNIQUE KEY produtos_sku_unique (sku),
    KEY produtos_categoria_status (categoria_id, status),
    KEY produtos_brand_id (brand_id),
    KEY produtos_unit_id (unit_id),
    FULLTEXT KEY ft_produtos_search (nome, short_description, long_description, descricao, sku),
    CONSTRAINT fk_produtos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias (id) ON DELETE RESTRICT,
    CONSTRAINT fk_produtos_brand FOREIGN KEY (brand_id) REFERENCES brands (id) ON DELETE SET NULL,
    CONSTRAINT fk_produtos_unit FOREIGN KEY (unit_id) REFERENCES measurement_units (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) NULL DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY product_images_product_sort (product_id, sort_order),
    CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES produtos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY product_categories_product_category (product_id, category_id),
    CONSTRAINT fk_product_categories_product FOREIGN KEY (product_id) REFERENCES produtos (id) ON DELETE CASCADE,
    CONSTRAINT fk_product_categories_category FOREIGN KEY (category_id) REFERENCES categorias (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_attribute_values (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    attribute_id INT UNSIGNED NOT NULL,
    attribute_option_id INT UNSIGNED NULL DEFAULT NULL,
    value_text VARCHAR(255) NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY product_attribute_values_product_attr (product_id, attribute_id),
    CONSTRAINT fk_product_attr_values_product FOREIGN KEY (product_id) REFERENCES produtos (id) ON DELETE CASCADE,
    CONSTRAINT fk_product_attr_values_definition FOREIGN KEY (attribute_id) REFERENCES attribute_definitions (id) ON DELETE CASCADE,
    CONSTRAINT fk_product_attr_values_option FOREIGN KEY (attribute_option_id) REFERENCES attribute_options (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_variants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    sku VARCHAR(64) NULL DEFAULT NULL,
    slug VARCHAR(200) NULL DEFAULT NULL,
    name VARCHAR(200) NULL DEFAULT NULL,
    price DECIMAL(12,2) NULL DEFAULT NULL,
    compare_at_price DECIMAL(12,2) NULL DEFAULT NULL,
    stock_quantity INT UNSIGNED NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    attributes_json JSON NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY product_variants_sku_unique (sku),
    KEY product_variants_product_status (product_id, status),
    CONSTRAINT fk_product_variants_product FOREIGN KEY (product_id) REFERENCES produtos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Clientes comerciais, estoque, pedidos e auditoria
-- -----------------------------------------------------------------------------

CREATE TABLE commercial_clients (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    document VARCHAR(40) NULL DEFAULT NULL,
    email VARCHAR(190) NULL DEFAULT NULL,
    phone VARCHAR(40) NULL DEFAULT NULL,
    address VARCHAR(255) NULL DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY commercial_clients_status_name (status, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stock_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    produto_id INT UNSIGNED NOT NULL,
    type ENUM('entrada', 'saida', 'ajuste') NOT NULL COMMENT 'Tipo da movimentação',
    quantity INT UNSIGNED NOT NULL COMMENT 'Quantidade movimentada (valor positivo)',
    previous_stock INT UNSIGNED NOT NULL,
    new_stock INT UNSIGNED NOT NULL,
    reason VARCHAR(255) NULL DEFAULT NULL,
    user_id INT UNSIGNED NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY stock_movements_produto_created (produto_id, created_at),
    KEY stock_movements_user_id (user_id),
    CONSTRAINT fk_stock_movements_produto FOREIGN KEY (produto_id) REFERENCES produtos (id) ON DELETE RESTRICT,
    CONSTRAINT fk_stock_movements_usuario FOREIGN KEY (user_id) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(80) NOT NULL,
    entity VARCHAR(80) NOT NULL,
    entity_id BIGINT UNSIGNED NULL DEFAULT NULL,
    user_id INT UNSIGNED NULL DEFAULT NULL,
    metadata JSON NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY audit_logs_entity_created (entity, created_at),
    KEY audit_logs_user_created (user_id, created_at),
    CONSTRAINT fk_audit_logs_usuario FOREIGN KEY (user_id) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    commercial_client_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    notes TEXT NULL DEFAULT NULL,
    created_by INT UNSIGNED NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY orders_client_created (commercial_client_id, created_at),
    KEY orders_status_created (status, created_at),
    CONSTRAINT fk_orders_commercial_client FOREIGN KEY (commercial_client_id) REFERENCES commercial_clients (id) ON DELETE RESTRICT,
    CONSTRAINT fk_orders_created_by FOREIGN KEY (created_by) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY order_items_order_id (order_id),
    KEY order_items_product_id (product_id),
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES produtos (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- E-commerce: carrinho
-- -----------------------------------------------------------------------------

CREATE TABLE carts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL DEFAULT NULL,
    session_token VARCHAR(64) NULL DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY carts_session_token_unique (session_token),
    KEY carts_user_id_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cart_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cart_id BIGINT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY cart_items_cart_product_unique (cart_id, product_id),
    KEY cart_items_cart_id (cart_id),
    KEY cart_items_product_id (product_id),
    CONSTRAINT fk_cart_items_cart FOREIGN KEY (cart_id) REFERENCES carts (id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_items_product FOREIGN KEY (product_id) REFERENCES produtos (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Controle de migrations (php migrate.php)
-- -----------------------------------------------------------------------------

CREATE TABLE migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL UNIQUE,
    batch INT NOT NULL,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (migration, batch) VALUES
    ('20250114120000_create_usuarios', 1),
    ('20250114120500_create_categorias', 1),
    ('20250114120600_create_produtos', 1),
    ('20250114121000_add_status_usuarios', 1),
    ('20250115120000_create_login_attempts', 1),
    ('20260515120000_create_stock_movements', 1),
    ('20260516190000_add_role_to_usuarios', 1),
    ('20260516191000_create_audit_logs', 1),
    ('20260516192000_create_commercial_clients', 1),
    ('20260516193000_create_orders', 1),
    ('20260516194000_create_order_items', 1),
    ('20260517031000_add_timestamps_to_usuarios', 1),
    ('20260517040000_create_carts_and_items', 1),
    ('20260528120000_evolve_categorias_hierarchy', 2),
    ('20260528120100_create_catalog_brands_and_units', 2),
    ('20260528120200_create_catalog_attributes', 2),
    ('20260528120300_evolve_produtos_catalog', 2),
    ('20260528120400_create_product_catalog_tables', 2),
    ('20260528120500_fix_cart_items_relations', 2);

-- Após importar: php database/seeds/seed_catalog_mvp.php
