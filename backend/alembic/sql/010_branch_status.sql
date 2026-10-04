UPDATE products SET business_unit = 0 WHERE business_unit IS NULL;

ALTER TABLE products
    ALTER COLUMN business_unit SET DEFAULT 0,
    ALTER COLUMN business_unit SET NOT NULL;

ALTER TABLE products
    ADD CONSTRAINT ck_products_business_unit CHECK (business_unit IN (0, 1));

COMMENT ON COLUMN products.business_unit IS 'Status Branch: 0 = Konvensional, 1 = Syariah';
