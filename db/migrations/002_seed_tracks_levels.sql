-- 002_seed_tracks_levels.sql
-- Seed education tracks and levels; idempotent inserts using INSERT IGNORE/ON DUPLICATE KEY patterns

INSERT IGNORE INTO education_tracks (name) VALUES
  ('Children'),
  ('Youth / Adult');

-- Children Levels
INSERT INTO class_levels (track_id, name, sort_order)
SELECT t.id, v.name, v.sort_order
FROM education_tracks t
JOIN (
  SELECT 'Children' AS track_name, 'Nursery' AS name, 1 AS sort_order UNION ALL
  SELECT 'Children', 'Grade 1', 2 UNION ALL
  SELECT 'Children', 'Grade 2', 3 UNION ALL
  SELECT 'Children', 'Grade 3', 4 UNION ALL
  SELECT 'Children', 'Grade 4', 5 UNION ALL
  SELECT 'Children', 'Grade 5', 6 UNION ALL
  SELECT 'Children', 'Grade 6', 7
) v ON v.track_name = t.name
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Youth / Adult Levels (Amharic)
INSERT INTO class_levels (track_id, name, sort_order)
SELECT t.id, v.name, v.sort_order
FROM education_tracks t
JOIN (
  SELECT 'Youth / Adult' AS track_name, 'ቀዳማይ' AS name, 1 AS sort_order UNION ALL
  SELECT 'Youth / Adult', 'ካላዓይ', 2 UNION ALL
  SELECT 'Youth / Adult', 'ሳልሳይ', 3 UNION ALL
  SELECT 'Youth / Adult', 'ራብዓይ', 4 UNION ALL
  SELECT 'Youth / Adult', 'ራብይ', 5 UNION ALL
  SELECT 'Youth / Adult', 'ሃምሳይ', 6
) v ON v.track_name = t.name
ON DUPLICATE KEY UPDATE name = VALUES(name);
