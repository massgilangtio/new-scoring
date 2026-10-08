-- Per-menu permission catalog (otorisasi tiap menu)
INSERT INTO permissions (code, name) VALUES
    ('dashboard.view', 'Melihat dashboard'),
    ('master.debtors', 'Mengelola menu Debitur'),
    ('master.products', 'Mengelola menu Produk'),
    ('master.branches', 'Mengelola menu Cabang'),
    ('scoring.parameters', 'Mengelola menu Konfigurasi Parameter'),
    ('scoring.mapping', 'Mengelola menu Mapping Produk'),
    ('report.scoring', 'Melihat Laporan Scoring'),
    ('report.debtors', 'Melihat Riwayat Debitur'),
    ('report.products', 'Melihat Riwayat Produk'),
    ('report.changes', 'Melihat Perubahan Parameter'),
    ('audit.view', 'Melihat Audit Trail'),
    ('access.users', 'Mengelola menu User'),
    ('access.roles', 'Mengelola menu Role'),
    ('access.permissions', 'Mengelola menu Permission')
ON CONFLICT (code) DO NOTHING;

-- Admin IT & Operator: semua permission menu baru
INSERT INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
JOIN permissions ON permissions.code IN (
    'dashboard.view',
    'master.debtors', 'master.products', 'master.branches',
    'scoring.parameters', 'scoring.mapping',
    'report.scoring', 'report.debtors', 'report.products', 'report.changes',
    'audit.view',
    'access.users', 'access.roles', 'access.permissions'
)
WHERE roles.code IN ('admin_it', 'operator')
ON CONFLICT DO NOTHING;

-- Reviewer Scoring: hanya dashboard + transaksi (scoring.submit sudah ada)
INSERT INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
JOIN permissions ON permissions.code = 'dashboard.view'
WHERE roles.code IN ('reviewer_scoring', 'reviewer')
ON CONFLICT DO NOTHING;

-- Approval Scoring: hanya dashboard + approval (scoring.approve sudah ada)
INSERT INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
JOIN permissions ON permissions.code = 'dashboard.view'
WHERE roles.code IN ('approval_scoring', 'approver')
ON CONFLICT DO NOTHING;
