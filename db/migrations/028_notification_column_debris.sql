-- 028_notification_column_debris.sql
-- Phase 3.4 (SYSTEM_AUDIT_AND_BLUEPRINT.md section 9): enum and column debris.
-- Migration 022 said it plainly: "read_by is left on notifications for rollback
-- safety; Phase 3's column-debris pass drops it." This is that pass.
--
-- Two pieces of dead schema go:
--
-- 1. notifications.read_by (legacy JSON array of user ids). Superseded by the
--    notification_reads join table in migration 022, which fixed the lost-update
--    race. Nothing has written read_by since; the only remaining reader was
--    api/admin/announcements/index.php, which returned it to a UI that never
--    displayed it, and now returns a real read_count from notification_reads.
--
-- 2. Three unreachable notifications.target_type values: 'subject',
--    'payment_defaulters' and 'event'. The Phase 2.1 contract (NOTIFY_TARGETS in
--    api/notifications_lib.php) defines exactly four targets, and notify() is the
--    only writer, so these three cannot be produced and no reader matches them.
--    Verified zero rows before narrowing.
--
-- The backfill below re-runs 022's logic defensively. It is a no-op on any
-- database where 022 already ran, but it means the drop can never lose read
-- state even if the two migrations are applied in the same batch on a database
-- that had rows in flight.
--
-- Single-apply: the migrate runner gates re-run via the migration_028_applied probe.

SET NAMES utf8mb4;

-- Defensive re-backfill before the column disappears.
INSERT IGNORE INTO notification_reads (notification_id, user_id, read_at)
SELECT n.id, u.id, COALESCE(n.updated_at, n.created_at, NOW())
  FROM notifications n
  JOIN users u
    ON n.read_by IS NOT NULL
   AND JSON_VALID(n.read_by)
   AND JSON_CONTAINS(n.read_by, CAST(u.id AS JSON));

ALTER TABLE notifications DROP COLUMN read_by;

-- Narrow the target contract to what notify() can actually produce.
ALTER TABLE notifications
  MODIFY COLUMN target_type ENUM('role','department','class','user') NOT NULL;

-- Stable marker for the migration runner's artifact probe.
INSERT INTO app_settings (setting_key, setting_value)
VALUES ('migration_028_applied', '1')
ON DUPLICATE KEY UPDATE setting_value = '1';
