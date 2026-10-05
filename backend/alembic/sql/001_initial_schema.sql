-- Initial schema for New Scoring Credit System.
-- Business source: docs/BUSINESS_RULES.md and the approved Phase 02 ERD.
-- Status transition order between submitted and waiting_for_approver_assignment
-- is intentionally not enforced here. That map is still unconfirmed.

CREATE TABLE branches (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    code text NOT NULL,
    name text NOT NULL,
    is_active boolean NOT NULL DEFAULT true,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT uq_branches_code UNIQUE (code)
);

CREATE TABLE roles (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    code text NOT NULL,
    name text NOT NULL,
    is_active boolean NOT NULL DEFAULT true,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT uq_roles_code UNIQUE (code)
);

CREATE TABLE permissions (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    code text NOT NULL,
    name text NOT NULL,
    is_active boolean NOT NULL DEFAULT true,
    CONSTRAINT uq_permissions_code UNIQUE (code)
);

CREATE TABLE role_permissions (
    role_id bigint NOT NULL,
    permission_id bigint NOT NULL,
    CONSTRAINT pk_role_permissions PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_role_permissions_role_id_roles
        FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission_id_permissions
        FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE
);

CREATE TABLE users (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    username text NOT NULL,
    password_hash text NOT NULL,
    full_name text NOT NULL,
    role_id bigint NOT NULL,
    branch_id bigint NOT NULL,
    is_active boolean NOT NULL DEFAULT true,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT uq_users_username UNIQUE (username),
    CONSTRAINT fk_users_role_id_roles
        FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE RESTRICT,
    CONSTRAINT fk_users_branch_id_branches
        FOREIGN KEY (branch_id) REFERENCES branches (id) ON DELETE RESTRICT
);

CREATE INDEX ix_users_role_id ON users (role_id);
CREATE INDEX ix_users_branch_id ON users (branch_id);

CREATE TABLE products (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    code text NOT NULL,
    name text NOT NULL,
    is_active boolean NOT NULL DEFAULT true,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT uq_products_code UNIQUE (code)
);

CREATE TABLE debtors (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nik varchar(16) NOT NULL,
    full_name text NOT NULL,
    branch_id bigint NOT NULL,
    is_active boolean NOT NULL DEFAULT true,
    created_by bigint NOT NULL,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT uq_debtors_nik UNIQUE (nik),
    CONSTRAINT ck_debtors_nik_digits CHECK (nik ~ '^[0-9]{16}$'),
    CONSTRAINT fk_debtors_branch_id_branches
        FOREIGN KEY (branch_id) REFERENCES branches (id) ON DELETE RESTRICT,
    CONSTRAINT fk_debtors_created_by_users
        FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
);

CREATE INDEX ix_debtors_branch_id ON debtors (branch_id);

CREATE TABLE scoring_versions (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    product_id bigint NOT NULL,
    version_no integer NOT NULL,
    status text NOT NULL DEFAULT 'draft',
    activated_at timestamptz,
    created_by bigint NOT NULL,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT uq_scoring_versions_product_version UNIQUE (product_id, version_no),
    CONSTRAINT ck_scoring_versions_status CHECK (status IN ('draft', 'active', 'inactive')),
    CONSTRAINT ck_scoring_versions_version_no CHECK (version_no >= 1),
    CONSTRAINT fk_scoring_versions_product_id_products
        FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT,
    CONSTRAINT fk_scoring_versions_created_by_users
        FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
);

CREATE UNIQUE INDEX uq_scoring_versions_one_active
    ON scoring_versions (product_id)
    WHERE status = 'active';

CREATE TABLE scoring_parameters (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    scoring_version_id bigint NOT NULL,
    name text NOT NULL,
    weight numeric(8, 4) NOT NULL,
    display_order integer NOT NULL DEFAULT 0,
    CONSTRAINT ck_scoring_parameters_weight CHECK (weight >= 0),
    CONSTRAINT fk_scoring_parameters_scoring_version_id
        FOREIGN KEY (scoring_version_id) REFERENCES scoring_versions (id) ON DELETE RESTRICT
);

CREATE INDEX ix_scoring_parameters_version_id ON scoring_parameters (scoring_version_id);

CREATE TABLE scoring_parameter_options (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    scoring_parameter_id bigint NOT NULL,
    label text NOT NULL,
    value numeric(14, 4) NOT NULL,
    display_order integer NOT NULL DEFAULT 0,
    CONSTRAINT fk_scoring_parameter_options_parameter_id
        FOREIGN KEY (scoring_parameter_id) REFERENCES scoring_parameters (id) ON DELETE RESTRICT
);

CREATE INDEX ix_scoring_parameter_options_parameter_id
    ON scoring_parameter_options (scoring_parameter_id);

CREATE TABLE scoring_thresholds (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    scoring_version_id bigint NOT NULL,
    min_score numeric(18, 4) NOT NULL,
    max_score numeric(18, 4),
    result_label text NOT NULL,
    display_order integer NOT NULL DEFAULT 0,
    CONSTRAINT ck_scoring_thresholds_range CHECK (max_score IS NULL OR min_score <= max_score),
    CONSTRAINT fk_scoring_thresholds_scoring_version_id
        FOREIGN KEY (scoring_version_id) REFERENCES scoring_versions (id) ON DELETE RESTRICT
);

CREATE INDEX ix_scoring_thresholds_version_id ON scoring_thresholds (scoring_version_id);

CREATE TABLE dynamic_fields (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    scoring_version_id bigint NOT NULL,
    field_key text NOT NULL,
    label text NOT NULL,
    field_type text NOT NULL,
    is_required boolean NOT NULL DEFAULT false,
    is_active boolean NOT NULL DEFAULT true,
    display_order integer NOT NULL DEFAULT 0,
    CONSTRAINT uq_dynamic_fields_version_key UNIQUE (scoring_version_id, field_key),
    CONSTRAINT ck_dynamic_fields_type CHECK (
        field_type IN ('text', 'number', 'date', 'dropdown', 'textarea', 'radio', 'checkbox')
    ),
    CONSTRAINT fk_dynamic_fields_scoring_version_id
        FOREIGN KEY (scoring_version_id) REFERENCES scoring_versions (id) ON DELETE RESTRICT
);

CREATE INDEX ix_dynamic_fields_version_id ON dynamic_fields (scoring_version_id);

CREATE TABLE dynamic_field_options (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    dynamic_field_id bigint NOT NULL,
    label text NOT NULL,
    value text NOT NULL,
    display_order integer NOT NULL DEFAULT 0,
    CONSTRAINT fk_dynamic_field_options_field_id
        FOREIGN KEY (dynamic_field_id) REFERENCES dynamic_fields (id) ON DELETE RESTRICT
);

CREATE INDEX ix_dynamic_field_options_field_id ON dynamic_field_options (dynamic_field_id);

CREATE TABLE scoring_transactions (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    transaction_no text NOT NULL,
    debtor_id bigint NOT NULL,
    product_id bigint NOT NULL,
    scoring_version_id bigint NOT NULL,
    branch_id bigint NOT NULL,
    status text NOT NULL DEFAULT 'draft',
    created_by bigint NOT NULL,
    assigned_approver_id bigint,
    duplicated_from_id bigint,
    current_revision integer NOT NULL DEFAULT 0,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT uq_scoring_transactions_transaction_no UNIQUE (transaction_no),
    CONSTRAINT ck_scoring_transactions_status CHECK (
        status IN (
            'draft',
            'submitted',
            'waiting_for_approver_assignment',
            'approved',
            'returned',
            'rejected'
        )
    ),
    CONSTRAINT ck_scoring_transactions_revision CHECK (current_revision >= 0),
    CONSTRAINT fk_scoring_transactions_debtor_id
        FOREIGN KEY (debtor_id) REFERENCES debtors (id) ON DELETE RESTRICT,
    CONSTRAINT fk_scoring_transactions_product_id
        FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT,
    CONSTRAINT fk_scoring_transactions_scoring_version_id
        FOREIGN KEY (scoring_version_id) REFERENCES scoring_versions (id) ON DELETE RESTRICT,
    CONSTRAINT fk_scoring_transactions_branch_id
        FOREIGN KEY (branch_id) REFERENCES branches (id) ON DELETE RESTRICT,
    CONSTRAINT fk_scoring_transactions_created_by
        FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fk_scoring_transactions_assigned_approver_id
        FOREIGN KEY (assigned_approver_id) REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fk_scoring_transactions_duplicated_from_id
        FOREIGN KEY (duplicated_from_id) REFERENCES scoring_transactions (id) ON DELETE RESTRICT
);

CREATE INDEX ix_scoring_transactions_debtor_product
    ON scoring_transactions (debtor_id, product_id);
CREATE INDEX ix_scoring_transactions_branch_status
    ON scoring_transactions (branch_id, status);

CREATE TABLE scoring_inputs (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    transaction_id bigint NOT NULL,
    scoring_parameter_id bigint NOT NULL,
    scoring_parameter_option_id bigint NOT NULL,
    CONSTRAINT uq_scoring_inputs_transaction_parameter UNIQUE (transaction_id, scoring_parameter_id),
    CONSTRAINT fk_scoring_inputs_transaction_id
        FOREIGN KEY (transaction_id) REFERENCES scoring_transactions (id) ON DELETE CASCADE,
    CONSTRAINT fk_scoring_inputs_parameter_id
        FOREIGN KEY (scoring_parameter_id) REFERENCES scoring_parameters (id) ON DELETE RESTRICT,
    CONSTRAINT fk_scoring_inputs_option_id
        FOREIGN KEY (scoring_parameter_option_id) REFERENCES scoring_parameter_options (id) ON DELETE RESTRICT
);

CREATE TABLE dynamic_field_inputs (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    transaction_id bigint NOT NULL,
    dynamic_field_id bigint NOT NULL,
    value_text text,
    CONSTRAINT uq_dynamic_field_inputs_transaction_field UNIQUE (transaction_id, dynamic_field_id),
    CONSTRAINT fk_dynamic_field_inputs_transaction_id
        FOREIGN KEY (transaction_id) REFERENCES scoring_transactions (id) ON DELETE CASCADE,
    CONSTRAINT fk_dynamic_field_inputs_field_id
        FOREIGN KEY (dynamic_field_id) REFERENCES dynamic_fields (id) ON DELETE RESTRICT
);

CREATE TABLE dynamic_field_input_choices (
    dynamic_field_input_id bigint NOT NULL,
    dynamic_field_option_id bigint NOT NULL,
    CONSTRAINT pk_dynamic_field_input_choices PRIMARY KEY (dynamic_field_input_id, dynamic_field_option_id),
    CONSTRAINT fk_dynamic_field_input_choices_input_id
        FOREIGN KEY (dynamic_field_input_id) REFERENCES dynamic_field_inputs (id) ON DELETE CASCADE,
    CONSTRAINT fk_dynamic_field_input_choices_option_id
        FOREIGN KEY (dynamic_field_option_id) REFERENCES dynamic_field_options (id) ON DELETE RESTRICT
);

CREATE TABLE scoring_snapshots (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    transaction_id bigint NOT NULL,
    revision_no integer NOT NULL,
    scoring_version_id bigint NOT NULL,
    total_score numeric(18, 4) NOT NULL,
    threshold_id bigint,
    result_label text NOT NULL,
    threshold_min numeric(18, 4) NOT NULL,
    threshold_max numeric(18, 4),
    calculated_at timestamptz NOT NULL DEFAULT now(),
    calculated_by bigint NOT NULL,
    CONSTRAINT uq_scoring_snapshots_transaction_revision UNIQUE (transaction_id, revision_no),
    CONSTRAINT ck_scoring_snapshots_revision CHECK (revision_no >= 1),
    CONSTRAINT fk_scoring_snapshots_transaction_id
        FOREIGN KEY (transaction_id) REFERENCES scoring_transactions (id) ON DELETE RESTRICT,
    CONSTRAINT fk_scoring_snapshots_scoring_version_id
        FOREIGN KEY (scoring_version_id) REFERENCES scoring_versions (id) ON DELETE RESTRICT,
    CONSTRAINT fk_scoring_snapshots_threshold_id
        FOREIGN KEY (threshold_id) REFERENCES scoring_thresholds (id) ON DELETE RESTRICT,
    CONSTRAINT fk_scoring_snapshots_calculated_by
        FOREIGN KEY (calculated_by) REFERENCES users (id) ON DELETE RESTRICT
);

CREATE TABLE scoring_snapshot_lines (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    snapshot_id bigint NOT NULL,
    parameter_name text NOT NULL,
    option_label text NOT NULL,
    value numeric(14, 4) NOT NULL,
    weight numeric(8, 4) NOT NULL,
    line_score numeric(18, 4) NOT NULL,
    display_order integer NOT NULL DEFAULT 0,
    CONSTRAINT fk_scoring_snapshot_lines_snapshot_id
        FOREIGN KEY (snapshot_id) REFERENCES scoring_snapshots (id) ON DELETE RESTRICT
);

CREATE INDEX ix_scoring_snapshot_lines_snapshot_id ON scoring_snapshot_lines (snapshot_id);

CREATE TABLE dynamic_field_snapshot_lines (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    snapshot_id bigint NOT NULL,
    field_label text NOT NULL,
    field_type text NOT NULL,
    value_text text,
    display_order integer NOT NULL DEFAULT 0,
    CONSTRAINT fk_dynamic_field_snapshot_lines_snapshot_id
        FOREIGN KEY (snapshot_id) REFERENCES scoring_snapshots (id) ON DELETE RESTRICT
);

CREATE INDEX ix_dynamic_field_snapshot_lines_snapshot_id
    ON dynamic_field_snapshot_lines (snapshot_id);

CREATE TABLE approval_decisions (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    transaction_id bigint NOT NULL,
    revision_no integer NOT NULL,
    approver_id bigint NOT NULL,
    decision text NOT NULL,
    note text NOT NULL,
    decided_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT uq_approval_decisions_transaction_revision UNIQUE (transaction_id, revision_no),
    CONSTRAINT ck_approval_decisions_revision CHECK (revision_no >= 1),
    CONSTRAINT ck_approval_decisions_note CHECK (length(btrim(note)) > 0),
    CONSTRAINT ck_approval_decisions_decision CHECK (decision IN ('approved', 'rejected', 'returned')),
    CONSTRAINT fk_approval_decisions_transaction_id
        FOREIGN KEY (transaction_id) REFERENCES scoring_transactions (id) ON DELETE RESTRICT,
    CONSTRAINT fk_approval_decisions_approver_id
        FOREIGN KEY (approver_id) REFERENCES users (id) ON DELETE RESTRICT
);

CREATE TABLE approver_assignments (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    transaction_id bigint NOT NULL,
    revision_no integer NOT NULL,
    approver_id bigint NOT NULL,
    reason text,
    assigned_by bigint NOT NULL,
    assigned_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT ck_approver_assignments_revision CHECK (revision_no >= 1),
    CONSTRAINT fk_approver_assignments_transaction_id
        FOREIGN KEY (transaction_id) REFERENCES scoring_transactions (id) ON DELETE RESTRICT,
    CONSTRAINT fk_approver_assignments_approver_id
        FOREIGN KEY (approver_id) REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fk_approver_assignments_assigned_by
        FOREIGN KEY (assigned_by) REFERENCES users (id) ON DELETE RESTRICT
);

CREATE INDEX ix_approver_assignments_transaction_revision
    ON approver_assignments (transaction_id, revision_no);

CREATE TABLE rescore_requests (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    debtor_id bigint NOT NULL,
    product_id bigint NOT NULL,
    branch_id bigint NOT NULL,
    reason text NOT NULL,
    status text NOT NULL DEFAULT 'waiting_approval',
    requested_by bigint NOT NULL,
    requested_at timestamptz NOT NULL DEFAULT now(),
    approved_by bigint,
    approved_at timestamptz,
    consumed_at timestamptz,
    consumed_transaction_id bigint,
    CONSTRAINT ck_rescore_requests_reason CHECK (length(btrim(reason)) > 0),
    CONSTRAINT ck_rescore_requests_status CHECK (status IN ('waiting_approval', 'approved', 'consumed')),
    CONSTRAINT uq_rescore_requests_consumed_transaction UNIQUE (consumed_transaction_id),
    CONSTRAINT fk_rescore_requests_debtor_id
        FOREIGN KEY (debtor_id) REFERENCES debtors (id) ON DELETE RESTRICT,
    CONSTRAINT fk_rescore_requests_product_id
        FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT,
    CONSTRAINT fk_rescore_requests_branch_id
        FOREIGN KEY (branch_id) REFERENCES branches (id) ON DELETE RESTRICT,
    CONSTRAINT fk_rescore_requests_requested_by
        FOREIGN KEY (requested_by) REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fk_rescore_requests_approved_by
        FOREIGN KEY (approved_by) REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fk_rescore_requests_consumed_transaction_id
        FOREIGN KEY (consumed_transaction_id) REFERENCES scoring_transactions (id) ON DELETE RESTRICT
);

CREATE INDEX ix_rescore_requests_debtor_product_status
    ON rescore_requests (debtor_id, product_id, status);

CREATE TABLE system_settings (
    setting_key text PRIMARY KEY,
    setting_value text NOT NULL,
    updated_by bigint,
    updated_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT fk_system_settings_updated_by
        FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE RESTRICT
);

CREATE TABLE notifications (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    recipient_user_id bigint NOT NULL,
    event_type text NOT NULL,
    channel text NOT NULL DEFAULT 'in_app',
    title text NOT NULL,
    body text NOT NULL,
    object_type text NOT NULL,
    object_id bigint NOT NULL,
    read_at timestamptz,
    created_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT ck_notifications_event_type CHECK (
        event_type IN ('sent_to_approver', 'approved', 'returned', 'rejected')
    ),
    CONSTRAINT ck_notifications_channel CHECK (channel = 'in_app'),
    CONSTRAINT fk_notifications_recipient_user_id
        FOREIGN KEY (recipient_user_id) REFERENCES users (id) ON DELETE RESTRICT
);

CREATE INDEX ix_notifications_recipient_user_id ON notifications (recipient_user_id);

CREATE TABLE audit_logs (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    actor_user_id bigint NOT NULL,
    actor_role_id bigint NOT NULL,
    actor_role_name text NOT NULL,
    actor_branch_id bigint NOT NULL,
    occurred_at timestamptz NOT NULL DEFAULT now(),
    action text NOT NULL,
    object_type text NOT NULL,
    object_id text NOT NULL,
    before_data jsonb,
    after_data jsonb,
    reason text,
    CONSTRAINT fk_audit_logs_actor_user_id
        FOREIGN KEY (actor_user_id) REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fk_audit_logs_actor_role_id
        FOREIGN KEY (actor_role_id) REFERENCES roles (id) ON DELETE RESTRICT,
    CONSTRAINT fk_audit_logs_actor_branch_id
        FOREIGN KEY (actor_branch_id) REFERENCES branches (id) ON DELETE RESTRICT
);

CREATE INDEX ix_audit_logs_object ON audit_logs (object_type, object_id);
CREATE INDEX ix_audit_logs_occurred_at ON audit_logs (occurred_at);

CREATE FUNCTION set_updated_at()
RETURNS trigger
LANGUAGE plpgsql
AS $fn$
BEGIN
    NEW.updated_at = now();
    RETURN NEW;
END;
$fn$;

CREATE FUNCTION prevent_row_mutation()
RETURNS trigger
LANGUAGE plpgsql
AS $fn$
BEGIN
    RAISE EXCEPTION '%.% cannot be updated or deleted', TG_TABLE_SCHEMA, TG_TABLE_NAME
        USING ERRCODE = 'restrict_violation';
END;
$fn$;

CREATE FUNCTION assert_active_same_branch_user(p_user_id bigint, p_branch_id bigint)
RETURNS void
LANGUAGE plpgsql
AS $fn$
DECLARE
    user_is_active boolean;
    user_branch_id bigint;
BEGIN
    SELECT is_active, branch_id
      INTO user_is_active, user_branch_id
      FROM users
     WHERE id = p_user_id;

    IF NOT FOUND OR user_is_active IS NOT TRUE OR user_branch_id IS DISTINCT FROM p_branch_id THEN
        RAISE EXCEPTION 'user % must be active and belong to branch %', p_user_id, p_branch_id
            USING ERRCODE = 'restrict_violation';
    END IF;
END;
$fn$;

CREATE FUNCTION guard_scoring_version()
RETURNS trigger
LANGUAGE plpgsql
AS $fn$
DECLARE
    total_weight numeric(12, 4);
    missing_options integer;
BEGIN
    IF OLD.status IS DISTINCT FROM 'draft' AND NEW.status = 'draft' THEN
        RAISE EXCEPTION 'scoring version % cannot return to draft', OLD.id
            USING ERRCODE = 'restrict_violation';
    END IF;

    IF OLD.status IS DISTINCT FROM 'draft'
       AND (NEW.product_id IS DISTINCT FROM OLD.product_id OR NEW.version_no IS DISTINCT FROM OLD.version_no) THEN
        RAISE EXCEPTION 'product and version number are locked after draft'
            USING ERRCODE = 'restrict_violation';
    END IF;

    IF NEW.status = 'active' AND OLD.status IS DISTINCT FROM 'active' THEN
        SELECT COALESCE(SUM(weight), 0)
          INTO total_weight
          FROM scoring_parameters
         WHERE scoring_version_id = NEW.id;

        IF total_weight <> 100 THEN
            RAISE EXCEPTION 'total weight must equal 100 before activation, got %', total_weight
                USING ERRCODE = 'check_violation';
        END IF;

        SELECT COUNT(*)
          INTO missing_options
          FROM scoring_parameters AS parameter
         WHERE parameter.scoring_version_id = NEW.id
           AND NOT EXISTS (
                SELECT 1
                  FROM scoring_parameter_options AS option
                 WHERE option.scoring_parameter_id = parameter.id
           );

        IF missing_options > 0 THEN
            RAISE EXCEPTION 'every scoring parameter needs at least one option before activation'
                USING ERRCODE = 'check_violation';
        END IF;

        NEW.activated_at = COALESCE(NEW.activated_at, now());
    END IF;

    RETURN NEW;
END;
$fn$;

CREATE FUNCTION guard_scoring_configuration()
RETURNS trigger
LANGUAGE plpgsql
AS $fn$
DECLARE
    version_id bigint;
    version_status text;
BEGIN
    IF TG_TABLE_NAME = 'scoring_parameters' OR TG_TABLE_NAME = 'scoring_thresholds' OR TG_TABLE_NAME = 'dynamic_fields' THEN
        version_id = COALESCE(NEW.scoring_version_id, OLD.scoring_version_id);
    ELSIF TG_TABLE_NAME = 'scoring_parameter_options' THEN
        SELECT scoring_version_id
          INTO version_id
          FROM scoring_parameters
         WHERE id = COALESCE(NEW.scoring_parameter_id, OLD.scoring_parameter_id);
    ELSIF TG_TABLE_NAME = 'dynamic_field_options' THEN
        SELECT scoring_version_id
          INTO version_id
          FROM dynamic_fields
         WHERE id = COALESCE(NEW.dynamic_field_id, OLD.dynamic_field_id);
    END IF;

    SELECT status
      INTO version_status
      FROM scoring_versions
     WHERE id = version_id;

    IF version_status IS DISTINCT FROM 'draft' THEN
        RAISE EXCEPTION 'scoring configuration is locked because version % is %', version_id, version_status
            USING ERRCODE = 'restrict_violation';
    END IF;

    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;
    RETURN NEW;
END;
$fn$;

CREATE FUNCTION guard_scoring_transaction()
RETURNS trigger
LANGUAGE plpgsql
AS $fn$
DECLARE
    creator_is_active boolean;
    creator_branch_id bigint;
BEGIN
    IF TG_OP = 'DELETE' THEN
        IF OLD.status IN ('approved', 'rejected') THEN
            RAISE EXCEPTION 'approved or rejected transactions cannot be deleted'
                USING ERRCODE = 'restrict_violation';
        END IF;
        RETURN OLD;
    END IF;

    IF TG_OP = 'UPDATE' AND OLD.status IN ('approved', 'rejected') THEN
        RAISE EXCEPTION 'approved or rejected transactions cannot be changed'
            USING ERRCODE = 'restrict_violation';
    END IF;

    IF TG_OP = 'UPDATE' AND NEW.branch_id IS DISTINCT FROM OLD.branch_id THEN
        RAISE EXCEPTION 'transaction branch is immutable'
            USING ERRCODE = 'restrict_violation';
    END IF;

    IF TG_OP = 'UPDATE' AND NEW.created_by IS DISTINCT FROM OLD.created_by THEN
        RAISE EXCEPTION 'transaction creator is immutable'
            USING ERRCODE = 'restrict_violation';
    END IF;

    IF TG_OP = 'UPDATE'
       AND NEW.scoring_version_id IS DISTINCT FROM OLD.scoring_version_id
       AND (
            OLD.status <> 'draft'
            OR EXISTS (
                SELECT 1
                  FROM scoring_snapshots
                 WHERE transaction_id = OLD.id
            )
       ) THEN
        RAISE EXCEPTION 'scoring version on this transaction is immutable'
            USING ERRCODE = 'restrict_violation';
    END IF;

    IF TG_OP = 'INSERT' THEN
        SELECT is_active, branch_id
          INTO creator_is_active, creator_branch_id
          FROM users
         WHERE id = NEW.created_by;

        IF creator_is_active IS NOT TRUE OR creator_branch_id IS DISTINCT FROM NEW.branch_id THEN
            RAISE EXCEPTION 'transaction branch must match the active reviewer branch'
                USING ERRCODE = 'restrict_violation';
        END IF;
    END IF;

    IF NEW.assigned_approver_id IS NOT NULL
       AND (
            TG_OP = 'INSERT'
            OR NEW.assigned_approver_id IS DISTINCT FROM OLD.assigned_approver_id
            OR NEW.branch_id IS DISTINCT FROM OLD.branch_id
       ) THEN
        PERFORM assert_active_same_branch_user(NEW.assigned_approver_id, NEW.branch_id);
    END IF;

    RETURN NEW;
END;
$fn$;

CREATE FUNCTION guard_scoring_input()
RETURNS trigger
LANGUAGE plpgsql
AS $fn$
DECLARE
    transaction_status text;
    target_transaction_id bigint;
BEGIN
    IF TG_TABLE_NAME = 'dynamic_field_input_choices' THEN
        SELECT transaction_id
          INTO target_transaction_id
          FROM dynamic_field_inputs
         WHERE id = COALESCE(NEW.dynamic_field_input_id, OLD.dynamic_field_input_id);
    ELSE
        target_transaction_id = COALESCE(NEW.transaction_id, OLD.transaction_id);
    END IF;

    SELECT status
      INTO transaction_status
      FROM scoring_transactions
     WHERE id = target_transaction_id;

    IF transaction_status IS DISTINCT FROM 'draft' AND transaction_status IS DISTINCT FROM 'returned' THEN
        RAISE EXCEPTION 'scoring input is locked while transaction status is %', transaction_status
            USING ERRCODE = 'restrict_violation';
    END IF;

    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;
    RETURN NEW;
END;
$fn$;

CREATE FUNCTION guard_approver_assignment()
RETURNS trigger
LANGUAGE plpgsql
AS $fn$
DECLARE
    transaction_branch_id bigint;
    prior_assignment_id bigint;
BEGIN
    SELECT branch_id
      INTO transaction_branch_id
      FROM scoring_transactions
     WHERE id = NEW.transaction_id;

    PERFORM assert_active_same_branch_user(NEW.approver_id, transaction_branch_id);

    SELECT id
      INTO prior_assignment_id
      FROM approver_assignments
     WHERE transaction_id = NEW.transaction_id
       AND revision_no = NEW.revision_no
     LIMIT 1;

    IF prior_assignment_id IS NOT NULL AND length(btrim(COALESCE(NEW.reason, ''))) = 0 THEN
        RAISE EXCEPTION 'reassignment before a decision requires a reason'
            USING ERRCODE = 'check_violation';
    END IF;

    RETURN NEW;
END;
$fn$;

CREATE FUNCTION guard_approval_decision()
RETURNS trigger
LANGUAGE plpgsql
AS $fn$
DECLARE
    transaction_branch_id bigint;
BEGIN
    SELECT branch_id
      INTO transaction_branch_id
      FROM scoring_transactions
     WHERE id = NEW.transaction_id;

    PERFORM assert_active_same_branch_user(NEW.approver_id, transaction_branch_id);
    RETURN NEW;
END;
$fn$;

CREATE TRIGGER trg_branches_set_updated_at
    BEFORE UPDATE ON branches
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_roles_set_updated_at
    BEFORE UPDATE ON roles
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_users_set_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_products_set_updated_at
    BEFORE UPDATE ON products
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_debtors_set_updated_at
    BEFORE UPDATE ON debtors
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_scoring_versions_set_updated_at
    BEFORE UPDATE ON scoring_versions
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_scoring_transactions_set_updated_at
    BEFORE UPDATE ON scoring_transactions
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_system_settings_set_updated_at
    BEFORE UPDATE ON system_settings
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER trg_scoring_versions_guard
    BEFORE UPDATE ON scoring_versions
    FOR EACH ROW EXECUTE FUNCTION guard_scoring_version();

CREATE TRIGGER trg_scoring_parameters_lock
    BEFORE INSERT OR UPDATE OR DELETE ON scoring_parameters
    FOR EACH ROW EXECUTE FUNCTION guard_scoring_configuration();
CREATE TRIGGER trg_scoring_parameter_options_lock
    BEFORE INSERT OR UPDATE OR DELETE ON scoring_parameter_options
    FOR EACH ROW EXECUTE FUNCTION guard_scoring_configuration();
CREATE TRIGGER trg_scoring_thresholds_lock
    BEFORE INSERT OR UPDATE OR DELETE ON scoring_thresholds
    FOR EACH ROW EXECUTE FUNCTION guard_scoring_configuration();
CREATE TRIGGER trg_dynamic_fields_lock
    BEFORE INSERT OR UPDATE OR DELETE ON dynamic_fields
    FOR EACH ROW EXECUTE FUNCTION guard_scoring_configuration();
CREATE TRIGGER trg_dynamic_field_options_lock
    BEFORE INSERT OR UPDATE OR DELETE ON dynamic_field_options
    FOR EACH ROW EXECUTE FUNCTION guard_scoring_configuration();

CREATE TRIGGER trg_scoring_transactions_guard
    BEFORE INSERT OR UPDATE OR DELETE ON scoring_transactions
    FOR EACH ROW EXECUTE FUNCTION guard_scoring_transaction();

CREATE TRIGGER trg_scoring_inputs_lock
    BEFORE INSERT OR UPDATE OR DELETE ON scoring_inputs
    FOR EACH ROW EXECUTE FUNCTION guard_scoring_input();
CREATE TRIGGER trg_dynamic_field_inputs_lock
    BEFORE INSERT OR UPDATE OR DELETE ON dynamic_field_inputs
    FOR EACH ROW EXECUTE FUNCTION guard_scoring_input();
CREATE TRIGGER trg_dynamic_field_input_choices_lock
    BEFORE INSERT OR UPDATE OR DELETE ON dynamic_field_input_choices
    FOR EACH ROW EXECUTE FUNCTION guard_scoring_input();

CREATE TRIGGER trg_approver_assignments_guard
    BEFORE INSERT ON approver_assignments
    FOR EACH ROW EXECUTE FUNCTION guard_approver_assignment();
CREATE TRIGGER trg_approver_assignments_immutable
    BEFORE UPDATE OR DELETE ON approver_assignments
    FOR EACH ROW EXECUTE FUNCTION prevent_row_mutation();

CREATE TRIGGER trg_approval_decisions_guard
    BEFORE INSERT ON approval_decisions
    FOR EACH ROW EXECUTE FUNCTION guard_approval_decision();
CREATE TRIGGER trg_approval_decisions_immutable
    BEFORE UPDATE OR DELETE ON approval_decisions
    FOR EACH ROW EXECUTE FUNCTION prevent_row_mutation();

CREATE TRIGGER trg_scoring_snapshots_immutable
    BEFORE UPDATE OR DELETE ON scoring_snapshots
    FOR EACH ROW EXECUTE FUNCTION prevent_row_mutation();
CREATE TRIGGER trg_scoring_snapshot_lines_immutable
    BEFORE UPDATE OR DELETE ON scoring_snapshot_lines
    FOR EACH ROW EXECUTE FUNCTION prevent_row_mutation();
CREATE TRIGGER trg_dynamic_field_snapshot_lines_immutable
    BEFORE UPDATE OR DELETE ON dynamic_field_snapshot_lines
    FOR EACH ROW EXECUTE FUNCTION prevent_row_mutation();
CREATE TRIGGER trg_audit_logs_immutable
    BEFORE UPDATE OR DELETE ON audit_logs
    FOR EACH ROW EXECUTE FUNCTION prevent_row_mutation();

-- Global reading of "jika setting aktif". Scope per product is still unconfirmed.
INSERT INTO system_settings (setting_key, setting_value)
VALUES ('duplicate_enabled', 'false');

INSERT INTO system_settings (setting_key, setting_value)
VALUES ('default_passing_score', '350.00');

COMMENT ON TABLE scoring_snapshots IS
    'Official score and result written by the scoring engine. Rows are insert-only.';
COMMENT ON TABLE audit_logs IS
    'Append-only audit. Stores actor, role, branch, before/after, and optional reason.';
COMMENT ON COLUMN users.role_id IS
    'Exactly one role. Authorization checks permissions, not the role name.';
COMMENT ON COLUMN users.branch_id IS
    'Exactly one branch. Viewing every branch is a permission, not a second branch.';
COMMENT ON COLUMN dynamic_fields.scoring_version_id IS
    'Dynamic fields belong to a scoring version, so products and versions can differ.';
