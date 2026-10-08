-- Migrasi 020: Hapus cabang di master cabang (branches & cfg_branch) yang selain dari respon /gateway/inqBranch
-- =========================================================================================================

-- 1. Pastikan cabang KANTOR PUSAT (001) tersedia
DO $$
DECLARE
    v_pusat_id INT;
BEGIN
    SELECT id INTO v_pusat_id FROM branches WHERE code = '001';
    IF v_pusat_id IS NULL THEN
        RAISE EXCEPTION 'Cabang 001 tidak ditemukan';
    END IF;

    -- 2. Alihkan foreign key references dari cabang non-gateway ke cabang KANTOR PUSAT (001)
    -- Tabel users
    UPDATE users
    SET branch_id = v_pusat_id
    WHERE branch_id IN (
        SELECT id FROM branches WHERE code NOT IN (
            SELECT branchid FROM cfg_branch
        ) OR code = 'PST'
    );

    -- Tabel debtors
    UPDATE debtors
    SET branch_id = v_pusat_id
    WHERE branch_id IN (
        SELECT id FROM branches WHERE code NOT IN (
            SELECT branchid FROM cfg_branch
        ) OR code = 'PST'
    );

    -- Tabel scoring_transactions
    UPDATE scoring_transactions
    SET branch_id = v_pusat_id
    WHERE branch_id IN (
        SELECT id FROM branches WHERE code NOT IN (
            SELECT branchid FROM cfg_branch
        ) OR code = 'PST'
    );

    -- Tabel credit_scorings
    UPDATE credit_scorings
    SET branch_id = v_pusat_id
    WHERE branch_id IN (
        SELECT id FROM branches WHERE code NOT IN (
            SELECT branchid FROM cfg_branch
        ) OR code = 'PST'
    );

    -- Tabel rescore_requests
    UPDATE rescore_requests
    SET branch_id = v_pusat_id
    WHERE branch_id IN (
        SELECT id FROM branches WHERE code NOT IN (
            SELECT branchid FROM cfg_branch
        ) OR code = 'PST'
    );

    -- Tabel audit_logs (disable & re-enable immutable trigger jika ada)
    PERFORM 1 FROM pg_trigger WHERE tgname = 'trg_audit_logs_immutable';
    IF FOUND THEN
        ALTER TABLE audit_logs DISABLE TRIGGER trg_audit_logs_immutable;
        UPDATE audit_logs
        SET actor_branch_id = v_pusat_id
        WHERE actor_branch_id IN (
            SELECT id FROM branches WHERE code NOT IN (
                SELECT branchid FROM cfg_branch
            ) OR code = 'PST'
        );
        ALTER TABLE audit_logs ENABLE TRIGGER trg_audit_logs_immutable;
    ELSE
        UPDATE audit_logs
        SET actor_branch_id = v_pusat_id
        WHERE actor_branch_id IN (
            SELECT id FROM branches WHERE code NOT IN (
                SELECT branchid FROM cfg_branch
            ) OR code = 'PST'
        );
    END IF;

END $$;
