<?php
// api/staff/announcements.php: department-head announcements.
//
// Approval-free by decision (blueprint §10 Q6, resolved 2026-07-15): only events
// need approval, announcements post immediately. Mirrors the teacher endpoint but
// scoped to the departments this user HEADS rather than merely belongs to, and the
// listing shows the whole department's traffic (including teachers' posts), which
// is what oversight actually needs.
//
//   GET    [?department_id=]                        -> announcements in headed depts
//   POST   {department_id,title,message,class_id?}  -> post now
//   DELETE {id}                                     -> archive (own posts only)
//
// is_public is deliberately NOT exposed here: publishing to the public landing feed
// stays an admin capability.

use App\Utils\Response;

require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../notifications_lib.php';
require_csrf_for_write();

$pdo    = $GLOBALS['__staff_pdo'];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$userId = (int)($_SESSION['user_id'] ?? 0);

/** Non-archived classes belonging to the given departments. */
function ann_classes_in_depts(PDO $pdo, array $deptIds): array {
    if (!$deptIds) return [];
    $ph = implode(',', array_fill(0, count($deptIds), '?'));
    $st = $pdo->prepare(
        "SELECT id, name, department_id FROM classes
          WHERE department_id IN ($ph) AND is_archived = 0
          ORDER BY name"
    );
    $st->execute($deptIds);
    return $st->fetchAll();
}

if ($method === 'GET') {
    $headed = staff_headed_department_ids();
    if (!$headed) Response::json(['data' => [], 'classes' => []]);

    $deptFilter = (int)($_GET['department_id'] ?? 0);
    if ($deptFilter > 0) {
        staff_assert_dept($deptFilter);
        $headed = [$deptFilter];
    }
    // Returned alongside the rows so the UI can offer class-scoped posting
    // without a second endpoint.
    $classes  = ann_classes_in_depts($pdo, $headed);
    $classIds = array_map(static fn($c) => (int)$c['id'], $classes);

    // Reuse the shared audience contract instead of hand-rolling target matching,
    // so this listing cannot drift from how the same rows are read elsewhere.
    $clause = notif_audience_clause([
        'user_id'        => 0,
        'role'           => '',
        'dept_ids'       => $headed,
        'class_ids'      => $classIds,
        'include_public' => false,
    ]);

    $sql = "SELECT n.id, n.title, n.message, n.target_type, n.target_payload,
                   n.sender_user_id, n.created_at,
                   (n.sender_user_id = ?) AS is_mine,
                   u.email AS sender_email, r.name AS sender_role
              FROM notifications n
              LEFT JOIN users u ON u.id = n.sender_user_id
              LEFT JOIN roles r ON r.id = n.sender_role_id
             WHERE n.is_archived = 0 AND {$clause['sql']}
             ORDER BY n.created_at DESC
             LIMIT 200";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge([$userId], $clause['params']));
    $rows = $stmt->fetchAll();

    // Resolve class -> department/name so a class-scoped row can show where it landed.
    $classMeta = [];
    foreach ($classes as $c) $classMeta[(int)$c['id']] = $c;

    $out = [];
    foreach ($rows as $r) {
        $payload = json_decode((string)$r['target_payload'], true) ?: [];
        $deptId = null; $classId = null; $className = null;
        if ($r['target_type'] === 'department') {
            $deptId = (int)($payload['department_id'] ?? 0);
        } else {
            $classId = (int)($payload['class_id'] ?? 0);
            if (isset($classMeta[$classId])) {
                $deptId    = (int)$classMeta[$classId]['department_id'];
                $className = $classMeta[$classId]['name'];
            }
        }
        $out[] = [
            'id'            => (int)$r['id'],
            'title'         => $r['title'],
            'message'       => $r['message'],
            'target_type'   => $r['target_type'],
            'department_id' => $deptId,
            'class_id'      => $classId,
            'class_name'    => $className,
            'sender_email'  => $r['sender_email'],
            'sender_role'   => $r['sender_role'],
            'is_mine'       => (int)$r['is_mine'] === 1,
            'created_at'    => $r['created_at'],
        ];
    }
    Response::json(['data' => $out, 'classes' => $classes]);
}

if ($method === 'POST') {
    $in      = json_decode(file_get_contents('php://input'), true) ?: [];
    $deptId  = (int)($in['department_id'] ?? 0);
    $title   = trim((string)($in['title'] ?? ''));
    $message = trim((string)($in['message'] ?? ''));
    $classId = isset($in['class_id']) && $in['class_id'] !== '' ? (int)$in['class_id'] : null;

    if ($deptId <= 0 || $title === '' || $message === '') {
        Response::error('department_id, title and message are required', 422);
    }
    staff_assert_dept($deptId);

    $targetType = 'department';
    $payload    = ['department_id' => $deptId];
    if ($classId) {
        $cchk = $pdo->prepare('SELECT department_id FROM classes WHERE id = ? AND is_archived = 0');
        $cchk->execute([$classId]);
        $cdept = $cchk->fetchColumn();
        if ($cdept === false) Response::error('Class not found', 404);
        if ((int)$cdept !== $deptId) Response::error('That class is not in this department', 422);
        $targetType = 'class';
        $payload    = ['class_id' => $classId];
    }

    $newId = notify($pdo, $targetType, $payload, $title, $message, [
        'senderUserId' => $userId,
        'senderRoleId' => (int)($_SESSION['role_id'] ?? 0),
    ]);
    \App\Audit::log('announcement.create', 'notification', $newId, [
        'target_type' => $targetType,
        'department_id' => $deptId,
    ]);
    Response::json(['message' => 'Posted', 'id' => $newId], 201);
}

if ($method === 'DELETE') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);

    $chk = $pdo->prepare('SELECT sender_user_id FROM notifications WHERE id = ? AND is_archived = 0');
    $chk->execute([$id]);
    $row = $chk->fetch();
    if (!$row) Response::error('Announcement not found', 404);
    if ((int)$row['sender_user_id'] !== $userId) {
        Response::error('You can only retract your own announcements', 403);
    }
    $pdo->prepare('UPDATE notifications SET is_archived = 1, archived_at = NOW() WHERE id = ?')->execute([$id]);
    \App\Audit::log('announcement.archive', 'notification', $id);
    Response::json(['message' => 'Retracted']);
}

Response::error('Method not allowed', 405);
