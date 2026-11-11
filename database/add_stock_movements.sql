-- =====================================================
-- MOVIMENTOS DE ESTOQUE
-- =====================================================
--
-- Histórico das entradas e correções de estoque:
--
--   initial    = estoque inicial do produto
--   restock    = reabastecimento
--   adjustment = correção manual da quantidade
--
-- As vendas não entram aqui: elas já ficam
-- registradas em purchase_items.
--
-- Rodar UMA vez no banco (phpMyAdmin > SQL) antes de
-- publicar a versão com reabastecimento.
--

CREATE TABLE stock_movements (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id INT UNSIGNED NOT NULL,
    type ENUM('initial', 'restock', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    stock_after INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_stock_movements_product (product_id),
    KEY idx_stock_movements_created (created_at),
    CONSTRAINT fk_stock_movements_product
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- O estoque que já existe hoje vira o "estoque inicial".

INSERT INTO stock_movements
    (product_id, type, quantity, stock_after)
SELECT
    id,
    'initial',
    stock,
    stock
FROM products
WHERE stock > 0;
