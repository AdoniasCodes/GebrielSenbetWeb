<?php
// api/admin/terms/close.php — admin closes/reopens an academic term (Phase 2.2).
//   POST { id, action: 'close' | 'reopen' }
// Closing a term is a HARD grade lock: it blocks every grade write for that term
// (teacher and admin alike) until reopened. Notifies all teachers on both actions.
use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_once __DIR__ . '/../../notifications_lib.php';
require_csrf_for_write();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') Response::error('Method not allowed', 405);

$pdo = (new Database(app_config()['db']))->pdo();
$adminId = (int)($_SESSION['user_id'] ?? 0);
$roleId  = (int)($_SESSION['role_id'] ?? 0);

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$id = (int)($in['id'] ?? 0);
$action = (string)($in['action'] ?? '');
if ($id <= 0) Response::error('id is required', 422);
if ($action !== 'close' && $action !== 'reopen') Response::error("action must be 'close' or 'reopen'", 422);

$ts = $pdo->prepare('SELECT id, name, academic_year, closed_at FROM academic_terms WHERE id=? AND is_archived=0');
$ts->execute([$id]);
$term = $ts->fetch();
if (!$term) Response::error('Term not found', 404);

$isClosed = $term['closed_at'] !== null;
$label = trim((string)$term['academic_year'] . ' ' . (string)$term['name']);

if ($action === 'close') {
    if ($isClosed) Response::error('Term is already closed', 409);
    $pdo->prepare('UPDATE academic_terms SET closed_at=NOW(), closed_by_user_id=? WHERE id=?')->execute([$adminId ?: null, $id]);
    \App\Audit::log('term.close', 'academic_term', $id, ['term' => $label]);
    notify_role($pdo, 'teacher',
        'Term closed / ኮርስ ተዘግቷል',
        'The term "' . $label . '" is now closed. Grades for it are locked.',
        ['senderUserId' => $adminId, 'senderRoleId' => $roleId]);
    Response::json(['ok' => true, 'closed' => true]);
}

// reopen
if (!$isClosed) Response::error('Term is not closed', 409);
$pdo->prepare('UPDATE academic_terms SET closed_at=NULL, closed_by_user_id=NULL WHERE id=?')->execute([$id]);
\App\Audit::log('term.reopen', 'academic_term', $id, ['term' => $label]);
notify_role($pdo, 'teacher',
    'Term reopened / ኮርስ እንደገና ተከፍቷል',
    'The term "' . $label . '" has been reopened. You can edit grades for it again.',
    ['senderUserId' => $adminId, 'senderRoleId' => $roleId]);
Response::json(['ok' => true, 'closed' => false]);
