DELETE FROM role_permissions
WHERE permission_id IN (SELECT id FROM permissions WHERE code = 'scoring.configure');

DELETE FROM permissions WHERE code = 'scoring.configure';
