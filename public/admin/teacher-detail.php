<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'admin') { header('Location: /'); exit; }

$teacher_id = (int)($_GET['id'] ?? 0);
if ($teacher_id <= 0) { header('Location: /admin/teachers.php'); exit; }

$page_title    = 'Teacher';
$page_title_am = 'መምህር';
$page_eyebrow    = 'Registry';
$page_eyebrow_am = 'መዝገብ';
$active_nav = 'teachers';
require __DIR__ . '/_partials/page-shell.php';
?>

<a href="/admin/teachers.php" class="inline-flex items-center gap-2 text-sm text-ink-soft hover:text-primary -mt-2">
  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
  <span data-en="Back to teachers" data-am="ወደ መምህራን ተመለስ">Back to teachers</span>
</a>

<section class="panel p-8" id="profileWrap">
  <p class="text-center text-ink-soft py-12">Loading…</p>
</section>

<section class="panel">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 class="font-display text-lg text-ink" data-en="Current Assignments" data-am="ወቅታዊ ምድቦች">Current Assignments</h2>
    <a href="/admin/assignments.php" class="text-xs font-semibold uppercase tracking-widestest text-gold hover:text-primary" data-en="Manage" data-am="ያስተዳድሩ">Manage</a>
  </header>
  <div id="assignmentsWrap" class="table-wrap">
    <p class="px-6 py-12 text-center text-ink-soft text-sm">—</p>
  </div>
</section>

<section class="panel">
  <header class="px-6 py-5 border-b border-outline-soft/40">
    <h2 class="font-display text-lg text-ink" data-en="Recent Grade Entries" data-am="የቅርብ ጊዜ ውጤቶች">Recent Grade Entries</h2>
  </header>
  <div id="gradesWrap" class="table-wrap">
    <p class="px-6 py-12 text-center text-ink-soft text-sm">—</p>
  </div>
</section>

<script>
  var TEACHER_ID = <?= (int)$teacher_id ?>;
  function escHtml(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];}); }

  function renderProfile(t) {
    var name = ((t.first_name||'') + ' ' + (t.last_name||'')).trim() || t.email;
    var initials = ((t.first_name||'?')[0] || '?').toUpperCase() + ((t.last_name||'')[0] || '').toUpperCase();
    var statusPill = t.is_archived == 1 ? '<span class="pill pill-archived">Archived</span>' : '<span class="pill pill-active">Active</span>';
    document.getElementById('profileWrap').innerHTML =
      '<div class="flex items-start gap-6 flex-wrap">' +
        '<div class="avatar-circle w-20 h-20 bg-gold/15 text-gold text-2xl">'+escHtml(initials)+'</div>' +
        '<div class="flex-1 min-w-[200px]">' +
          '<h2 class="font-display text-3xl text-ink mb-2">'+escHtml(name)+'</h2>' +
          '<p class="text-sm text-ink-soft mb-3">'+escHtml(t.email)+'</p>' +
          '<div class="flex flex-wrap gap-2 mb-4">'+statusPill+'</div>' +
          '<dl class="grid grid-cols-2 gap-4 text-sm mb-4">' +
            '<div><dt class="text-[10px] uppercase tracking-widestest text-ink-soft mb-1">Phone</dt><dd>'+escHtml(t.phone||'—')+'</dd></div>' +
          '</dl>' +
          (t.bio ? '<p class="text-ink-soft leading-relaxed">'+escHtml(t.bio)+'</p>' : '') +
        '</div>' +
      '</div>';
  }

  function renderAssignments(rows) {
    var wrap = document.getElementById('assignmentsWrap');
    if (!rows.length) { wrap.innerHTML = '<p class="px-6 py-12 text-center text-ink-soft text-sm">No assignments yet.</p>'; return; }
    wrap.innerHTML = '<table class="data"><thead><tr>' +
      '<th>Track / Level</th><th>Class</th><th>Subject</th><th>Role</th><th>Period</th>' +
      '</tr></thead><tbody>' + rows.map(function (a) {
        var rolePill = a.role === 'primary' ? '<span class="pill pill-active">Primary</span>' : '<span class="pill pill-draft">Substitute</span>';
        return '<tr>' +
          '<td class="text-ink-soft">'+escHtml(a.track_name)+' · '+escHtml(a.level_name)+'</td>' +
          '<td>'+escHtml(a.class_name)+' <span class="text-xs text-outline">('+escHtml(a.academic_year)+')</span></td>' +
          '<td>'+escHtml(a.subject_name)+'</td>' +
          '<td>'+rolePill+'</td>' +
          '<td class="text-xs text-outline">'+escHtml(a.start_date)+(a.end_date?' → '+escHtml(a.end_date):'')+'</td>' +
        '</tr>';
      }).join('') + '</tbody></table>';
  }

  function renderGrades(rows) {
    var wrap = document.getElementById('gradesWrap');
    if (!rows.length) { wrap.innerHTML = '<p class="px-6 py-12 text-center text-ink-soft text-sm">No grade entries yet.</p>'; return; }
    wrap.innerHTML = '<table class="data"><thead><tr>' +
      '<th>Student</th><th>Class</th><th>Subject</th><th>Score</th>' +
      '</tr></thead><tbody>' + rows.map(function (g) {
        return '<tr>' +
          '<td>'+escHtml((g.student_first||'') + ' ' + (g.student_last||''))+'</td>' +
          '<td class="text-ink-soft">'+escHtml(g.class_name)+' ('+escHtml(g.level_name)+')</td>' +
          '<td>'+escHtml(g.subject_name)+'</td>' +
          '<td><span class="font-display text-lg text-primary">'+escHtml(g.score)+'</span></td>' +
        '</tr>';
      }).join('') + '</tbody></table>';
  }

  (async function () {
    try {
      var d = await gs.api('/api/admin/teachers/detail.php?id=' + TEACHER_ID);
      renderProfile(d.teacher);
      renderAssignments(d.assignments || []);
      renderGrades(d.recent_grades || []);
    } catch (e) { gs.toast(e.message, 'error'); }
  })();
</script>

<?php require __DIR__ . '/_partials/page-shell-end.php'; ?>
