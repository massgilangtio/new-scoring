DELETE FROM role_permissions
WHERE permission_id IN (
    SELECT id FROM permissions WHERE code = 'scoring.view_score_details'
);

DELETE FROM permissions WHERE code = 'scoring.view_score_details';
