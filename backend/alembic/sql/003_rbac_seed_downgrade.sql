DELETE FROM role_permissions
WHERE permission_id IN (
    SELECT id FROM permissions
    WHERE code IN ('branch.view_all', 'access.manage', 'scoring.submit', 'scoring.approve', 'scoring.assign')
);

DELETE FROM roles
WHERE code IN ('reviewer', 'approver', 'admin_it')
  AND NOT EXISTS (SELECT 1 FROM users WHERE users.role_id = roles.id);

DELETE FROM permissions
WHERE code IN ('branch.view_all', 'access.manage', 'scoring.submit', 'scoring.approve', 'scoring.assign');
