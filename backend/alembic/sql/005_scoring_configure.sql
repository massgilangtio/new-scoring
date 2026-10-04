INSERT INTO permissions (code, name) VALUES
    ('scoring.configure', 'Mengelola konfigurasi scoring produk')
ON CONFLICT (code) DO NOTHING;

INSERT INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
JOIN permissions ON permissions.code = 'scoring.configure'
WHERE roles.code IN ('admin_it', 'operator')
ON CONFLICT DO NOTHING;
