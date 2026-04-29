<?php
// api/admin/export/students.php — CSV export of all active students with class assignment

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

$rows = $pdo->query("
    SELECT s.id, s.first_name, s.last_name, u.email, s.phone, s.guardian_name, s.date_of_birth, s.address,
           c.name AS class_name, c.academic_year, lvl.name AS level_name, t.name AS track_name,
           s.is_archived, s.created_at
    FROM students s
    JOIN users u ON u.id=s.user_id
    LEFT JOIN student_class_assignments sca
      ON sca.student_id=s.id AND sca.is_archived=0
      AND sca.id = (SELECT MAX(id) FROM student_class_assignments WHERE student_id=s.id AND is_archived=0)
    LEFT JOIN classes c ON c.id=sca.class_id
    LEFT JOIN class_levels lvl ON lvl.id=c.level_id
    LEFT JOIN education_tracks t ON t.id=lvl.track_id
    ORDER BY s.last_name, s.first_name
")->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="students-' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
// BOM so Excel reads UTF-8 (Ge'ez chars) properly
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['ID','First name','Last name','Email','Phone','Guardian','Date of birth','Address','Class','Level','Track','Academic year','Status','Created']);
foreach ($rows as $r) {
    fputcsv($out, [
        $r['id'], $r['first_name'], $r['last_name'], $r['email'], $r['phone'], $r['guardian_name'],
        $r['date_of_birth'], $r['address'], $r['class_name'], $r['level_name'], $r['track_name'],
        $r['academic_year'], $r['is_archived'] == 1 ? 'archived' : 'active', $r['created_at'],
    ]);
}
fclose($out);
