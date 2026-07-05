<?php
// api/staff/events.php — department-head approval workflow for events.
//   GET  [?department_id=]                                       -> events for headed depts (pending first)
//   POST {action:'approve'|'reject', id}                         -> decide a proposal in a headed dept
//   POST {action:'create', department_id,title,description?,
//         start_datetime,end_datetime?}                          -> head creates directly (immediately approved)
use App\Utils\Response;

require_once __DIR__ . '/_guard.php';
require_csrf_for_write();

$pdo = $GLOBALS['__staff_pdo'];
$method = $_SERVER['REQUEST_METHOD'];
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($method === 'GET') {
    $deptIds = staff_headed_department_ids();
    if (!$deptIds) Response::json(['data' => []]);

    $deptFilter = (int)($_GET['department_id'] ?? 0);
    if ($deptFilter > 0) {
        staff_assert_dept($deptFilter);
        $deptIds = [$deptFilter];
    }

    $ph = implode(',', array_fill(0, count($deptIds), '?'));
    $sql = "SELECT e.id, e.title, e.description, e.start_datetime, e.end_datetime,
                   e.status, e.department_id, e.created_by_user_id, e.approved_by_user_id, e.approved_at, e.created_at,
                   d.name AS department_name, d.name_am AS department_name_am,
                   creator.email AS created_by_email
            FROM events e
            LEFT JOIN departments d ON d.id = e.department_id
            LEFT JOIN users creator ON creator.id = e.created_by_user_id
            WHERE e.department_id IN ($ph) AND e.is_archived = 0
            ORDER BY (e.status = 'pending') DESC, e.start_datetime DESC
            LIMIT 300";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($deptIds);
    Response::json(['data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = trim((string)($in['action'] ?? ''));

    if ($action === 'approve' || $action === 'reject') {
        $id = (int)($in['id'] ?? 0);
        if ($id <= 0) Response::error('id is required', 422);

        $chk = $pdo->prepare('SELECT department_id, status FROM events WHERE id=? AND is_archived=0');
        $chk->execute([$id]);
        $row = $chk->fetch();
        if (!$row) Response::error('Event not found', 404);
        if (empty($row['department_id'])) Response::error('This event has no department to manage', 422);
        staff_assert_dept((int)$row['department_id']);
        if ($row['status'] !== 'pending') Response::error('Only pending proposals can be decided', 422);

        $newStatus = $action === 'approve' ? 'approved' : 'rejected';
        $upd = $pdo->prepare('UPDATE events SET status=?, approved_by_user_id=?, approved_at=NOW() WHERE id=?');
        $upd->execute([$newStatus, $userId, $id]);
        Response::json(['message' => $action === 'approve' ? 'Approved' : 'Rejected']);
    }

    if ($action === 'create') {
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

        staff_assert_dept($deptId);

        $ins = $pdo->prepare(
            "INSERT INTO events (title, description, start_datetime, end_datetime, status, created_by_user_id, department_id, approved_by_user_id, approved_at)
             VALUES (?, ?, ?, ?, 'approved', ?, ?, ?, NOW())"
        );
        $ins->execute([$title, $description, $start, $end, $userId, $deptId, $userId]);
        Response::json(['message' => 'Created', 'id' => (int)$pdo->lastInsertId()], 201);
    }

    Response::error('Unknown action', 422);
}

Response::error('Method not allowed', 405);
