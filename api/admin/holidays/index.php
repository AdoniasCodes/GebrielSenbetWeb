<?php
// api/admin/holidays/index.php — EOTC holiday / celebration calendar.
// GET    ?include_archived=   → holidays (with serving-assignment count)
// POST   { name, name_am?, holiday_date?, scale?, is_recurring_annually?, description? }
// PUT    { id, ...any }
// DELETE { id }

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$config = app_config();
$pdo = (new Database($config['db']))->pdo();

const HOLIDAY_SCALES = ['major','minor'];

if ($method === 'GET') {
    $includeArc = isset($_GET['include_archived']) && $_GET['include_archived'] === '1';
    $sql = "SELECT h.id, h.name, h.name_am, h.holiday_date, h.scale, h.is_recurring_annually, h.description, h.is_archived,
                   (SELECT COUNT(*) FROM serving_assignments sa WHERE sa.holiday_id=h.id AND sa.is_archived=0) AS serving_count
            FROM holidays h";
    if (!$includeArc) $sql .= ' WHERE h.is_archived=0';
    $sql .= ' ORDER BY (h.holiday_date IS NULL), h.holiday_date, h.name';
    Response::json(['data' => $pdo->query($sql)->fetchAll()]);
}

if ($method === 'POST') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $name = trim((string)($in['name'] ?? ''));
    $nameAm = isset($in['name_am']) && $in['name_am'] !== '' ? trim((string)$in['name_am']) : null;
    if ($name === '' && $nameAm === null) Response::error('name (or name_am) is required', 422);
    $date = isset($in['holiday_date']) && $in['holiday_date'] !== '' ? trim((string)$in['holiday_date']) : null;
    $scale = in_array($in['scale'] ?? '', HOLIDAY_SCALES, true) ? $in['scale'] : 'minor';
    $rec = array_key_exists('is_recurring_annually', $in) ? (!empty($in['is_recurring_annually']) ? 1 : 0) : 1;
    $desc = isset($in['description']) && $in['description'] !== '' ? trim((string)$in['description']) : null;
    $ins = $pdo->prepare('INSERT INTO holidays (name, name_am, holiday_date, scale, is_recurring_annually, description) VALUES (?,?,?,?,?,?)');
    $ins->execute([$name ?: ($nameAm ?? ''), $nameAm, $date, $scale, $rec, $desc]);
    $id = (int)$pdo->lastInsertId();
    \App\Audit::log('holiday.create', 'holiday', $id, ['name' => $name]);
    Response::json(['ok' => true, 'id' => $id], 201);
}

if ($method === 'PUT') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $cur = $pdo->prepare('SELECT id FROM holidays WHERE id=? AND is_archived=0'); $cur->execute([$id]);
    if (!$cur->fetch()) Response::error('Holiday not found', 404);
    $fields = []; $params = [];
    if (array_key_exists('name', $in))         { $fields[]='name=?'; $params[]=trim((string)$in['name']); }
    if (array_key_exists('name_am', $in))      { $fields[]='name_am=?'; $params[]=$in['name_am']!==''?trim((string)$in['name_am']):null; }
    if (array_key_exists('holiday_date', $in)) { $fields[]='holiday_date=?'; $params[]=$in['holiday_date']!==''?trim((string)$in['holiday_date']):null; }
    if (array_key_exists('scale', $in))        { $fields[]='scale=?'; $params[]=in_array($in['scale'],HOLIDAY_SCALES,true)?$in['scale']:'minor'; }
    if (array_key_exists('is_recurring_annually', $in)) { $fields[]='is_recurring_annually=?'; $params[]=!empty($in['is_recurring_annually'])?1:0; }
    if (array_key_exists('description', $in))  { $fields[]='description=?'; $params[]=$in['description']!==''?trim((string)$in['description']):null; }
    if (!$fields) Response::error('Nothing to update', 422);
    $params[] = $id;
    $pdo->prepare('UPDATE holidays SET ' . implode(', ', $fields) . ' WHERE id=?')->execute($params);
    \App\Audit::log('holiday.update', 'holiday', $id);
    Response::json(['ok' => true]);
}

if ($method === 'DELETE') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $pdo->prepare('UPDATE serving_assignments SET is_archived=1, archived_at=NOW() WHERE holiday_id=? AND is_archived=0')->execute([$id]);
    $pdo->prepare('UPDATE holidays SET is_archived=1, archived_at=NOW() WHERE id=?')->execute([$id]);
    \App\Audit::log('holiday.archive', 'holiday', $id);
    Response::json(['ok' => true]);
}

Response::error('Method not allowed', 405);
