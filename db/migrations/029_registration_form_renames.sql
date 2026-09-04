-- 029_registration_form_renames.sql
-- Broadens two registration forms beyond the single activity they were named for:
-- the Begena class is now instrument training generally, and the Gishen Mariam
-- trip is now a spiritual pilgrimage generally. Titles and descriptions move in
-- both languages. Slugs are deliberately left alone: 'begena' and
-- 'gishen-pilgrimage' are the stable keys that 019 seeds fields against and that
-- the department-ownership defaults key off, so renaming them would break both.
-- Idempotent: the UPDATEs are matched on slug and simply rewrite to the target
-- values, so a second run is a no-op.

SET NAMES utf8mb4;

UPDATE registration_forms
   SET title_en       = 'Sacred Instrument Training',
       title_am       = 'የዜማ መሳሪያ ስልጠና ምዝገባ',
       description_en = 'Sign up to learn our sacred liturgical instruments with our instructors.',
       description_am = 'ከመምህራኖቻችን ጋር ቅዱሳን የዜማ መሳሪያዎችን ለመማር ይመዝገቡ።'
 WHERE slug = 'begena';

UPDATE registration_forms
   SET title_en       = 'Spiritual Pilgrimage',
       title_am       = 'የመንፈሳዊ ጉዞ ምዝገባ',
       description_en = 'Reserve your place on our spiritual pilgrimage.',
       description_am = 'በሚደረገው መንፈሳዊ ጉዞ ላይ ቦታዎን ያስይዙ።'
 WHERE slug = 'gishen-pilgrimage';

INSERT INTO app_settings (setting_key, setting_value)
VALUES ('migration_029_applied', '1')
ON DUPLICATE KEY UPDATE setting_value = '1';
