<?php
// api/teacher/resources.php — view / upload (file or link) / remove resources
// scoped to the grades (class_levels) the current teacher teaches.
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

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $grades = [];
    if ($gradeIds) {
        $ph = implode(',', array_fill(0, count($gradeIds), '?'));
        $gst = $pdo->prepare("SELECT id, name, name_am FROM class_levels WHERE id IN ($ph) AND is_archived=0 ORDER BY sort_order, id");
        $gst->execute($gradeIds);
        $grades = $gst->fetchAll();
    }
    Response::json(['grades' => $grades, 'data' => res_list($pdo, 'grade', $gradeIds)]);
}

if ($method === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');
    $isMultipart = !empty($_FILES) || stripos($contentType, 'multipart') !== false;

    if ($isMultipart) {
        $sid = (int)($_POST['scope_id'] ?? 0);
        res_assert_scope($sid, $gradeIds);
        Response::json(res_store_file($pdo, 'grade', $sid, (int)($_SESSION['user_id'] ?? 0)));
    }

    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $sid = (int)($in['scope_id'] ?? 0);
    res_assert_scope($sid, $gradeIds);
    Response::json(res_store_link($pdo, 'grade', $sid, $in['title'] ?? '', $in['url'] ?? '', (int)($_SESSION['user_id'] ?? 0)));
}

if ($method === 'DELETE') {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    Response::json(res_archive_scoped($pdo, (int)($in['id'] ?? 0), 'grade', $gradeIds));
}

Response::error('Method not allowed', 405);
