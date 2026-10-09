-- 023_system_logs.sql
-- Tabel penyimpan riwayat log aktivitas request API, respon sukses, error (4xx), dan fatal bug / exception (5xx)
CREATE TABLE IF NOT EXISTS system_logs (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(),
    level VARCHAR(20) NOT NULL DEFAULT 'INFO',
    status_code INTEGER NOT NULL,
    method VARCHAR(10) NOT NULL,
    path VARCHAR(500) NOT NULL,
    query_params TEXT,
    client_ip VARCHAR(50),
    user_agent TEXT,
    user_id BIGINT,
    username VARCHAR(100),
    execution_time_ms NUMERIC(10, 2) NOT NULL DEFAULT 0.0,
    request_body JSONB,
    response_body JSONB,
    error_message TEXT,
    traceback TEXT
);

CREATE INDEX IF NOT EXISTS ix_system_logs_created_at ON system_logs (created_at DESC);
CREATE INDEX IF NOT EXISTS ix_system_logs_level ON system_logs (level);
CREATE INDEX IF NOT EXISTS ix_system_logs_status_code ON system_logs (status_code);
CREATE INDEX IF NOT EXISTS ix_system_logs_path ON system_logs (path);
CREATE INDEX IF NOT EXISTS ix_system_logs_username ON system_logs (username);

-- Tambahkan permission system.logs
INSERT INTO permissions (code, name) VALUES
    ('system.logs', 'Melihat Log Sistem, Error & Bug')
ON CONFLICT (code) DO NOTHING;

-- Berikan ke SUPERADMIN
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.code = 'SUPERADMIN' AND p.code = 'system.logs'
ON CONFLICT DO NOTHING;

-- Berikan ke Admin IT dan Operator jika ada
INSERT INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
JOIN permissions ON permissions.code = 'system.logs'
WHERE roles.code IN ('admin_it', 'operator')
ON CONFLICT DO NOTHING;
