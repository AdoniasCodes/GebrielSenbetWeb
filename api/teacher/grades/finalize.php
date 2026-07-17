<?php
// api/teacher/grades/finalize.php — teacher self-service gradebook lock (Phase 2.2).
//   POST { class_id, subject_id, term_id, action: 'finalize' | 'reopen' }
// A gradebook is one (class, subject, term). Finalizing inserts a grade_finalizations
// row (presence = locked); reopening deletes it. The teacher must be assigned to the
// class+subject. A closed term is a hard lock: it cannot be finalized/reopened here
// (admin owns the term lock) — reopening a gradebook in a closed term is refused.
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_once __DIR__ . '/../../grades_lib.php';
require_csrf_for_write();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') Response::error('Method Not Allowed', 405);

$pdo = tch_pdo();
$userId = (int)($_SESSION['user_id'] ?? 0);

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$classId   = (int)($in['class_id'] ?? 0);
$subjectId = (int)($in['subject_id'] ?? 0);
$termId    = (int)($in['term_id'] ?? 0);
$action    = (string)($in['action'] ?? '');
if ($classId <= 0 || $subjectId <= 0 || $termId <= 0) {
    Response::error('class_id, subject_id, term_id are required', 422);
}
if ($action !== 'finalize' && $action !== 'reopen') {
    Response::error("action must be 'finalize' or 'reopen'", 422);
}

// Must teach this class+subject.
teacher_assert_class_subject($classId, $subjectId);

// The hard term lock overrides the teacher's soft lock in both directions.
if (grade_is_term_closed($pdo, $termId)) {
    Response::error('This term is closed; an admin must reopen it first. / ይህ ኮርስ ተዘግቷል፤ መጀመሪያ አስተዳዳሪ መክፈት አለበት።', 423);
}

if ($action === 'finalize') {
    $st = $pdo->prepare(
        'INSERT INTO grade_finalizations (class_id, subject_id, term_id, finalized_by_user_id)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE finalized_by_user_id = VALUES(finalized_by_user_id), finalized_at = CURRENT_TIMESTAMP'
    );
    $st->execute([$classId, $subjectId, $termId, $userId]);
    \App\Audit::log('grade.finalize', 'gradebook', 0, ['class_id' => $classId, 'subject_id' => $subjectId, 'term_id' => $termId]);
    Response::json(['message' => 'Gradebook finalized', 'finalized' => true]);
}

// reopen
$st = $pdo->prepare('DELETE FROM grade_finalizations WHERE class_id=? AND subject_id=? AND term_id=?');
$st->execute([$classId, $subjectId, $termId]);
\App\Audit::log('grade.reopen', 'gradebook', 0, ['class_id' => $classId, 'subject_id' => $subjectId, 'term_id' => $termId]);
Response::json(['message' => 'Gradebook reopened', 'finalized' => false]);
