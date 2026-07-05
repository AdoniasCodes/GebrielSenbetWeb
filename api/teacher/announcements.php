<?php
// api/teacher/announcements.php — direct-post (no approval) announcements scoped
// to a department the teacher belongs to, optionally narrowed to one of their
// classes inside that department.
//   GET  [?department_id=]                        -> announcements this teacher posted
//   POST {department_id,title,message,class_id?}  -> post now
use App\Utils\Response;

require_once __DIR__ . '/_guard.php';
require_csrf_for_write();

$pdo = tch_pdo();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $deptFilter = (int)($_GET['department_id'] ?? 0);
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $stmt = $pdo->prepare(
        "SELECT id, title, message, target_type, target_payload, is_public, created_at
         FROM notifications
         WHERE sender_user_id = ? AND is_archived = 0 AND target_type IN ('department','class')
         ORDER BY created_at DESC LIMIT 200"
    );
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();

    // Resolve class -> department for filtering / display, since a class-scoped
    // announcement's target_payload only carries class_id.
    $classIds = [];
    foreach ($rows as $r) {
        if ($r['target_type'] === 'class') {
            $p = json_decode($r['target_payload'], true) ?: [];
            if (!empty($p['class_id'])) $classIds[] = (int)$p['class_id'];
        }
    }
    $classDept = [];
    if ($classIds) {
        $ids = array_values(array_unique($classIds));
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $cst = $pdo->prepare("SELECT id, department_id, name FROM classes WHERE id IN ($ph)");
        $cst->execute($ids);
        foreach ($cst->fetchAll() as $c) $classDept[(int)$c['id']] = $c;
    }

    $out = [];
    foreach ($rows as $r) {
        $payload = json_decode($r['target_payload'], true) ?: [];
        $rowDeptId = null;
        $className = null;
        $classId = null;
        if ($r['target_type'] === 'department') {
            $rowDeptId = (int)($payload['department_id'] ?? 0);
        } else {
            $classId = (int)($payload['class_id'] ?? 0);
            if (isset($classDept[$classId])) {
                $rowDeptId = (int)$classDept[$classId]['department_id'];
                $className = $classDept[$classId]['name'];
            }
        }
        if ($deptFilter > 0 && $rowDeptId !== $deptFilter) continue;
        $out[] = [
            'id' => $r['id'],
            'title' => $r['title'],
            'message' => $r['message'],
            'target_type' => $r['target_type'],
            'department_id' => $rowDeptId,
            'class_id' => $classId,
            'class_name' => $className,
            'created_at' => $r['created_at'],
        ];
    }
    Response::json(['data' => $out]);
}

if ($method === 'POST') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $deptId = (int)($in['department_id'] ?? 0);
    $title = trim((string)($in['title'] ?? ''));
    $message = trim((string)($in['message'] ?? ''));
    $classId = isset($in['class_id']) && $in['class_id'] !== '' ? (int)$in['class_id'] : null;
    if ($deptId <= 0 || $title === '' || $message === '') Response::error('department_id, title and message are required', 422);
    teacher_assert_department($deptId);

    $targetType = 'department';
    $payload = ['department_id' => $deptId];
    if ($classId) {
        if (!in_array($classId, teacher_class_ids(), true)) Response::error('You do not teach this class', 403);
        $cchk = $pdo->prepare('SELECT department_id FROM classes WHERE id=? AND is_archived=0');
        $cchk->execute([$classId]);
        $cdept = $cchk->fetchColumn();
        if ($cdept === false || (int)$cdept !== $deptId) Response::error('That class is not in this department', 422);
        $targetType = 'class';
        $payload = ['class_id' => $classId];
    }

    $userId = (int)($_SESSION['user_id'] ?? 0);
    $roleId = (int)($_SESSION['role_id'] ?? 0);
    $ins = $pdo->prepare(
        'INSERT INTO notifications (sender_user_id, sender_role_id, target_type, target_payload, title, message, is_public)
         VALUES (?, ?, ?, ?, ?, ?, 0)'
    );
    $ins->execute([$userId ?: null, $roleId ?: null, $targetType, json_encode($payload), $title, $message]);
    Response::json(['message' => 'Posted', 'id' => (int)$pdo->lastInsertId()], 201);
}

Response::error('Method not allowed', 405);
