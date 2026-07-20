<?php
// api/admin/eligibility/index.php — serving eligibility for a department's members.
// GET ?department_id=   (defaults to the choir / mezmur)  → { threshold, department_id, members[] }

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_once __DIR__ . '/../../eligibility_lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') Response::error('Method not allowed', 405);

$config = app_config();
$pdo = (new Database($config['db']))->pdo();

$deptId = (int)($_GET['department_id'] ?? 0);
if ($deptId <= 0) {
    $deptId = (int)($pdo->query("SELECT id FROM departments WHERE slug='mezmur' AND is_archived=0 LIMIT 1")->fetchColumn() ?: 0);
}
if ($deptId <= 0) Response::error('No department available', 404);

// Phase 2.3: term-scoped rate per member (additive; eligibility flag unchanged).
$termId = (int)($_GET['term_id'] ?? 0) ?: gs_current_term_id($pdo);
$result = gs_compute_eligibility($pdo, $deptId, $termId);
$result['department_id'] = $deptId;
Response::json($result);
