<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'admin') { header('Location: /'); exit; }

$student_id = (int)($_GET['id'] ?? 0);
if ($student_id <= 0) { header('Location: /admin/students.php'); exit; }

$page_title    = 'Student';
$page_title_am = 'ተማሪ';
$page_eyebrow    = 'Registry';
$page_eyebrow_am = 'መዝገብ';
$active_nav = 'students';
require __DIR__ . '/_partials/page-shell.php';
?>

<a href="/admin/students.php" class="inline-flex items-center gap-2 text-sm text-ink-soft hover:text-primary -mt-2">
  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
  <span data-en="Back to students" data-am="ወደ ተማሪዎች ተመለስ">Back to students</span>
</a>

<!-- Profile header -->
<section class="panel p-8" id="profileWrap">
  <p class="text-center text-ink-soft py-12" data-en="Loading…" data-am="በመጫን ላይ…">Loading…</p>
</section>

<!-- Two columns: assign + history left | grades + payments right -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

  <section class="panel">
    <header class="px-6 py-5 border-b border-outline-soft/40">
      <h2 class="font-display text-lg text-ink" data-en="Class Assignment" data-am="የክፍል ምድብ">Class Assignment</h2>
    </header>
    <div class="p-6 space-y-4" id="assignmentWrap">
      <div id="currentAssignment" class="text-sm text-ink-soft">—</div>
      <div class="rule-gold opacity-40"></div>
      <p class="text-[11px] font-semibold uppercase tracking-widestest text-ink-soft" data-en="Assign to class" data-am="ለክፍል መድብ">Assign to class</p>
      <form id="assignForm" class="space-y-3">
        <select id="assignClass" class="input-field" required>
          <option value="">Choose a class…</option>
        </select>
        <input id="assignDate" type="date" class="input-field" />
        <button type="submit" class="btn-primary"><span data-en="Assign" data-am="መድብ">Assign</span></button>
      </form>
    </div>
  </section>

  <section class="panel">
    <header class="px-6 py-5 border-b border-outline-soft/40">
      <h2 class="font-display text-lg text-ink" data-en="Assignment History" data-am="የምድብ ታሪክ">Assignment History</h2>
    </header>
    <ul id="historyList" class="divide-y divide-outline-soft/30">
      <li class="px-6 py-12 text-center text-ink-soft text-sm">—</li>
    </ul>
  </section>

  <section class="panel lg:col-span-2">
    <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
      <h2 class="font-display text-lg text-ink" data-en="Grades" data-am="ውጤቶች">Grades</h2>
      <a id="reportCardLink" target="_blank" class="text-xs font-semibold uppercase tracking-widestest text-gold hover:text-primary inline-flex items-center gap-2" href="/admin/report-card.php?student_id=<?= (int)$student_id ?>">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        <span data-en="Print report card" data-am="ሪፖርት ካርድ ይታተም">Print report card</span>
      </a>
    </header>
    <div id="gradesWrap" class="table-wrap">
      <p class="px-6 py-12 text-center text-ink-soft text-sm">—</p>
    </div>
  </section>

  <section class="panel lg:col-span-2">
    <header class="px-6 py-5 border-b border-outline-soft/40">
      <h2 class="font-display text-lg text-ink" data-en="Payments" data-am="ክፍያዎች">Payments</h2>
    </header>
    <div id="paymentsWrap" class="table-wrap">
      <p class="px-6 py-12 text-center text-ink-soft text-sm">—</p>
    </div>
  </section>
</div>

<script>
  var STUDENT_ID = <?= (int)$student_id ?>;
  function escHtml(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];}); }

  function renderProfile(s, current) {
    var name = ((s.first_name||'') + ' ' + (s.last_name||'')).trim() || s.email;
    var initials = ((s.first_name||'?')[0] || '?').toUpperCase() + ((s.last_name||'')[0] || '').toUpperCase();
    var statusPill = s.is_archived == 1 ? '<span class="pill pill-archived">Archived</span>' : '<span class="pill pill-active">Active</span>';
    var html =
      '<div class="flex items-start gap-6 flex-wrap">' +
        '<div class="avatar-circle w-20 h-20 bg-primary/10 text-primary text-2xl">'+escHtml(initials)+'</div>' +
        '<div class="flex-1 min-w-[200px]">' +
          '<h2 class="font-display text-3xl text-ink mb-2">'+escHtml(name)+'</h2>' +
          '<p class="text-sm text-ink-soft mb-3">'+escHtml(s.email)+'</p>' +
          '<div class="flex flex-wrap gap-2 mb-4">'+statusPill+'</div>' +
          '<dl class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">' +
            '<div><dt class="text-[10px] uppercase tracking-widestest text-ink-soft mb-1">Phone</dt><dd>'+escHtml(s.phone||'—')+'</dd></div>' +
            '<div><dt class="text-[10px] uppercase tracking-widestest text-ink-soft mb-1">Guardian</dt><dd>'+escHtml(s.guardian_name||'—')+'</dd></div>' +
            '<div><dt class="text-[10px] uppercase tracking-widestest text-ink-soft mb-1">Date of birth</dt><dd>'+escHtml(s.date_of_birth||'—')+'</dd></div>' +
            '<div class="col-span-2 sm:col-span-3"><dt class="text-[10px] uppercase tracking-widestest text-ink-soft mb-1">Address</dt><dd>'+escHtml(s.address||'—')+'</dd></div>' +
          '</dl>' +
        '</div>' +
      '</div>';
    document.getElementById('profileWrap').innerHTML = html;
  }

  function renderCurrentAssignment(asn) {
    var el = document.getElementById('currentAssignment');
    if (!asn) {
      el.innerHTML = '<p class="text-sm text-outline">Not currently assigned to any class.</p>';
      return;
    }
    el.innerHTML =
      '<div class="bg-surface-low border border-outline-soft/40 rounded p-4">' +
        '<p class="text-[10px] uppercase tracking-widestest text-gold mb-1">Currently in</p>' +
        '<p class="font-display text-xl text-primary">'+escHtml(asn.class_name)+'</p>' +
        '<p class="text-sm text-ink-soft">'+escHtml(asn.track_name)+' · '+escHtml(asn.level_name)+' · '+escHtml(asn.academic_year)+'</p>' +
        '<p class="text-xs text-outline mt-2">Since '+escHtml(asn.assigned_at||'—')+'</p>' +
      '</div>';
  }

  function renderHistory(rows) {
    var ul = document.getElementById('historyList');
    if (!rows.length) { ul.innerHTML = '<li class="px-6 py-12 text-center text-ink-soft text-sm">No assignment history.</li>'; return; }
    ul.innerHTML = rows.map(function (h) {
      var status = h.is_archived == 1 ? '<span class="pill pill-archived">Ended</span>' : '<span class="pill pill-active">Current</span>';
      return '<li class="px-6 py-4">' +
        '<div class="flex items-center justify-between gap-4">' +
          '<div><p class="font-medium">'+escHtml(h.class_name)+' <span class="text-xs text-outline">('+escHtml(h.level_name)+')</span></p>' +
          '<p class="text-xs text-ink-soft mt-0.5">'+escHtml(h.track_name)+' · '+escHtml(h.academic_year)+'</p></div>' +
          '<div class="text-right">'+status+'<p class="text-xs text-outline mt-1">'+escHtml(h.assigned_at)+(h.ended_at?' → '+escHtml(h.ended_at):'')+'</p></div>' +
        '</div></li>';
    }).join('');
  }

  function renderGrades(rows) {
    var wrap = document.getElementById('gradesWrap');
    if (!rows.length) { wrap.innerHTML = '<p class="px-6 py-12 text-center text-ink-soft text-sm">No grades yet.</p>'; return; }
    wrap.innerHTML = '<table class="data"><thead><tr>' +
      '<th>Term</th><th>Class</th><th>Subject</th><th>Score</th><th>Remarks</th>' +
      '</tr></thead><tbody>' + rows.map(function (g) {
        return '<tr>' +
          '<td class="text-ink-soft">'+escHtml(g.academic_year)+' · '+escHtml(g.term_name)+'</td>' +
          '<td>'+escHtml(g.class_name)+' <span class="text-xs text-outline">('+escHtml(g.level_name)+')</span></td>' +
          '<td>'+escHtml(g.subject_name)+'</td>' +
          '<td><span class="font-display text-lg text-primary">'+escHtml(g.score)+'</span></td>' +
          '<td class="text-ink-soft text-sm">'+escHtml(g.remarks||'—')+'</td>' +
        '</tr>';
      }).join('') + '</tbody></table>';
  }

  function renderPayments(rows) {
    var wrap = document.getElementById('paymentsWrap');
    if (!rows.length) { wrap.innerHTML = '<p class="px-6 py-12 text-center text-ink-soft text-sm">No payments recorded.</p>'; return; }
    wrap.innerHTML = '<table class="data"><thead><tr>' +
      '<th>Term</th><th>Amount</th><th>Status</th><th>Notes</th>' +
      '</tr></thead><tbody>' + rows.map(function (p) {
        var pill = '<span class="pill pill-' + escHtml(p.status) + '">' + escHtml(p.status) + '</span>';
        return '<tr>' +
          '<td class="text-ink-soft">'+escHtml(p.academic_year)+' · '+escHtml(p.term_name)+'</td>' +
          '<td>ETB '+escHtml(parseFloat(p.amount).toFixed(2))+'</td>' +
          '<td>'+pill+'</td>' +
          '<td class="text-ink-soft text-sm">'+escHtml(p.notes||'—')+'</td>' +
        '</tr>';
      }).join('') + '</tbody></table>';
  }

  async function load() {
    try {
      var d = await gs.api('/api/admin/students/detail.php?id=' + STUDENT_ID);
      renderProfile(d.student, d.current_assignment);
      renderCurrentAssignment(d.current_assignment);
      renderHistory(d.assignment_history || []);
      renderGrades(d.grades || []);
      renderPayments(d.payments || []);
    } catch (e) { gs.toast(e.message, 'error'); }
  }

  async function loadClassesForAssign() {
    try {
      var d = await gs.api('/api/admin/classes/index.php');
      var sel = document.getElementById('assignClass');
      (d.data || []).forEach(function (c) {
        if (c.is_archived == 1) return;
        var opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = (c.track_name||'') + ' · ' + (c.level_name||'') + ' · ' + (c.name||'') + ' (' + (c.academic_year||'') + ')';
        sel.appendChild(opt);
      });
    } catch (e) { /* fail silent */ }
  }

  document.getElementById('assignDate').valueAsDate = new Date();

  document.getElementById('assignForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    var classId = parseInt(document.getElementById('assignClass').value, 10);
    var date = document.getElementById('assignDate').value;
    if (!classId) return;
    try {
      await gs.api('/api/admin/student-assignments/index.php', {
        method: 'POST',
        body: JSON.stringify({ student_id: STUDENT_ID, class_id: classId, assigned_at: date })
      });
      gs.toast('Assigned', 'success');
      await load();
    } catch (err) { gs.toast(err.message, 'error'); }
  });

  load();
  loadClassesForAssign();
</script>

<?php require __DIR__ . '/_partials/page-shell-end.php'; ?>
