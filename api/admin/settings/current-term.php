<?php
// api/admin/settings/current-term.php — GET returns current term, PUT marks one term as current

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$config = app_config();
$db = new Database($config['db']);
$pdo = $db->pdo();

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM academic_terms WHERE is_current=1 AND is_archived=0 LIMIT 1");
    $term = $stmt->fetch() ?: null;
    if (!$term) {
        $stmt = $pdo->query("SELECT * FROM academic_terms WHERE is_archived=0 ORDER BY ABS(DATEDIFF(start_date, CURDATE())) ASC LIMIT 1");
        $term = $stmt->fetch() ?: null;
    }
    Response::json(['term' => $term]);
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);

    try {
        $pdo->beginTransaction();
        $check = $pdo->prepare("SELECT id FROM academic_terms WHERE id=? AND is_archived=0");
        $check->execute([$id]);
        if (!$check->fetch()) { $pdo->rollBack(); Response::error('Term not found', 404); }

        $pdo->exec("UPDATE academic_terms SET is_current=0");
        $upd = $pdo->prepare("UPDATE academic_terms SET is_current=1 WHERE id=?");
        $upd->execute([$id]);
        $pdo->commit();
        Response::json(['ok' => true]);
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        Response::error('Failed to set current term', 500);
    }
}

Response::error('Method not allowed', 405);
