<?php
// api/terms/index.php - list academic terms
use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../../bootstrap.php';

$config = app_config();
$db = new Database($config['db']);
$pdo = $db->pdo();

$sql = 'SELECT id, name, academic_year, start_date, end_date FROM academic_terms WHERE is_archived = 0 ORDER BY academic_year DESC, start_date DESC';
$rows = $pdo->query($sql)->fetchAll();
Response::json(['data' => $rows]);
