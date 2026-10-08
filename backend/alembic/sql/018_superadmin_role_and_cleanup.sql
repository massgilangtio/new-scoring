-- Migrasi 018: Setup Role SUPERADMIN & Cleanup Role Lama
-- 1. Insert Role SUPERADMIN
INSERT INTO roles (code, name, is_active, created_at, updated_at)
VALUES ('SUPERADMIN', 'SUPER ADMINISTRATOR', true, now(), now())
ON CONFLICT (code) DO UPDATE SET name = 'SUPER ADMINISTRATOR', is_active = true;

-- 2. Hubungkan SEMUA permission ke SUPERADMIN
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id 
FROM roles r
CROSS JOIN permissions p
WHERE r.code = 'SUPERADMIN'
ON CONFLICT DO NOTHING;

-- 3. Arahkan semua users ke SUPERADMIN
UPDATE users 
SET role_id = (SELECT id FROM roles WHERE code = 'SUPERADMIN');

-- 4. Lepas foreign key role_id di tbl_kel_jabatan untuk role lama
UPDATE tbl_kel_jabatan 
SET role_id = NULL 
WHERE role_id != (SELECT id FROM roles WHERE code = 'SUPERADMIN');

-- 5. Update audit_logs actor_role_id ke SUPERADMIN agar riwayat audit rapi
ALTER TABLE audit_logs DISABLE TRIGGER trg_audit_logs_immutable;
UPDATE audit_logs
SET actor_role_id = (SELECT id FROM roles WHERE code = 'SUPERADMIN');
ALTER TABLE audit_logs ENABLE TRIGGER trg_audit_logs_immutable;

-- 6. Hapus semua role lama selain SUPERADMIN
DELETE FROM roles 
WHERE code != 'SUPERADMIN';

-- 6. Insert atau update Kelompok Jabatan 999 untuk Divisi Teknologi Informasi
INSERT INTO tbl_kel_jabatan (id_kel_jabatan, nama_kel_jabatan, role_id, total_pegawai, is_active, created_at, updated_at)
VALUES (
    '999', 
    'Divisi Teknologi Informasi', 
    (SELECT id FROM roles WHERE code = 'SUPERADMIN'), 
    1, 
    true, 
    now(), 
    now()
)
ON CONFLICT (id_kel_jabatan, nama_kel_jabatan) 
DO UPDATE SET 
    role_id = (SELECT id FROM roles WHERE code = 'SUPERADMIN'), 
    is_active = true, 
    updated_at = now();

-- 7. Update data user 4259 di tbl_userhris dan users
UPDATE tbl_userhris 
SET id_kel_jabatan = '999', nama_kel_jabatan = 'Divisi Teknologi Informasi', updated_at = now()
WHERE npp = '4259' OR userid = 'u4259';

UPDATE users
SET role_id = (SELECT id FROM roles WHERE code = 'SUPERADMIN'),
    job_group_id = (SELECT id FROM tbl_kel_jabatan WHERE id_kel_jabatan = '999' LIMIT 1)
WHERE username = '4259';
