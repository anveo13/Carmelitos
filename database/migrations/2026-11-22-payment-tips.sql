-- Execute once on an existing Carmelito's database.
-- Old payments remain with no tip.

ALTER TABLE purchase_payments
    ADD COLUMN tip_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER fee_amount;
