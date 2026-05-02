<?php
// api/events/index.php — public read-only feed of upcoming events (no auth required)

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') Response::error('Method not allowed', 405);

$limit = max(1, min(50, (int)($_GET['limit'] ?? 10)));
$config = app_config();
$db = new Database($config['db']);
$pdo = $db->pdo();

$sql = "SELECT e.id, e.title, e.description, e.start_datetime, e.end_datetime, e.is_recurring,
               r.freq, r.interval_num, r.by_day, r.until_date
        FROM events e
        LEFT JOIN event_recurrence_rules r ON r.event_id = e.id
        WHERE e.is_archived = 0
          AND (e.end_datetime >= NOW() OR e.start_datetime >= NOW() OR e.is_recurring = 1)
        ORDER BY e.start_datetime ASC
        LIMIT $limit";
$rows = $pdo->query($sql)->fetchAll();

Response::json(['data' => $rows]);
