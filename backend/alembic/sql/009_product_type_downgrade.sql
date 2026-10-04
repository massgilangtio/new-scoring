ALTER TABLE products DROP CONSTRAINT IF EXISTS fk_products_product_type_id;
ALTER TABLE products DROP COLUMN IF EXISTS product_type_id;
ALTER TABLE products
    ADD COLUMN min_plafond numeric(20,2),
    ADD COLUMN max_plafond numeric(20,2),
    ADD COLUMN min_period integer,
    ADD COLUMN max_period integer;
DROP TABLE IF EXISTS product_types;
