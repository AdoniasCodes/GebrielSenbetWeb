<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'admin') { header('Location: /'); exit; }

$page_title    = 'Grades';
$page_title_am = 'ውጤቶች';
$page_eyebrow    = 'Records';
$page_eyebrow_am = 'መዝገቦች';
$active_nav = 'grades';
require __DIR__ . '/_partials/page-shell.php';
?>

<section class="panel">
  <div class="px-6 py-5 grid grid-cols-1 md:grid-cols-[1fr_1fr_1fr_1fr_auto_auto] gap-3 items-end">
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Term</label>
      <select id="filterTerm" class="input-field"><option value="">All terms</option></select>
    </div>
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Class</label>
      <select id="filterClass" class="input-field"><option value="">All classes</option></select>
    </div>
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Subject</label>
      <select id="filterSubject" class="input-field"><option value="">All subjects</option></select>
    </div>
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Student</label>
      <select id="filterStudent" class="input-field"><option value="">All students</option></select>
    </div>
    <button id="reloadBtn" class="btn-ghost">Refresh</button>
    <button id="newBtn" class="btn-primary">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
      <span>Add grade</span>
    </button>
  </div>
</section>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
  <div class="stat-card border-t-olive">
    <p class="text-[10px] font-semibold uppercase tracking-widestest text-ink-soft mb-3">Rows</p>
    <p class="font-display text-3xl text-ink leading-none"><span id="totCount">—</span></p>
  </div>
  <div class="stat-card border-t-gold-soft">
    <p class="text-[10px] font-semibold uppercase tracking-widestest text-ink-soft mb-3">Average score</p>
    <p class="font-display text-3xl text-ink leading-none"><span id="totAvg">—</span></p>
  </div>
</div>

<section id="formPanel" class="panel hidden">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 id="formTitle" class="font-display text-lg text-ink">New grade</h2>
    <button type="button" id="cancelBtn" class="btn-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
  </header>
  <form id="entityForm" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
    <input type="hidden" id="f_id" />
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Student</label>
      <select id="f_student" class="input-field" required></select>
    </div>
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Term</label>
      <select id="f_term" class="input-field" required></select>
    </div>
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Class</label>
      <select id="f_class" class="input-field" required></select>
    </div>
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Subject</label>
      <select id="f_subject" class="input-field" required></select>
    </div>
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Score (0–100)</label>
      <input id="f_score" type="number" step="0.01" min="0" max="100" class="input-field" required />
    </div>
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Remarks (optional)</label>
      <input id="f_remarks" class="input-field" maxlength="255" />
    </div>
    <div class="md:col-span-2 flex items-center gap-3">
      <button type="submit" class="btn-primary">Save</button>
      <button type="button" id="cancelBtn2" class="btn-ghost">Cancel</button>
      <p id="formMsg" class="text-sm text-error hidden"></p>
    </div>
  </form>
</section>

<section class="panel">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 class="font-display text-lg text-ink">Grades · <span id="rowCount" class="text-ink-soft text-sm">—</span></h2>
  </header>
  <div class="table-wrap">
    <table class="data">
      <thead><tr>
        <th>Student</th><th>Term</th><th>Class</th><th>Subject</th><th>Score</th><th>Remarks</th><th class="text-right">&nbsp;</th>
      </tr></thead>
      <tbody id="tbody"><tr><td colspan="7" class="text-center text-ink-soft py-12">Loading…</td></tr></tbody>
    </table>
  </div>
</section>

<script>
  var all = [], terms = [], classes = [], subjects = [], students = [];
  function escHtml(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];}); }

  function fillFilters() {
    var ts = document.getElementById('filterTerm');
    ts.innerHTML = '<option value="">All terms</option>' + terms.filter(function(t){return t.is_archived==0;}).map(function(t){
      return '<option value="'+t.id+'"'+(t.is_current==1?' selected':'')+'>'+escHtml(t.academic_year)+' · '+escHtml(t.name)+'</option>';
    }).join('');
    var cs = document.getElementById('filterClass');
    cs.innerHTML = '<option value="">All classes</option>' + classes.filter(function(c){return c.is_archived==0;}).map(function(c){
      return '<option value="'+c.id+'">'+escHtml((c.track_name||'') + ' · ' + (c.level_name||'') + ' · ' + c.name + ' (' + c.academic_year + ')')+'</option>';
    }).join('');
    var subs = document.getElementById('filterSubject');
    subs.innerHTML = '<option value="">All subjects</option>' + subjects.filter(function(s){return s.is_archived==0;}).map(function(s){
      return '<option value="'+s.id+'">'+escHtml(s.name)+'</option>';
    }).join('');
    var st = document.getElementById('filterStudent');
    st.innerHTML = '<option value="">All students</option>' + students.filter(function(s){return s.is_archived==0;}).map(function(s){
      var nm = ((s.first_name||'') + ' ' + (s.last_name||'')).trim() || s.email;
      return '<option value="'+s.id+'">'+escHtml(nm)+'</option>';
    }).join('');
  }

  function fillFormDropdowns() {
    document.getElementById('f_term').innerHTML = terms.filter(function(t){return t.is_archived==0;}).map(function(t){
      return '<option value="'+t.id+'"'+(t.is_current==1?' selected':'')+'>'+escHtml(t.academic_year)+' · '+escHtml(t.name)+'</option>';
    }).join('');
    document.getElementById('f_class').innerHTML = classes.filter(function(c){return c.is_archived==0;}).map(function(c){
      return '<option value="'+c.id+'">'+escHtml((c.track_name||'') + ' · ' + (c.level_name||'') + ' · ' + c.name)+'</option>';
    }).join('');
    document.getElementById('f_subject').innerHTML = subjects.filter(function(s){return s.is_archived==0;}).map(function(s){
      return '<option value="'+s.id+'">'+escHtml(s.name)+'</option>';
    }).join('');
    document.getElementById('f_student').innerHTML = students.filter(function(s){return s.is_archived==0;}).map(function(s){
      var nm = ((s.first_name||'') + ' ' + (s.last_name||'')).trim() || s.email;
      return '<option value="'+s.id+'">'+escHtml(nm)+'</option>';
    }).join('');
  }

  function render() {
    document.getElementById('rowCount').textContent = all.length + ' rows';
    var tbody = document.getElementById('tbody');
    if (!all.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="text-center text-ink-soft py-16">No grades match these filters. Add one above to get started.</td></tr>';
      return;
    }
    tbody.innerHTML = all.map(function (g) {
      var name = ((g.first_name||'') + ' ' + (g.last_name||'')).trim();
      var cls = escHtml(g.class_name || '') + (g.level_name ? ' <span class="text-xs text-outline">('+escHtml(g.level_name)+')</span>' : '');
      return '<tr>' +
        '<td><a class="font-medium hover:text-primary" href="/admin/student-detail.php?id='+g.student_id+'">'+escHtml(name)+'</a></td>' +
        '<td class="text-ink-soft">'+escHtml(g.academic_year || '')+' · '+escHtml(g.term_name || '')+'</td>' +
        '<td>'+cls+'</td>' +
        '<td>'+escHtml(g.subject_name || '')+'</td>' +
        '<td class="font-medium">'+escHtml(g.score)+'</td>' +
        '<td class="text-ink-soft text-sm">'+escHtml(g.remarks || '')+'</td>' +
        '<td class="text-right"><div class="inline-flex items-center gap-1">' +
          '<button class="btn-icon" title="Edit" data-edit="'+g.id+'"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>' +
          '<button class="btn-icon danger" title="Archive" data-archive="'+g.id+'"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/></svg></button>' +
        '</div></td>' +
      '</tr>';
    }).join('');
  }

  function renderTotals(t) {
    document.getElementById('totCount').textContent = (t.count != null) ? t.count : '—';
    document.getElementById('totAvg').textContent   = (t.avg   != null) ? t.avg   : '—';
  }

  async function load() {
    var qs = [];
    var t = document.getElementById('filterTerm').value;
    var c = document.getElementById('filterClass').value;
    var s = document.getElementById('filterSubject').value;
    var st = document.getElementById('filterStudent').value;
    if (t) qs.push('term_id='+encodeURIComponent(t));
    if (c) qs.push('class_id='+encodeURIComponent(c));
    if (s) qs.push('subject_id='+encodeURIComponent(s));
    if (st) qs.push('student_id='+encodeURIComponent(st));
    try {
      var d = await gs.api('/api/admin/grades/index.php' + (qs.length ? '?' + qs.join('&') : ''));
      all = d.data || []; renderTotals(d.totals || {}); render();
    } catch (e) { gs.toast(e.message, 'error'); }
  }

  async function init() {
    try {
      var [tr, cl, su, us] = await Promise.all([
        gs.api('/api/admin/terms/index.php'),
        gs.api('/api/admin/classes/index.php'),
        gs.api('/api/admin/subjects/index.php'),
        gs.api('/api/admin/users/index.php?role=student'),
      ]);
      terms = tr.data || []; classes = cl.data || []; subjects = su.data || []; students = us.data || [];
      fillFilters(); fillFormDropdowns(); load();
    } catch (e) { gs.toast(e.message, 'error'); }
  }

  ['filterTerm','filterClass','filterSubject','filterStudent'].forEach(function(id){
    document.getElementById(id).addEventListener('change', load);
  });
  document.getElementById('reloadBtn').addEventListener('click', load);

  var formPanel = document.getElementById('formPanel');
  var formTitle = document.getElementById('formTitle');
  var msg = document.getElementById('formMsg');

  function showForm(item) {
    formPanel.classList.remove('hidden');
    msg.classList.add('hidden');
    document.getElementById('f_id').value      = item ? item.id : '';
    document.getElementById('f_score').value   = item ? item.score : '';
    document.getElementById('f_remarks').value = item && item.remarks ? item.remarks : '';
    if (item) {
      document.getElementById('f_student').value = item.student_id;
      document.getElementById('f_term').value    = item.term_id;
      document.getElementById('f_class').value   = item.class_id;
      document.getElementById('f_subject').value = item.subject_id;
    }
    formTitle.textContent = item ? 'Edit grade' : 'New grade';
    formPanel.scrollIntoView({ behavior:'smooth', block:'center' });
  }
  function hideForm() { formPanel.classList.add('hidden'); }
  document.getElementById('newBtn').addEventListener('click', function () { showForm(null); });
  document.getElementById('cancelBtn').addEventListener('click', hideForm);
  document.getElementById('cancelBtn2').addEventListener('click', hideForm);

  document.getElementById('entityForm').addEventListener('submit', async function (ev) {
    ev.preventDefault();
    msg.classList.add('hidden');
    var id = document.getElementById('f_id').value;
    var body = {
      score: parseFloat(document.getElementById('f_score').value || '0'),
      remarks: document.getElementById('f_remarks').value || null,
    };
    try {
      if (id) {
        body.id = parseInt(id, 10);
        await gs.api('/api/admin/grades/index.php', { method:'PUT', body: JSON.stringify(body) });
      } else {
        body.student_id = parseInt(document.getElementById('f_student').value, 10);
        body.term_id    = parseInt(document.getElementById('f_term').value, 10);
        body.class_id   = parseInt(document.getElementById('f_class').value, 10);
        body.subject_id = parseInt(document.getElementById('f_subject').value, 10);
        await gs.api('/api/admin/grades/index.php', { method:'POST', body: JSON.stringify(body) });
      }
      gs.toast(id ? 'Grade updated' : 'Grade created', 'success');
      hideForm(); load();
    } catch (err) { msg.textContent = err.message; msg.classList.remove('hidden'); }
  });

  document.addEventListener('click', async function (e) {
    var t = e.target.closest('[data-edit], [data-archive]');
    if (!t) return;
    var id = parseInt(t.dataset.edit || t.dataset.archive, 10);
    if (t.dataset.edit) {
      var item = all.find(function (x) { return x.id === id; });
      if (item) showForm(item);
    } else {
      if (!await gs.confirm('Archive this grade?')) return;
      try { await gs.api('/api/admin/grades/index.php', { method:'DELETE', body: JSON.stringify({ id: id })}); gs.toast('Archived','success'); load(); }
      catch (err) { gs.toast(err.message,'error'); }
    }
  });

  init();
</script>

<?php require __DIR__ . '/_partials/page-shell-end.php'; ?>
