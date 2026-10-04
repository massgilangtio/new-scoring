ALTER TABLE products
    ADD COLUMN business_unit smallint,
    ADD COLUMN interest_rate numeric(8,2),
    ADD COLUMN min_plafond numeric(20,2),
    ADD COLUMN max_plafond numeric(20,2),
    ADD COLUMN min_period integer,
    ADD COLUMN max_period integer;
