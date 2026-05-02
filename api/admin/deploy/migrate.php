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

// Per-migration "is this already in the schema?" probes. Used both to (a)
// bootstrap when the tracker is empty but the base schema is present, and
// (b) prune stale tracker rows that point at migrations whose effects are
// missing (e.g. a botched earlier bootstrap that marked too many as applied).
function _migration_artifact_present(\PDO $pdo, string $filename): ?bool {
    $checks = [
        '001_initial_schema.sql'           => "SHOW TABLES LIKE 'users'",
        '002_seed_tracks_levels.sql'       => "SELECT 1 FROM education_tracks LIMIT 1",
        '003_grades_unique_index.sql'      => "SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='grades' AND index_name='uq_grade_unique' LIMIT 1",
        '004_academic_terms_is_current.sql'=> "SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='academic_terms' AND column_name='is_current' LIMIT 1",
        '005_payments_extensions.sql'      => "SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='payments' AND column_name='paid_amount' LIMIT 1",
        '006_audit_log.sql'                => "SHOW TABLES LIKE 'audit_log'",
        '007_notifications_public_flag.sql'=> "SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='notifications' AND column_name='is_public' LIMIT 1",
        '008_seed_demo_content.sql'        => "SELECT 1 FROM events WHERE title='Sabbath Morning Service' LIMIT 1",
        '009_parents.sql'                  => "SELECT 1 FROM roles WHERE name='parent' LIMIT 1",
    ];
    if (!isset($checks[$filename])) return null;
    try {
        $r = $pdo->query($checks[$filename])->fetch();
        return $r !== false;
    } catch (\Throwable $e) {
        return false;
    }
}

$applied = [];
$stmt = $pdo->query('SELECT filename, checksum FROM schema_migrations');
foreach ($stmt->fetchAll() as $row) { $applied[$row['filename']] = $row['checksum']; }

$dir = __DIR__ . '/../../../db/migrations';
$files = glob($dir . '/*.sql');
sort($files, SORT_NATURAL);

// Self-heal: drop any tracker rows whose schema artifact isn't actually
// present. This catches earlier bad bootstraps that recorded migrations as
// applied when their effects were never installed.
$pruned = [];
$delStmt = $pdo->prepare('DELETE FROM schema_migrations WHERE filename = ?');
foreach (array_keys($applied) as $fn) {
    $present = _migration_artifact_present($pdo, $fn);
    if ($present === false) {
        $delStmt->execute([$fn]);
        unset($applied[$fn]);
        $pruned[] = $fn;
    }
}

// Bootstrap: when the tracker is empty AND the base schema is present, mark
// each migration as applied ONLY if its artifact already exists. Migrations
// whose artifacts are missing flow through the normal apply path below.
$bootstrapped = [];
if (empty($applied)) {
    $hasUsers = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
    if ($hasUsers) {
        $insBoot = $pdo->prepare('INSERT INTO schema_migrations (filename, checksum) VALUES (?, ?)');
        foreach ($files as $path) {
            $fn = basename($path);
            if (_migration_artifact_present($pdo, $fn) !== true) continue;
            $sqlContent = file_get_contents($path);
            if ($sqlContent === false) continue;
            $cs = hash('sha256', $sqlContent);
            $insBoot->execute([$fn, $cs]);
            $applied[$fn] = $cs;
            $bootstrapped[] = $fn;
        }
    }
}

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

    // Strip line comments so they don't end up at the head of a statement
    // and cause the splitter to discard real SQL with them.
    $cleanSql = preg_replace('/^\s*--.*$/m', '', $sql);
    // DDL (ALTER/CREATE/DROP/RENAME/TRUNCATE) implicitly commits in MySQL.
    // Skip the transaction wrapper for migrations that contain any DDL.
    $usesDdl = (bool)preg_match('/\b(ALTER|CREATE|DROP|RENAME|TRUNCATE)\b/i', $cleanSql);
    $statements = [];
    foreach (preg_split("/(;\s*\n)|(;\s*$)/m", $cleanSql) as $part) {
        $chunk = trim($part);
        if ($chunk === '') { continue; }
        $statements[] = $chunk;
    }
    try {
        if (!$usesDdl) { $pdo->beginTransaction(); }
        foreach ($statements as $stmtSql) {
            $pdo->exec($stmtSql);
        }
        $ins = $pdo->prepare('INSERT INTO schema_migrations (filename, checksum) VALUES (?, ?)');
        $ins->execute([$filename, $checksum]);
        if (!$usesDdl && $pdo->inTransaction()) { $pdo->commit(); }
        $appliedNow[] = $filename;
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) { try { $pdo->rollBack(); } catch (\Throwable $_) {} }
        // For DDL migrations, statements may have partially applied. If the
        // artifact is now present, record the migration as applied and move on.
        if (_migration_artifact_present($pdo, $filename) === true) {
            try {
                $pdo->prepare('INSERT INTO schema_migrations (filename, checksum) VALUES (?, ?)')
                    ->execute([$filename, $checksum]);
                $appliedNow[] = $filename;
                continue;
            } catch (\Throwable $_) { /* fall through to failure */ }
        }
        $failed[] = [$filename, $e->getMessage()];
        break; // stop on first hard failure
    }
}

Response::json([
    'applied' => $appliedNow,
    'skipped' => $skipped,
    'failed' => $failed,
    'bootstrapped' => $bootstrapped,
    'pruned' => $pruned,
]);
