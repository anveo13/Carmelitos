-- =====================================================
-- PAGAMENTOS E JUROS
-- =====================================================
--
-- payment_fees = porcentagem de juros de cada forma
--                de pagamento que tem juros
--
-- purchase_payments = cada pagamento feito: forma, valor das
--                compras, juros e total cobrado.
--                Um pagamento pode quitar várias compras
--                e o juros é calculado uma vez só,
--                sobre o total pago.
--
-- Rodar UMA vez no banco (phpMyAdmin > SQL) antes de
-- publicar a versão com juros.
--

CREATE TABLE payment_fees (
    method ENUM('credit', 'debit', 'link') NOT NULL,
    percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (method)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO payment_fees
    (method, percent)
VALUES
    ('credit', 0),
    ('debit', 0),
    ('link', 0);


CREATE TABLE purchase_payments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    person_id INT UNSIGNED NOT NULL,
    method ENUM('cash', 'pix', 'credit', 'debit', 'link') NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    fee_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    fee_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_purchase_payments_person (person_id),
    KEY idx_purchase_payments_method (method),
    KEY idx_purchase_payments_created (created_at),
    CONSTRAINT fk_purchase_payments_person
        FOREIGN KEY (person_id) REFERENCES people (id)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Compras pagas antes desta versão ficam sem pagamento
-- vinculado (forma de pagamento "não informada").

ALTER TABLE purchases
    ADD COLUMN payment_id INT UNSIGNED NULL AFTER paid_at,
    ADD KEY idx_purchases_payment (payment_id),
    ADD CONSTRAINT fk_purchases_payment
        FOREIGN KEY (payment_id) REFERENCES purchase_payments (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE;
