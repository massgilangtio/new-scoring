DROP TABLE IF EXISTS system_logs CASCADE;
DELETE FROM role_permissions WHERE permission_id IN (SELECT id FROM permissions WHERE code = 'system.logs');
DELETE FROM permissions WHERE code = 'system.logs';
