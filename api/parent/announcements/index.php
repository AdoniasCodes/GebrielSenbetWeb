<?php
// api/parent/announcements/index.php — announcements relevant to the parent.
// Includes: any is_public announcement, anything targeted at role=parent, and
// (per child) any department- or class-targeted announcement for a department
// the child belongs to / the child's current active class.
use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';

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

$sql = "SELECT id, title, message, target_type, target_payload, is_public, created_at
        FROM notifications
        WHERE is_archived=0
          AND (
            is_public=1
            OR (target_type='role' AND JSON_EXTRACT(target_payload, '$.role') = 'parent')";
$params = [];
if ($deptIds) {
    $dph = implode(',', array_fill(0, count($deptIds), '?'));
    // JSON_UNQUOTE, not a bare JSON_EXTRACT comparison: PDO sends the bound
    // param as a typed value (ATTR_EMULATE_PREPARES=false), which a raw
    // JSON_EXTRACT scalar won't match (see api/teacher/notifications.php).
    $sql .= " OR (target_type='department' AND JSON_UNQUOTE(JSON_EXTRACT(target_payload,'$.department_id')) IN ($dph))";
    $params = array_merge($params, $deptIds);
}
if ($classIds) {
    $cph = implode(',', array_fill(0, count($classIds), '?'));
    $sql .= " OR (target_type='class' AND JSON_UNQUOTE(JSON_EXTRACT(target_payload,'$.class_id')) IN ($cph))";
    $params = array_merge($params, $classIds);
}
$sql .= "  )
        ORDER BY created_at DESC
        LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
foreach ($rows as &$r) {
    $r['target_payload'] = $r['target_payload'] ? json_decode($r['target_payload'], true) : null;
}
Response::json(['data' => $rows]);
