<?php
// api/admin/terms/index.php
use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';
require_csrf_for_write();

$config = app_config();
$db = new Database($config['db']);
$pdo = $db->pdo();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $includeArchived = isset($_GET['include_archived']) && $_GET['include_archived'] === '1';
    $sql = 'SELECT id, name, academic_year, start_date, end_date, is_archived, archived_at, created_at, updated_at
            FROM academic_terms';
    if (!$includeArchived) { $sql .= ' WHERE is_archived = 0'; }
    $sql .= ' ORDER BY academic_year DESC, start_date DESC, name ASC';
    $rows = $pdo->query($sql)->fetchAll();
    Response::json(['data' => $rows]);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $name = trim($input['name'] ?? '');
    $academicYear = trim($input['academic_year'] ?? '');
    $start = trim($input['start_date'] ?? '');
    $end = trim($input['end_date'] ?? '');
    if ($name === '' || $academicYear === '' || $start === '' || $end === '') {
        Response::error('name, academic_year, start_date, end_date are required', 422);
    }
    if (strtotime($start) === false || strtotime($end) === false) {
        Response::error('Invalid date format', 422);
    }
    if ($start >= $end) {
        Response::error('start_date must be before end_date', 422);
    }
    // Prevent overlapping active terms within the same academic_year
    $overlapSql = "SELECT id FROM academic_terms
                   WHERE is_archived = 0 AND academic_year = ?
                     AND ((? < end_date) AND (? > start_date))
                   LIMIT 1";
    $overlap = $pdo->prepare($overlapSql);
    $overlap->execute([$academicYear, $start, $end]);
    if ($overlap->fetch()) {
        Response::error('Overlapping term exists in the same academic_year', 409);
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO academic_terms (name, academic_year, start_date, end_date) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $academicYear, $start, $end]);
        Response::json(['message' => 'Term created', 'id' => (int)$pdo->lastInsertId()], 201);
    } catch (\PDOException $e) {
        if ((int)$e->getCode() === 23000) {
            Response::error('Term already exists for this academic_year', 409);
        }
        Response::error('Failed to create term', 500);
    }
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? 0);
    $name = isset($input['name']) ? trim((string)$input['name']) : null;
    $academicYear = isset($input['academic_year']) ? trim((string)$input['academic_year']) : null;
    $start = array_key_exists('start_date', $input) ? trim((string)$input['start_date']) : null;
    $end = array_key_exists('end_date', $input) ? trim((string)$input['end_date']) : null;
    if ($id <= 0) { Response::error('id is required', 422); }
    if ($start !== null && $end !== null) {
        if (strtotime($start) === false || strtotime($end) === false) {
            Response::error('Invalid date format', 422);
        }
        if ($start >= $end) { Response::error('start_date must be before end_date', 422); }
    }

    // Load existing row to determine AY for overlap check
    $cur = $pdo->prepare('SELECT academic_year FROM academic_terms WHERE id = ?');
    $cur->execute([$id]);
    $row = $cur->fetch();
    if (!$row) { Response::error('Term not found', 404); }
    $ay = $academicYear !== null && $academicYear !== '' ? $academicYear : $row['academic_year'];

    if (($start !== null && $start !== '') || ($end !== null && $end !== '')) {
        // Determine start/end values to validate
        $s = $start; $e = $end;
        if ($s === null || $s === '') {
            $prev = $pdo->prepare('SELECT start_date, end_date FROM academic_terms WHERE id = ?');
            $prev->execute([$id]);
            $pr = $prev->fetch();
            if ($pr) { $s = $pr['start_date']; $e = $e ?? $pr['end_date']; }
        } else if ($e === null || $e === '') {
            $prev = $pdo->prepare('SELECT start_date, end_date FROM academic_terms WHERE id = ?');
            $prev->execute([$id]);
            $pr = $prev->fetch();
            if ($pr) { $e = $pr['end_date']; }
        }
        if ($s >= $e) { Response::error('start_date must be before end_date', 422); }
        // Overlap check excluding self
        $chk = $pdo->prepare("SELECT id FROM academic_terms
                              WHERE is_archived = 0 AND academic_year = ? AND id <> ?
                                AND ((? < end_date) AND (? > start_date))
                              LIMIT 1");
        $chk->execute([$ay, $id, $s, $e]);
        if ($chk->fetch()) { Response::error('Overlapping term exists in the same academic_year', 409); }
    }

    $fields = [];$params = [];
    if ($name !== null && $name !== '') { $fields[]='name = ?'; $params[]=$name; }
    if ($academicYear !== null && $academicYear !== '') { $fields[]='academic_year = ?'; $params[]=$academicYear; }
    if ($start !== null && $start !== '') { $fields[]='start_date = ?'; $params[]=$start; }
    if ($end !== null && $end !== '') { $fields[]='end_date = ?'; $params[]=$end; }
    if (!$fields) { Response::error('No changes provided', 422); }
    $params[] = $id;

    $stmt = $pdo->prepare('UPDATE academic_terms SET ' . implode(', ', $fields) . ' WHERE id = ?');
    $stmt->execute($params);
    if ($stmt->rowCount() === 0) { Response::error('No change or term not found', 404); }
    Response::json(['message' => 'Term updated']);
}

if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) { Response::error('id is required', 422); }
    $stmt = $pdo->prepare('UPDATE academic_terms SET is_archived = 1, archived_at = NOW() WHERE id = ? AND is_archived = 0');
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) { Response::error('Term not found or already archived', 404); }
    Response::json(['message' => 'Term archived']);
}

Response::error('Method Not Allowed', 405);
