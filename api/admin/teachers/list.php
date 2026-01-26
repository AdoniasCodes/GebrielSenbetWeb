<?php
// api/admin/teachers/list.php
use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';

$config = app_config();
$db = new Database($config['db']);
$pdo = $db->pdo();

// Optional search by name or phone
$search = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$params = [];
$sql = 'SELECT t.id, t.user_id, t.first_name, t.last_name, t.phone FROM teachers t WHERE t.is_archived = 0';
if ($search !== '') {
    $sql .= ' AND (t.first_name LIKE ? OR t.last_name LIKE ? OR t.phone LIKE ?)';
    $like = '%' . $search . '%';
    $params = [$like, $like, $like];
}
$sql .= ' ORDER BY t.first_name ASC, t.last_name ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
Response::json(['data' => $stmt->fetchAll()]);
