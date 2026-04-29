<?php
// api/admin/import/students.php — CSV import of students.
// Expects multipart/form-data with field "file" (CSV).
// CSV header (first row), case-insensitive, columns supported: first_name, last_name, email, phone, guardian_name, date_of_birth, address
// Skips rows where the email already exists. Generates a password per new student and returns the list.

use App\Database;
use App\Utils\Password;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') Response::error('Method not allowed', 405);
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) Response::error('No file uploaded', 400);
if ($_FILES['file']['size'] > 2 * 1024 * 1024) Response::error('File too large (max 2 MB)', 413);

$config = app_config();
$db = new Database($config['db']);
$pdo = $db->pdo();

$fp = fopen($_FILES['file']['tmp_name'], 'r');
if (!$fp) Response::error('Could not read file', 500);

// Strip UTF-8 BOM if present
$first = fgets($fp);
if (substr($first, 0, 3) === "\xEF\xBB\xBF") $first = substr($first, 3);
rewind($fp);
fseek($fp, strlen("\xEF\xBB\xBF") === 3 && substr(file_get_contents($_FILES['file']['tmp_name']), 0, 3) === "\xEF\xBB\xBF" ? 3 : 0);

$header = fgetcsv($fp);
if (!$header) { fclose($fp); Response::error('CSV is empty', 422); }
$header = array_map(function ($h) { return strtolower(trim($h)); }, $header);
$idx = array_flip($header);
if (!isset($idx['first_name']) || !isset($idx['last_name']) || !isset($idx['email'])) {
    fclose($fp);
    Response::error('CSV must include first_name, last_name, email columns', 422);
}

$roleStmt = $pdo->prepare('SELECT id FROM roles WHERE name=? LIMIT 1');
$roleStmt->execute(['student']);
$role = $roleStmt->fetch();
if (!$role) Response::error('Student role missing in DB', 500);
$studentRoleId = (int)$role['id'];

$created = []; $skipped = []; $errors = [];
$line = 1;
while (($cells = fgetcsv($fp)) !== false) {
    $line++;
    $get = function ($k) use ($cells, $idx) { return isset($idx[$k]) && isset($cells[$idx[$k]]) ? trim($cells[$idx[$k]]) : ''; };
    $first = $get('first_name');
    $last  = $get('last_name');
    $email = $get('email');
    if ($first === '' || $last === '' || $email === '') { $errors[] = "Line $line: missing required field"; continue; }

    $check = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
    $check->execute([$email]);
    if ($check->fetch()) { $skipped[] = $email; continue; }

    $pwd = Password::generate(12);
    $hash = password_hash($pwd, PASSWORD_DEFAULT);
    try {
        $pdo->beginTransaction();
        $iu = $pdo->prepare('INSERT INTO users (email, password_hash, role_id) VALUES (?, ?, ?)');
        $iu->execute([$email, $hash, $studentRoleId]);
        $userId = (int)$pdo->lastInsertId();
        $is = $pdo->prepare('INSERT INTO students (user_id, first_name, last_name, guardian_name, phone, address, date_of_birth) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $is->execute([$userId, $first, $last, $get('guardian_name'), $get('phone'), $get('address'), $get('date_of_birth') ?: null]);
        $pdo->commit();
        $created[] = ['email' => $email, 'first_name' => $first, 'last_name' => $last, 'password' => $pwd];
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $errors[] = "Line $line: failed to create ($email)";
    }
}
fclose($fp);

Response::json([
    'ok' => true,
    'created_count' => count($created),
    'skipped_count' => count($skipped),
    'error_count'   => count($errors),
    'created'       => $created,   // includes generated passwords — admin must save these
    'skipped'       => $skipped,
    'errors'        => $errors,
]);
