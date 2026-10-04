CREATE TABLE product_types (
    id integer PRIMARY KEY,
    name text NOT NULL
);

ALTER TABLE products
    DROP COLUMN min_plafond,
    DROP COLUMN max_plafond,
    DROP COLUMN min_period,
    DROP COLUMN max_period,
    ADD COLUMN product_type_id integer;

ALTER TABLE products
    ADD CONSTRAINT fk_products_product_type_id
        FOREIGN KEY (product_type_id) REFERENCES product_types (id) ON DELETE RESTRICT;
