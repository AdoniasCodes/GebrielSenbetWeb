-- 016_app_settings.sql
-- Generic key/value app settings store (admin-editable). First use: the minimum
-- academic-attendance percentage required for a choir member to be eligible to
-- serve. Fully admin-controlled.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS app_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL UNIQUE,
  setting_value VARCHAR(500) NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO app_settings (setting_key, setting_value)
SELECT 'serving_eligibility_min_attendance', '75'
WHERE NOT EXISTS (SELECT 1 FROM app_settings WHERE setting_key = 'serving_eligibility_min_attendance');
