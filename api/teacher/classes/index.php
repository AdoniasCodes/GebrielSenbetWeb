<?php
// api/teacher/classes/index.php — the classes + subjects this teacher teaches,
// plus the term list (for grade/attendance pickers).
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') Response::error('Method not allowed', 405);

$pdo = tch_pdo();
$tid = teacher_id();

$classes = [];
if ($tid) {
    $stmt = $pdo->prepare(
        "SELECT a.class_id, a.subject_id,
                c.name AS class_name, c.academic_year,
                lvl.name AS level_name, lvl.name_am AS level_name_am,
                s.name AS subject_name, s.name_am AS subject_name_am,
                (SELECT COUNT(*) FROM student_class_assignments sca
                   WHERE sca.class_id=a.class_id AND sca.is_archived=0) AS student_count
         FROM teacher_subject_assignments a
         JOIN classes c ON c.id=a.class_id AND c.is_archived=0
         LEFT JOIN class_levels lvl ON lvl.id=c.level_id
         JOIN subjects s ON s.id=a.subject_id
         WHERE a.teacher_id=? AND a.is_archived=0 AND (a.end_date IS NULL OR a.end_date >= CURDATE())
         GROUP BY a.class_id, a.subject_id
         ORDER BY c.academic_year DESC, c.name, s.name");
    $stmt->execute([$tid]);
    $classes = $stmt->fetchAll();
}

$terms = $pdo->query(
    "SELECT id, name, academic_year, is_current
     FROM academic_terms
     WHERE is_archived=0
     ORDER BY is_current DESC, academic_year DESC, start_date DESC")->fetchAll();

Response::json(['data' => $classes, 'terms' => $terms]);
