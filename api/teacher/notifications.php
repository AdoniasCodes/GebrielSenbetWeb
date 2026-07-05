<?php
// api/teacher/notifications.php — assignment/announcement notices targeting this teacher.
//   GET       -> notifications where target_type='user' and target_payload.user_id = me
//   POST {id} -> mark one as read (adds my user id into read_by)
use App\Utils\Response;

require_once __DIR__ . '/_guard.php';
require_csrf_for_write();

$pdo = tch_pdo();
$method = $_SERVER['REQUEST_METHOD'];
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($method === 'GET') {
    // JSON_UNQUOTE, not a bare JSON_EXTRACT comparison: PDO sends the bound
    // int as a string, and MySQL will NOT coerce a JSON-typed int to match a
    // string operand (only a literal integer works) — unquoting both sides
    // to plain strings makes the comparison reliable.
    $stmt = $pdo->prepare(
        "SELECT id, title, message, read_by, created_at
         FROM notifications
         WHERE target_type = 'user' AND is_archived = 0
           AND JSON_UNQUOTE(JSON_EXTRACT(target_payload, '$.user_id')) = ?
         ORDER BY created_at DESC LIMIT 100"
    );
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();
    $out = array_map(function ($r) use ($userId) {
        $readBy = $r['read_by'] ? (json_decode($r['read_by'], true) ?: []) : [];
        $readBy = array_map('intval', is_array($readBy) ? $readBy : []);
        return [
            'id' => $r['id'],
            'title' => $r['title'],
            'message' => $r['message'],
            'created_at' => $r['created_at'],
            'is_read' => in_array($userId, $readBy, true),
        ];
    }, $rows);
    Response::json(['data' => $out]);
}

if ($method === 'POST') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $stmt = $pdo->prepare(
        "SELECT read_by FROM notifications
         WHERE id = ? AND target_type = 'user' AND is_archived = 0
           AND JSON_UNQUOTE(JSON_EXTRACT(target_payload, '$.user_id')) = ?"
    );
    $stmt->execute([$id, $userId]);
    $row = $stmt->fetch();
    if (!$row) Response::error('Notification not found', 404);
    $readBy = $row['read_by'] ? (json_decode($row['read_by'], true) ?: []) : [];
    $readBy = array_map('intval', is_array($readBy) ? $readBy : []);
    if (!in_array($userId, $readBy, true)) $readBy[] = $userId;
    $pdo->prepare('UPDATE notifications SET read_by=? WHERE id=?')->execute([json_encode(array_values($readBy)), $id]);
    Response::json(['message' => 'Marked read']);
}

Response::error('Method not allowed', 405);
