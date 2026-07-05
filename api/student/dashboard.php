<?php
// api/student/dashboard.php — everything the logged-in student sees, in one call:
// profile + current class, grades, attendance summary, payments, announcements.
use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') Response::error('Method not allowed', 405);

$me = student_record();
if (!$me) {
    Response::json([
        'profile' => null,
        'grades' => [], 'attendance' => null, 'payments' => [],
        'payment_totals' => ['expected'=>0,'paid'=>0,'outstanding'=>0],
        'announcements' => [],
        'message' => 'No student record is linked to this account yet.',
    ]);
}

$pdo = (new Database(app_config()['db']))->pdo();
$sid = (int)$me['id'];
$pid = (int)($me['person_id'] ?? 0);

// Current class / level / track (most recent active assignment).
$cstmt = $pdo->prepare(
    "SELECT c.id AS class_id, c.name AS class_name, c.academic_year,
            lvl.name AS level_name, lvl.name_am AS level_name_am, lvl.alias AS level_alias,
            tr.name AS track_name
     FROM student_class_assignments sca
     JOIN classes c ON c.id=sca.class_id
     LEFT JOIN class_levels lvl ON lvl.id=c.level_id
     LEFT JOIN education_tracks tr ON tr.id=lvl.track_id
     WHERE sca.student_id=? AND sca.is_archived=0
     ORDER BY sca.id DESC LIMIT 1");
$cstmt->execute([$sid]);
$class = $cstmt->fetch() ?: null;

$profile = [
    'first_name' => $me['first_name'], 'last_name' => $me['last_name'],
    'date_of_birth' => $me['date_of_birth'], 'guardian_name' => $me['guardian_name'],
    'phone' => $me['phone'], 'class' => $class,
];

// Grades (own).
$gstmt = $pdo->prepare(
    "SELECT g.id, g.score, g.remarks, g.created_at,
            sub.name AS subject_name, sub.name_am AS subject_name_am,
            c.name AS class_name,
            t.name AS term_name, t.academic_year, t.is_current
     FROM grades g
     JOIN subjects sub ON sub.id=g.subject_id
     JOIN classes c ON c.id=g.class_id
     JOIN academic_terms t ON t.id=g.term_id
     WHERE g.is_archived=0 AND g.student_id=?
     ORDER BY t.academic_year DESC, t.start_date DESC, sub.name");
$gstmt->execute([$sid]);
$grades = $gstmt->fetchAll();

// Attendance summary from class roll-calls (person-based).
$attendance = ['present'=>0,'late'=>0,'absent'=>0,'excused'=>0,'rate'=>null];
if ($pid > 0) {
    $astmt = $pdo->prepare(
        "SELECT ar.status, COUNT(*) AS cnt
         FROM attendance_records ar
         JOIN attendance_sessions s ON s.id=ar.session_id
              AND s.context_type='class' AND s.is_archived=0
         WHERE ar.person_id=?
         GROUP BY ar.status");
    $astmt->execute([$pid]);
    foreach ($astmt->fetchAll() as $row) {
        $attendance[$row['status']] = (int)$row['cnt'];
    }
    $counted = $attendance['present'] + $attendance['late'] + $attendance['absent'];
    if ($counted > 0) {
        $attendance['rate'] = round(($attendance['present'] + $attendance['late']) * 100.0 / $counted, 1);
    }
}

// Payments (own).
$pstmt = $pdo->prepare(
    "SELECT p.id, p.amount, p.paid_amount, p.status, p.notes,
            t.name AS term_name, t.academic_year
     FROM payments p
     JOIN academic_terms t ON t.id=p.term_id
     WHERE p.is_archived=0 AND p.student_id=?
     ORDER BY t.academic_year DESC, t.start_date DESC");
$pstmt->execute([$sid]);
$payments = $pstmt->fetchAll();
$expected = 0.0; $paid = 0.0;
foreach ($payments as $p) { $expected += (float)$p['amount']; $paid += (float)$p['paid_amount']; }

// Announcements: public + targeted at the student role + department-targeted
// (for departments this student belongs to, via their linked person) +
// class-targeted (for their current active class).
$deptIds = [];
if ($pid > 0) {
    $dstmt = $pdo->prepare(
        "SELECT DISTINCT department_id FROM department_memberships
         WHERE person_id=? AND is_archived=0 AND (ended_at IS NULL OR ended_at >= CURDATE())"
    );
    $dstmt->execute([$pid]);
    $deptIds = array_map('intval', $dstmt->fetchAll(\PDO::FETCH_COLUMN));
}
$classId = $class ? (int)$class['class_id'] : 0;

$annSql = "SELECT id, title, message, target_type, target_payload, created_at
     FROM notifications
     WHERE is_archived=0
       AND (
         is_public=1
         OR (target_type='role' AND JSON_EXTRACT(target_payload,'$.role')='student')";
$annParams = [];
if ($deptIds) {
    $ph = implode(',', array_fill(0, count($deptIds), '?'));
    // JSON_UNQUOTE, not a bare JSON_EXTRACT comparison: PDO sends the bound
    // param as a typed value (ATTR_EMULATE_PREPARES=false), which a raw
    // JSON_EXTRACT scalar won't match (see api/teacher/notifications.php).
    $annSql .= " OR (target_type='department' AND JSON_UNQUOTE(JSON_EXTRACT(target_payload,'$.department_id')) IN ($ph))";
    $annParams = array_merge($annParams, $deptIds);
}
if ($classId > 0) {
    $annSql .= " OR (target_type='class' AND JSON_UNQUOTE(JSON_EXTRACT(target_payload,'$.class_id')) = ?)";
    $annParams[] = $classId;
}
$annSql .= ") ORDER BY created_at DESC LIMIT 50";
$astmt2 = $pdo->prepare($annSql);
$astmt2->execute($annParams);
$ann = $astmt2->fetchAll();
foreach ($ann as &$a) {
    unset($a['target_type'], $a['target_payload']);
}
unset($a);

Response::json([
    'profile' => $profile,
    'grades' => $grades,
    'attendance' => $attendance,
    'payments' => $payments,
    'payment_totals' => ['expected'=>$expected,'paid'=>$paid,'outstanding'=>$expected-$paid],
    'announcements' => $ann,
]);
