<?php
// api/staff/departments.php — departments the current user manages (heads).
// GET → [{ id, name, name_am, member_count, level_count }]

use App\Utils\Response;

require_once __DIR__ . '/_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') Response::error('Method not allowed', 405);

$pdo = $GLOBALS['__staff_pdo'];
$ids = staff_headed_department_ids();
if (!$ids) Response::json(['data' => []]);

$in = implode(',', array_fill(0, count($ids), '?'));
$sql = "SELECT d.id, d.parent_id, d.slug, d.name, d.name_am, d.description,
               (SELECT COUNT(*) FROM department_memberships dm WHERE dm.department_id=d.id AND dm.is_archived=0) AS member_count,
               (SELECT COUNT(*) FROM department_levels dl WHERE dl.department_id=d.id AND dl.is_archived=0) AS level_count
        FROM departments d
        WHERE d.id IN ($in) AND d.is_archived=0
        ORDER BY d.sort_order, d.id";
$stmt = $pdo->prepare($sql);
$stmt->execute($ids);
Response::json(['data' => $stmt->fetchAll()]);
