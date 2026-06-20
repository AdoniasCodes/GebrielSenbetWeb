<?php
// api/admin/attendance/records.php — the roster + marks for one session.
// GET ?session_id=  → { session, roster: [{ person_id, name, status|null, notes }] }
// PUT { session_id, records: [{ person_id, status, notes? }] }  → bulk upsert

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$config = app_config();
$pdo = (new Database($config['db']))->pdo();

const ATT_STATUSES = ['present','absent','late','excused'];

function _session(\PDO $pdo, int $id): ?array {
    $s = $pdo->prepare('SELECT id, context_type, context_id, title, session_date, church_id FROM attendance_sessions WHERE id=? AND is_archived=0');
    $s->execute([$id]);
    return $s->fetch() ?: null;
}

// The eligible roster for a session's context, resolved to canonical people.
function _roster(\PDO $pdo, array $sess): array {
    if ($sess['context_type'] === 'class') {
        $sql = "SELECT DISTINCT p.id AS person_id, CONCAT(p.first_name,' ',p.last_name) AS name
                FROM student_class_assignments sca
                JOIN students st ON st.id = sca.student_id AND st.is_archived = 0
                JOIN people p ON p.id = st.person_id
                WHERE sca.class_id = ? AND sca.is_archived = 0 AND (sca.ended_at IS NULL OR sca.ended_at >= CURDATE())
                ORDER BY name";
    } else {
        $sql = "SELECT DISTINCT p.id AS person_id, CONCAT(p.first_name,' ',p.last_name) AS name
                FROM department_memberships dm
                JOIN people p ON p.id = dm.person_id
                WHERE dm.department_id = ? AND dm.is_archived = 0
                ORDER BY name";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute([(int)$sess['context_id']]);
    return $stmt->fetchAll();
}

if ($method === 'GET') {
    $sid = (int)($_GET['session_id'] ?? 0);
    if ($sid <= 0) Response::error('session_id is required', 422);
    $sess = _session($pdo, $sid);
    if (!$sess) Response::error('Session not found', 404);

    $marks = [];
    $r = $pdo->prepare('SELECT person_id, status, notes FROM attendance_records WHERE session_id=?');
    $r->execute([$sid]);
    foreach ($r->fetchAll() as $row) $marks[(int)$row['person_id']] = $row;

    $roster = array_map(function ($p) use ($marks) {
        $pid = (int)$p['person_id'];
        return [
            'person_id' => $pid,
            'name'      => $p['name'],
            'status'    => $marks[$pid]['status'] ?? null,
            'notes'     => $marks[$pid]['notes'] ?? null,
        ];
    }, _roster($pdo, $sess));

    Response::json(['session' => $sess, 'roster' => $roster]);
}

if ($method === 'PUT') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $sid = (int)($in['session_id'] ?? 0);
    if ($sid <= 0) Response::error('session_id is required', 422);
    if (!_session($pdo, $sid)) Response::error('Session not found', 404);
    $records = is_array($in['records'] ?? null) ? $in['records'] : [];

    try {
        $pdo->beginTransaction();
        $up = $pdo->prepare('INSERT INTO attendance_records (session_id, person_id, status, notes)
                             VALUES (?,?,?,?)
                             ON DUPLICATE KEY UPDATE status=VALUES(status), notes=VALUES(notes)');
        $n = 0;
        foreach ($records as $rec) {
            $pid = (int)($rec['person_id'] ?? 0);
            $status = (string)($rec['status'] ?? '');
            if ($pid <= 0 || !in_array($status, ATT_STATUSES, true)) continue;
            $notes = isset($rec['notes']) && $rec['notes'] !== '' ? trim((string)$rec['notes']) : null;
            $up->execute([$sid, $pid, $status, $notes]);
            $n++;
        }
        $pdo->commit();
        \App\Audit::log('attendance.records.save', 'attendance_session', $sid, ['marked' => $n]);
        Response::json(['ok' => true, 'marked' => $n]);
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        Response::error('Failed to save attendance', 500);
    }
}

Response::error('Method not allowed', 405);
