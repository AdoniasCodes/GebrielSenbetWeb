<?php
// api/admin/payments/index.php — CRUD for tuition payments per term/student.
// GET   /api/admin/payments?term_id=&class_id=&status=&include_archived=
// POST  body: { student_id, term_id, amount, paid_amount?, status?, notes? }
// PUT   body: { id, amount?, paid_amount?, status?, notes? }
// DELETE body: { id }

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$config = app_config();
$db = new Database($config['db']);
$pdo = $db->pdo();

if ($method === 'GET') {
    $termId  = isset($_GET['term_id'])  ? (int)$_GET['term_id']  : 0;
    $classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
    $status  = isset($_GET['status'])   ? trim((string)$_GET['status']) : '';
    $includeArchived = isset($_GET['include_archived']) && $_GET['include_archived'] === '1';

    $sql = "SELECT p.id, p.student_id, p.term_id, p.amount, p.paid_amount, p.status, p.notes,
                   p.is_archived, p.created_at, p.updated_at,
                   s.first_name, s.last_name,
                   t.name AS term_name, t.academic_year, t.default_tuition,
                   sca.class_id,
                   c.name AS class_name, lvl.name AS level_name
            FROM payments p
            JOIN students s ON s.id=p.student_id
            JOIN academic_terms t ON t.id=p.term_id
            LEFT JOIN student_class_assignments sca
              ON sca.student_id=p.student_id AND sca.is_archived=0
              AND sca.id = (SELECT MAX(id) FROM student_class_assignments WHERE student_id=p.student_id AND is_archived=0)
            LEFT JOIN classes c ON c.id=sca.class_id
            LEFT JOIN class_levels lvl ON lvl.id=c.level_id
            WHERE 1=1";
    $params = [];
    if ($termId  > 0) { $sql .= ' AND p.term_id=?';   $params[] = $termId; }
    if ($classId > 0) { $sql .= ' AND sca.class_id=?'; $params[] = $classId; }
    if ($status !== '' && in_array($status, ['paid','unpaid','partial'], true)) {
        $sql .= ' AND p.status=?'; $params[] = $status;
    }
    if (!$includeArchived) $sql .= ' AND p.is_archived=0';
    $sql .= ' ORDER BY p.term_id DESC, s.last_name, s.first_name';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Aggregate totals
    $total = 0.0; $paid = 0.0;
    foreach ($rows as $r) {
        $total += (float)$r['amount'];
        $paid  += (float)$r['paid_amount'];
    }
    Response::json([
        'data' => $rows,
        'totals' => [ 'expected' => $total, 'paid' => $paid, 'outstanding' => $total - $paid ],
    ]);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $studentId = (int)($input['student_id'] ?? 0);
    $termId    = (int)($input['term_id'] ?? 0);
    $amount    = (float)($input['amount'] ?? 0);
    $paid      = isset($input['paid_amount']) ? (float)$input['paid_amount'] : 0.0;
    $status    = trim($input['status'] ?? '');
    $notes     = isset($input['notes']) ? (string)$input['notes'] : null;

    if ($studentId <= 0 || $termId <= 0) Response::error('student_id and term_id are required', 422);
    if ($amount < 0 || $paid < 0) Response::error('Amounts must be non-negative', 422);
    if ($status === '') $status = derive_status($amount, $paid);
    if (!in_array($status, ['paid','unpaid','partial'], true)) Response::error('Invalid status', 422);

    try {
        $pdo->beginTransaction();
        $check = $pdo->prepare('SELECT id FROM payments WHERE student_id=? AND term_id=? AND is_archived=0 LIMIT 1');
        $check->execute([$studentId, $termId]);
        if ($check->fetch()) { $pdo->rollBack(); Response::error('Payment row already exists for this student in this term', 409); }

        $ins = $pdo->prepare('INSERT INTO payments (student_id, term_id, amount, paid_amount, status, notes) VALUES (?, ?, ?, ?, ?, ?)');
        $ins->execute([$studentId, $termId, $amount, $paid, $status, $notes]);
        $pdo->commit();
        $newId = (int)$pdo->lastInsertId();
        \App\Audit::log('payment.create', 'payment', $newId, ['student_id' => $studentId, 'term_id' => $termId, 'amount' => $amount, 'paid_amount' => $paid, 'status' => $status]);
        Response::json(['ok' => true, 'id' => $newId]);
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        Response::error('Failed to create payment', 500);
    }
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);

    $stmt = $pdo->prepare('SELECT id, amount, paid_amount, status FROM payments WHERE id=? AND is_archived=0');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) Response::error('Payment not found', 404);

    $amount = isset($input['amount']) ? (float)$input['amount'] : (float)$row['amount'];
    $paid   = isset($input['paid_amount']) ? (float)$input['paid_amount'] : (float)$row['paid_amount'];
    $status = isset($input['status']) ? trim((string)$input['status']) : $row['status'];
    $notes  = array_key_exists('notes', $input) ? (is_null($input['notes']) ? null : (string)$input['notes']) : null;

    if ($amount < 0 || $paid < 0) Response::error('Amounts must be non-negative', 422);
    if (!isset($input['status']) || $input['status'] === '') $status = derive_status($amount, $paid);
    if (!in_array($status, ['paid','unpaid','partial'], true)) Response::error('Invalid status', 422);

    $sql = 'UPDATE payments SET amount=?, paid_amount=?, status=?';
    $params = [$amount, $paid, $status];
    if (array_key_exists('notes', $input)) { $sql .= ', notes=?'; $params[] = $notes; }
    $sql .= ' WHERE id=?';
    $params[] = $id;

    $upd = $pdo->prepare($sql);
    $upd->execute($params);
    \App\Audit::log('payment.update', 'payment', $id, ['amount' => $amount, 'paid_amount' => $paid, 'status' => $status]);
    Response::json(['ok' => true]);
}

if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) Response::error('id is required', 422);
    $stmt = $pdo->prepare('UPDATE payments SET is_archived=1, archived_at=NOW() WHERE id=? AND is_archived=0');
    $stmt->execute([$id]);
    \App\Audit::log('payment.archive', 'payment', $id);
    Response::json(['ok' => true]);
}

Response::error('Method not allowed', 405);

function derive_status(float $amount, float $paid): string {
    if ($amount <= 0 && $paid <= 0) return 'unpaid';
    if ($paid >= $amount && $amount > 0) return 'paid';
    if ($paid > 0) return 'partial';
    return 'unpaid';
}
