-- 031_seed_real_events.sql
-- The two announcements that were sent through for the site: the general
-- assembly and the annual feast of Saint Raphael. They are seeded here rather
-- than typed into admin because this host has no terminal access, and rather
-- than being written into the landing page because that would make them
-- uneditable, which is the whole point of having an events table.
--
-- Once applied they are ordinary rows: admin > Events can change the title,
-- description, venue, poster, time or archive them, and this migration will
-- never run again to undo that.
--
-- Requires 030 (title_am, description_am, location_en, location_am, image_url).
-- Idempotent: each insert is guarded on (start_datetime, title).

SET NAMES utf8mb4;

INSERT INTO events (title, title_am, description, description_am, start_datetime, end_datetime,
                    location_en, location_am, image_url, is_recurring, status, is_archived)
SELECT 'General Assembly', 'ጠቅላላ ጉባኤ', 'With our reverent spiritual greetings in the name of God Most High, the Kotebe Debre Liul Dagmawi Kulbi St Gabriel and Hohite Semay St Mary Cathedral Mekane Selam Sunday School will hold its General Assembly on Sunday, Pagume 1, 2018 EC (6 September 2026), beginning at 2:30 in the morning (8:30 AM).

At this assembly the Sunday school''s two-year work report will be presented, and the executive committee members who will serve for the coming three years will be introduced. It will also be a day of thanksgiving for the outgoing leadership, who by the good will of God have completed their term in peace.

All members are asked to be present at the Sunday school hall on the stated day and time. Attendance is an obligation for all of us.

Because the assembly has been called for that morning, there will be no regular classes on this day.', 'አስቀድመን የከበረ መንፈሳዊ ሰላምታችንን በልዑል እግዚአብሔር ስም እያቀረብን፣ የኮተቤ ደብረ ልዑል ዳግማዊ ቁልቢ ቅዱስ ገብርኤል እና ኆኅተ ሰማይ ቅድስት ማርያም ካቴድራል መካነ ሰላም ሰንበት ትምህርት ቤት እሑድ ጳጉሜ 1 ቀን 2018 ዓ.ም. ከጠዋቱ 2፡30 ጀምሮ ጠቅላላ ጉባኤውን ያካሂዳል።

በዚህ ጉባኤ ላይ የሰንበት ት/ቤቱ የሁለት ዓመት የሥራ ሪፖርት ይቀርባል፤ ለቀጣዮቹ ሦስት ዓመታት የሚያገለግሉ የሥራ አስፈጻሚዎችም ይተዋወቃሉ። እንዲሁም የሥራ ዘመኑን እያጠናቀቀ ያለው አመራር እንደ እግዚአብሔር መልካም ፈቃድ በሰላም ስላጠናቀቀ በምስጋና የምናከብርበት ቀን ይሆናል።

ሁላችሁም በተጠቀሰው ቀንና ሰዓት በሰንበት ት/ቤቱ አዳራሽ እንድትገኙ እናሳስባለን። በጉባኤው ሁላችንም የመገኘት ግዴታ አለብን።

በዚህ ዕለት ጠዋት ጉባኤው ስለተጠራ መደበኛ ትምህርት አይኖርም።', '2026-09-06 08:30:00', '2026-09-06 12:00:00', 'The Sunday school hall', 'በሰንበት ት/ቤቱ አዳራሽ', NULL, 0, 'approved', 0
  FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM events e WHERE e.start_datetime = '2026-09-06 08:30:00' AND e.title = 'General Assembly');

INSERT INTO events (title, title_am, description, description_am, start_datetime, end_datetime,
                    location_en, location_am, image_url, is_recurring, status, is_archived)
SELECT 'Annual Feast of Saint Raphael', 'የቅዱስ ሩፋኤል ዓመታዊ ክብረ በዓል', 'On Pagume 3, 2018 EC (8 September 2026), the annual feast of Saint Raphael is celebrated at Kotebe Debre Liul Dagmawi Kulbi St Gabriel Church.

1. His ordination and the consecration of his church
This day commemorates the appointment of Saint Raphael as one of the archangels, and the consecration of the church built in his name. The name Raphael means the one who gladdens, kind, merciful, upright and gentle, and he is known as the angel of health and peace.

2. The opening of the heavens
Pagume 3 is held to be the day the gates of heaven are opened. Believing that prayer and supplication offered on this day receive a swift answer, Christians pray and make entreaty both together and alone.

3. Remembrance of the Book of Tobit
As recounted in the Book of Tobit, Saint Raphael travelled in human form under the name Azariah: he restored the sight of Tobit, freed Sarah the daughter of Raguel from the demon Asmodeus, and blessed and brought about the marriage of Tobias and Sarah.

4. Opener of the womb
In the history of the Church, Saint Raphael is held to be the protector of mothers and children, and the angel who opens the womb for mothers in the distress of childbirth. For this reason mothers entreat him especially on this day.', 'ጳጉሜ 3 ቀን 2018 ዓ.ም. የቅዱስ ሩፋኤል ዓመታዊ ክብረ በዓል በኮተቤ ደብረ ልዑል ዳግማዊ ቁልቢ ቅዱስ ገብርኤል ቤተክርስቲያን ይከበራል።

1. በዓለ ሢመቱና ቅዳሴ ቤቱ
ይህ ቀን ቅዱስ ሩፋኤል ከሊቃነ መላእክት አንዱ ሆኖ የተሾመበት (በዓለ ሢመት) እና በስሙ የተሠራው ቅዳሴ ቤት የከበረበት መታሰቢያ ዕለት ነው። «ሩፋኤል» ማለት «ደስ የሚያሰኝ፣ ቸር፣ መሐሪ፣ ቅንና የዋህ» ማለት ሲሆን፣ መልአከ ጥዒና ወሰላም (የጤናና የሰላም መልአክ) በመባል ይታወቃል።

2. ርኅወተ ሰማይ
ጳጉሜ 3 ቀን ርኅወተ ሰማይ፣ ማለትም የሰማይ ደጆች የሚከፈቱበት ዕለት ተብሎ ይታመናል። ክርስቲያኖች በዚህ ዕለት ወደ ፈጣሪ የሚቀርብ ጸሎትና ምህላ ፈጣን ምላሽ ያገኛል ብለው ስለሚያምኑ በጋራና በግል ይጸልያሉ፣ ይማለላሉ።

3. የመጽሐፈ ጦቢት መታሰቢያ
በመጽሐፈ ጦቢት እንደተገለጸው፣ ቅዱስ ሩፋኤል ሰብአዊ አካል መስሎ (አዛርያ ተብሎ) በመጓዝ የጦቢትን ዓይን ያበራ፣ ወለተ ራጉኤልን (ሣራን) አስማንድዮስ ከተባለው ጋኔን ነፃ ያወጣ፣ የጦብያንና የሣራን ትዳር የባረከና ያስፈጸመ መሆኑ ይዘከራል።

4. ፈታሔ ማኅፀን
ቅዱስ ሩፋኤል በቤተ ክርስቲያን ታሪክ የእናቶችና የሕፃናት ጠባቂ፣ እንዲሁም በወሊድ ጊዜ ለሚጨነቁ እናቶች ማኅፀን የሚፈታ (ፈታሔ ማኅፀን) መልአክ ተደርጎ ይወሰዳል። በዚህም ምክንያት እናቶች በዚህ ዕለት በተለየ መልኩ ይማጸኑታል።', '2026-09-08 08:00:00', NULL, 'Kotebe Debre Liul Dagmawi Kulbi St Gabriel Church', 'ኮተቤ ደብረ ልዑል ዳግማዊ ቁልቢ ቅዱስ ገብርኤል ቤተክርስቲያን', NULL, 0, 'approved', 0
  FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM events e WHERE e.start_datetime = '2026-09-08 08:00:00' AND e.title = 'Annual Feast of Saint Raphael');

INSERT INTO app_settings (setting_key, setting_value)
VALUES ('migration_031_applied', '1')
ON DUPLICATE KEY UPDATE setting_value = '1';
