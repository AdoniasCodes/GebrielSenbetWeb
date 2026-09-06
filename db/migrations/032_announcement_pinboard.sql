-- 032_announcement_pinboard.sql
-- The public announcements feed reads notifications flagged is_public. That
-- table had no Amharic columns, so a public notice could only ever appear in
-- one language, and no way to mark a notice as pinned to the top of the board.
--
-- Adds, all nullable or defaulted so existing rows stay valid:
--   title_am / message_am  the Amharic half of a public notice
--   is_pinned              sorts a notice to the top and marks it on the board
--
-- Then posts the general assembly notice, so the board is not empty on arrival.
-- It is an ordinary notification row afterwards: editable and archivable from
-- admin > Announcements like any other.
--
-- Idempotent: each ALTER is guarded by an information_schema check, and the
-- insert is guarded on its own title.

SET NAMES utf8mb4;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.columns
               WHERE table_schema = DATABASE() AND table_name = 'notifications'
                 AND column_name = 'title_am') = 0,
              'ALTER TABLE notifications ADD COLUMN title_am VARCHAR(200) NULL AFTER title',
              'SELECT 1');
PREPARE st FROM @ddl;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.columns
               WHERE table_schema = DATABASE() AND table_name = 'notifications'
                 AND column_name = 'message_am') = 0,
              'ALTER TABLE notifications ADD COLUMN message_am TEXT NULL AFTER message',
              'SELECT 1');
PREPARE st FROM @ddl;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @ddl = IF((SELECT COUNT(*) FROM information_schema.columns
               WHERE table_schema = DATABASE() AND table_name = 'notifications'
                 AND column_name = 'is_pinned') = 0,
              'ALTER TABLE notifications ADD COLUMN is_pinned TINYINT(1) NOT NULL DEFAULT 0 AFTER is_public',
              'SELECT 1');
PREPARE st FROM @ddl;
EXECUTE st;
DEALLOCATE PREPARE st;

INSERT INTO notifications (sender_user_id, sender_role_id, target_type, target_payload,
                           title, title_am, message, message_am, is_public, is_pinned, is_archived)
SELECT NULL, NULL, 'role', JSON_OBJECT('role', 'student'),
       'No classes: general assembly',
       'ጠቅላላ ጉባኤ ስለተጠራ ትምህርት የለም',
       'On Sunday, Pagume 1, 2018 EC, there are no classes in the morning, because the Sunday school has called its General Assembly. Attendance at the assembly is an obligation for all of us.',
       'እሑድ ጳጉሜ 1 ቀን 2018 ዓ.ም. ጥዋት የሰንበት ትምህርት ቤቱ ጠቅላላ ጉባኤ ስለጠራ ትምህርት የለም።\nጉባኤው ላይ ሁላችንም የመገኘት ግዴታ አለብን።',
       1, 1, 0
  FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM notifications n WHERE n.title_am = 'ጠቅላላ ጉባኤ ስለተጠራ ትምህርት የለም');

INSERT INTO app_settings (setting_key, setting_value)
VALUES ('migration_032_applied', '1')
ON DUPLICATE KEY UPDATE setting_value = '1';
