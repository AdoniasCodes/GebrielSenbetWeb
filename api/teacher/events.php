<?php
// api/teacher/events.php — teacher-proposed events for a department they belong
// to. Teachers cannot publish events unilaterally: every proposal starts
// status='pending' and must be approved by a department head (api/staff/events.php).
//   GET    [?department_id=]                                  -> own proposals (+ dept name)
//   POST   {department_id,title,description?,start_datetime,end_datetime?} -> propose (pending)
//   DELETE {id}                                                -> archive own PENDING proposal only
use App\Utils\Response;

require_once __DIR__ . '/_guard.php';
require_csrf_for_write();

$pdo = tch_pdo();
$method = $_SERVER['REQUEST_METHOD'];
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($method === 'GET') {
    $deptFilter = (int)($_GET['department_id'] ?? 0);
    $sql = "SELECT e.id, e.title, e.description, e.start_datetime, e.end_datetime,
                   e.status, e.department_id, e.approved_at, e.created_at,
                   d.name AS department_name, d.name_am AS department_name_am
            FROM events e
            LEFT JOIN departments d ON d.id = e.department_id
            WHERE e.created_by_user_id = ? AND e.is_archived = 0";
    $params = [$userId];
    if ($deptFilter > 0) {
        $sql .= ' AND e.department_id = ?';
        $params[] = $deptFilter;
    }
    $sql .= ' ORDER BY e.start_datetime DESC LIMIT 200';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    Response::json(['data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $deptId = (int)($in['department_id'] ?? 0);
    $title = trim((string)($in['title'] ?? ''));
    $description = isset($in['description']) && trim((string)$in['description']) !== '' ? trim((string)$in['description']) : null;
    $start = trim((string)($in['start_datetime'] ?? ''));
    $end = isset($in['end_datetime']) && $in['end_datetime'] !== '' ? trim((string)$in['end_datetime']) : null;

    if ($deptId <= 0 || $title === '' || $start === '') {
        Response::error('department_id, title and start_datetime are required', 422);
    }
    if (strtotime($start) === false) Response::error('Invalid start_datetime', 422);
    if ($end !== null && strtotime($end) === false) Response::error('Invalid end_datetime', 422);
    if ($end !== null && $end < $start) Response::error('end_datetime must be on/after start_datetime', 422);

    teacher_assert_department($deptId);

    $ins = $pdo->prepare(
        "INSERT INTO events (title, description, start_datetime, end_datetime, status, created_by_user_id, department_id)
         VALUES (?, ?, ?, ?, 'pending', ?, ?)"
    );
    $ins->execute([$title, $description, $start, $end, $userId, $deptId]);
    Response::json(['message' => 'Proposed', 'id' => (int)$pdo->lastInsertId()], 201);
}

if ($method === 'DELETE') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);

    $chk = $pdo->prepare('SELECT created_by_user_id, status FROM events WHERE id=? AND is_archived=0');
    $chk->execute([$id]);
    $row = $chk->fetch();
    if (!$row) Response::error('Event not found', 404);
    if ((int)$row['created_by_user_id'] !== $userId) Response::error('You can only withdraw your own proposals', 403);
    if ($row['status'] !== 'pending') Response::error('Only pending proposals can be withdrawn', 422);

    $pdo->prepare('UPDATE events SET is_archived=1, archived_at=NOW() WHERE id=?')->execute([$id]);
    Response::json(['message' => 'Withdrawn']);
}

Response::error('Method not allowed', 405);
