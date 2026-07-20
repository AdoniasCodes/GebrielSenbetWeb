<?php
// api/teacher/attendance/index.php — class roll-call for the teacher.
//   GET  ?class_id=&date=  -> roster with each student's status for that day
//   POST {class_id,date,records:[{person_id,status}]} -> save the roll-call
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_once __DIR__ . '/../../attendance_lib.php';
require_csrf_for_write();

$pdo = tch_pdo();
$method = $_SERVER['REQUEST_METHOD'];
$VALID = ['present','absent','late','excused'];

function tch_find_session(PDO $pdo, int $classId, string $date): ?int {
    $st = $pdo->prepare("SELECT id FROM attendance_sessions
                         WHERE context_type='class' AND context_id=? AND session_date=? AND is_archived=0
                         ORDER BY id DESC LIMIT 1");
    $st->execute([$classId, $date]);
    $id = $st->fetchColumn();
    return $id ? (int)$id : null;
}

if ($method === 'GET') {
    $classId = (int)($_GET['class_id'] ?? 0);
    $date = $_GET['date'] ?? '';
    if ($classId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) Response::error('class_id and date are required', 422);
    teacher_assert_class($classId);

    $sessionId = tch_find_session($pdo, $classId, $date);
    $existing = [];
    if ($sessionId) {
        $r = $pdo->prepare("SELECT person_id, status FROM attendance_records WHERE session_id=?");
        $r->execute([$sessionId]);
        foreach ($r->fetchAll() as $row) $existing[(int)$row['person_id']] = $row['status'];
    }
    $st = $pdo->prepare(
        "SELECT s.id AS student_id, s.person_id, s.first_name, s.last_name
         FROM student_class_assignments sca
         JOIN students s ON s.id=sca.student_id AND s.is_archived=0
         WHERE sca.class_id=? AND sca.is_archived=0
         GROUP BY s.id ORDER BY s.first_name, s.last_name");
    $st->execute([$classId]);
    $roster = array_map(function ($r) use ($existing) {
        $pid = $r['person_id'] !== null ? (int)$r['person_id'] : null;
        $r['status'] = ($pid && isset($existing[$pid])) ? $existing[$pid] : 'present';
        return $r;
    }, $st->fetchAll());
    Response::json(['session_id' => $sessionId, 'date' => $date, 'roster' => $roster]);
}

if ($method === 'POST') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $classId = (int)($in['class_id'] ?? 0);
    $date = $in['date'] ?? '';
    $records = $in['records'] ?? [];
    if ($classId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) Response::error('class_id and date are required', 422);
    teacher_assert_class($classId);
    if (!is_array($records) || !$records) Response::error('No records to save', 422);

    // Only people actually enrolled in this class may be marked — attendance
    // feeds the serving-eligibility engine, so reject smuggled person_ids.
    $al = $pdo->prepare("SELECT DISTINCT s.person_id
                         FROM student_class_assignments sca
                         JOIN students s ON s.id=sca.student_id AND s.is_archived=0
                         WHERE sca.class_id=? AND sca.is_archived=0 AND s.person_id IS NOT NULL");
    $al->execute([$classId]);
    $allowed = array_flip(array_map('intval', $al->fetchAll(\PDO::FETCH_COLUMN)));

    $valid = [];
    foreach ($records as $rec) {
        $pid = (int)($rec['person_id'] ?? 0);
        $status = $rec['status'] ?? '';
        if ($pid <= 0 || !isset($allowed[$pid]) || !in_array($status, $VALID, true)) continue;
        $valid[$pid] = $status; // last status wins per person
    }
    if (!$valid) Response::error('No valid records for this class', 422);

    $sessionId = tch_find_session($pdo, $classId, $date);
    if (!$sessionId) {
        $termId = attendance_term_for_date($pdo, $date); // Phase 2.3: term from the date
        $ins = $pdo->prepare("INSERT INTO attendance_sessions (context_type, context_id, session_date, term_id, created_by_user_id)
                              VALUES ('class', ?, ?, ?, ?)");
        $ins->execute([$classId, $date, $termId, (int)($_SESSION['user_id'] ?? 0)]);
        $sessionId = (int)$pdo->lastInsertId();
    }
    $up = $pdo->prepare("INSERT INTO attendance_records (session_id, person_id, status) VALUES (?,?,?)
                         ON DUPLICATE KEY UPDATE status=VALUES(status)");
    $saved = 0;
    foreach ($valid as $pid => $status) {
        $up->execute([$sessionId, $pid, $status]);
        $saved++;
    }
    Response::json(['message' => 'Saved', 'session_id' => $sessionId, 'saved' => $saved]);
}

Response::error('Method not allowed', 405);
