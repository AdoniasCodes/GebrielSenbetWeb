<?php
// api/teacher/roster/index.php — students in a class the teacher teaches, each
// with their current grade for a given subject+term (LEFT JOIN, so ungraded
// students are included for entry).
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_once __DIR__ . '/../../grades_lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') Response::error('Method not allowed', 405);

$classId = (int)($_GET['class_id'] ?? 0);
$subjectId = (int)($_GET['subject_id'] ?? 0);
$termId = (int)($_GET['term_id'] ?? 0);
if ($classId <= 0 || $subjectId <= 0 || $termId <= 0) Response::error('class_id, subject_id and term_id are required', 422);
teacher_assert_class_subject($classId, $subjectId);

// Dedupe assignments in a subquery (a student can have multiple assignment rows);
// grades' unique key (student,subject,class,term) guarantees at most one g row each,
// so no GROUP BY is needed (which ONLY_FULL_GROUP_BY would reject for g.*).
$pdo = tch_pdo();
$stmt = $pdo->prepare(
    "SELECT s.id AS student_id, s.person_id, s.first_name, s.last_name,
            g.id AS grade_id, g.score, g.remarks
     FROM (SELECT DISTINCT student_id FROM student_class_assignments
           WHERE class_id=? AND is_archived=0) sca
     JOIN students s ON s.id=sca.student_id AND s.is_archived=0
     LEFT JOIN grades g ON g.student_id=s.id AND g.class_id=? AND g.subject_id=? AND g.term_id=? AND g.is_archived=0
     ORDER BY s.first_name, s.last_name");
$stmt->execute([$classId, $classId, $subjectId, $termId]);

// Lock state for this gradebook so the UI can disable entry + show finalize/reopen.
$termClosed = grade_is_term_closed($pdo, $termId);
$finalized  = grade_is_finalized($pdo, $classId, $subjectId, $termId);
Response::json([
    'data' => $stmt->fetchAll(),
    'lock' => [
        'term_closed' => $termClosed,
        'finalized'   => $finalized,
        // editable = writable by THIS teacher (admin bypass doesn't apply here).
        'editable'    => !$termClosed && !$finalized,
    ],
]);
