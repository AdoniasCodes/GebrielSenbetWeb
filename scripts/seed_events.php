#!/usr/bin/env php
<?php
/**
 * scripts/seed_events.php
 *
 * Idempotent CLI seeder for real parish events.
 *
 * These events are DATA, not page content. They are written into the `events`
 * table exactly as if they had been typed into admin > Events, so they stay
 * fully editable there afterwards: title, description, venue, poster and times
 * can all be changed without touching the code. Nothing about them is
 * hardcoded into the landing page.
 *
 * Safe to run repeatedly. An event is matched on (start_datetime, title) and
 * UPDATED in place rather than duplicated, so re-running after editing this
 * file republishes the corrected text. Editing an event in the admin UI and
 * then re-running this script WILL overwrite those admin edits, so run it once
 * per event set and make later changes in admin.
 *
 * Requires migration 030 (title_am, description_am, location_en, location_am,
 * image_url). The script checks for it and exits cleanly if it is missing.
 *
 * USAGE
 *   Local:
 *     APP_DB_HOST=127.0.0.1 APP_DB_NAME=eagleerq_gebriel \
 *       APP_DB_USER=gsb APP_DB_PASS=gsblocal php scripts/seed_events.php
 *   Production (cPanel Terminal, from the repo root):
 *     php scripts/seed_events.php
 *   Preview without writing:
 *     php scripts/seed_events.php --dry-run
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Database;

$dryRun = in_array('--dry-run', $argv, true);

$config = app_config();
$pdo = (new Database($config['db']))->pdo();

// ---- guard: migration 030 must be applied -------------------------------
$cols = $pdo->query(
    "SELECT COUNT(*) FROM information_schema.columns
      WHERE table_schema = DATABASE() AND table_name = 'events'
        AND column_name IN ('title_am','description_am','location_en','location_am','image_url')"
)->fetchColumn();
if ((int)$cols < 5) {
    fwrite(STDERR, "Migration 030_event_details.sql has not been applied to this database.\n");
    fwrite(STDERR, "Run the migrate endpoint first, then re-run this script.\n");
    exit(1);
}

// The official full name, per the naming rule. Used in the announcement body.
$SCHOOL_AM = 'የኮተቤ ደብረ ልዑል ዳግማዊ ቁልቢ ቅዱስ ገብርኤል እና ኆኅተ ሰማይ ቅድስት ማርያም ካቴድራል መካነ ሰላም ሰንበት ትምህርት ቤት';
$SCHOOL_EN = 'the Kotebe Debre Liul Dagmawi Kulbi St Gabriel and Hohite Semay St Mary Cathedral Mekane Selam Sunday School';
$GABRIEL_AM = 'ኮተቤ ደብረ ልዑል ዳግማዊ ቁልቢ ቅዱስ ገብርኤል ቤተክርስቲያን';
$GABRIEL_EN = 'Kotebe Debre Liul Dagmawi Kulbi St Gabriel Church';

$events = [

    // ---- 1. General assembly, Pagume 1 2018 EC = 6 September 2026 --------
    [
        'title'       => 'General Assembly',
        'title_am'    => 'ጠቅላላ ጉባኤ',
        'start'       => '2026-09-06 08:30:00',
        'end'         => '2026-09-06 12:00:00',
        'location_en' => 'The Sunday school hall',
        'location_am' => 'በሰንበት ት/ቤቱ አዳራሽ',
        'image_url'   => null,
        'description_am' =>
            "አስቀድመን የከበረ መንፈሳዊ ሰላምታችንን በልዑል እግዚአብሔር ስም እያቀረብን፣ {$SCHOOL_AM} እሑድ ጳጉሜ 1 ቀን 2018 ዓ.ም. ከጠዋቱ 2፡30 ጀምሮ ጠቅላላ ጉባኤውን ያካሂዳል።\n\n"
          . "በዚህ ጉባኤ ላይ የሰንበት ት/ቤቱ የሁለት ዓመት የሥራ ሪፖርት ይቀርባል፤ ለቀጣዮቹ ሦስት ዓመታት የሚያገለግሉ የሥራ አስፈጻሚዎችም ይተዋወቃሉ። እንዲሁም የሥራ ዘመኑን እያጠናቀቀ ያለው አመራር እንደ እግዚአብሔር መልካም ፈቃድ በሰላም ስላጠናቀቀ በምስጋና የምናከብርበት ቀን ይሆናል።\n\n"
          . "ሁላችሁም በተጠቀሰው ቀንና ሰዓት በሰንበት ት/ቤቱ አዳራሽ እንድትገኙ እናሳስባለን። በጉባኤው ሁላችንም የመገኘት ግዴታ አለብን።\n\n"
          . "በዚህ ዕለት ጠዋት ጉባኤው ስለተጠራ መደበኛ ትምህርት አይኖርም።",
        'description_en' =>
            "With our reverent spiritual greetings in the name of God Most High, {$SCHOOL_EN} will hold its General Assembly on Sunday, Pagume 1, 2018 EC (6 September 2026), beginning at 2:30 in the morning (8:30 AM).\n\n"
          . "At this assembly the Sunday school's two-year work report will be presented, and the executive committee members who will serve for the coming three years will be introduced. It will also be a day of thanksgiving for the outgoing leadership, who by the good will of God have completed their term in peace.\n\n"
          . "All members are asked to be present at the Sunday school hall on the stated day and time. Attendance is an obligation for all of us.\n\n"
          . "Because the assembly has been called for that morning, there will be no regular classes on this day.",
    ],

    // ---- 2. Feast of St Raphael, Pagume 3 2018 EC = 8 September 2026 -----
    [
        'title'       => 'Annual Feast of Saint Raphael',
        'title_am'    => 'የቅዱስ ሩፋኤል ዓመታዊ ክብረ በዓል',
        'start'       => '2026-09-08 08:00:00',
        'end'         => null,
        'location_en' => $GABRIEL_EN,
        'location_am' => $GABRIEL_AM,
        'image_url'   => null,
        'description_am' =>
            "ጳጉሜ 3 ቀን 2018 ዓ.ም. የቅዱስ ሩፋኤል ዓመታዊ ክብረ በዓል በ{$GABRIEL_AM} ይከበራል።\n\n"
          . "1. በዓለ ሢመቱና ቅዳሴ ቤቱ\n"
          . "ይህ ቀን ቅዱስ ሩፋኤል ከሊቃነ መላእክት አንዱ ሆኖ የተሾመበት (በዓለ ሢመት) እና በስሙ የተሠራው ቅዳሴ ቤት የከበረበት መታሰቢያ ዕለት ነው። «ሩፋኤል» ማለት «ደስ የሚያሰኝ፣ ቸር፣ መሐሪ፣ ቅንና የዋህ» ማለት ሲሆን፣ መልአከ ጥዒና ወሰላም (የጤናና የሰላም መልአክ) በመባል ይታወቃል።\n\n"
          . "2. ርኅወተ ሰማይ\n"
          . "ጳጉሜ 3 ቀን ርኅወተ ሰማይ፣ ማለትም የሰማይ ደጆች የሚከፈቱበት ዕለት ተብሎ ይታመናል። ክርስቲያኖች በዚህ ዕለት ወደ ፈጣሪ የሚቀርብ ጸሎትና ምህላ ፈጣን ምላሽ ያገኛል ብለው ስለሚያምኑ በጋራና በግል ይጸልያሉ፣ ይማለላሉ።\n\n"
          . "3. የመጽሐፈ ጦቢት መታሰቢያ\n"
          . "በመጽሐፈ ጦቢት እንደተገለጸው፣ ቅዱስ ሩፋኤል ሰብአዊ አካል መስሎ (አዛርያ ተብሎ) በመጓዝ የጦቢትን ዓይን ያበራ፣ ወለተ ራጉኤልን (ሣራን) አስማንድዮስ ከተባለው ጋኔን ነፃ ያወጣ፣ የጦብያንና የሣራን ትዳር የባረከና ያስፈጸመ መሆኑ ይዘከራል።\n\n"
          . "4. ፈታሔ ማኅፀን\n"
          . "ቅዱስ ሩፋኤል በቤተ ክርስቲያን ታሪክ የእናቶችና የሕፃናት ጠባቂ፣ እንዲሁም በወሊድ ጊዜ ለሚጨነቁ እናቶች ማኅፀን የሚፈታ (ፈታሔ ማኅፀን) መልአክ ተደርጎ ይወሰዳል። በዚህም ምክንያት እናቶች በዚህ ዕለት በተለየ መልኩ ይማጸኑታል።",
        'description_en' =>
            "On Pagume 3, 2018 EC (8 September 2026), the annual feast of Saint Raphael is celebrated at {$GABRIEL_EN}.\n\n"
          . "1. His ordination and the consecration of his church\n"
          . "This day commemorates the appointment of Saint Raphael as one of the archangels, and the consecration of the church built in his name. The name Raphael means the one who gladdens, kind, merciful, upright and gentle, and he is known as the angel of health and peace.\n\n"
          . "2. The opening of the heavens\n"
          . "Pagume 3 is held to be the day the gates of heaven are opened. Believing that prayer and supplication offered on this day receive a swift answer, Christians pray and make entreaty both together and alone.\n\n"
          . "3. Remembrance of the Book of Tobit\n"
          . "As recounted in the Book of Tobit, Saint Raphael travelled in human form under the name Azariah: he restored the sight of Tobit, freed Sarah the daughter of Raguel from the demon Asmodeus, and blessed and brought about the marriage of Tobias and Sarah.\n\n"
          . "4. Opener of the womb\n"
          . "In the history of the Church, Saint Raphael is held to be the protector of mothers and children, and the angel who opens the womb for mothers in the distress of childbirth. For this reason mothers entreat him especially on this day.",
    ],
];

$find = $pdo->prepare('SELECT id FROM events WHERE start_datetime = ? AND title = ? LIMIT 1');
$ins  = $pdo->prepare(
    'INSERT INTO events (title, title_am, description, description_am, start_datetime, end_datetime,
                         location_en, location_am, image_url, is_recurring, status, is_archived)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, \'approved\', 0)'
);
$upd = $pdo->prepare(
    'UPDATE events SET title_am=?, description=?, description_am=?, end_datetime=?,
                       location_en=?, location_am=?, image_url=?, status=\'approved\', is_archived=0
      WHERE id=?'
);

$created = 0; $updated = 0;
foreach ($events as $e) {
    $find->execute([$e['start'], $e['title']]);
    $existing = $find->fetchColumn();

    if ($dryRun) {
        printf("%-8s %s  %s\n", $existing ? 'UPDATE' : 'CREATE', $e['start'], $e['title_am']);
        continue;
    }

    if ($existing) {
        $upd->execute([
            $e['title_am'], $e['description_en'], $e['description_am'], $e['end'],
            $e['location_en'], $e['location_am'], $e['image_url'], (int)$existing,
        ]);
        $updated++;
        printf("updated  #%-4d %s  %s\n", (int)$existing, $e['start'], $e['title_am']);
    } else {
        $ins->execute([
            $e['title'], $e['title_am'], $e['description_en'], $e['description_am'],
            $e['start'], $e['end'], $e['location_en'], $e['location_am'], $e['image_url'],
        ]);
        $created++;
        printf("created  #%-4d %s  %s\n", (int)$pdo->lastInsertId(), $e['start'], $e['title_am']);
    }
}

if (!$dryRun) {
    printf("\nDone. %d created, %d updated.\n", $created, $updated);
    echo "Both are now editable in admin > Events, including venue and poster.\n";
}
