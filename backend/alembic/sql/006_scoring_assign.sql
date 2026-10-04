INSERT INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
JOIN permissions ON permissions.code = 'scoring.assign'
WHERE roles.code IN ('admin_it', 'operator')
ON CONFLICT DO NOTHING;
