<?php
// api/admin/export/payments.php — CSV export of payments, optionally scoped to a term

use App\Database;
use App\Utils\Csrf;
use App\Utils\Response;

require_once __DIR__ . '/../../../bootstrap.php';

$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? '') !== 'admin') Response::error('Forbidden', 403);
if ($_SERVER['REQUEST_METHOD'] !== 'GET') Response::error('Method not allowed', 405);

$db = new Database($config['db']);
$pdo = $db->pdo();
$termId = isset($_GET['term_id']) ? (int)$_GET['term_id'] : 0;

$sql = "SELECT p.id, s.first_name, s.last_name, t.name AS term_name, t.academic_year,
               p.amount, p.paid_amount, (p.amount - p.paid_amount) AS outstanding, p.status, p.notes,
               p.created_at, p.updated_at
        FROM payments p
        JOIN students s ON s.id=p.student_id
        JOIN academic_terms t ON t.id=p.term_id
        WHERE p.is_archived=0";
$params = [];
if ($termId > 0) { $sql .= ' AND p.term_id=?'; $params[] = $termId; }
$sql .= ' ORDER BY t.start_date DESC, s.last_name, s.first_name';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="payments-' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['Payment ID','First name','Last name','Term','Academic year','Expected (ETB)','Paid (ETB)','Outstanding (ETB)','Status','Notes','Created','Updated']);
foreach ($rows as $r) {
    fputcsv($out, [
        $r['id'], $r['first_name'], $r['last_name'], $r['term_name'], $r['academic_year'],
        number_format((float)$r['amount'], 2, '.', ''), number_format((float)$r['paid_amount'], 2, '.', ''),
        number_format((float)$r['outstanding'], 2, '.', ''), $r['status'], $r['notes'], $r['created_at'], $r['updated_at'],
    ]);
}
fclose($out);
