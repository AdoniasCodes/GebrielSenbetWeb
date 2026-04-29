<?php
// api/admin/announcements/index.php — admin compose & list of broadcast notifications
// GET   /api/admin/announcements?include_archived=
// POST  body: { title, message, target_type, target_payload? }
//   target_type: role | class | subject | payment_defaulters | event
//   target_payload: { role?: 'student|teacher|admin', class_id?, subject_id?, term_id?, event_id? }
// DELETE body: { id }

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$config = app_config();
$db = new Database($config['db']);
$pdo = $db->pdo();

$validTargets = ['role','class','subject','payment_defaulters','event'];

if ($method === 'GET') {
    $includeArchived = isset($_GET['include_archived']) && $_GET['include_archived'] === '1';
    $sql = "SELECT n.id, n.title, n.message, n.target_type, n.target_payload, n.read_by, n.is_archived,
                   n.created_at, n.updated_at,
                   u.email AS sender_email, r.name AS sender_role
            FROM notifications n
            LEFT JOIN users u ON u.id=n.sender_user_id
            LEFT JOIN roles r ON r.id=n.sender_role_id
            WHERE 1=1";
    if (!$includeArchived) $sql .= ' AND n.is_archived=0';
    $sql .= ' ORDER BY n.created_at DESC LIMIT 200';
    $rows = $pdo->query($sql)->fetchAll();
    foreach ($rows as &$r) {
        $r['target_payload'] = $r['target_payload'] ? json_decode($r['target_payload'], true) : null;
        $r['read_by']        = $r['read_by'] ? json_decode($r['read_by'], true) : [];
    }
    Response::json(['data' => $rows]);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $title   = trim($input['title'] ?? '');
    $message = trim($input['message'] ?? '');
    $tgt     = trim($input['target_type'] ?? '');
    $payload = $input['target_payload'] ?? null;

    if ($title === '' || $message === '') Response::error('title and message are required', 422);
    if (!in_array($tgt, $validTargets, true)) Response::error('Invalid target_type', 422);
    if ($payload !== null && !is_array($payload)) Response::error('target_payload must be an object', 422);
    $payloadJson = $payload ? json_encode($payload) : null;

    $userId = (int)($_SESSION['user_id'] ?? 0);
    $roleId = (int)($_SESSION['role_id'] ?? 0);

    $ins = $pdo->prepare('INSERT INTO notifications (sender_user_id, sender_role_id, target_type, target_payload, title, message) VALUES (?, ?, ?, ?, ?, ?)');
    $ins->execute([$userId ?: null, $roleId ?: null, $tgt, $payloadJson, $title, $message]);
    Response::json(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
}

if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $stmt = $pdo->prepare('UPDATE notifications SET is_archived=1, archived_at=NOW() WHERE id=? AND is_archived=0');
    $stmt->execute([$id]);
    Response::json(['ok' => true]);
}

Response::error('Method not allowed', 405);
