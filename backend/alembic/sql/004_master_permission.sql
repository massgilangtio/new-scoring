INSERT INTO permissions (code, name) VALUES
    ('master.manage', 'Mengelola cabang, produk, dan debitur')
ON CONFLICT (code) DO NOTHING;

INSERT INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
JOIN permissions ON permissions.code = 'master.manage'
WHERE roles.code IN ('admin_it', 'operator')
ON CONFLICT DO NOTHING;
