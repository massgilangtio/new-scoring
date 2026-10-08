DELETE FROM role_permissions
WHERE role_id IN (
    SELECT id FROM roles WHERE code IN ('reviewer_scoring', 'approval_scoring')
);

DELETE FROM roles
WHERE code IN ('reviewer_scoring', 'approval_scoring')
  AND NOT EXISTS (SELECT 1 FROM users WHERE users.role_id = roles.id);
