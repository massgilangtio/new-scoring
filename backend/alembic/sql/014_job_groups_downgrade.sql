-- Downgrade 014 job_groups

ALTER TABLE users DROP COLUMN IF EXISTS job_group_id;
DROP TABLE IF EXISTS job_groups;
