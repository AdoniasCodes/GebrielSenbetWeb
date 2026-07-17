-- 022_notification_reads.sql
-- Phase 2.1 (SYSTEM_AUDIT_AND_BLUEPRINT.md §9): per-user read state moves out of
-- the notifications.read_by JSON array into a proper join table. Fixes the
-- lost-update race in the old read-modify-write (api/teacher/notifications.php)
-- and gives every portal — not just teachers — a queryable unread model.
-- read_by is left on notifications for rollback safety; Phase 3's column-debris
-- pass drops it. Idempotent: safe to re-run.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS notification_reads (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  notification_id BIGINT NOT NULL,
  user_id INT NOT NULL,
  read_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_notif_read (notification_id, user_id),
  CONSTRAINT fk_notifread_notif FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_notifread_user  FOREIGN KEY (user_id)         REFERENCES users(id)         ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_notifread_user ON notification_reads(user_id);

-- Backfill from the legacy read_by array. JSON_CONTAINS(read_by, CAST(u.id AS
-- JSON)) tests membership without JSON_TABLE (which MariaDB lacks before 10.6).
-- The read_at we never stored, so it defaults to now(); INSERT IGNORE makes the
-- backfill re-runnable against the unique key.
INSERT IGNORE INTO notification_reads (notification_id, user_id)
SELECT n.id, u.id
  FROM notifications n
  JOIN users u
    ON n.read_by IS NOT NULL
   AND JSON_VALID(n.read_by)
   AND JSON_CONTAINS(n.read_by, CAST(u.id AS JSON));

-- Stable marker for the migration runner's artifact probe.
INSERT INTO app_settings (setting_key, setting_value)
VALUES ('migration_022_applied', '1')
ON DUPLICATE KEY UPDATE setting_value = '1';
