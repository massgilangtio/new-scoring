DROP TABLE IF EXISTS
    audit_logs,
    notifications,
    system_settings,
    rescore_requests,
    approver_assignments,
    approval_decisions,
    dynamic_field_snapshot_lines,
    scoring_snapshot_lines,
    scoring_snapshots,
    dynamic_field_input_choices,
    dynamic_field_inputs,
    scoring_inputs,
    scoring_transactions,
    dynamic_field_options,
    dynamic_fields,
    scoring_thresholds,
    scoring_parameter_options,
    scoring_parameters,
    scoring_versions,
    debtors,
    products,
    users,
    role_permissions,
    permissions,
    roles,
    branches
CASCADE;

DROP FUNCTION IF EXISTS guard_approval_decision();
DROP FUNCTION IF EXISTS guard_approver_assignment();
DROP FUNCTION IF EXISTS guard_scoring_input();
DROP FUNCTION IF EXISTS guard_scoring_transaction();
DROP FUNCTION IF EXISTS guard_scoring_configuration();
DROP FUNCTION IF EXISTS guard_scoring_version();
DROP FUNCTION IF EXISTS assert_active_same_branch_user(bigint, bigint);
DROP FUNCTION IF EXISTS prevent_row_mutation();
DROP FUNCTION IF EXISTS set_updated_at();
