<?php
// api/staff/resources.php — files & links scoped to the departments the user heads.
// GET ?department_id= (optional) | POST (multipart file or JSON link) | DELETE {id}

use App\Utils\Response;

require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../resources_lib.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$pdo = $GLOBALS['__staff_pdo'];
$deptIds = staff_headed_department_ids();

if ($method === 'GET') {
    $deptId = (int)($_GET['department_id'] ?? 0);
    if ($deptId > 0) {
        staff_assert_dept($deptId);
        $data = res_list($pdo, 'department', [$deptId]);
    } else {
        $data = res_list($pdo, 'department', $deptIds);
    }

    $departments = [];
    if ($deptIds) {
        $ph = implode(',', array_fill(0, count($deptIds), '?'));
        $stmt = $pdo->prepare("SELECT id, name, name_am FROM departments WHERE id IN ($ph) AND is_archived=0 ORDER BY sort_order, id");
        $stmt->execute($deptIds);
        $departments = $stmt->fetchAll();
    }

    Response::json(['departments' => $departments, 'data' => $data]);
}

if ($method === 'POST') {
    $isMultipart = !empty($_FILES) ||
        (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false);

    if ($isMultipart) {
        $sid = (int)($_POST['scope_id'] ?? 0);
        res_assert_scope($sid, $deptIds);
        Response::json(res_store_file($pdo, 'department', $sid, (int)($_SESSION['user_id'] ?? 0)));
    }

    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $sid = (int)($in['scope_id'] ?? 0);
    res_assert_scope($sid, $deptIds);
    Response::json(res_store_link($pdo, 'department', $sid, (string)($in['title'] ?? ''), (string)($in['url'] ?? ''), (int)($_SESSION['user_id'] ?? 0)));
}

if ($method === 'DELETE') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    Response::json(res_archive_scoped($pdo, $id, 'department', $deptIds));
}

Response::error('Method not allowed', 405);
