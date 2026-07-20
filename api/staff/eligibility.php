<?php
// api/staff/eligibility.php — serving eligibility, scoped to a department the user heads.
// GET ?department_id=   → { threshold, department_id, members[] }

use App\Utils\Response;

require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../eligibility_lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') Response::error('Method not allowed', 405);

$pdo = $GLOBALS['__staff_pdo'];
$deptId = (int)($_GET['department_id'] ?? 0);
if ($deptId <= 0) {
    $ids = staff_headed_department_ids();
    $deptId = $ids[0] ?? 0;
}
if ($deptId <= 0) Response::error('No department available', 404);
staff_assert_dept($deptId);

// Phase 2.3: attach a term-scoped rate per member (additive; eligibility flag
// still uses the all-time rate). Default to the current term.
$termId = (int)($_GET['term_id'] ?? 0) ?: gs_current_term_id($pdo);
$result = gs_compute_eligibility($pdo, $deptId, $termId);
$result['department_id'] = $deptId;
Response::json($result);
