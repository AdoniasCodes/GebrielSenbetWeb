<?php
// api/teacher/attendance/summary.php — per-student attendance % for a class in a
// term (Phase 2.3). Read-only.
//   GET ?class_id=&term_id=  -> { data: [ {student_id, first_name, last_name,
//                                          present, late, absent, excused, rate}, ... ] }
// Rate uses the canonical formula (present+late)/(present+late+absent), excused
// excluded — same as the student dashboard and eligibility engine.
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_once __DIR__ . '/../../attendance_lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') Response::error('Method not allowed', 405);

$classId = (int)($_GET['class_id'] ?? 0);
$termId  = (int)($_GET['term_id'] ?? 0);
if ($classId <= 0 || $termId <= 0) Response::error('class_id and term_id are required', 422);

// A teacher may only see attendance for a class they teach.
teacher_assert_class($classId);

Response::json(['data' => attendance_class_summary(tch_pdo(), $classId, $termId)]);
