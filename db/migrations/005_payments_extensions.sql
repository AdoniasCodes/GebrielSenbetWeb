-- 005_payments_extensions.sql
-- Add default tuition per term + paid_amount on payments so partial payments are explicit.

ALTER TABLE academic_terms
  ADD COLUMN default_tuition DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER end_date;

ALTER TABLE payments
  ADD COLUMN paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER amount;

CREATE INDEX idx_payments_term ON payments(term_id);
