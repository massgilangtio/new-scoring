COMMENT ON COLUMN products.business_unit IS NULL;
ALTER TABLE products DROP CONSTRAINT IF EXISTS ck_products_business_unit;
ALTER TABLE products ALTER COLUMN business_unit DROP DEFAULT;
ALTER TABLE products ALTER COLUMN business_unit DROP NOT NULL;
