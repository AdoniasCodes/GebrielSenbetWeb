<?php
// api/parent/students/index.php — list of children visible to this parent.
use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') Response::error('Method not allowed', 405);

$ids = parent_student_ids();
if (!$ids) { Response::json(['data' => []]); }

$config = app_config();
$pdo = (new Database($config['db']))->pdo();
$placeholders = parent_student_id_placeholders();
$stmt = $pdo->prepare("SELECT s.id, s.first_name, s.last_name, s.date_of_birth, s.guardian_name, s.phone,
                              c.name AS class_name, lvl.name AS level_name, tr.name AS track_name
                       FROM students s
                       LEFT JOIN student_class_assignments sca
                              ON sca.student_id=s.id AND sca.is_archived=0
                              AND sca.id=(SELECT MAX(id) FROM student_class_assignments WHERE student_id=s.id AND is_archived=0)
                       LEFT JOIN classes c     ON c.id=sca.class_id
                       LEFT JOIN class_levels lvl ON lvl.id=c.level_id
                       LEFT JOIN education_tracks tr ON tr.id=lvl.track_id
                       WHERE s.id IN $placeholders AND s.is_archived=0
                       ORDER BY s.first_name");
$stmt->execute($ids);
Response::json(['data' => $stmt->fetchAll()]);
