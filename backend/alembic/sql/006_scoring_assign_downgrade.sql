DELETE FROM role_permissions
WHERE permission_id IN (SELECT id FROM permissions WHERE code = 'scoring.assign')
  AND role_id IN (SELECT id FROM roles WHERE code IN ('admin_it', 'operator'));
