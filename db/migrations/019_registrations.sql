-- 019_registrations.sql
-- Customizable public registration system. Public visitors submit registrations
-- for three activities (Sunday School, Begena classes, Gishen pilgrimage). Each
-- form belongs to a department; the main admin and that department's head can
-- see the submissions and customize the form's fields and open/closed status.

SET NAMES utf8mb4;

-- A registration form = one public activity people can sign up for.
CREATE TABLE IF NOT EXISTS registration_forms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(80) NOT NULL,
  title_en VARCHAR(200) NOT NULL,
  title_am VARCHAR(200) NULL,
  description_en TEXT NULL,
  description_am TEXT NULL,
  department_id INT NULL,
  status ENUM('open','limited','closed') NOT NULL DEFAULT 'open',
  sort_order INT NOT NULL DEFAULT 0,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_regform_slug (slug),
  CONSTRAINT fk_regform_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE INDEX idx_regforms_dept ON registration_forms(department_id, is_archived);

-- A configurable question/field on a form. options_json holds a JSON array of
-- {value,label_en,label_am} for select/radio/checkbox types.
CREATE TABLE IF NOT EXISTS registration_form_fields (
  id INT AUTO_INCREMENT PRIMARY KEY,
  form_id INT NOT NULL,
  label_en VARCHAR(200) NOT NULL,
  label_am VARCHAR(200) NULL,
  field_type ENUM('text','textarea','email','phone','number','date','select','radio','checkbox') NOT NULL DEFAULT 'text',
  options_json TEXT NULL,
  placeholder_en VARCHAR(200) NULL,
  placeholder_am VARCHAR(200) NULL,
  is_required TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_regfield_form FOREIGN KEY (form_id) REFERENCES registration_forms(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX idx_regfields_form ON registration_form_fields(form_id, is_archived, sort_order);

-- One public submission. answers_json maps field_id -> value (or array of values
-- for checkboxes). labels_snapshot_json freezes the field labels at submit time
-- so later edits to the form do not orphan old submissions.
CREATE TABLE IF NOT EXISTS registration_submissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  form_id INT NOT NULL,
  answers_json TEXT NOT NULL,
  labels_snapshot_json TEXT NULL,
  applicant_name VARCHAR(200) NULL,
  applicant_phone VARCHAR(60) NULL,
  status ENUM('new','seen','contacted') NOT NULL DEFAULT 'new',
  submitted_ip VARCHAR(45) NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_regsub_form FOREIGN KEY (form_id) REFERENCES registration_forms(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE INDEX idx_regsubs_form ON registration_submissions(form_id, is_archived, created_at);
CREATE INDEX idx_regsubs_ip ON registration_submissions(form_id, submitted_ip, created_at);

-- ---- Seed the three activity forms (idempotent on slug). ----
INSERT INTO registration_forms (slug, title_en, title_am, description_en, description_am, department_id, status, sort_order)
SELECT v.slug, v.title_en, v.title_am, v.description_en, v.description_am,
       (SELECT id FROM departments WHERE slug = v.dept_slug), v.status, v.sort_order
FROM (
  SELECT 'sunday-school' AS slug,
         'Sunday School Academic Registration' AS title_en,
         'የሰንበት ትምህርት ቤት ምዝገባ' AS title_am,
         'Register a child or youth for the Sunday School academic program.' AS description_en,
         'ልጅዎን ወይም ወጣቱን ለሰንበት ትምህርት ቤት የትምህርት መርሃ ግብር ያስመዝግቡ።' AS description_am,
         'timhirt' AS dept_slug, 'open' AS status, 10 AS sort_order
  UNION ALL
  SELECT 'begena',
         'Begena Classes',
         'የበገና ስልጠና ምዝገባ',
         'Sign up to learn the sacred Begena (harp) with our instructors.',
         'ከመምህራኖቻችን ጋር ቅዱሱን በገና ለመማር ይመዝገቡ።',
         'mezmur', 'open', 20
  UNION ALL
  SELECT 'gishen-pilgrimage',
         'Spiritual Pilgrimage to Gishen Mariam',
         'የግሸን ማርያም ጉዞ ምዝገባ',
         'Reserve your place on the spiritual pilgrimage to Gishen Mariam.',
         'ወደ ግሸን ማርያም በሚደረገው መንፈሳዊ ጉዞ ላይ ቦታዎን ያስይዙ።',
         'guzo', 'limited', 30
) v
WHERE NOT EXISTS (SELECT 1 FROM registration_forms f WHERE f.slug = v.slug);

-- ---- Seed default fields per form (only when the form has no fields yet). ----

-- Sunday School fields.
INSERT INTO registration_form_fields (form_id, label_en, label_am, field_type, options_json, placeholder_en, placeholder_am, is_required, sort_order)
SELECT rf.id, v.label_en, v.label_am, v.field_type, v.options_json, v.placeholder_en, v.placeholder_am, v.is_required, v.sort_order
FROM (
  SELECT 'Full name' AS label_en, 'ሙሉ ስም' AS label_am, 'text' AS field_type, CAST(NULL AS CHAR) AS options_json, 'First and last name' AS placeholder_en, 'ስምና የአባት ስም' AS placeholder_am, 1 AS is_required, 10 AS sort_order
  UNION ALL SELECT 'Phone number', 'ስልክ ቁጥር', 'phone', NULL, '09xxxxxxxx', '09xxxxxxxx', 1, 20
  UNION ALL SELECT 'Baptismal name', 'የክርስትና ስም', 'text', NULL, NULL, NULL, 0, 30
  UNION ALL SELECT 'Date of birth', 'የልደት ቀን', 'date', NULL, NULL, NULL, 0, 40
  UNION ALL SELECT 'Sex', 'ጾታ', 'select', '[{"value":"male","label_en":"Male","label_am":"ወንድ"},{"value":"female","label_en":"Female","label_am":"ሴት"}]', NULL, NULL, 0, 50
  UNION ALL SELECT 'Guardian name', 'የአሳዳጊ ስም', 'text', NULL, NULL, NULL, 0, 60
  UNION ALL SELECT 'Guardian phone', 'የአሳዳጊ ስልክ', 'phone', NULL, '09xxxxxxxx', '09xxxxxxxx', 0, 70
  UNION ALL SELECT 'Address', 'አድራሻ', 'textarea', NULL, 'Sub-city, woreda, house no.', 'ክፍለ ከተማ፣ ወረዳ፣ የቤት ቁጥር', 0, 80
) v
JOIN registration_forms rf ON rf.slug = 'sunday-school'
WHERE NOT EXISTS (
  SELECT 1 FROM registration_form_fields f JOIN registration_forms rf2 ON rf2.id = f.form_id WHERE rf2.slug = 'sunday-school'
);

-- Begena fields.
INSERT INTO registration_form_fields (form_id, label_en, label_am, field_type, options_json, placeholder_en, placeholder_am, is_required, sort_order)
SELECT rf.id, v.label_en, v.label_am, v.field_type, v.options_json, v.placeholder_en, v.placeholder_am, v.is_required, v.sort_order
FROM (
  SELECT 'Full name' AS label_en, 'ሙሉ ስም' AS label_am, 'text' AS field_type, CAST(NULL AS CHAR) AS options_json, 'First and last name' AS placeholder_en, 'ስምና የአባት ስም' AS placeholder_am, 1 AS is_required, 10 AS sort_order
  UNION ALL SELECT 'Phone number', 'ስልክ ቁጥር', 'phone', NULL, '09xxxxxxxx', '09xxxxxxxx', 1, 20
  UNION ALL SELECT 'Age', 'ዕድሜ', 'number', NULL, NULL, NULL, 0, 30
  UNION ALL SELECT 'Prior experience', 'ቀዳሚ ልምድ', 'select', '[{"value":"none","label_en":"None","label_am":"የለም"},{"value":"beginner","label_en":"Beginner","label_am":"ጀማሪ"},{"value":"intermediate","label_en":"Intermediate","label_am":"መካከለኛ"}]', NULL, NULL, 1, 40
  UNION ALL SELECT 'Preferred schedule', 'የሚመርጡት ጊዜ', 'select', '[{"value":"weekday_evening","label_en":"Weekday evening","label_am":"የስራ ቀን ማታ"},{"value":"weekend_morning","label_en":"Weekend morning","label_am":"ቅዳሜና እሁድ ጠዋት"},{"value":"flexible","label_en":"Flexible","label_am":"ተለዋዋጭ"}]', NULL, NULL, 0, 50
) v
JOIN registration_forms rf ON rf.slug = 'begena'
WHERE NOT EXISTS (
  SELECT 1 FROM registration_form_fields f JOIN registration_forms rf2 ON rf2.id = f.form_id WHERE rf2.slug = 'begena'
);

-- Gishen pilgrimage fields.
INSERT INTO registration_form_fields (form_id, label_en, label_am, field_type, options_json, placeholder_en, placeholder_am, is_required, sort_order)
SELECT rf.id, v.label_en, v.label_am, v.field_type, v.options_json, v.placeholder_en, v.placeholder_am, v.is_required, v.sort_order
FROM (
  SELECT 'Full name' AS label_en, 'ሙሉ ስም' AS label_am, 'text' AS field_type, CAST(NULL AS CHAR) AS options_json, 'First and last name' AS placeholder_en, 'ስምና የአባት ስም' AS placeholder_am, 1 AS is_required, 10 AS sort_order
  UNION ALL SELECT 'Phone number', 'ስልክ ቁጥር', 'phone', NULL, '09xxxxxxxx', '09xxxxxxxx', 1, 20
  UNION ALL SELECT 'Emergency contact name', 'የአደጋ ጊዜ ተጠሪ ስም', 'text', NULL, NULL, NULL, 1, 30
  UNION ALL SELECT 'Emergency contact phone', 'የአደጋ ጊዜ ተጠሪ ስልክ', 'phone', NULL, '09xxxxxxxx', '09xxxxxxxx', 1, 40
  UNION ALL SELECT 'Number of seats', 'የመቀመጫ ብዛት', 'number', NULL, '1', '1', 1, 50
) v
JOIN registration_forms rf ON rf.slug = 'gishen-pilgrimage'
WHERE NOT EXISTS (
  SELECT 1 FROM registration_form_fields f JOIN registration_forms rf2 ON rf2.id = f.form_id WHERE rf2.slug = 'gishen-pilgrimage'
);
