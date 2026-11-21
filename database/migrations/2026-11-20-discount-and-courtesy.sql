-- Execute once on an existing Carmelito's database.
-- Preserves existing purchases and items with zero discount/courtesy.

ALTER TABLE purchases
    ADD COLUMN discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER total,
    ADD COLUMN is_courtesy TINYINT(1) NOT NULL DEFAULT 0 AFTER discount_amount;

ALTER TABLE purchase_items
    ADD COLUMN is_courtesy TINYINT(1) NOT NULL DEFAULT 0 AFTER subtotal;
