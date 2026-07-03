<?php
// api/staff/people.php — read-only people lookup for the staff roster picker.
// GET ?q=  → [{ id, first_name, last_name, phone }] (active, non-archived)
// Scoped: staff users see only people in departments they head; admins see all.

use App\Utils\Response;

require_once __DIR__ . '/_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') Response::error('Method not allowed', 405);

$pdo = $GLOBALS['__staff_pdo'];
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

// Get department IDs the current user may manage
$dept_ids = staff_headed_department_ids();
if (empty($dept_ids)) {
    Response::json(['data' => []]);
    exit;
}

// Build the department filter with placeholders
$dept_placeholders = implode(',', array_fill(0, count($dept_ids), '?'));
$sql = "SELECT p.id, p.first_name, p.last_name, p.phone FROM people p
        WHERE p.is_archived=0
        AND p.id IN (
            SELECT person_id FROM department_memberships
            WHERE is_archived=0 AND department_id IN ($dept_placeholders)
        )";

// Start params with dept_ids
$params = $dept_ids;

// Add search filter if provided
if ($q !== '') {
    $sql .= " AND (CONCAT(p.first_name,' ',p.last_name) LIKE ? OR p.phone LIKE ?)";
    $like = '%' . $q . '%';
    $params = array_merge($params, [$like, $like]);
}

$sql .= ' ORDER BY p.first_name, p.last_name LIMIT 500';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
Response::json(['data' => $stmt->fetchAll()]);
