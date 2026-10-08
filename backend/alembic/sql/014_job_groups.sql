-- 014: Kelompok Jabatan (job_groups) linked to roles + users.job_group_id

CREATE TABLE IF NOT EXISTS job_groups (
    id BIGSERIAL PRIMARY KEY,
    code TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    role_id BIGINT NOT NULL REFERENCES roles(id) ON DELETE RESTRICT,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS ix_job_groups_role_id ON job_groups(role_id);

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS job_group_id BIGINT NULL REFERENCES job_groups(id) ON DELETE RESTRICT;

CREATE INDEX IF NOT EXISTS ix_users_job_group_id ON users(job_group_id);

-- Seed 1 kelompok jabatan per role (kode = role.code)
INSERT INTO job_groups (code, name, role_id, is_active)
SELECT r.code, r.name, r.id, r.is_active
FROM roles r
WHERE NOT EXISTS (
    SELECT 1 FROM job_groups j WHERE j.code = r.code
);

-- Map existing users to kelompok jabatan by role
UPDATE users u
SET job_group_id = j.id
FROM job_groups j
WHERE u.job_group_id IS NULL
  AND j.role_id = u.role_id;
