<?php
// api/teacher/notifications.php — announcement notices reaching this teacher.
//   GET       -> user-, role:teacher-, department- and class-targeted rows for me
//   POST {id} -> mark one as read (notification_reads, idempotent)
// Targeting/unread logic lives in api/notifications_lib.php (the single contract).
use App\Utils\Response;

require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../notifications_lib.php';
require_csrf_for_write();

$pdo = tch_pdo();
$method = $_SERVER['REQUEST_METHOD'];
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($method === 'GET') {
    $q = notif_inbox_query([
        'user_id'  => $userId,
        'role'     => 'teacher',
        'dept_ids' => teacher_department_ids(),
        'class_ids'=> teacher_class_ids(),
    ], 100);
    $stmt = $pdo->prepare($q['sql']);
    $stmt->execute($q['params']);
    $out = array_map(function ($r) {
        return [
            'id' => $r['id'],
            'title' => $r['title'],
            'message' => $r['message'],
            'created_at' => $r['created_at'],
            'is_read' => (bool)$r['is_read'],
        ];
    }, $stmt->fetchAll());
    Response::json(['data' => $out]);
}

if ($method === 'POST') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    notif_mark_read($pdo, $id, $userId);
    Response::json(['message' => 'Marked read']);
}

Response::error('Method not allowed', 405);
