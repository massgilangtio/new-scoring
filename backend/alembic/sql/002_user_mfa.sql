ALTER TABLE users
    ADD COLUMN mfa_secret_encrypted text,
    ADD COLUMN mfa_last_step bigint,
    ADD COLUMN mfa_enabled boolean NOT NULL DEFAULT false,
    ADD COLUMN mfa_confirmed_at timestamptz;

ALTER TABLE users
    ADD CONSTRAINT ck_users_mfa_enabled CHECK (
        mfa_enabled = false
        OR (mfa_secret_encrypted IS NOT NULL AND mfa_confirmed_at IS NOT NULL)
    );

COMMENT ON COLUMN users.mfa_secret_encrypted IS
    'Fernet-encrypted TOTP secret for Google Authenticator. Empty until enrollment starts.';
COMMENT ON COLUMN users.mfa_enabled IS
    'True only after the user confirms a valid authenticator code.';
