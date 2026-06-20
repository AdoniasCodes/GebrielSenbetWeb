-- 013_academic_grades_1_11.sql
-- Phase A: correct the academic model to match the diocese curriculum.
-- The school offers Grades 1-11; Grades 7-11 also carry Ge'ez names
-- (ቀዳማይ, ካልዓይ, ሳልሳይ, ራባዓይ, ሃምሳይ). Subjects are curriculum-defined per grade.
-- The old "Children / Youth-Adult" two-track seed is ARCHIVED, not deleted.

SET NAMES utf8mb4;

-- Bilingual + alias support on levels (alias = the Ge'ez senior name for 7-11).
ALTER TABLE class_levels ADD COLUMN name_am VARCHAR(100) NULL AFTER name;
ALTER TABLE class_levels ADD COLUMN alias VARCHAR(60) NULL AFTER name_am;
ALTER TABLE subjects ADD COLUMN name_am VARCHAR(150) NULL AFTER name;

-- Archive the old seeded tracks and their levels (no hard delete).
UPDATE class_levels SET is_archived=1, archived_at=NOW()
  WHERE is_archived=0 AND track_id IN (SELECT id FROM education_tracks WHERE name IN ('Children','Youth / Adult'));
UPDATE education_tracks SET is_archived=1, archived_at=NOW()
  WHERE is_archived=0 AND name IN ('Children','Youth / Adult');

-- One canonical academic track for the diocese curriculum.
INSERT INTO education_tracks (name)
SELECT 'Sunday School Curriculum'
WHERE NOT EXISTS (SELECT 1 FROM education_tracks WHERE name='Sunday School Curriculum');

-- Grades 1-11 under that track. 7-11 get their Ge'ez alias.
INSERT INTO class_levels (track_id, name, name_am, alias, sort_order)
SELECT (SELECT id FROM education_tracks WHERE name='Sunday School Curriculum'),
       v.name, v.name_am, v.alias, v.sort_order
FROM (
  SELECT 'Grade 1'  AS name, '1ኛ ክፍል'  AS name_am, NULL     AS alias, 1  AS sort_order UNION ALL
  SELECT 'Grade 2',  '2ኛ ክፍል',  NULL,     2  UNION ALL
  SELECT 'Grade 3',  '3ኛ ክፍል',  NULL,     3  UNION ALL
  SELECT 'Grade 4',  '4ኛ ክፍል',  NULL,     4  UNION ALL
  SELECT 'Grade 5',  '5ኛ ክፍል',  NULL,     5  UNION ALL
  SELECT 'Grade 6',  '6ኛ ክፍል',  NULL,     6  UNION ALL
  SELECT 'Grade 7',  '7ኛ ክፍል',  'ቀዳማይ',  7  UNION ALL
  SELECT 'Grade 8',  '8ኛ ክፍል',  'ካልዓይ',  8  UNION ALL
  SELECT 'Grade 9',  '9ኛ ክፍል',  'ሳልሳይ',  9  UNION ALL
  SELECT 'Grade 10', '10ኛ ክፍል', 'ራባዓይ',  10 UNION ALL
  SELECT 'Grade 11', '11ኛ ክፍል', 'ሃምሳይ',  11
) v
WHERE NOT EXISTS (
  SELECT 1 FROM class_levels cl
  JOIN education_tracks t ON t.id = cl.track_id
  WHERE t.name='Sunday School Curriculum' AND cl.name = v.name
);

-- Curriculum subjects catalog (global). Per-grade assignment via grade_subjects.
INSERT INTO subjects (name, name_am)
SELECT v.name, v.name_am FROM (
  SELECT 'Ge''ez'                                  AS name, 'ግዕዝ'                          AS name_am UNION ALL
  SELECT 'History of Orthodoxy & the EOTC',           'የኦርቶዶክስ ተዋሕዶ ቤተክርስቲያን ታሪክ'  UNION ALL
  SELECT 'Faith (Negere Haymanot)',                    'ነገረ ሃይማኖት'                    UNION ALL
  SELECT 'Church Order (Sireate Bete-Kristian)',       'ስርዓተ ቤተክርስቲያን'                UNION ALL
  SELECT 'On the Saints (Negere Qidusan)',             'ነገረ ቅድሳን'                      UNION ALL
  SELECT 'Traditional Church Schooling (Abinet)',      'አብነት ትምህርት'                   UNION ALL
  SELECT 'On St. Mary (Negere Mariam)',                'ነገረ ማርያም'                     UNION ALL
  SELECT 'On Christ (Negere Kristos)',                 'ነገረ ክርስቶስ'
) v
WHERE NOT EXISTS (SELECT 1 FROM subjects s WHERE s.name = v.name);

-- Which subjects belong to which grade (curriculum mapping; filled per the
-- ትምህርት ክፍል curriculum). A subject can appear in many grades and vice versa.
CREATE TABLE IF NOT EXISTS grade_subjects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  level_id INT NOT NULL,
  subject_id INT NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_gs_level FOREIGN KEY (level_id) REFERENCES class_levels(id) ON UPDATE CASCADE,
  CONSTRAINT fk_gs_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON UPDATE CASCADE,
  UNIQUE KEY uq_grade_subject (level_id, subject_id)
) ENGINE=InnoDB;
CREATE INDEX idx_gs_level ON grade_subjects(level_id);
