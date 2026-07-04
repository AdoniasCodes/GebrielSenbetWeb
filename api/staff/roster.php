<?php
// api/staff/roster.php — dept-head roster additions (scoped to headed depts).
// POST { action:'add_existing', department_id, person_id, level_id?, title? }
//   Add an existing person (teacher/student) to a department the caller heads.
// POST { action:'create_new', department_id, role:'teacher'|'student',
//        first_name, last_name, email, password?, phone?, guardian_name?, address?, bio? }
//   Create a brand-new account AND add it to the caller's department in one go.
//
// Rules enforced here:
//  - staff_assert_dept() gates every write to a department the caller heads.
//  - assigned_by_user_id is always the acting user.
//  - duplicate active membership is rejected (409).
//  - assigning a TEACHER writes the per-user assignment notification.

use App\Utils\Response;

require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../person_accounts_lib.php';
require_csrf_for_write();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    Response::error('Method not allowed', 405);
}

$pdo    = $GLOBALS['__staff_pdo'];
$in     = json_decode(file_get_contents('php://input'), true) ?: [];
$action = trim((string)($in['action'] ?? ''));
$deptId = (int)($in['department_id'] ?? 0);
$actorUserId = (int)($_SESSION['user_id'] ?? 0);
$actorRoleId = (int)($_SESSION['role_id'] ?? 0);

if ($deptId <= 0) Response::error('department_id is required', 422);
staff_assert_dept($deptId);

// Load the target department (for the notification label + level validation).
$dq = $pdo->prepare('SELECT id, name, name_am FROM departments WHERE id = ? AND is_archived = 0');
$dq->execute([$deptId]);
$dept = $dq->fetch();
if (!$dept) Response::error('Department not found', 404);
$deptLabel = department_display_name($dept);

// Optional level must belong to this department.
function _roster_validate_level(\PDO $pdo, $rawLevel, int $deptId): ?int {
    $levelId = (int)($rawLevel ?? 0) ?: null;
    if ($levelId) {
        $lc = $pdo->prepare('SELECT id FROM department_levels WHERE id=? AND department_id=? AND is_archived=0');
        $lc->execute([$levelId, $deptId]);
        if (!$lc->fetch()) Response::error('Level does not belong to this department', 422);
    }
    return $levelId;
}

// Insert a membership and, when the person is a teacher, notify them.
function _roster_add_membership(
    \PDO $pdo, int $personId, int $deptId, ?int $levelId, ?string $title,
    int $actorUserId, ?int $actorRoleId, string $deptLabel
): int {
    $ex = $pdo->prepare('SELECT id FROM department_memberships WHERE person_id=? AND department_id=? AND is_archived=0 LIMIT 1');
    $ex->execute([$personId, $deptId]);
    if ($ex->fetch()) Response::error('This person is already a member of this department', 409);

    $ins = $pdo->prepare('INSERT INTO department_memberships (person_id, department_id, level_id, title, assigned_by_user_id, is_head, joined_at) VALUES (?,?,?,?,?,0,NULL)');
    $ins->execute([$personId, $deptId, $levelId, $title, $actorUserId ?: null]);
    $membershipId = (int)$pdo->lastInsertId();

    // Notify the person IF they are a teacher (per product rule).
    $tq = $pdo->prepare('SELECT p.user_id FROM people p JOIN teachers t ON t.person_id = p.id AND t.is_archived = 0 WHERE p.id = ?');
    $tq->execute([$personId]);
    $trow = $tq->fetch();
    if ($trow && (int)$trow['user_id'] > 0) {
        notify_department_assignment($pdo, (int)$trow['user_id'], [$deptLabel], $actorUserId, $actorRoleId);
    }
    return $membershipId;
}

if ($action === 'add_existing') {
    $personId = (int)($in['person_id'] ?? 0);
    if ($personId <= 0) Response::error('person_id is required', 422);
    $pc = $pdo->prepare('SELECT id FROM people WHERE id=? AND is_archived=0');
    $pc->execute([$personId]);
    if (!$pc->fetch()) Response::error('Person not found', 404);

    $levelId = _roster_validate_level($pdo, $in['level_id'] ?? null, $deptId);
    $title   = isset($in['title']) && $in['title'] !== '' ? trim((string)$in['title']) : null;

    try {
        $pdo->beginTransaction();
        $membershipId = _roster_add_membership($pdo, $personId, $deptId, $levelId, $title, $actorUserId, $actorRoleId, $deptLabel);
        $pdo->commit();
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
    \App\Audit::log('staff.roster.add_existing', 'department_membership', $membershipId, ['department_id' => $deptId, 'person_id' => $personId]);
    Response::json(['ok' => true, 'membership_id' => $membershipId], 201);
}

if ($action === 'create_new') {
    $role = trim((string)($in['role'] ?? ''));
    if (!in_array($role, ['teacher', 'student'], true)) {
        Response::error("role must be 'teacher' or 'student'", 422);
    }
    $levelId = _roster_validate_level($pdo, $in['level_id'] ?? null, $deptId);
    $title   = isset($in['title']) && $in['title'] !== '' ? trim((string)$in['title']) : null;

    try {
        $pdo->beginTransaction();
        $acct = create_person_account($pdo, $in); // throws PersonAccountError
        $personId = (int)$acct['person_id'];
        $membershipId = _roster_add_membership($pdo, $personId, $deptId, $levelId, $title, $actorUserId, $actorRoleId, $deptLabel);
        $pdo->commit();
    } catch (PersonAccountError $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        Response::error($e->getMessage(), $e->status);
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        Response::error('Failed to create account', 500);
    }

    \App\Audit::log('staff.roster.create_new', 'user', (int)$acct['user_id'], ['role' => $role, 'department_id' => $deptId]);
    Response::json([
        'ok'                 => true,
        'membership_id'      => $membershipId,
        'user_id'            => (int)$acct['user_id'],
        'person_id'          => $personId,
        'role'               => $role,
        'generated_password' => $acct['password_generated'] ? $acct['password'] : null,
    ], 201);
}

Response::error('Unknown action', 422);
