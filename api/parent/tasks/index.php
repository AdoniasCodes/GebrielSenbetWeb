<?php
// api/parent/tasks/index.php: read-only homework/tasks for the parent's children.
// Same resolution rules as the student view (api/tasks_lib.php), so a parent and
// their child never see a different homework list.
//   GET [?student_id=N] -> { data: [...], students: [{id,name}] }

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_once __DIR__ . '/../../tasks_lib.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    Response::error('Method not allowed', 405);
}

$allowed = parent_student_ids();
if (!$allowed) Response::json(['data' => [], 'students' => []]);

$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
if ($studentId > 0 && !in_array($studentId, $allowed, true)) Response::error('Forbidden', 403);

$pdo = (new Database(app_config()['db']))->pdo();

// Child list so the UI can label and filter without a second call.
$ph = implode(',', array_fill(0, count($allowed), '?'));
$st = $pdo->prepare("SELECT id, first_name, last_name FROM students WHERE id IN ($ph) ORDER BY first_name, last_name");
$st->execute($allowed);
$students = array_map(static function (array $s): array {
    return [
        'id'   => (int)$s['id'],
        'name' => trim($s['first_name'] . ' ' . $s['last_name']),
    ];
}, $st->fetchAll());

Response::json([
    'data'     => tsk_list_for_students($pdo, $allowed, $studentId > 0 ? $studentId : null),
    'students' => $students,
]);
