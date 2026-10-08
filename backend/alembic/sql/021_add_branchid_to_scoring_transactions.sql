-- Migration 021: Add branchid column to scoring_transactions and credit_scorings
ALTER TABLE scoring_transactions ADD COLUMN IF NOT EXISTS branchid VARCHAR(50);
ALTER TABLE credit_scorings ADD COLUMN IF NOT EXISTS branchid VARCHAR(50);

-- Backfill from branches table
UPDATE scoring_transactions st 
SET branchid = b.code 
FROM branches b 
WHERE st.branch_id = b.id AND st.branchid IS NULL;

UPDATE credit_scorings cs 
SET branchid = b.code 
FROM branches b 
WHERE cs.branch_id = b.id AND cs.branchid IS NULL;
