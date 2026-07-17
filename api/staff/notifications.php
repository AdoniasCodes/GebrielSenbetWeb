<?php
// api/staff/notifications.php — announcement notices reaching this staff/dept-head.
//   GET       -> user-, role:staff- and department-targeted rows for me
//   POST {id} -> mark one as read (notification_reads, idempotent)
// New in Phase 2.1: staff had no inbox, so "event proposed → dept head" had
// nowhere to land. Targeting/unread logic lives in api/notifications_lib.php.
use App\Utils\Response;

require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../notifications_lib.php';

$pdo = $GLOBALS['__staff_pdo'];
$method = $_SERVER['REQUEST_METHOD'];
$userId = (int)($_SESSION['user_id'] ?? 0);

// Departments this person actively belongs to (any role in them, not only heads):
// department-targeted announcements reach every member.
function staff_member_department_ids(\PDO $pdo, int $userId): array {
    $st = $pdo->prepare(
        'SELECT DISTINCT dm.department_id
           FROM department_memberships dm
           JOIN people p ON p.id = dm.person_id
          WHERE p.user_id = ? AND dm.is_archived = 0
            AND (dm.ended_at IS NULL OR dm.ended_at >= CURDATE())'
    );
    $st->execute([$userId]);
    return array_map('intval', $st->fetchAll(\PDO::FETCH_COLUMN));
}

if ($method === 'GET') {
    // Only true 'staff' users get the role:staff feed; an admin viewing this
    // endpoint sees their own user-targeted rows plus any dept they belong to.
    $role = ($_SESSION['role_name'] ?? '') === 'staff' ? 'staff' : '';
    $q = notif_inbox_query([
        'user_id'  => $userId,
        'role'     => $role,
        'dept_ids' => staff_member_department_ids($pdo, $userId),
    ], 100);
    $stmt = $pdo->prepare($q['sql']);
    $stmt->execute($q['params']);
    $out = array_map(function ($r) {
        return [
            'id' => $r['id'], 'title' => $r['title'], 'message' => $r['message'],
            'created_at' => $r['created_at'], 'is_read' => (bool)$r['is_read'],
        ];
    }, $stmt->fetchAll());
    Response::json(['data' => $out]);
}

if ($method === 'POST') {
    require_csrf_for_write();
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    notif_mark_read($pdo, $id, $userId);
    Response::json(['message' => 'Marked read']);
}

Response::error('Method not allowed', 405);
