-- 007_notifications_public_flag.sql
-- Add a flag so admins can mark an announcement as visible on the public landing page.

ALTER TABLE notifications
  ADD COLUMN is_public TINYINT(1) NOT NULL DEFAULT 0 AFTER message;

CREATE INDEX idx_notifications_public ON notifications(is_public, is_archived, created_at);
