<?php
// api/admin/payments/generate.php — bulk-create payment rows for a term using its default_tuition.
// Optional: scope to a class. Skips students who already have a non-archived payment row for that term.
// Body: { term_id, class_id?, amount? }  (amount overrides term default if provided)

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') Response::error('Method not allowed', 405);

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$termId  = (int)($input['term_id'] ?? 0);
$classId = isset($input['class_id']) ? (int)$input['class_id'] : 0;
$amountOverride = isset($input['amount']) ? (float)$input['amount'] : null;
if ($termId <= 0) Response::error('term_id is required', 422);

$config = app_config();
$db = new Database($config['db']);
$pdo = $db->pdo();

// Term + default tuition
$ts = $pdo->prepare('SELECT id, default_tuition FROM academic_terms WHERE id=? AND is_archived=0');
$ts->execute([$termId]);
$term = $ts->fetch();
if (!$term) Response::error('Term not found', 404);

$amount = $amountOverride !== null ? $amountOverride : (float)$term['default_tuition'];
if ($amount < 0) Response::error('Amount must be non-negative', 422);

// Find students currently assigned (and matching class scope if given)
$sql = 'SELECT DISTINCT sca.student_id
        FROM student_class_assignments sca
        WHERE sca.is_archived=0';
$params = [];
if ($classId > 0) { $sql .= ' AND sca.class_id=?'; $params[] = $classId; }
$ss = $pdo->prepare($sql);
$ss->execute($params);
$studentIds = array_column($ss->fetchAll(), 'student_id');

if (!$studentIds) Response::json(['ok'=>true, 'created'=>0, 'skipped'=>0]);

try {
    $pdo->beginTransaction();
    // Skip students already having a payment row for this term (any status, non-archived)
    $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
    $eq = $pdo->prepare("SELECT student_id FROM payments WHERE term_id=? AND is_archived=0 AND student_id IN ($placeholders)");
    $eq->execute(array_merge([$termId], $studentIds));
    $existing = array_column($eq->fetchAll(), 'student_id');
    $existingSet = array_flip($existing);

    $created = 0; $skipped = 0;
    $ins = $pdo->prepare('INSERT INTO payments (student_id, term_id, amount, paid_amount, status) VALUES (?, ?, ?, 0.00, ?)');
    foreach ($studentIds as $sid) {
        if (isset($existingSet[$sid])) { $skipped++; continue; }
        $status = $amount > 0 ? 'unpaid' : 'paid';
        $ins->execute([(int)$sid, $termId, $amount, $status]);
        $created++;
    }
    $pdo->commit();
    \App\Audit::log('payment.generate', 'term', $termId, ['class_id' => $classId, 'amount' => $amount, 'created' => $created, 'skipped' => $skipped]);
    Response::json(['ok'=>true, 'created'=>$created, 'skipped'=>$skipped]);
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    Response::error('Failed to generate payments', 500);
}
