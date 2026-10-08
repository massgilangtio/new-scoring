-- Migration 022: Add duplicate_reason and waiting_duplicate_approval status

ALTER TABLE scoring_transactions ADD COLUMN IF NOT EXISTS duplicate_reason TEXT;
ALTER TABLE credit_scorings ADD COLUMN IF NOT EXISTS duplicate_reason TEXT;

-- Drop and recreate ck_scoring_transactions_status to allow waiting_duplicate_approval
ALTER TABLE scoring_transactions DROP CONSTRAINT IF EXISTS ck_scoring_transactions_status;
ALTER TABLE scoring_transactions ADD CONSTRAINT ck_scoring_transactions_status 
CHECK (status = ANY (ARRAY['draft'::text, 'submitted'::text, 'waiting_for_approver_assignment'::text, 'waiting_duplicate_approval'::text, 'approved'::text, 'returned'::text, 'rejected'::text]));
