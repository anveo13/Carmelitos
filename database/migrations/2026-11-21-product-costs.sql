-- Execute once on an existing Carmelito's database.
-- Existing sales keep unit_cost as zero; reports use the current product cost
-- as a fallback until a new sale records its own cost snapshot.

ALTER TABLE products
    ADD COLUMN cost_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER price;

ALTER TABLE purchase_items
    ADD COLUMN unit_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER unit_price;
