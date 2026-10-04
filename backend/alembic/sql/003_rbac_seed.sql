INSERT INTO permissions (code, name) VALUES
    ('branch.view_all', 'Melihat data seluruh cabang'),
    ('access.manage', 'Mengelola user, role, dan permission'),
    ('scoring.submit', 'Mengajukan scoring'),
    ('scoring.approve', 'Memutuskan scoring'),
    ('scoring.assign', 'Menugaskan approver')
ON CONFLICT (code) DO NOTHING;

INSERT INTO roles (code, name) VALUES
    ('reviewer', 'Reviewer'),
    ('approver', 'Approver'),
    ('admin_it', 'Admin IT')
ON CONFLICT (code) DO NOTHING;

INSERT INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
JOIN permissions ON permissions.code = 'scoring.submit'
WHERE roles.code = 'reviewer'
ON CONFLICT DO NOTHING;

INSERT INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
JOIN permissions ON permissions.code = 'scoring.approve'
WHERE roles.code = 'approver'
ON CONFLICT DO NOTHING;

INSERT INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
JOIN permissions ON permissions.code IN ('branch.view_all', 'access.manage')
WHERE roles.code = 'admin_it'
ON CONFLICT DO NOTHING;

-- Akun bootstrap yang sudah ada perlu bisa membuka manajemen akses.
INSERT INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
JOIN permissions ON permissions.code IN ('branch.view_all', 'access.manage')
WHERE roles.code = 'operator'
ON CONFLICT DO NOTHING;
