-- Downgrade 019: Rollback RV-SCR & APRV-SCR roles
UPDATE tbl_kel_jabatan
SET role_id = NULL
WHERE role_id IN (SELECT id FROM roles WHERE code IN ('RV-SCR', 'APRV-SCR'));

DELETE FROM role_permissions
WHERE role_id IN (SELECT id FROM roles WHERE code IN ('RV-SCR', 'APRV-SCR'));

DELETE FROM roles
WHERE code IN ('RV-SCR', 'APRV-SCR');
