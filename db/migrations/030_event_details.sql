-- 030_event_details.sql
-- The landing page shows events as cards: venue and time at a glance, then a
-- detail view with an optional poster. The events table carried none of that,
-- and no Amharic columns either, so every event was English-only and had
-- nowhere to record where it happens.
--
-- Adds, all nullable so existing rows stay valid:
--   title_am / description_am  bilingual pair for the existing columns
--   location_en / location_am  the venue, shown on the card face
--   image_url                  poster or photo, shown in the detail view
--
-- Idempotent: each ALTER is guarded by an information_schema check, because
-- MariaDB has no ADD COLUMN IF NOT EXISTS that is safe across versions here.

SET NAMES utf8mb4;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.columns
               WHERE table_schema = DATABASE() AND table_name = 'events'
                 AND column_name = 'title_am') = 0,
              'ALTER TABLE events ADD COLUMN title_am VARCHAR(200) NULL AFTER title',
              'SELECT 1');
PREPARE st FROM @ddl;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.columns
               WHERE table_schema = DATABASE() AND table_name = 'events'
                 AND column_name = 'description_am') = 0,
              'ALTER TABLE events ADD COLUMN description_am TEXT NULL AFTER description',
              'SELECT 1');
PREPARE st FROM @ddl;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.columns
               WHERE table_schema = DATABASE() AND table_name = 'events'
                 AND column_name = 'location_en') = 0,
              'ALTER TABLE events ADD COLUMN location_en VARCHAR(200) NULL AFTER end_datetime',
              'SELECT 1');
PREPARE st FROM @ddl;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.columns
               WHERE table_schema = DATABASE() AND table_name = 'events'
                 AND column_name = 'location_am') = 0,
              'ALTER TABLE events ADD COLUMN location_am VARCHAR(200) NULL AFTER location_en',
              'SELECT 1');
PREPARE st FROM @ddl;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.columns
               WHERE table_schema = DATABASE() AND table_name = 'events'
                 AND column_name = 'image_url') = 0,
              'ALTER TABLE events ADD COLUMN image_url VARCHAR(500) NULL AFTER location_am',
              'SELECT 1');
PREPARE st FROM @ddl;
EXECUTE st;
DEALLOCATE PREPARE st;

INSERT INTO app_settings (setting_key, setting_value)
VALUES ('migration_030_applied', '1')
ON DUPLICATE KEY UPDATE setting_value = '1';
