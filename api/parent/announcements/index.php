<?php
// api/parent/announcements/index.php — announcements relevant to the parent.
// Includes: any is_public announcement, anything targeted at role=parent, and
// (per child) any department- or class-targeted announcement for a department
// the child belongs to / the child's current active class.
use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_once __DIR__ . '/../../notifications_lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') Response::error('Method not allowed', 405);

$config = app_config();
$pdo = (new Database($config['db']))->pdo();

$studentIds = parent_student_ids();
$deptIds = [];
$classIds = [];
if ($studentIds) {
    $ph = parent_student_id_placeholders();

    $dstmt = $pdo->prepare(
        "SELECT DISTINCT dm.department_id
         FROM department_memberships dm
         JOIN students s ON s.person_id = dm.person_id
         WHERE s.id IN $ph AND dm.is_archived=0 AND (dm.ended_at IS NULL OR dm.ended_at >= CURDATE())"
    );
    $dstmt->execute($studentIds);
    $deptIds = array_map('intval', $dstmt->fetchAll(\PDO::FETCH_COLUMN));

    $cstmt = $pdo->prepare(
        "SELECT DISTINCT sca.class_id
         FROM student_class_assignments sca
         WHERE sca.student_id IN $ph AND sca.is_archived=0
           AND sca.id = (SELECT MAX(id) FROM student_class_assignments WHERE student_id = sca.student_id AND is_archived=0)"
    );
    $cstmt->execute($studentIds);
    $classIds = array_map('intval', $cstmt->fetchAll(\PDO::FETCH_COLUMN));
}

// Targeting + unread via the shared contract (api/notifications_lib.php).
$q = notif_inbox_query([
    'user_id'  => (int)($_SESSION['user_id'] ?? 0),
    'role'     => 'parent',
    'dept_ids' => $deptIds,
    'class_ids'=> $classIds,
], 100);
$stmt = $pdo->prepare($q['sql']);
$stmt->execute($q['params']);
$rows = array_map(function ($r) {
    return [
        'id' => $r['id'], 'title' => $r['title'], 'message' => $r['message'],
        'created_at' => $r['created_at'], 'is_read' => (bool)$r['is_read'],
    ];
}, $stmt->fetchAll());
Response::json(['data' => $rows]);
