<?php
// api/staff/people.php — people directory lookup for the staff roster picker.
// GET ?q=&type=&exclude_dept_id=  → [{ id, first_name, last_name, phone, type }]
//   q               name/phone LIKE filter (optional)
//   type            'teacher' | 'student' — restrict to people with that profile
//   exclude_dept_id omit people who already have an active membership in that dept
// Staff/admin guarded (see _guard.php). Unlike the roster itself, the picker
// searches the FULL non-archived people directory so a dept head can find and
// add people who are not yet in any of their departments.

use App\Utils\Response;

require_once __DIR__ . '/_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') Response::error('Method not allowed', 405);

$pdo = $GLOBALS['__staff_pdo'];
$q    = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$type = isset($_GET['type']) ? trim((string)$_GET['type']) : '';
$excludeDept = (int)($_GET['exclude_dept_id'] ?? 0);

$where  = ['p.is_archived = 0'];
$joins  = '';
$params = [];

if ($type === 'teacher') {
    $joins .= ' JOIN teachers t ON t.person_id = p.id AND t.is_archived = 0';
} elseif ($type === 'student') {
    $joins .= ' JOIN students s ON s.person_id = p.id AND s.is_archived = 0';
}

if ($q !== '') {
    $where[] = "(CONCAT(p.first_name, ' ', p.last_name) LIKE ? OR p.phone LIKE ?)";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
}

if ($excludeDept > 0) {
    $where[] = 'p.id NOT IN (SELECT person_id FROM department_memberships WHERE is_archived = 0 AND department_id = ?)';
    $params[] = $excludeDept;
}

// Derive a coarse type flag so the UI can label results without extra lookups.
$sql = "SELECT p.id, p.first_name, p.last_name, p.phone,
               EXISTS(SELECT 1 FROM teachers te WHERE te.person_id = p.id AND te.is_archived = 0) AS is_teacher,
               EXISTS(SELECT 1 FROM students st WHERE st.person_id = p.id AND st.is_archived = 0) AS is_student
        FROM people p" . $joins . '
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY p.first_name, p.last_name
        LIMIT 500';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
foreach ($rows as &$r) {
    $r['is_teacher'] = (int)$r['is_teacher'];
    $r['is_student'] = (int)$r['is_student'];
    $r['type'] = $r['is_teacher'] ? 'teacher' : ($r['is_student'] ? 'student' : 'person');
}
unset($r);

Response::json(['data' => $rows]);
