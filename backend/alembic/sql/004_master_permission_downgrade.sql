DELETE FROM role_permissions
WHERE permission_id IN (SELECT id FROM permissions WHERE code = 'master.manage');

DELETE FROM permissions WHERE code = 'master.manage';
