ALTER TABLE users DROP CONSTRAINT IF EXISTS ck_users_mfa_enabled;
ALTER TABLE users
    DROP COLUMN IF EXISTS mfa_confirmed_at,
    DROP COLUMN IF EXISTS mfa_enabled,
    DROP COLUMN IF EXISTS mfa_last_step,
    DROP COLUMN IF EXISTS mfa_secret_encrypted;
