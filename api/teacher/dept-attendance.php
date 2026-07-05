<?php
// api/teacher/dept-attendance.php — department-wide roll-call (context_type='department').
//   GET  ?department_id=&date=  -> dept roster with each person's status for that
//                                  day, plus the department's recent sessions
//   POST {department_id, session_date, title?, records:[{person_id,status,notes?}]}
//        -> create/update the day's session and upsert the given records
use App\Utils\Response;

require_once __DIR__ . '/_guard.php';
require_csrf_for_write();

$pdo = tch_pdo();
$method = $_SERVER['REQUEST_METHOD'];
$VALID = ['present', 'absent', 'late', 'excused'];

function tda_find_session(PDO $pdo, int $deptId, string $date): ?int {
    $st = $pdo->prepare(
        "SELECT id FROM attendance_sessions
         WHERE context_type='department' AND context_id=? AND session_date=? AND is_archived=0
         ORDER BY id DESC LIMIT 1"
    );
    $st->execute([$deptId, $date]);
    $id = $st->fetchColumn();
    return $id ? (int)$id : null;
}

function tda_roster(PDO $pdo, int $deptId): array {
    $st = $pdo->prepare(
        'SELECT s.id AS student_id, s.person_id, s.first_name, s.last_name
         FROM department_memberships dm
         JOIN students s ON s.person_id = dm.person_id AND s.is_archived = 0
         WHERE dm.department_id = ? AND dm.is_archived = 0
           AND (dm.ended_at IS NULL OR dm.ended_at >= CURDATE())
         ORDER BY s.first_name, s.last_name'
    );
    $st->execute([$deptId]);
    return $st->fetchAll();
}

if ($method === 'GET') {
    $deptId = (int)($_GET['department_id'] ?? 0);
    $date = $_GET['date'] ?? '';
    if ($deptId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) Response::error('department_id and date are required', 422);
    teacher_assert_department($deptId);

    $sessionId = tda_find_session($pdo, $deptId, $date);
    $existing = [];
    if ($sessionId) {
        $r = $pdo->prepare('SELECT person_id, status FROM attendance_records WHERE session_id=?');
        $r->execute([$sessionId]);
        foreach ($r->fetchAll() as $row) $existing[(int)$row['person_id']] = $row['status'];
    }
    $roster = array_map(function ($r) use ($existing) {
        $pid = (int)$r['person_id'];
        $r['status'] = $existing[$pid] ?? 'present';
        return $r;
    }, tda_roster($pdo, $deptId));

    $sess = $pdo->prepare(
        "SELECT id, session_date, title FROM attendance_sessions
         WHERE context_type='department' AND context_id=? AND is_archived=0
         ORDER BY session_date DESC LIMIT 20"
    );
    $sess->execute([$deptId]);

    Response::json(['session_id' => $sessionId, 'date' => $date, 'roster' => $roster, 'sessions' => $sess->fetchAll()]);
}

if ($method === 'POST') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $deptId = (int)($in['department_id'] ?? 0);
    $date = $in['session_date'] ?? ($in['date'] ?? '');
    $title = isset($in['title']) && trim((string)$in['title']) !== '' ? trim((string)$in['title']) : null;
    $records = $in['records'] ?? [];
    if ($deptId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date)) Response::error('department_id and session_date are required', 422);
    teacher_assert_department($deptId);
    if (!is_array($records) || !$records) Response::error('No records to save', 422);

    // Only people who are actual (student) members of this department may be
    // marked — reject smuggled person_ids, same discipline as class attendance.
    $al = $pdo->prepare(
        'SELECT DISTINCT s.person_id
         FROM department_memberships dm
         JOIN students s ON s.person_id = dm.person_id AND s.is_archived = 0
         WHERE dm.department_id=? AND dm.is_archived=0
           AND (dm.ended_at IS NULL OR dm.ended_at >= CURDATE())'
    );
    $al->execute([$deptId]);
    $allowed = array_flip(array_map('intval', $al->fetchAll(\PDO::FETCH_COLUMN)));

    $valid = [];
    foreach ($records as $rec) {
        $pid = (int)($rec['person_id'] ?? 0);
        $status = $rec['status'] ?? '';
        if ($pid <= 0 || !isset($allowed[$pid]) || !in_array($status, $VALID, true)) continue;
        $notes = isset($rec['notes']) && trim((string)$rec['notes']) !== '' ? trim((string)$rec['notes']) : null;
        $valid[$pid] = ['status' => $status, 'notes' => $notes]; // last wins per person
    }
    if (!$valid) Response::error('No valid records for this department', 422);

    $sessionId = tda_find_session($pdo, $deptId, $date);
    if (!$sessionId) {
        $ins = $pdo->prepare(
            "INSERT INTO attendance_sessions (context_type, context_id, session_date, title, created_by_user_id)
             VALUES ('department', ?, ?, ?, ?)"
        );
        $ins->execute([$deptId, $date, $title, (int)($_SESSION['user_id'] ?? 0)]);
        $sessionId = (int)$pdo->lastInsertId();
    } elseif ($title !== null) {
        $pdo->prepare('UPDATE attendance_sessions SET title=? WHERE id=?')->execute([$title, $sessionId]);
    }
    $up = $pdo->prepare(
        'INSERT INTO attendance_records (session_id, person_id, status, notes) VALUES (?,?,?,?)
         ON DUPLICATE KEY UPDATE status=VALUES(status), notes=VALUES(notes)'
    );
    $saved = 0;
    foreach ($valid as $pid => $rec) {
        $up->execute([$sessionId, $pid, $rec['status'], $rec['notes']]);
        $saved++;
    }
    Response::json(['message' => 'Saved', 'session_id' => $sessionId, 'saved' => $saved]);
}

Response::error('Method not allowed', 405);
