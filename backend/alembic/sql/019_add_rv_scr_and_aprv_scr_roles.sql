-- Migrasi 019: Buat Role RV-SCR (REVIEWER SCORING) dan APRV-SCR (APPROVAL SCORING) serta Pemetaan Kelompok Jabatan
-- =========================================================================================================

-- 1. Insert Role RV-SCR (REVIEWER SCORING)
INSERT INTO roles (code, name, is_active, created_at, updated_at)
VALUES ('RV-SCR', 'REVIEWER SCORING', true, now(), now())
ON CONFLICT (code) DO UPDATE 
SET name = 'REVIEWER SCORING', is_active = true, updated_at = now();

-- 2. Insert Role APRV-SCR (APPROVAL SCORING)
INSERT INTO roles (code, name, is_active, created_at, updated_at)
VALUES ('APRV-SCR', 'APPROVAL SCORING', true, now(), now())
ON CONFLICT (code) DO UPDATE 
SET name = 'APPROVAL SCORING', is_active = true, updated_at = now();

-- 3. Berikan Hak Akses (Permissions) untuk RV-SCR (Reviewer Scoring)
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.code = 'RV-SCR'
  AND p.code IN (
      'dashboard.view',
      'scoring.submit',
      'scoring.view_score_details',
      'report.scoring',
      'master.debtors',
      'master.products'
  )
ON CONFLICT DO NOTHING;

-- 4. Berikan Hak Akses (Permissions) untuk APRV-SCR (Approval Scoring)
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.code = 'APRV-SCR'
  AND p.code IN (
      'dashboard.view',
      'scoring.approve',
      'scoring.assign',
      'scoring.view_score_details',
      'report.scoring',
      'master.debtors',
      'master.products'
  )
ON CONFLICT DO NOTHING;

-- 5. Petakan Kelompok Jabatan ke Role RV-SCR
-- Kriteria: Dari tbl_userhris, branchid != '001', nama jabatan '%Account Officer%' dan '%Credit Review Officer%'
-- Hasil id_kel_jabatan: 239 (Account Officer), 290 (Credit Reviewer), 297 (Account Officer Senior)
UPDATE tbl_kel_jabatan
SET role_id = (SELECT id FROM roles WHERE code = 'RV-SCR'),
    updated_at = now()
WHERE id_kel_jabatan IN (
    SELECT DISTINCT id_kel_jabatan
    FROM tbl_userhris
    WHERE branchid != '001'
      AND id_kel_jabatan IS NOT NULL
      AND (nm_jabatan ILIKE '%Account Officer%' OR nm_jabatan ILIKE '%Credit Review Officer%')
);

-- 6. Petakan Kelompok Jabatan ke Role APRV-SCR
-- Kriteria: Dari tbl_userhris, branchid != '001', nama jabatan '%Pemimpin Cabang%', '%Pemimpin Cabang Pembantu%', '%Wakil Pemimpin Cabang%', '%Pemimpin Cluster Credit Review%'
-- Hasil id_kel_jabatan: 137, 138, 139, 140, 166, 167, 201, 222, 225, 226, 227, 238, 299, 300, 312
UPDATE tbl_kel_jabatan
SET role_id = (SELECT id FROM roles WHERE code = 'APRV-SCR'),
    updated_at = now()
WHERE id_kel_jabatan IN (
    SELECT DISTINCT id_kel_jabatan
    FROM tbl_userhris
    WHERE branchid != '001'
      AND id_kel_jabatan IS NOT NULL
      AND (
          nm_jabatan ILIKE '%Pemimpin Cabang%'
          OR nm_jabatan ILIKE '%Pemimpin Cabang Pembantu%'
          OR nm_jabatan ILIKE '%Wakil Pemimpin Cabang%'
          OR nm_jabatan ILIKE '%Pemimpin Cluster Credit Review%'
      )
);
