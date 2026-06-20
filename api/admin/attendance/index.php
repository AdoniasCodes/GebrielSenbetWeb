<?php
// api/admin/attendance/index.php — attendance sessions (roll-call headers).
// GET    ?context_type=&context_id=   → recent sessions (with present/total counts)
// POST   { context_type, context_id, session_date, title?, church_id? }  → create (or return existing for that ctx+date)
// DELETE { id }                        → archive session (records cascade-archive via app)

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$config = app_config();
$pdo = (new Database($config['db']))->pdo();

const ATT_CONTEXTS = ['class','department'];

if ($method === 'GET') {
    $ctype = isset($_GET['context_type']) ? trim((string)$_GET['context_type']) : '';
    $cid   = (int)($_GET['context_id'] ?? 0);
    $sql = "SELECT s.id, s.context_type, s.context_id, s.title, s.session_date, s.church_id, ch.short_name AS church_name,
                   (SELECT COUNT(*) FROM attendance_records r WHERE r.session_id=s.id) AS marked,
                   (SELECT COUNT(*) FROM attendance_records r WHERE r.session_id=s.id AND r.status IN ('present','late')) AS present
            FROM attendance_sessions s
            LEFT JOIN churches ch ON ch.id = s.church_id
            WHERE s.is_archived=0";
    $params = [];
    if (in_array($ctype, ATT_CONTEXTS, true)) { $sql .= ' AND s.context_type=?'; $params[] = $ctype; }
    if ($cid > 0) { $sql .= ' AND s.context_id=?'; $params[] = $cid; }
    $sql .= ' ORDER BY s.session_date DESC, s.id DESC LIMIT 200';
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    Response::json(['data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $ctype = trim((string)($in['context_type'] ?? ''));
    $cid   = (int)($in['context_id'] ?? 0);
    $date  = trim((string)($in['session_date'] ?? ''));
    if (!in_array($ctype, ATT_CONTEXTS, true)) Response::error('Invalid context_type', 422);
    if ($cid <= 0 || $date === '') Response::error('context_id and session_date are required', 422);

    // Validate the context exists.
    $tbl = $ctype === 'class' ? 'classes' : 'departments';
    $chk = $pdo->prepare("SELECT id FROM $tbl WHERE id=? AND is_archived=0");
    $chk->execute([$cid]);
    if (!$chk->fetch()) Response::error(ucfirst($ctype) . ' not found', 404);

    // One session per (context, date): reuse if it exists.
    $ex = $pdo->prepare('SELECT id FROM attendance_sessions WHERE context_type=? AND context_id=? AND session_date=? AND is_archived=0 LIMIT 1');
    $ex->execute([$ctype, $cid, $date]);
    if ($row = $ex->fetch()) Response::json(['ok' => true, 'id' => (int)$row['id'], 'existing' => true]);

    $title = isset($in['title']) && $in['title'] !== '' ? trim((string)$in['title']) : null;
    $churchId = (int)($in['church_id'] ?? 0) ?: null;
    $uid = (int)($_SESSION['user_id'] ?? 0) ?: null;
    $ins = $pdo->prepare('INSERT INTO attendance_sessions (context_type, context_id, title, session_date, church_id, created_by_user_id) VALUES (?,?,?,?,?,?)');
    $ins->execute([$ctype, $cid, $title, $date, $churchId, $uid]);
    $id = (int)$pdo->lastInsertId();
    \App\Audit::log('attendance.session.create', 'attendance_session', $id, ['context' => "$ctype:$cid", 'date' => $date]);
    Response::json(['ok' => true, 'id' => $id], 201);
}

if ($method === 'DELETE') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $pdo->prepare('DELETE FROM attendance_records WHERE session_id=?')->execute([$id]);
    $pdo->prepare('UPDATE attendance_sessions SET is_archived=1, archived_at=NOW() WHERE id=?')->execute([$id]);
    \App\Audit::log('attendance.session.archive', 'attendance_session', $id);
    Response::json(['ok' => true]);
}

Response::error('Method not allowed', 405);
