<?php
// api/student/resources.php — read-only resources for the logged-in student's grade(s).
use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../resources_lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

$me = student_record();
if (!$me) {
    Response::json(['data' => []]);
}

$pdo = (new Database(app_config()['db']))->pdo();
$sid = (int)$me['id'];

// Compute student's grade ids (class_levels) from active class assignments.
$gstmt = $pdo->prepare(
    "SELECT DISTINCT c.level_id FROM student_class_assignments sca
     JOIN classes c ON c.id=sca.class_id
     WHERE sca.student_id=? AND sca.is_archived=0 AND c.is_archived=0");
$gstmt->execute([$sid]);
$gradeIds = array_map(function($row) { return (int)$row['level_id']; }, $gstmt->fetchAll());

Response::json(['data' => res_list($pdo, 'grade', $gradeIds)]);
