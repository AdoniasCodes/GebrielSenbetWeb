<?php
// api/teacher/resources.php — view / upload (file or link) / remove resources
// scoped to the grades (class_levels) the current teacher teaches, and the
// departments the teacher belongs to.
use App\Utils\Response;

require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../resources_lib.php';
require_csrf_for_write();

$pdo = tch_pdo();
$tid = teacher_id();

$gradeIds = [];
if ($tid) {
    $st = $pdo->prepare(
        "SELECT DISTINCT c.level_id
         FROM teacher_subject_assignments a
         JOIN classes c ON c.id = a.class_id
         WHERE a.teacher_id = ? AND a.is_archived = 0
           AND (a.end_date IS NULL OR a.end_date >= CURDATE())"
    );
    $st->execute([$tid]);
    $gradeIds = array_values(array_filter(array_map('intval', $st->fetchAll(\PDO::FETCH_COLUMN))));
}
$deptIds = teacher_department_ids();

// Normalize a client-supplied scope_type and resolve it to the caller's
// allowed-id set for that type. Unknown/omitted -> 'grade' (back-compat).
function res_norm_scope_type($v): string {
    return $v === 'department' ? 'department' : 'grade';
}
function res_scope_ids(string $scopeType, array $gradeIds, array $deptIds): array {
    return $scopeType === 'department' ? $deptIds : $gradeIds;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $grades = [];
    if ($gradeIds) {
        $ph = implode(',', array_fill(0, count($gradeIds), '?'));
        $gst = $pdo->prepare("SELECT id, name, name_am FROM class_levels WHERE id IN ($ph) AND is_archived=0 ORDER BY sort_order, id");
        $gst->execute($gradeIds);
        $grades = $gst->fetchAll();
    }
    $departments = [];
    if ($deptIds) {
        $ph = implode(',', array_fill(0, count($deptIds), '?'));
        $dst = $pdo->prepare("SELECT id, slug, name, name_am FROM departments WHERE id IN ($ph) AND is_archived=0 ORDER BY sort_order, name");
        $dst->execute($deptIds);
        $departments = $dst->fetchAll();
    }
    $data = array_merge(res_list($pdo, 'grade', $gradeIds), res_list($pdo, 'department', $deptIds));
    Response::json(['grades' => $grades, 'departments' => $departments, 'data' => $data]);
}

if ($method === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');
    $isMultipart = !empty($_FILES) || stripos($contentType, 'multipart') !== false;

    if ($isMultipart) {
        $scopeType = res_norm_scope_type($_POST['scope_type'] ?? null);
        $sid = (int)($_POST['scope_id'] ?? 0);
        res_assert_scope($sid, res_scope_ids($scopeType, $gradeIds, $deptIds));
        Response::json(res_store_file($pdo, $scopeType, $sid, (int)($_SESSION['user_id'] ?? 0)));
    }

    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $scopeType = res_norm_scope_type($in['scope_type'] ?? null);
    $sid = (int)($in['scope_id'] ?? 0);
    res_assert_scope($sid, res_scope_ids($scopeType, $gradeIds, $deptIds));
    Response::json(res_store_link($pdo, $scopeType, $sid, $in['title'] ?? '', $in['url'] ?? '', (int)($_SESSION['user_id'] ?? 0)));
}

if ($method === 'DELETE') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($in['id'] ?? 0);
    // Determine the resource's actual scope_type so it's checked against the
    // right allowed-id set — the client doesn't need to (and shouldn't have to)
    // tell us which scope a resource lives in.
    $row = $pdo->prepare('SELECT scope_type FROM resources WHERE id=? AND is_archived=0');
    $row->execute([$id]);
    $scopeType = $row->fetchColumn();
    if ($scopeType !== 'grade' && $scopeType !== 'department') Response::error('Not found in your scope', 404);
    Response::json(res_archive_scoped($pdo, $id, $scopeType, res_scope_ids($scopeType, $gradeIds, $deptIds)));
}

Response::error('Method not allowed', 405);
