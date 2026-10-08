INSERT INTO roles (code, name) VALUES
    ('reviewer_scoring', 'Reviewer Scoring'),
    ('approval_scoring', 'Approval Scoring')
ON CONFLICT (code) DO NOTHING;

-- Reviewer Scoring: mengajukan & mengisi scoring (sama seperti role reviewer)
INSERT INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
JOIN permissions ON permissions.code = 'scoring.submit'
WHERE roles.code = 'reviewer_scoring'
ON CONFLICT DO NOTHING;

-- Approval Scoring: memutuskan approval scoring
INSERT INTO role_permissions (role_id, permission_id)
SELECT roles.id, permissions.id
FROM roles
JOIN permissions ON permissions.code = 'scoring.approve'
WHERE roles.code = 'approval_scoring'
ON CONFLICT DO NOTHING;
