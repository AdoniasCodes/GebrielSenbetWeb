<?php
// api/parent/payments/index.php — payments for the parent's children.
use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') Response::error('Method not allowed', 405);

$allowed = parent_student_ids();
if (!$allowed) { Response::json(['data' => [], 'totals' => ['expected'=>0,'paid'=>0,'outstanding'=>0]]); }

$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
if ($studentId > 0 && !in_array($studentId, $allowed, true)) Response::error('Forbidden', 403);

$config = app_config();
$pdo = (new Database($config['db']))->pdo();

if ($studentId > 0) {
    $ids = [$studentId];
    $placeholders = '(?)';
} else {
    $ids = $allowed;
    $placeholders = parent_student_id_placeholders();
}

$sql = "SELECT p.id, p.student_id, p.term_id, p.amount, p.paid_amount, p.status, p.notes,
               s.first_name, s.last_name,
               t.name AS term_name, t.academic_year
        FROM payments p
        JOIN students s ON s.id=p.student_id
        JOIN academic_terms t ON t.id=p.term_id
        WHERE p.is_archived=0 AND p.student_id IN $placeholders
        ORDER BY t.academic_year DESC, t.start_date DESC, s.first_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($ids);
$rows = $stmt->fetchAll();

$total=0.0; $paid=0.0;
foreach ($rows as $r) { $total += (float)$r['amount']; $paid += (float)$r['paid_amount']; }
Response::json([
    'data' => $rows,
    'totals' => ['expected'=>$total,'paid'=>$paid,'outstanding'=>$total-$paid],
]);
