<?php
// api/admin/inbox/index.php — announcement notices reaching this admin.
//   GET       -> role:admin- and user-targeted rows for me
//   POST {id} -> mark one as read (notification_reads, idempotent)
// New in Phase 2.1: admin had no inbox, so "registration submitted → admins"
// had nowhere to land. Distinct from api/admin/announcements (the composer /
// management list). Targeting/unread logic lives in api/notifications_lib.php.
use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_once __DIR__ . '/../../notifications_lib.php';

$pdo = (new Database(app_config()['db']))->pdo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($method === 'GET') {
    // Admins have no department/class identity for targeting; they receive
    // role:admin broadcasts and anything sent to them personally. Public rows
    // belong on the landing feed, not the admin work inbox.
    $q = notif_inbox_query([
        'user_id'        => $userId,
        'role'           => 'admin',
        'include_public' => false,
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
