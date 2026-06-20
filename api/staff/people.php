<?php
// api/staff/people.php — read-only people lookup for the staff roster picker.
// GET ?q=  → [{ id, first_name, last_name, phone }] (active, non-archived)

use App\Utils\Response;

require_once __DIR__ . '/_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') Response::error('Method not allowed', 405);

$pdo = $GLOBALS['__staff_pdo'];
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$sql = "SELECT id, first_name, last_name, phone FROM people WHERE is_archived=0";
$params = [];
if ($q !== '') {
    $sql .= " AND (CONCAT(first_name,' ',last_name) LIKE ? OR phone LIKE ?)";
    $like = '%' . $q . '%';
    $params = [$like, $like];
}
$sql .= ' ORDER BY first_name, last_name LIMIT 500';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
Response::json(['data' => $stmt->fetchAll()]);
