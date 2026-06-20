<?php
/**
 * db/seeds/demo_seed.php — comprehensive DEMO data for local testing.
 *
 * Fills every pre-defined category (churches, departments, choir levels,
 * Grades 1–11, curriculum subjects) AND populates them with realistic,
 * interconnected data: members across every department & church, choir levels
 * with heads, students/teachers with logins, classes, terms, enrollments,
 * grades, payments, parent links, announcements, and holiday events.
 *
 * Re-runnable: wipes demo-domain rows first (keeps reference/seed data and the
 * admin login), then re-inserts. Run:  php db/seeds/demo_seed.php
 *
 * All demo logins use the password: demo1234
 */

require_once __DIR__ . '/../../bootstrap.php';

use App\Database;

$config = app_config();
$pdo = (new Database($config['db']))->pdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

mt_srand(2018); // deterministic
$DEMO_PW = password_hash('demo1234', PASSWORD_DEFAULT);
$today = '2026-06-14';

function insert(PDO $pdo, string $table, array $data): int {
    $cols = array_keys($data);
    $sql = "INSERT INTO `$table` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ")";
    $pdo->prepare($sql)->execute(array_values($data));
    return (int)$pdo->lastInsertId();
}
function pick(array $a) { return $a[array_rand($a)]; }
function chance(int $pct): bool { return mt_rand(1, 100) <= $pct; }

echo "Seeding demo data...\n";

// ---------------------------------------------------------------------------
// 0) WIPE demo-domain rows (children first). Keeps reference data + admin.
// ---------------------------------------------------------------------------
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
foreach ([
    'attendance_records','attendance_sessions','serving_assignments','holidays',
    'grades','payments','student_class_assignments','teacher_subject_assignments',
    'student_guardians','department_memberships','students','teachers','people',
    'classes','academic_terms','grade_subjects','notifications',
] as $t) { $pdo->exec("DELETE FROM `$t`"); }
// Remove demo logins (everything except the admin role).
$pdo->exec("DELETE FROM users WHERE role_id <> (SELECT id FROM roles WHERE name='admin')");
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
echo "  wiped previous demo rows\n";

// ---------------------------------------------------------------------------
// 1) Reference lookups (seeded by migrations)
// ---------------------------------------------------------------------------
$roleId = [];
foreach ($pdo->query("SELECT id,name FROM roles") as $r) $roleId[$r['name']] = (int)$r['id'];

$churches = $pdo->query("SELECT id,short_name FROM churches WHERE is_archived=0 ORDER BY id")->fetchAll();
$churchIds = array_column($churches, 'id');

$trackId = (int)$pdo->query("SELECT id FROM education_tracks WHERE name='Sunday School Curriculum'")->fetchColumn();
$levels = [];   // sort_order => ['id'=>, 'name'=>]
foreach ($pdo->query("SELECT id,name,sort_order FROM class_levels WHERE track_id=$trackId AND is_archived=0 ORDER BY sort_order") as $r) {
    $levels[(int)$r['sort_order']] = ['id' => (int)$r['id'], 'name' => $r['name']];
}

// subjects by a friendly key
$subjName = [
    'geez'    => "Ge'ez",
    'history' => 'History of Orthodoxy & the EOTC',
    'faith'   => 'Faith (Negere Haymanot)',
    'order'   => 'Church Order (Sireate Bete-Kristian)',
    'qidusan' => 'On the Saints (Negere Qidusan)',
    'abinet'  => 'Traditional Church Schooling (Abinet)',
    'mariam'  => 'On St. Mary (Negere Mariam)',
    'kristos' => 'On Christ (Negere Kristos)',
];
$subjId = [];
foreach ($subjName as $k => $nm) {
    $id = $pdo->query("SELECT id FROM subjects WHERE name=" . $pdo->quote($nm) . " LIMIT 1")->fetchColumn();
    if ($id) $subjId[$k] = (int)$id;
}

$deptId = [];
foreach ($pdo->query("SELECT id,slug FROM departments WHERE is_archived=0") as $r) $deptId[$r['slug']] = (int)$r['id'];

$choirLevel = []; // name => id
foreach ($pdo->query("SELECT id,name FROM department_levels WHERE department_id={$deptId['mezmur']} AND is_archived=0") as $r) $choirLevel[$r['name']] = (int)$r['id'];

// ---------------------------------------------------------------------------
// 2) Academic terms (Term I is current)
// ---------------------------------------------------------------------------
$termI = insert($pdo, 'academic_terms', [
    'name' => 'Term I', 'academic_year' => '2018', 'start_date' => '2025-10-05',
    'end_date' => '2026-02-07', 'default_tuition' => 300.00, 'is_current' => 1,
]);
insert($pdo, 'academic_terms', [
    'name' => 'Term II', 'academic_year' => '2018', 'start_date' => '2026-02-14',
    'end_date' => '2026-06-27', 'default_tuition' => 300.00, 'is_current' => 0,
]);
echo "  terms: Term I (current) + Term II\n";

// ---------------------------------------------------------------------------
// 3) Classes — one "Section A" per grade for academic year 2018
// ---------------------------------------------------------------------------
$classOfGrade = []; // sort_order => class_id
foreach ($levels as $sort => $lvl) {
    $classOfGrade[$sort] = insert($pdo, 'classes', [
        'level_id' => $lvl['id'], 'academic_year' => '2018', 'name' => 'Section A',
    ]);
}
echo "  classes: " . count($classOfGrade) . " (one section per grade)\n";

// ---------------------------------------------------------------------------
// 4) grade_subjects — curriculum mapping (grows with grade level)
// ---------------------------------------------------------------------------
$gradeSubjects = []; // sort_order => [subject_id,...]
foreach ($levels as $sort => $lvl) {
    $keys = ['faith', 'order', 'abinet'];          // core for all grades
    if ($sort >= 4) $keys[] = 'geez';
    if ($sort >= 6) $keys[] = 'history';
    if ($sort >= 7) $keys[] = 'qidusan';
    if ($sort >= 8) $keys[] = 'mariam';
    if ($sort >= 9) $keys[] = 'kristos';
    $ids = [];
    $o = 0;
    foreach ($keys as $k) {
        if (!isset($subjId[$k])) continue;
        insert($pdo, 'grade_subjects', ['level_id' => $lvl['id'], 'subject_id' => $subjId[$k], 'sort_order' => $o++]);
        $ids[] = $subjId[$k];
    }
    $gradeSubjects[$sort] = $ids;
}
echo "  grade_subjects mapped for all grades\n";

// ---------------------------------------------------------------------------
// 5) Name pools + person helper
// ---------------------------------------------------------------------------
$firsts = ['Abel','Bethlehem','Dawit','Eyob','Frehiwot','Hanna','Israel','Kidist','Lidya','Mahlet',
           'Nahom','Robel','Saron','Tewodros','Yohannes','Selam','Mihret','Bisrat','Kaleb','Ruth',
           'Henok','Marta','Samuel','Tsion','Yeabsira','Amanuel','Bezawit','Dagmawi','Eden','Fitsum',
           'Girma','Helen','Naod','Rahel','Yared','Meron','Surafel','Hiwot','Biruk','Eyerusalem'];
$lasts  = ['Tesfaye','Bekele','Alemu','Girma','Haile','Solomon','Tadesse','Mengistu','Worku','Assefa',
           'Desta','Negash','Kebede','Abebe','Tafesse','Gebre','Yilma','Demissie','Wolde','Mekonnen'];
$baptismal = ['ገብረ ማርያም','ወልደ ሚካኤል','ኃይለ ስላሴ','ገብረ እግዚአብሔር','ወለተ ማርያም','ተክለ ሃይማኖት',
              'ገብረ መንፈስ ቅዱስ','ወልደ ገብርኤል','ብርሃነ መስቀል','ፍቅረ ማርያም','ወለተ ሰንበት','ገብረ ኪዳን'];
$ni = 0; $bi = 0;
function nextName(&$ni, $firsts, $lasts) {
    $f = $firsts[$ni % count($firsts)];
    $l = $lasts[intdiv($ni, count($firsts)) % count($lasts) + ($ni % count($lasts))];
    $l = $lasts[$ni % count($lasts)];
    $ni++;
    return [$f, $l];
}

$createPerson = function(array $over = []) use ($pdo, &$ni, $firsts, $lasts, $baptismal, &$bi, $churchIds) {
    [$f, $l] = nextName($ni, $firsts, $lasts);
    $data = [
        'first_name' => $over['first_name'] ?? $f,
        'last_name'  => $over['last_name'] ?? $l,
        'baptismal_name' => $over['baptismal_name'] ?? (chance(70) ? $baptismal[$bi++ % count($baptismal)] : null),
        'date_of_birth'  => $over['date_of_birth'] ?? null,
        'gender'     => $over['gender'] ?? (chance(50) ? 'male' : 'female'),
        'phone'      => $over['phone'] ?? ('09' . str_pad((string)mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT)),
        'address'    => $over['address'] ?? pick(['Kotebe','Megenagna','Bole','Gerji','Summit','CMC']),
        'primary_church_id' => $over['primary_church_id'] ?? pick($churchIds),
        'member_status' => $over['member_status'] ?? 'active',
        'joined_at'  => $over['joined_at'] ?? null,
        'last_communion_date' => $over['last_communion_date'] ?? (chance(65) ? sprintf('2026-%02d-%02d', mt_rand(1,6), mt_rand(1,28)) : null),
        'notes' => $over['notes'] ?? null,
    ];
    return insert($pdo, 'people', $data);
};

$emailN = 1;
$createLogin = function(string $role) use ($pdo, &$emailN, $roleId, $DEMO_PW) {
    $email = $role . $emailN++ . '@demo.gebriel';
    return insert($pdo, 'users', ['email' => $email, 'password_hash' => $DEMO_PW, 'role_id' => $roleId[$role]]);
};

// ---------------------------------------------------------------------------
// 6) Teachers (person + login + teacher row)
// ---------------------------------------------------------------------------
$teachers = []; // ['person_id','teacher_id','name']
for ($i = 0; $i < 7; $i++) {
    $pid = $createPerson(['member_status' => 'active', 'joined_at' => '2015-09-01']);
    $p = $pdo->query("SELECT first_name,last_name FROM people WHERE id=$pid")->fetch();
    $uid = $createLogin('teacher');
    $tid = insert($pdo, 'teachers', [
        'user_id' => $uid, 'person_id' => $pid,
        'first_name' => $p['first_name'], 'last_name' => $p['last_name'],
        'phone' => '0911' . str_pad((string)mt_rand(100000,999999),6,'0',STR_PAD_LEFT),
        'bio' => 'Serving the Sunday school faithfully.',
    ]);
    $teachers[] = ['person_id' => $pid, 'teacher_id' => $tid, 'name' => $p['first_name'].' '.$p['last_name']];
}
echo "  teachers: " . count($teachers) . "\n";

// ---------------------------------------------------------------------------
// 7) Students (person + login + student row + enrollment)
//    Distributed across grades 1–11; grades 7–11 weighted a bit heavier.
// ---------------------------------------------------------------------------
$gradePlan = [1,2,3,4,5,6,7,7,8,9,9,10,10,11,11,11];
$students = []; // ['person_id','student_id','grade','class_id','name']
foreach ($gradePlan as $grade) {
    $age = 6 + $grade + mt_rand(0,1);
    $dob = sprintf('%04d-%02d-%02d', 2026 - $age, mt_rand(1,12), mt_rand(1,28));
    $pid = $createPerson(['member_status' => 'active', 'date_of_birth' => $dob, 'joined_at' => '2024-09-01']);
    $p = $pdo->query("SELECT first_name,last_name FROM people WHERE id=$pid")->fetch();
    $uid = $createLogin('student');
    $sid = insert($pdo, 'students', [
        'user_id' => $uid, 'person_id' => $pid,
        'first_name' => $p['first_name'], 'last_name' => $p['last_name'],
        'date_of_birth' => $dob,
        'guardian_name' => pick($lasts) . ' (guardian)',
        'phone' => '09' . str_pad((string)mt_rand(10000000,99999999),8,'0',STR_PAD_LEFT),
        'address' => pick(['Kotebe','Megenagna','Bole','Gerji']),
    ]);
    insert($pdo, 'student_class_assignments', [
        'student_id' => $sid, 'class_id' => $classOfGrade[$grade], 'assigned_at' => '2025-10-05',
    ]);
    $students[] = ['person_id' => $pid, 'student_id' => $sid, 'grade' => $grade, 'class_id' => $classOfGrade[$grade], 'name' => $p['first_name'].' '.$p['last_name']];
}
echo "  students: " . count($students) . " (enrolled)\n";

// ---------------------------------------------------------------------------
// 8) Standalone members/staff (person only — no student/teacher login)
// ---------------------------------------------------------------------------
$members = [];
for ($i = 0; $i < 12; $i++) {
    $members[] = $createPerson(['member_status' => chance(85) ? 'active' : pick(['alumni','inactive']), 'joined_at' => '2012-09-01']);
}
echo "  members/staff: " . count($members) . "\n";

// ---------------------------------------------------------------------------
// 9) Department memberships — every department gets a head + members
// ---------------------------------------------------------------------------
$addMember = function(int $personId, int $deptId, ?int $levelId = null, ?string $title = null, bool $head = false) use ($pdo) {
    // skip dup
    $ex = $pdo->prepare("SELECT id FROM department_memberships WHERE person_id=? AND department_id=? AND is_archived=0");
    $ex->execute([$personId, $deptId]);
    if ($ex->fetch()) return;
    insert($pdo, 'department_memberships', [
        'person_id' => $personId, 'department_id' => $deptId, 'level_id' => $levelId,
        'title' => $title, 'is_head' => $head ? 1 : 0, 'joined_at' => '2018-01-01',
    ]);
};

// Education (ትምህርት) — head = a teacher; members = teachers + a top grade-11 student (instructor cycle)
$addMember($teachers[0]['person_id'], $deptId['timhirt'], null, 'Department Head', true);
$addMember($teachers[1]['person_id'], $deptId['timhirt'], null, 'Curriculum Coordinator');
$addMember($teachers[2]['person_id'], $deptId['timhirt'], null, 'Teacher Recruiter');
$topStudent = end($students); // a grade-11 student
$addMember($topStudent['person_id'], $deptId['timhirt'], null, 'Student Instructor (lower grades)');

// Choir (መዝሙር) — head is a Regular Servant; members across all 4 levels (mix of senior students + members)
$choirPeople = [
    [$members[0], 'Regular Servant', true,  'Head Servant'],
    [$members[1], 'Regular Servant', false, 'Servant'],
    [$students[13]['person_id'], 'Successor 1', false, null],   // grade 11 student
    [$students[11]['person_id'], 'Successor 1', false, null],   // grade 10
    [$students[8]['person_id'],  'Successor 2', false, null],   // grade 8
    [$students[6]['person_id'],  'Newcomer',    false, null],   // grade 7
    [$members[2], 'Successor 2', false, null],
];
foreach ($choirPeople as $cp) {
    $lvlId = $choirLevel[$cp[1]] ?? null;
    $addMember(is_array($cp[0]) ? $cp[0] /*shouldn't happen*/ : $cp[0], $deptId['mezmur'], $lvlId, $cp[3], $cp[2]);
}

// Outreach umbrella + subs
$addMember($members[3], $deptId['outreach'], null, 'Department Head', true);
$addMember($members[4], $deptId['limat'], null, 'Sub-head (Development)', true);
$addMember($members[5], $deptId['limat'], null, 'Shop Coordinator');
$addMember($members[6], $deptId['guzo'], null, 'Sub-head (Travel)', true);
$addMember($students[9]['person_id'], $deptId['guzo'], null, 'Trip Volunteer');
$addMember($members[7], $deptId['bego-adragot'], null, 'Sub-head (Charity)', true);
$addMember($students[4]['person_id'], $deptId['bego-adragot'], null, 'Volunteer');
$addMember($students[5]['person_id'], $deptId['bego-adragot'], null, 'Volunteer');

// Fine Arts (ኪነጥበብ)
$addMember($members[8], $deptId['kinetbeb'], null, 'Department Head', true);
$addMember($students[13]['person_id'], $deptId['kinetbeb'], null, 'Poet / Performer'); // also choir → shows multi-dept
$addMember($students[10]['person_id'], $deptId['kinetbeb'], null, 'Performer');

// Audio & Visual
$addMember($members[9], $deptId['av'], null, 'Department Head', true);
$addMember($teachers[3]['person_id'], $deptId['av'], null, 'Social Media Manager');

// Board of Admins
$addMember($members[10], $deptId['board'], null, 'Chairperson', true);
$addMember($teachers[0]['person_id'], $deptId['board'], null, 'Board Member'); // teacher also on board
$addMember($members[11], $deptId['board'], null, 'Board Member');

// Secretariat
$addMember($members[0], $deptId['secretariat'], null, 'Secretary', true); // choir head also secretary

// Construction Committee
$addMember($members[4], $deptId['construction'], null, 'Committee Head', true);
$addMember($members[6], $deptId['construction'], null, 'Member');

// Parents' Committee (ወላጆች ኮሚቴ) — dedicated parent members
$pc1 = $createPerson(['joined_at' => '2019-09-01']);
$pc2 = $createPerson(['joined_at' => '2020-09-01']);
$pc3 = $createPerson(['joined_at' => '2021-09-01']);
$addMember($pc1, $deptId['parents'], null, 'Committee Chair', true);
$addMember($pc2, $deptId['parents'], null, 'Member');
$addMember($pc3, $deptId['parents'], null, 'Member');

echo "  department memberships seeded (every department has a head)\n";

// Grant a 'staff' login to each department head so the staff portal is testable.
// Email pattern: head-<slug>@demo.gebriel (password demo1234). One login per person.
$staffHeadLogins = [];
$staffRoleId = $roleId['staff'] ?? null;
if ($staffRoleId) {
    $heads = $pdo->query("SELECT dm.person_id, d.slug
                          FROM department_memberships dm
                          JOIN departments d ON d.id = dm.department_id
                          WHERE dm.is_head = 1 AND dm.is_archived = 0
                          ORDER BY dm.id")->fetchAll();
    foreach ($heads as $h) {
        $pid = (int)$h['person_id'];
        $cur = $pdo->query("SELECT user_id FROM people WHERE id=$pid")->fetch();
        if ($cur && $cur['user_id']) continue; // person already has a login (heads >1 dept)
        $email = 'head-' . $h['slug'] . '@demo.gebriel';
        $uid = insert($pdo, 'users', ['email' => $email, 'password_hash' => $DEMO_PW, 'role_id' => $staffRoleId]);
        $pdo->prepare('UPDATE people SET user_id=? WHERE id=?')->execute([$uid, $pid]);
        $staffHeadLogins[] = $email;
    }
}
echo "  staff (dept-head) logins: " . count($staffHeadLogins) . "\n";

// ---------------------------------------------------------------------------
// 10) Grades — for each enrolled student, score every subject in their grade
// ---------------------------------------------------------------------------
$gradeCount = 0;
foreach ($students as $st) {
    foreach (($gradeSubjects[$st['grade']] ?? []) as $subId) {
        $score = mt_rand(58, 98) + (mt_rand(0,1) ? 0.5 : 0);
        $remark = $score >= 90 ? 'Excellent' : ($score >= 75 ? 'Very good' : ($score >= 60 ? 'Good' : 'Needs improvement'));
        insert($pdo, 'grades', [
            'student_id' => $st['student_id'], 'subject_id' => $subId, 'class_id' => $st['class_id'],
            'term_id' => $termI, 'score' => $score, 'remarks' => $remark,
        ]);
        $gradeCount++;
    }
}
echo "  grades: $gradeCount (Term I)\n";

// ---------------------------------------------------------------------------
// 11) Teacher assignments — spread teachers over classes & subjects
// ---------------------------------------------------------------------------
$assignCount = 0;
foreach ($students as $st) {
    // ensure each class has assignments for its subjects (dedup by class+subject)
}
$seenCS = [];
foreach ($classOfGrade as $sort => $classId) {
    foreach (($gradeSubjects[$sort] ?? []) as $idx => $subId) {
        $key = $classId . '-' . $subId;
        if (isset($seenCS[$key])) continue;
        $seenCS[$key] = true;
        $t = $teachers[($sort + $idx) % count($teachers)];
        insert($pdo, 'teacher_subject_assignments', [
            'teacher_id' => $t['teacher_id'], 'class_id' => $classId, 'subject_id' => $subId,
            'role' => 'primary', 'start_date' => '2025-10-05',
        ]);
        $assignCount++;
    }
}
echo "  teacher assignments: $assignCount\n";

// ---------------------------------------------------------------------------
// 12) Payments — one per student for the current term (mixed status)
// ---------------------------------------------------------------------------
foreach ($students as $st) {
    $roll = mt_rand(1, 100);
    if ($roll <= 60)      { $status = 'paid';    $paid = 300.00; }
    elseif ($roll <= 80)  { $status = 'partial'; $paid = 150.00; }
    else                  { $status = 'unpaid';  $paid = 0.00; }
    insert($pdo, 'payments', [
        'student_id' => $st['student_id'], 'term_id' => $termI, 'amount' => 300.00,
        'paid_amount' => $paid, 'status' => $status,
        'notes' => $status === 'partial' ? 'Half paid' : null,
    ]);
}
echo "  payments: " . count($students) . " (Term I)\n";

// ---------------------------------------------------------------------------
// 13) Parents — 2 parent logins linked to students (parent portal testable)
// ---------------------------------------------------------------------------
$adminUserId = (int)$pdo->query("SELECT id FROM users WHERE role_id={$roleId['admin']} ORDER BY id LIMIT 1")->fetchColumn();
for ($i = 0; $i < 2; $i++) {
    $uid = $createLogin('parent');
    // link 2 students each
    $kids = array_slice($students, $i * 2, 2);
    foreach ($kids as $k => $kid) {
        insert($pdo, 'student_guardians', [
            'user_id' => $uid, 'student_id' => $kid['student_id'],
            'relationship' => 'parent', 'is_primary' => $k === 0 ? 1 : 0,
        ]);
    }
}
$parentEmails = $pdo->query("SELECT email FROM users WHERE role_id={$roleId['parent']} ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
echo "  parents: " . count($parentEmails) . " (linked to students)\n";

// ---------------------------------------------------------------------------
// 14) Announcements (public) + holiday events
// ---------------------------------------------------------------------------
$announce = [
    ['Welcome to the 2018 School Year', 'Registration for all grades is now open. Classes begin the first Sunday of ጥቅምት.'],
    ['Choir Service Schedule Posted', 'The መዝሙር department serving roster for the upcoming holidays is now available.'],
    ['Tuition Reminder — Term I', 'Families are kindly reminded to settle Term I contributions at the secretariat.'],
    ['Pilgrimage to Historic Sites', 'The ጉዞ department is organizing a blessed trip; sign up with your group leader.'],
];
foreach ($announce as $a) {
    insert($pdo, 'notifications', [
        'sender_user_id' => $adminUserId, 'sender_role_id' => $roleId['admin'],
        'target_type' => 'role', 'target_payload' => json_encode(['role' => 'student']),
        'title' => $a[0], 'message' => $a[1], 'is_public' => 1,
    ]);
}
$events = [
    ['Feast of St. Gabriel (ቅዱስ ገብርኤል)', 'Major celebration; the choir serves at both churches.', '2026-07-26 06:00:00', '2026-07-26 12:00:00'],
    ['Hidar Tsion (ህዳር ጽዮን)', 'Commemoration of St. Mary of Zion.', '2026-11-30 06:00:00', '2026-11-30 11:00:00'],
];
foreach ($events as $e) {
    insert($pdo, 'events', [
        'title' => $e[0], 'description' => $e[1], 'start_datetime' => $e[2], 'end_datetime' => $e[3], 'is_recurring' => 0,
    ]);
}
echo "  announcements: " . count($announce) . " · holiday events: " . count($events) . "\n";

// ---------------------------------------------------------------------------
// 15) Phase B — holidays, serving assignments, attendance
// ---------------------------------------------------------------------------
$holidayRows = [
    ['Timket (Epiphany)', 'ጥምቀት', '2026-01-19', 'major'],
    ['Meskel (Finding of the True Cross)', 'መስቀል', '2025-09-27', 'major'],
    ['Genna (Nativity)', 'ገና', '2026-01-07', 'major'],
    ['Feast of St. Gabriel', 'ቅዱስ ገብርኤል', '2026-07-26', 'major'],
    ['Buhe', 'ቡሄ', '2025-08-19', 'minor'],
    ['Hidar Tsion', 'ሕዳር ጽዮን', '2025-11-30', 'minor'],
];
$holidayIds = [];
foreach ($holidayRows as $h) {
    $holidayIds[$h[1]] = insert($pdo, 'holidays', ['name' => $h[0], 'name_am' => $h[1], 'holiday_date' => $h[2], 'scale' => $h[3], 'is_recurring_annually' => 1]);
}
// Serving assignments: choir (mezmur) serves the majors. Regular Servants lead
// the big feasts (both churches); Successors serve smaller ones with seniors.
$mezmur = $deptId['mezmur'];
$lvlRegular = $choirLevel['Regular Servant'] ?? null;
$lvlT1 = $choirLevel['Successor 1'] ?? null;
$serv = [
    ['ቅዱስ ገብርኤል', $lvlRegular, $churchIds[0], 0],
    ['ቅዱስ ገብርኤል', $lvlRegular, $churchIds[1], 0],
    ['ጥምቀት',       $lvlRegular, null, 0],
    ['መስቀል',       $lvlT1, null, 1],
    ['ገና',         $lvlT1, $churchIds[0], 1],
];
$servCount = 0;
foreach ($serv as $s) {
    if (!isset($holidayIds[$s[0]])) continue;
    insert($pdo, 'serving_assignments', ['holiday_id' => $holidayIds[$s[0]], 'department_id' => $mezmur, 'level_id' => $s[1], 'church_id' => $s[2], 'with_seniors' => $s[3]]);
    $servCount++;
}
echo "  holidays: " . count($holidayIds) . " · serving assignments: $servCount\n";

// Attendance: a few class roll-calls + choir service roll-calls.
$attStatuses = ['present','present','present','present','late','absent','excused'];
$attSessions = 0; $attRecords = 0;
// class sessions for every grade (so academic attendance — and thus serving
// eligibility — has data for all enrolled students)
foreach (array_keys($classOfGrade) as $g) {
    foreach (['2025-10-12','2025-10-19','2025-10-26'] as $date) {
        $sid = insert($pdo, 'attendance_sessions', ['context_type' => 'class', 'context_id' => $classOfGrade[$g], 'session_date' => $date, 'title' => 'Sunday class']);
        $attSessions++;
        foreach ($students as $st) {
            if ($st['grade'] !== $g) continue;
            insert($pdo, 'attendance_records', ['session_id' => $sid, 'person_id' => $st['person_id'], 'status' => pick($attStatuses)]);
            $attRecords++;
        }
    }
}
// choir service roll-calls
$choirMembers = $pdo->query("SELECT person_id FROM department_memberships WHERE department_id=$mezmur AND is_archived=0")->fetchAll(PDO::FETCH_COLUMN);
foreach (['2025-09-27','2026-01-07'] as $date) {
    $sid = insert($pdo, 'attendance_sessions', ['context_type' => 'department', 'context_id' => $mezmur, 'session_date' => $date, 'title' => 'Holiday service', 'church_id' => $churchIds[0]]);
    $attSessions++;
    foreach ($choirMembers as $pid) {
        insert($pdo, 'attendance_records', ['session_id' => $sid, 'person_id' => (int)$pid, 'status' => pick($attStatuses)]);
        $attRecords++;
    }
}
echo "  attendance: $attSessions sessions, $attRecords records\n";

echo "\nDEMO SEED COMPLETE.\n";
echo "Demo logins (password: demo1234):\n";
echo "  teachers: teacher1..teacher" . count($teachers) . "@demo.gebriel\n";
echo "  students: student1..student" . count($students) . "@demo.gebriel\n";
echo "  parents:  " . implode(', ', $parentEmails) . "\n";
echo "  staff (dept heads): " . implode(', ', $staffHeadLogins) . "\n";
echo "  admin (unchanged): admin@local.test / admin1234\n";
