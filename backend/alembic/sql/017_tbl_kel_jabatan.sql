CREATE TABLE IF NOT EXISTS tbl_kel_jabatan (
    id SERIAL PRIMARY KEY,
    id_kel_jabatan VARCHAR(50) NOT NULL,
    nama_kel_jabatan VARCHAR(200),
    role_id INTEGER REFERENCES roles(id) ON DELETE SET NULL,
    total_pegawai INTEGER DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_tbl_kel_jabatan_id_kel ON tbl_kel_jabatan(id_kel_jabatan);
CREATE INDEX IF NOT EXISTS idx_tbl_kel_jabatan_nama ON tbl_kel_jabatan(nama_kel_jabatan);
CREATE UNIQUE INDEX IF NOT EXISTS uq_tbl_kel_jabatan_id_nama ON tbl_kel_jabatan (id_kel_jabatan, nama_kel_jabatan);

INSERT INTO tbl_kel_jabatan (id_kel_jabatan, nama_kel_jabatan, total_pegawai, is_active, created_at, updated_at)
SELECT 
    id_kel_jabatan,
    COALESCE(NULLIF(nama_kel_jabatan, ''), nm_jabatan, 'Kelompok ' || id_kel_jabatan) as nama_kel_jabatan,
    COUNT(*) as total_pegawai,
    true as is_active,
    CURRENT_TIMESTAMP as created_at,
    CURRENT_TIMESTAMP as updated_at
FROM tbl_userhris
WHERE id_kel_jabatan IS NOT NULL AND id_kel_jabatan != ''
GROUP BY id_kel_jabatan, COALESCE(NULLIF(nama_kel_jabatan, ''), nm_jabatan, 'Kelompok ' || id_kel_jabatan)
ORDER BY nama_kel_jabatan ASC
ON CONFLICT DO NOTHING;

ALTER TABLE users DROP CONSTRAINT IF EXISTS users_job_group_id_fkey;
DROP TABLE IF EXISTS job_groups CASCADE;
ALTER TABLE users ADD CONSTRAINT users_job_group_id_fkey FOREIGN KEY (job_group_id) REFERENCES tbl_kel_jabatan(id) ON DELETE SET NULL;
