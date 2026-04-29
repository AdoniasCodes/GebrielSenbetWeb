<?php
// public/admin/report-card.php — printable report card for one student in one term
// Query: ?student_id=&term_id=

require_once __DIR__ . '/../../bootstrap.php';
use App\Database;
use App\Utils\Csrf;

$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'admin') { header('Location: /'); exit; }

$studentId = (int)($_GET['student_id'] ?? 0);
$termId    = (int)($_GET['term_id'] ?? 0);
if ($studentId <= 0) { http_response_code(400); echo 'student_id is required'; exit; }

$db = new Database($config['db']);
$pdo = $db->pdo();

$ss = $pdo->prepare("SELECT s.id, s.first_name, s.last_name, u.email FROM students s JOIN users u ON u.id=s.user_id WHERE s.id=?");
$ss->execute([$studentId]);
$student = $ss->fetch();
if (!$student) { http_response_code(404); echo 'Student not found'; exit; }

if ($termId <= 0) {
    $tr = $pdo->query("SELECT id FROM academic_terms WHERE is_current=1 AND is_archived=0 LIMIT 1")->fetch();
    if ($tr) $termId = (int)$tr['id'];
}
if ($termId <= 0) { http_response_code(400); echo 'term_id is required (no current term set)'; exit; }

$ts = $pdo->prepare("SELECT * FROM academic_terms WHERE id=?");
$ts->execute([$termId]);
$term = $ts->fetch();
if (!$term) { http_response_code(404); echo 'Term not found'; exit; }

// Current class (if any) at the time of this term — pick latest non-archived assignment overlapping term dates
$cs = $pdo->prepare("
    SELECT c.id, c.name, c.academic_year, lvl.name AS level_name, t.name AS track_name
    FROM student_class_assignments sca
    JOIN classes c ON c.id=sca.class_id
    JOIN class_levels lvl ON lvl.id=c.level_id
    JOIN education_tracks t ON t.id=lvl.track_id
    WHERE sca.student_id=? AND sca.is_archived=0
    ORDER BY sca.id DESC
    LIMIT 1
");
$cs->execute([$studentId]);
$class = $cs->fetch();

// Grades for this term
$gs = $pdo->prepare("
    SELECT g.score, g.remarks, subj.name AS subject_name
    FROM grades g
    JOIN subjects subj ON subj.id=g.subject_id
    WHERE g.student_id=? AND g.term_id=? AND g.is_archived=0
    ORDER BY subj.name
");
$gs->execute([$studentId, $termId]);
$grades = $gs->fetchAll();

$avg = null;
if ($grades) {
    $sum = 0; foreach ($grades as $g) { $sum += (float)$g['score']; }
    $avg = $sum / count($grades);
}

$studentName = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')) ?: $student['email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Report Card · <?= htmlspecialchars($studentName) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,400..700&family=Plus+Jakarta+Sans:wght@400..700&family=Noto+Sans+Ethiopic:wght@400;500;700&display=swap" rel="stylesheet" />
  <style>
    body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; color:#1c1c18; background:#fcf9f2; margin:0; }
    .sheet { max-width: 780px; margin: 32px auto; background: #fff; border: 1px solid #c9a14a; padding: 56px 56px 48px; box-shadow: 0 1px 0 rgba(91,6,23,0.04), 0 12px 32px -16px rgba(91,6,23,0.18); position: relative; }
    .sheet::before, .sheet::after { content:''; position:absolute; width:32px; height:32px; border:1px solid #c9a14a; }
    .sheet::before { top:14px; left:14px; border-right:none; border-bottom:none; }
    .sheet::after  { bottom:14px; right:14px; border-left:none; border-top:none; }
    .header { text-align:center; margin-bottom: 28px; }
    .crown { font-size: 22px; color:#c9a14a; }
    .display { font-family: 'Newsreader', serif; }
    .ethiopic { font-family: 'Noto Sans Ethiopic', serif; }
    .eyebrow { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.18em; color: #795901; }
    .gold-rule { height: 1px; background: linear-gradient(to right, transparent, #c9a14a 20%, #c9a14a 80%, transparent); margin: 18px 0; }
    h1 { font-family:'Newsreader', serif; font-size: 30px; color:#5b0617; margin: 6px 0; }
    .meta { display:grid; grid-template-columns: 1fr 1fr; gap: 16px 32px; margin: 24px 0; font-size: 14px; }
    .meta dt { font-size: 10px; text-transform: uppercase; letter-spacing: 0.18em; color: #897172; margin-bottom: 4px; }
    table { width:100%; border-collapse: collapse; margin: 16px 0; }
    table th { text-align:left; padding: 10px 14px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.18em; color: #564242; border-bottom: 1px solid #dcc0c0; }
    table td { padding: 14px; border-bottom: 1px solid rgba(220,192,192,0.4); font-size: 14px; }
    .score { font-family:'Newsreader', serif; font-size: 22px; color:#5b0617; }
    .summary { display:flex; align-items:center; justify-content: space-between; padding: 16px 0 8px; }
    .avg { font-family:'Newsreader', serif; font-size: 36px; color:#5b0617; }
    .footer { margin-top: 36px; display:flex; justify-content: space-between; gap: 32px; }
    .sig { flex:1; padding-top: 40px; border-top: 1px solid #897172; font-size: 11px; text-transform: uppercase; letter-spacing: 0.18em; color: #897172; }

    .toolbar { max-width: 780px; margin: 16px auto -16px; display:flex; justify-content: flex-end; gap: 8px; }
    .btn { display:inline-flex; align-items:center; gap:6px; padding: 8px 14px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.18em; border-radius: 4px; border: 1px solid #dcc0c0; background: #fff; color: #1c1c18; text-decoration: none; cursor: pointer; }
    .btn:hover { background: #f0eee7; }
    .btn-primary { background: #5b0617; color: #fcf9f2; border-color: #5b0617; }
    .btn-primary:hover { background: #7a1f2b; }

    @media print {
      .toolbar { display: none !important; }
      body { background: #fff; }
      .sheet { box-shadow: none; border: none; margin: 0; padding: 32px; }
    }
  </style>
</head>
<body>

<div class="toolbar">
  <a class="btn" href="/admin/student-detail.php?id=<?= (int)$studentId ?>">← Back</a>
  <button class="btn btn-primary" onclick="window.print()">Print</button>
</div>

<div class="sheet">
  <div class="header">
    <div class="crown">✦</div>
    <p class="eyebrow">Saint Gabriel Sabbath School</p>
    <p class="ethiopic" style="font-size:14px; color:#897172; margin-top:4px;">ቅዱስ ገብርኤል ሰንበት ት/ቤት</p>
    <h1>Term Report Card</h1>
    <p class="ethiopic" style="font-size:14px; color:#5b0617;">የወቅት ሪፖርት ካርድ</p>
  </div>

  <div class="gold-rule"></div>

  <dl class="meta">
    <div><dt>Student</dt><dd style="font-family:'Newsreader',serif;font-size:18px;color:#5b0617;"><?= htmlspecialchars($studentName) ?></dd></div>
    <div><dt>Term</dt><dd><?= htmlspecialchars($term['academic_year']) ?> · <?= htmlspecialchars($term['name']) ?></dd></div>
    <div><dt>Class</dt><dd><?= htmlspecialchars($class['class_name'] ?? ($class['name'] ?? '—')) ?> <span style="color:#897172"><?= $class ? '(' . htmlspecialchars($class['level_name']) . ')' : '' ?></span></dd></div>
    <div><dt>Track</dt><dd><?= htmlspecialchars($class['track_name'] ?? '—') ?></dd></div>
    <div><dt>Term dates</dt><dd><?= htmlspecialchars($term['start_date']) ?> → <?= htmlspecialchars($term['end_date']) ?></dd></div>
    <div><dt>Issued</dt><dd><?= date('Y-m-d') ?></dd></div>
  </dl>

  <table>
    <thead><tr><th>Subject</th><th>Score</th><th>Remarks</th></tr></thead>
    <tbody>
      <?php if (!$grades): ?>
        <tr><td colspan="3" style="text-align:center; padding:32px; color:#897172;">No grades recorded for this term.</td></tr>
      <?php else: foreach ($grades as $g): ?>
        <tr>
          <td><?= htmlspecialchars($g['subject_name']) ?></td>
          <td><span class="score"><?= htmlspecialchars($g['score']) ?></span></td>
          <td style="color:#564242;"><?= htmlspecialchars($g['remarks'] ?? '') ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>

  <?php if ($avg !== null): ?>
    <div class="summary">
      <p class="eyebrow">Average</p>
      <p class="avg"><?= number_format($avg, 2) ?></p>
    </div>
  <?php endif; ?>

  <div class="footer">
    <div class="sig">Class teacher</div>
    <div class="sig">Superintendent</div>
  </div>
</div>

</body>
</html>
