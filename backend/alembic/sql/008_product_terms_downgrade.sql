ALTER TABLE products
    DROP COLUMN IF EXISTS max_period,
    DROP COLUMN IF EXISTS min_period,
    DROP COLUMN IF EXISTS max_plafond,
    DROP COLUMN IF EXISTS min_plafond,
    DROP COLUMN IF EXISTS interest_rate,
    DROP COLUMN IF EXISTS business_unit;
