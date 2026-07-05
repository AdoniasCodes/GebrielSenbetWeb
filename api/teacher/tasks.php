<?php
// api/teacher/tasks.php — homework/assignment tasks scoped to a department
// (department-wide) or to one of the teacher's classes inside that department.
//   GET    ?department_id=                                    -> visible tasks
//   POST   {scope_type,scope_id,title,description?,due_date?} -> create
//   PUT    {id,title?,description?,due_date?}                 -> update (own tasks only)
//   DELETE {id}                                                -> archive (own tasks only)
use App\Utils\Response;

require_once __DIR__ . '/_guard.php';
require_csrf_for_write();

$pdo = tch_pdo();
$tid = teacher_id();
$method = $_SERVER['REQUEST_METHOD'];

function tsk_class_ids_in_dept(PDO $pdo, int $tid, int $deptId): array {
    $st = $pdo->prepare(
        'SELECT DISTINCT a.class_id
         FROM teacher_subject_assignments a
         JOIN classes c ON c.id = a.class_id AND c.department_id = ?
         WHERE a.teacher_id = ? AND a.is_archived = 0
           AND (a.end_date IS NULL OR a.end_date >= CURDATE())'
    );
    $st->execute([$deptId, $tid]);
    return array_map('intval', $st->fetchAll(\PDO::FETCH_COLUMN));
}

if ($method === 'GET') {
    $deptId = (int)($_GET['department_id'] ?? 0);
    if ($deptId <= 0) Response::error('department_id is required', 422);
    teacher_assert_department($deptId);

    $classIds = tsk_class_ids_in_dept($pdo, $tid, $deptId);
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $params = [$userId, 'department', $deptId];
    $sql = "SELECT t.id, t.scope_type, t.scope_id, t.title, t.description, t.due_date,
                   t.created_by_user_id, t.created_at,
                   (t.created_by_user_id = ?) AS is_mine
            FROM tasks t
            WHERE t.is_archived = 0 AND ((t.scope_type = ? AND t.scope_id = ?)";
    if ($classIds) {
        $ph = implode(',', array_fill(0, count($classIds), '?'));
        $sql .= " OR (t.scope_type = 'class' AND t.scope_id IN ($ph))";
        $params = array_merge($params, $classIds);
    }
    $sql .= ') ORDER BY (t.due_date IS NULL), t.due_date, t.created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    Response::json(['data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $scopeType = $in['scope_type'] ?? '';
    $scopeId = (int)($in['scope_id'] ?? 0);
    $title = trim((string)($in['title'] ?? ''));
    $description = isset($in['description']) && trim((string)$in['description']) !== '' ? trim((string)$in['description']) : null;
    $dueDate = isset($in['due_date']) && $in['due_date'] !== '' ? $in['due_date'] : null;
    if ($dueDate !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$dueDate)) Response::error('due_date must be YYYY-MM-DD', 422);
    if ($title === '' || $scopeId <= 0) Response::error('scope_type, scope_id and title are required', 422);

    if ($scopeType === 'department') {
        if (!in_array($scopeId, teacher_department_ids(), true)) Response::error('You are not assigned to this department', 403);
    } elseif ($scopeType === 'class') {
        if (!in_array($scopeId, teacher_class_ids(), true)) Response::error('You do not teach this class', 403);
    } else {
        Response::error('scope_type must be department or class', 422);
    }

    $ins = $pdo->prepare(
        'INSERT INTO tasks (scope_type, scope_id, title, description, due_date, created_by_user_id)
         VALUES (?,?,?,?,?,?)'
    );
    $ins->execute([$scopeType, $scopeId, $title, $description, $dueDate, (int)($_SESSION['user_id'] ?? 0)]);
    Response::json(['message' => 'Task created', 'id' => (int)$pdo->lastInsertId()], 201);
}

if ($method === 'PUT') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $chk = $pdo->prepare('SELECT created_by_user_id FROM tasks WHERE id=? AND is_archived=0');
    $chk->execute([$id]);
    $row = $chk->fetch();
    if (!$row) Response::error('Task not found', 404);
    if ((int)$row['created_by_user_id'] !== (int)($_SESSION['user_id'] ?? 0)) Response::error('You can only edit your own tasks', 403);

    $fields = [];
    $params = [];
    if (array_key_exists('title', $in)) {
        $t = trim((string)$in['title']);
        if ($t === '') Response::error('title cannot be empty', 422);
        $fields[] = 'title=?';
        $params[] = $t;
    }
    if (array_key_exists('description', $in)) {
        $d = $in['description'];
        $fields[] = 'description=?';
        $params[] = ($d === '' || $d === null) ? null : trim((string)$d);
    }
    if (array_key_exists('due_date', $in)) {
        $d = $in['due_date'];
        if ($d !== null && $d !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$d)) Response::error('due_date must be YYYY-MM-DD', 422);
        $fields[] = 'due_date=?';
        $params[] = ($d === '' ? null : $d);
    }
    if (!$fields) Response::error('Nothing to update', 422);
    $params[] = $id;
    $pdo->prepare('UPDATE tasks SET ' . implode(', ', $fields) . ' WHERE id=?')->execute($params);
    Response::json(['message' => 'Task updated']);
}

if ($method === 'DELETE') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $chk = $pdo->prepare('SELECT created_by_user_id FROM tasks WHERE id=? AND is_archived=0');
    $chk->execute([$id]);
    $row = $chk->fetch();
    if (!$row) Response::error('Task not found', 404);
    if ((int)$row['created_by_user_id'] !== (int)($_SESSION['user_id'] ?? 0)) Response::error('You can only archive your own tasks', 403);
    $pdo->prepare('UPDATE tasks SET is_archived=1, archived_at=NOW() WHERE id=?')->execute([$id]);
    Response::json(['message' => 'Archived']);
}

Response::error('Method not allowed', 405);
