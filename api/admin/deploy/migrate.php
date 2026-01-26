<?php
// api/admin/deploy/migrate.php
// Secure migration runner for cPanel shared hosting.
// Protection: requires POST and X-DEPLOY-TOKEN header to match config app.deploy_token.

use App\Database;
use App\Utils\Response;

require_once __DIR__ . '/../../../bootstrap.php';

$config = app_config();
$token = $_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? '';
$expected = $config['app']['deploy_token'] ?? '';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method Not Allowed', 405);
}
if ($expected === '' || $expected === 'CHANGE_ME_DEPLOY_TOKEN' || !hash_equals($expected, $token)) {
    Response::error('Forbidden', 403);
}

$db = new Database($config['db']);
$pdo = $db->pdo();

// Ensure schema_migrations table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  filename VARCHAR(255) NOT NULL UNIQUE,
  checksum VARCHAR(64) NOT NULL,
  applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");

$applied = [];
$stmt = $pdo->query('SELECT filename, checksum FROM schema_migrations');
foreach ($stmt->fetchAll() as $row) { $applied[$row['filename']] = $row['checksum']; }

$dir = __DIR__ . '/../../../db/migrations';
$files = glob($dir . '/*.sql');
sort($files, SORT_NATURAL);

$appliedNow = [];
$skipped = [];
$failed = [];

foreach ($files as $path) {
    $filename = basename($path);
    $sql = file_get_contents($path);
    if ($sql === false) { $failed[] = [$filename, 'read_error']; continue; }
    $checksum = hash('sha256', $sql);

    if (isset($applied[$filename]) && $applied[$filename] === $checksum) {
        $skipped[] = $filename; // already applied and matches
        continue;
    }
    if (isset($applied[$filename]) && $applied[$filename] !== $checksum) {
        $failed[] = [$filename, 'checksum_mismatch_already_applied'];
        continue; // do not reapply changed migration
    }

    try {
        $pdo->beginTransaction();
        // Basic splitting by semicolons, skipping comments and empty lines.
        $statements = [];
        $buffer = '';
        foreach (preg_split("/(;\s*\n)|(;\s*$)/m", $sql) as $part) {
            $chunk = trim($part);
            if ($chunk === '' || preg_match('/^--/', $chunk)) { continue; }
            $statements[] = $chunk;
        }
        foreach ($statements as $stmtSql) {
            $pdo->exec($stmtSql);
        }
        $ins = $pdo->prepare('INSERT INTO schema_migrations (filename, checksum) VALUES (?, ?)');
        $ins->execute([$filename, $checksum]);
        $pdo->commit();
        $appliedNow[] = $filename;
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $failed[] = [$filename, $e->getMessage()];
        break; // stop on first failure
    }
}

Response::json([
    'applied' => $appliedNow,
    'skipped' => $skipped,
    'failed' => $failed,
]);
