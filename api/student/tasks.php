<?php
// api/student/tasks.php: read-only homework/tasks for the logged-in student.
// Closes the Phase 2.5 dead-end (teachers wrote tasks, nobody could read them).
//   GET -> { data: [ ...tasks addressed to this student's class/grade/departments ] }

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../tasks_lib.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    Response::error('Method not allowed', 405);
}

$me = student_record();
if (!$me) Response::json(['data' => []]);

$pdo = (new Database(app_config()['db']))->pdo();
Response::json(['data' => tsk_list_for_students($pdo, [(int)$me['id']])]);
