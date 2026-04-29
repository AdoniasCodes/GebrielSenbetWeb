<?php
// api/admin/audit-log/index.php — read-only viewer for the activity log

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') Response::error('Method not allowed', 405);

$config = app_config();
$db = new Database($config['db']);
$pdo = $db->pdo();

$limit  = max(1, min(500, (int)($_GET['limit'] ?? 100)));
$action = trim((string)($_GET['action'] ?? ''));
$actor  = isset($_GET['actor_user_id']) ? (int)$_GET['actor_user_id'] : 0;
$entity = trim((string)($_GET['entity_type'] ?? ''));

$sql = "SELECT al.id, al.actor_user_id, al.action, al.entity_type, al.entity_id, al.metadata,
               al.ip_addr, al.created_at,
               u.email AS actor_email
        FROM audit_log al
        LEFT JOIN users u ON u.id=al.actor_user_id
        WHERE 1=1";
$params = [];
if ($action !== '') { $sql .= ' AND al.action=?'; $params[] = $action; }
if ($actor > 0)     { $sql .= ' AND al.actor_user_id=?'; $params[] = $actor; }
if ($entity !== '') { $sql .= ' AND al.entity_type=?'; $params[] = $entity; }
$sql .= " ORDER BY al.created_at DESC LIMIT $limit";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
foreach ($rows as &$r) {
    $r['metadata'] = $r['metadata'] ? json_decode($r['metadata'], true) : null;
}

Response::json(['data' => $rows]);
