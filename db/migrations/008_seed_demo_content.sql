-- 008_seed_demo_content.sql
-- Seed 5 events and 5 blog posts so the public landing page has real content.
-- Idempotent: each row is guarded by a NOT EXISTS check on title.

-- ---- Events ----
INSERT INTO events (title, description, start_datetime, end_datetime, is_recurring)
SELECT * FROM (
  SELECT 'Sabbath Morning Service' AS title,
         'Weekly Sunday morning worship and Sabbath school for all ages. ሳምንታዊ የሰንበት ቀን አምልኮ።' AS description,
         DATE_ADD(CURDATE(), INTERVAL 3 DAY) + INTERVAL 9 HOUR AS start_datetime,
         DATE_ADD(CURDATE(), INTERVAL 3 DAY) + INTERVAL 12 HOUR AS end_datetime,
         1 AS is_recurring
) tmp
WHERE NOT EXISTS (SELECT 1 FROM events WHERE title = 'Sabbath Morning Service');

INSERT INTO events (title, description, start_datetime, end_datetime, is_recurring)
SELECT * FROM (
  SELECT 'Parent–Teacher Conference' AS title,
         'Term progress review with class teachers. የወላጅ-መምህር ምክክር።' AS description,
         DATE_ADD(CURDATE(), INTERVAL 14 DAY) + INTERVAL 14 HOUR AS start_datetime,
         DATE_ADD(CURDATE(), INTERVAL 14 DAY) + INTERVAL 17 HOUR AS end_datetime,
         0 AS is_recurring
) tmp
WHERE NOT EXISTS (SELECT 1 FROM events WHERE title = 'Parent–Teacher Conference');

INSERT INTO events (title, description, start_datetime, end_datetime, is_recurring)
SELECT * FROM (
  SELECT 'Choir Practice' AS title,
         'Weekly choir rehearsal for the children and youth choirs. ሳምንታዊ መዝሙር ልምምድ።' AS description,
         DATE_ADD(CURDATE(), INTERVAL 5 DAY) + INTERVAL 16 HOUR AS start_datetime,
         DATE_ADD(CURDATE(), INTERVAL 5 DAY) + INTERVAL 18 HOUR AS end_datetime,
         1 AS is_recurring
) tmp
WHERE NOT EXISTS (SELECT 1 FROM events WHERE title = 'Choir Practice');

INSERT INTO events (title, description, start_datetime, end_datetime, is_recurring)
SELECT * FROM (
  SELECT 'Mid-Term Assessments' AS title,
         'Mid-term written assessments across all classes. የመካከለኛ ጊዜ ፈተናዎች።' AS description,
         DATE_ADD(CURDATE(), INTERVAL 30 DAY) + INTERVAL 9 HOUR AS start_datetime,
         DATE_ADD(CURDATE(), INTERVAL 32 DAY) + INTERVAL 13 HOUR AS end_datetime,
         0 AS is_recurring
) tmp
WHERE NOT EXISTS (SELECT 1 FROM events WHERE title = 'Mid-Term Assessments');

INSERT INTO events (title, description, start_datetime, end_datetime, is_recurring)
SELECT * FROM (
  SELECT 'Annual Spiritual Retreat' AS title,
         'Three-day spiritual retreat for youth and adult tracks. ዓመታዊ መንፈሳዊ ጉባኤ።' AS description,
         DATE_ADD(CURDATE(), INTERVAL 60 DAY) + INTERVAL 8 HOUR AS start_datetime,
         DATE_ADD(CURDATE(), INTERVAL 62 DAY) + INTERVAL 18 HOUR AS end_datetime,
         0 AS is_recurring
) tmp
WHERE NOT EXISTS (SELECT 1 FROM events WHERE title = 'Annual Spiritual Retreat');

-- Recurrence rule for the weekly Sunday/Sabbath service
INSERT INTO event_recurrence_rules (event_id, freq, interval_num, by_day, until_date)
SELECT e.id, 'weekly', 1, 'SU', NULL
FROM events e
WHERE e.title = 'Sabbath Morning Service'
  AND NOT EXISTS (SELECT 1 FROM event_recurrence_rules r WHERE r.event_id = e.id);

-- Recurrence for choir
INSERT INTO event_recurrence_rules (event_id, freq, interval_num, by_day, until_date)
SELECT e.id, 'weekly', 1, 'TU', NULL
FROM events e
WHERE e.title = 'Choir Practice'
  AND NOT EXISTS (SELECT 1 FROM event_recurrence_rules r WHERE r.event_id = e.id);

-- ---- Blog posts ----
-- Use the first admin user as author; if none exists, the post is skipped.
INSERT INTO blog_posts (title, content, author_user_id)
SELECT 'Welcome to the New School Year',
       'We are excited to begin a new year of learning and worship together. Registration for both children and youth tracks is now open. Please reach out to the admin office for any questions.',
       (SELECT u.id FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='admin' AND u.is_archived=0 ORDER BY u.id LIMIT 1)
WHERE EXISTS (SELECT 1 FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='admin' AND u.is_archived=0)
  AND NOT EXISTS (SELECT 1 FROM blog_posts WHERE title='Welcome to the New School Year');

INSERT INTO blog_posts (title, content, author_user_id)
SELECT 'Reflections on the Feast of the Holy Cross',
       'The Feast of Meskel reminds us of the triumph of light over darkness. Our community gathered this past weekend for prayer, song, and a shared meal. Thank you to every family who made it a beautiful celebration.',
       (SELECT u.id FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='admin' AND u.is_archived=0 ORDER BY u.id LIMIT 1)
WHERE EXISTS (SELECT 1 FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='admin' AND u.is_archived=0)
  AND NOT EXISTS (SELECT 1 FROM blog_posts WHERE title='Reflections on the Feast of the Holy Cross');

INSERT INTO blog_posts (title, content, author_user_id)
SELECT 'Supporting Your Child Sabbath School Journey',
       'A short guide for parents on how to encourage daily reading, weekly attendance, and joyful participation. We have included practical tips and resources you can use at home.',
       (SELECT u.id FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='admin' AND u.is_archived=0 ORDER BY u.id LIMIT 1)
WHERE EXISTS (SELECT 1 FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='admin' AND u.is_archived=0)
  AND NOT EXISTS (SELECT 1 FROM blog_posts WHERE title='Supporting Your Child Sabbath School Journey');

INSERT INTO blog_posts (title, content, author_user_id)
SELECT 'New Tuition Options for the Coming Term',
       'We are introducing more flexible tuition arrangements for families this term, including partial payment plans. Please review the new options and contact the admin office to set up a plan.',
       (SELECT u.id FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='admin' AND u.is_archived=0 ORDER BY u.id LIMIT 1)
WHERE EXISTS (SELECT 1 FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='admin' AND u.is_archived=0)
  AND NOT EXISTS (SELECT 1 FROM blog_posts WHERE title='New Tuition Options for the Coming Term');

INSERT INTO blog_posts (title, content, author_user_id)
SELECT 'Meet Our Teachers',
       'Each of our teachers brings years of devotion and expertise. In this short series we will introduce the people who guide our students every Sabbath. Stay tuned for the first profile next week.',
       (SELECT u.id FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='admin' AND u.is_archived=0 ORDER BY u.id LIMIT 1)
WHERE EXISTS (SELECT 1 FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='admin' AND u.is_archived=0)
  AND NOT EXISTS (SELECT 1 FROM blog_posts WHERE title='Meet Our Teachers');
