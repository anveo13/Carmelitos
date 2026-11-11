-- =====================================================
-- ESTOQUE
-- =====================================================
--
-- Adiciona a quantidade em estoque aos produtos.
--
-- Rodar UMA vez no banco (phpMyAdmin > SQL) antes de
-- publicar a versão com controle de estoque.
--
-- O estoque pode ficar negativo: isso indica que foi
-- vendido mais do que estava cadastrado.
--

ALTER TABLE products
    ADD COLUMN stock INT NOT NULL DEFAULT 0
    AFTER price;
