<?php
// api/admin/events/index.php — events with optional recurrence rule
// GET    /api/admin/events?upcoming=1&include_archived=&status=pending|approved|rejected
// POST   body: { title, description?, start_datetime, end_datetime?, is_recurring?, recurrence?{ freq, interval_num, by_day?, until_date? } }
//        or   { action: 'approve'|'reject', id }   (oversight of dept/teacher proposals)
// PUT    body: { id, ...same fields }
// DELETE body: { id }

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_once __DIR__ . '/../../notifications_lib.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$config = app_config();
$db = new Database($config['db']);
$pdo = $db->pdo();

if ($method === 'GET') {
    $upcomingOnly    = isset($_GET['upcoming']) && $_GET['upcoming'] === '1';
    $includeArchived = isset($_GET['include_archived']) && $_GET['include_archived'] === '1';

    $statusFilter = isset($_GET['status']) && in_array($_GET['status'], ['pending','approved','rejected'], true)
        ? $_GET['status'] : null;

    $sql = "SELECT e.*,
                   d.name AS department_name, d.name_am AS department_name_am,
                   cu.email AS created_by_email,
                   r.id AS rr_id, r.freq, r.interval_num, r.by_day, r.until_date
            FROM events e
            LEFT JOIN departments d ON d.id = e.department_id
            LEFT JOIN users cu ON cu.id = e.created_by_user_id
            LEFT JOIN event_recurrence_rules r ON r.event_id=e.id
            WHERE 1=1";
    $params = [];
    if ($upcomingOnly) { $sql .= ' AND (e.end_datetime >= NOW() OR e.start_datetime >= NOW())'; }
    if (!$includeArchived) { $sql .= ' AND e.is_archived=0'; }
    if ($statusFilter !== null) { $sql .= ' AND e.status = ?'; $params[] = $statusFilter; }
    $sql .= ' ORDER BY e.start_datetime ASC LIMIT 500';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    Response::json(['data' => $rows]);
}

function _writeRecurrence(\PDO $pdo, int $eventId, array $r): void {
    $valid = ['weekly','monthly','every_x_months'];
    if (!in_array($r['freq'] ?? '', $valid, true)) return;
    $interval = max(1, (int)($r['interval_num'] ?? 1));
    $byDay = isset($r['by_day']) && $r['by_day'] !== '' ? (string)$r['by_day'] : null;
    $until = isset($r['until_date']) && $r['until_date'] !== '' ? (string)$r['until_date'] : null;
    $del = $pdo->prepare('DELETE FROM event_recurrence_rules WHERE event_id=?');
    $del->execute([$eventId]);
    $ins = $pdo->prepare('INSERT INTO event_recurrence_rules (event_id, freq, interval_num, by_day, until_date) VALUES (?, ?, ?, ?, ?)');
    $ins->execute([$eventId, $r['freq'], $interval, $byDay, $until]);
}

if ($method === 'POST' || $method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    // Approval oversight: admin can decide dept/teacher proposals from any state.
    $action = (string)($input['action'] ?? '');
    if ($method === 'POST' && ($action === 'approve' || $action === 'reject')) {
        $eid = (int)($input['id'] ?? 0);
        if ($eid <= 0) Response::error('id is required', 422);
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';
        $chk = $pdo->prepare('SELECT title, created_by_user_id FROM events WHERE id=? AND is_archived=0');
        $chk->execute([$eid]);
        $ev = $chk->fetch();
        if (!$ev) Response::error('Event not found', 404);
        $adminId = (int)($_SESSION['user_id'] ?? 0);
        $stmt = $pdo->prepare('UPDATE events SET status=?, approved_by_user_id=?, approved_at=NOW() WHERE id=?');
        $stmt->execute([$newStatus, $adminId ?: null, $eid]);

        // Producer: notify the proposer of the admin's decision.
        $proposerId = (int)$ev['created_by_user_id'];
        if ($proposerId > 0 && $proposerId !== $adminId) {
            notify_user(
                $pdo, $proposerId,
                'Event ' . $newStatus . ' / የመርሐ ግብር ውሳኔ',
                'Your proposed event "' . (string)$ev['title'] . '" was ' . $newStatus . '.',
                ['senderUserId' => $adminId, 'senderRoleId' => (int)($_SESSION['role_id'] ?? 0)]
            );
        }
        Response::json(['ok' => true, 'id' => $eid, 'status' => $newStatus]);
    }

    $id = $method === 'PUT' ? (int)($input['id'] ?? 0) : 0;
    if ($method === 'PUT' && $id <= 0) Response::error('id is required', 422);

    $title = trim($input['title'] ?? '');
    $description = isset($input['description']) ? (string)$input['description'] : null;
    $start = trim($input['start_datetime'] ?? '');
    $end   = isset($input['end_datetime']) && $input['end_datetime'] !== '' ? trim((string)$input['end_datetime']) : null;
    $isRecurring = !empty($input['is_recurring']) ? 1 : 0;
    $rec = isset($input['recurrence']) && is_array($input['recurrence']) ? $input['recurrence'] : null;

    if ($title === '' || $start === '') Response::error('title and start_datetime are required', 422);
    if (strtotime($start) === false) Response::error('Invalid start_datetime', 422);
    if ($end !== null && strtotime($end) === false) Response::error('Invalid end_datetime', 422);
    if ($end !== null && $end < $start) Response::error('end_datetime must be on/after start_datetime', 422);

    try {
        $pdo->beginTransaction();
        if ($method === 'POST') {
            // Admin-created events are always immediately approved (explicit for clarity;
            // 'approved' is also the column default).
            $ins = $pdo->prepare("INSERT INTO events (title, description, start_datetime, end_datetime, is_recurring, status) VALUES (?, ?, ?, ?, ?, 'approved')");
            $ins->execute([$title, $description, $start, $end, $isRecurring]);
            $id = (int)$pdo->lastInsertId();
        } else {
            $upd = $pdo->prepare('UPDATE events SET title=?, description=?, start_datetime=?, end_datetime=?, is_recurring=? WHERE id=?');
            $upd->execute([$title, $description, $start, $end, $isRecurring, $id]);
        }
        if ($isRecurring && $rec) {
            _writeRecurrence($pdo, $id, $rec);
        } elseif (!$isRecurring) {
            $pdo->prepare('DELETE FROM event_recurrence_rules WHERE event_id=?')->execute([$id]);
        }
        $pdo->commit();
        Response::json(['ok' => true, 'id' => $id]);
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        Response::error('Failed to save event', 500);
    }
}

if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $stmt = $pdo->prepare('UPDATE events SET is_archived=1, archived_at=NOW() WHERE id=? AND is_archived=0');
    $stmt->execute([$id]);
    Response::json(['ok' => true]);
}

Response::error('Method not allowed', 405);
