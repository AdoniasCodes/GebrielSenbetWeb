<?php
// api/staff/_guard.php — guard for the staff (department-head) area.
// Allows role 'staff' (scoped to the departments their person heads) and 'admin'
// (full scope). Provides staff_headed_department_ids() and staff_assert_dept().

use App\Database;
use App\Utils\Response;
use App\Utils\Csrf;

require_once __DIR__ . '/../../bootstrap.php';

$config = app_config();
Csrf::ensureSession($config['app']['session_name']);

$__role = $_SESSION['role_name'] ?? '';
if ($__role !== 'staff' && $__role !== 'admin') {
    Response::error('Forbidden', 403);
}

$GLOBALS['__staff_pdo'] = (new Database($config['db']))->pdo();

function require_csrf_for_write(): void {
    $cfg = app_config();
    if (!in_array($_SERVER['REQUEST_METHOD'], ['POST','PUT','PATCH','DELETE'], true)) return;
    if (!\App\Utils\Csrf::validate($cfg['app']['csrf_header'])) {
        \App\Utils\Response::error('Invalid CSRF token', 403);
    }
}

// Department ids the current user may manage. Admin → all; staff → the depts
// where their linked person is a head (membership.is_head=1).
function staff_headed_department_ids(): array {
    $pdo = $GLOBALS['__staff_pdo'];
    if (($_SESSION['role_name'] ?? '') === 'admin') {
        return array_map('intval', $pdo->query('SELECT id FROM departments WHERE is_archived=0')->fetchAll(\PDO::FETCH_COLUMN));
    }
    $uid = (int)($_SESSION['user_id'] ?? 0);
    $stmt = $pdo->prepare(
        'SELECT DISTINCT dm.department_id
         FROM department_memberships dm
         JOIN people p ON p.id = dm.person_id
         WHERE p.user_id = ? AND dm.is_head = 1 AND dm.is_archived = 0'
    );
    $stmt->execute([$uid]);
    return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
}

function staff_assert_dept(int $deptId): void {
    if (!in_array($deptId, staff_headed_department_ids(), true)) {
        \App\Utils\Response::error('You do not manage this department', 403);
    }
}
