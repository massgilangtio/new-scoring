-- Flexible permission: who may view score metrics (batas skor, kelayakan, bobot, nilai, skor).
-- Reviewer does NOT get this by default; Admin + Approval do.
-- Assignable later via Access → Role.

INSERT INTO permissions (code, name) VALUES
    (
        'scoring.view_score_details',
        'Melihat rincian skor (batas skor, status kelayakan, bobot, nilai, skor)'
    )
ON CONFLICT (code) DO NOTHING;

-- Admin IT & Operator
INSERT INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
JOIN permissions ON permissions.code = 'scoring.view_score_details'
WHERE roles.code IN ('admin_it', 'operator')
ON CONFLICT DO NOTHING;

-- Approval / Approver (reviewer intentionally omitted)
INSERT INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
JOIN permissions ON permissions.code = 'scoring.view_score_details'
WHERE roles.code IN ('approval_scoring', 'approver')
ON CONFLICT DO NOTHING;
