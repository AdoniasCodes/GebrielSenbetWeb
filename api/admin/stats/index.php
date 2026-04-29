<?php
// api/admin/stats/index.php — dashboard summary counts for admin overview

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';

$config = app_config();
$db = new Database($config['db']);
$pdo = $db->pdo();

// Total active students
$stmt = $pdo->query("SELECT COUNT(*) AS c FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='student' AND u.is_archived=0");
$totalStudents = (int)$stmt->fetch()['c'];

// Active classes
$stmt = $pdo->query("SELECT COUNT(*) AS c FROM classes WHERE is_archived=0");
$activeClasses = (int)$stmt->fetch()['c'];

// Active teachers
$stmt = $pdo->query("SELECT COUNT(*) AS c FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='teacher' AND u.is_archived=0");
$activeTeachers = (int)$stmt->fetch()['c'];

// Current term — pick row with is_current=1, else nearest by date
$stmt = $pdo->query("SELECT * FROM academic_terms WHERE is_current=1 AND is_archived=0 LIMIT 1");
$currentTerm = $stmt->fetch();
if (!$currentTerm) {
    $stmt = $pdo->query("SELECT * FROM academic_terms WHERE is_archived=0 ORDER BY ABS(DATEDIFF(start_date, CURDATE())) ASC LIMIT 1");
    $currentTerm = $stmt->fetch() ?: null;
}

// Unpaid this term
$unpaidThisTerm = 0;
$outstandingAmount = 0.0;
if ($currentTerm) {
    $u = $pdo->prepare("SELECT COUNT(*) AS c, COALESCE(SUM(amount),0) AS owed FROM payments WHERE term_id=? AND status IN ('unpaid','partial') AND is_archived=0");
    $u->execute([(int)$currentTerm['id']]);
    $row = $u->fetch();
    $unpaidThisTerm = (int)$row['c'];
    $outstandingAmount = (float)$row['owed'];
}

// Posts this month
$stmt = $pdo->query("SELECT COUNT(*) AS c FROM blog_posts WHERE is_archived=0 AND created_at >= DATE_FORMAT(CURDATE(),'%Y-%m-01')");
$postsThisMonth = (int)$stmt->fetch()['c'];

// Recent grade entries (last 5)
$stmt = $pdo->query("
    SELECT g.id, g.score, g.remarks, g.created_at,
           c.name AS class_name, c.academic_year,
           lvl.name AS level_name,
           s.first_name AS student_first, s.last_name AS student_last,
           subj.name AS subject_name,
           t.first_name AS teacher_first, t.last_name AS teacher_last
    FROM grades g
    JOIN classes c ON c.id=g.class_id
    JOIN class_levels lvl ON lvl.id=c.level_id
    JOIN students s ON s.id=g.student_id
    JOIN subjects subj ON subj.id=g.subject_id
    LEFT JOIN teacher_subject_assignments tsa
      ON tsa.class_id=g.class_id AND tsa.subject_id=g.subject_id AND tsa.role='primary' AND tsa.is_archived=0
    LEFT JOIN teachers t ON t.id=tsa.teacher_id
    WHERE g.is_archived=0
    ORDER BY g.created_at DESC
    LIMIT 5
");
$recentGrades = $stmt->fetchAll();

// Recent enrollments (last 5 students added)
$stmt = $pdo->query("
    SELECT s.id, s.first_name, s.last_name, s.created_at,
           sca.id AS assignment_id,
           c.name AS class_name, lvl.name AS level_name
    FROM students s
    LEFT JOIN student_class_assignments sca
      ON sca.student_id=s.id AND sca.is_archived=0
      AND sca.id = (SELECT MAX(id) FROM student_class_assignments WHERE student_id=s.id AND is_archived=0)
    LEFT JOIN classes c ON c.id=sca.class_id
    LEFT JOIN class_levels lvl ON lvl.id=c.level_id
    WHERE s.is_archived=0
    ORDER BY s.created_at DESC
    LIMIT 5
");
$recentEnrollments = $stmt->fetchAll();

Response::json([
    'stats' => [
        'total_students'    => $totalStudents,
        'active_classes'    => $activeClasses,
        'active_teachers'   => $activeTeachers,
        'unpaid_this_term'  => $unpaidThisTerm,
        'outstanding_amount'=> $outstandingAmount,
        'posts_this_month'  => $postsThisMonth,
    ],
    'current_term'        => $currentTerm,
    'recent_grades'       => $recentGrades,
    'recent_enrollments'  => $recentEnrollments,
]);
