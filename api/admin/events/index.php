<?php
// api/admin/events/index.php — events with optional recurrence rule
// GET    /api/admin/events?upcoming=1&include_archived=
// POST   body: { title, description?, start_datetime, end_datetime?, is_recurring?, recurrence?{ freq, interval_num, by_day?, until_date? } }
// PUT    body: { id, ...same fields }
// DELETE body: { id }

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$config = app_config();
$db = new Database($config['db']);
$pdo = $db->pdo();

if ($method === 'GET') {
    $upcomingOnly    = isset($_GET['upcoming']) && $_GET['upcoming'] === '1';
    $includeArchived = isset($_GET['include_archived']) && $_GET['include_archived'] === '1';

    $sql = "SELECT e.*,
                   r.id AS rr_id, r.freq, r.interval_num, r.by_day, r.until_date
            FROM events e
            LEFT JOIN event_recurrence_rules r ON r.event_id=e.id
            WHERE 1=1";
    $params = [];
    if ($upcomingOnly) { $sql .= ' AND (e.end_datetime >= NOW() OR e.start_datetime >= NOW())'; }
    if (!$includeArchived) { $sql .= ' AND e.is_archived=0'; }
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
            $ins = $pdo->prepare('INSERT INTO events (title, description, start_datetime, end_datetime, is_recurring) VALUES (?, ?, ?, ?, ?)');
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
