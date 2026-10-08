DELETE FROM role_permissions
WHERE permission_id IN (
    SELECT id FROM permissions
    WHERE code IN (
        'dashboard.view',
        'master.debtors', 'master.products', 'master.branches',
        'scoring.parameters', 'scoring.mapping',
        'report.scoring', 'report.debtors', 'report.products', 'report.changes',
        'audit.view',
        'access.users', 'access.roles', 'access.permissions'
    )
);

DELETE FROM permissions
WHERE code IN (
    'dashboard.view',
    'master.debtors', 'master.products', 'master.branches',
    'scoring.parameters', 'scoring.mapping',
    'report.scoring', 'report.debtors', 'report.products', 'report.changes',
    'audit.view',
    'access.users', 'access.roles', 'access.permissions'
);
