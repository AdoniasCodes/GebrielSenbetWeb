<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'admin') { header('Location: /'); exit; }

$page_title    = 'Teacher Assignments';
$page_title_am = 'የመምህር ምድቦች';
$page_eyebrow    = 'Classroom';
$page_eyebrow_am = 'ክፍል';
$active_nav = 'assignments';
require __DIR__ . '/_partials/page-shell.php';
?>

<div class="flex items-center justify-between flex-wrap gap-3">
  <p class="text-sm text-ink-soft max-w-xl">Assign teachers to teach a subject in a specific class as Primary or Substitute, with date-bounded responsibility.</p>
  <button id="newBtn" class="btn-primary">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
    <span>New Assignment</span>
  </button>
</div>

<section id="formPanel" class="panel hidden">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 id="formTitle" class="font-display text-lg text-ink">New Assignment</h2>
    <button type="button" id="cancelBtn" class="btn-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
  </header>
  <form id="entityForm" class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
    <input type="hidden" id="f_id" />
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Teacher</label>
      <select id="f_teacher" class="input-field" required></select>
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
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Role</label>
      <select id="f_role" class="input-field">
        <option value="primary">Primary</option>
        <option value="substitute">Substitute</option>
      </select>
    </div>
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Start date</label>
      <input id="f_start" type="date" class="input-field" required />
    </div>
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">End date (optional)</label>
      <input id="f_end" type="date" class="input-field" />
    </div>
    <div class="md:col-span-3 flex items-center gap-3">
      <button type="submit" class="btn-primary">Save</button>
      <button type="button" id="cancelBtn2" class="btn-ghost">Cancel</button>
      <p id="formMsg" class="text-sm text-error hidden"></p>
    </div>
  </form>
</section>

<section class="panel">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 class="font-display text-lg text-ink">All assignments · <span id="rowCount" class="text-ink-soft text-sm">—</span></h2>
  </header>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Teacher</th><th>Class</th><th>Subject</th><th>Role</th><th>Period</th><th>Status</th><th class="text-right">&nbsp;</th></tr></thead>
      <tbody id="tbody"><tr><td colspan="7" class="text-center text-ink-soft py-12">Loading…</td></tr></tbody>
    </table>
  </div>
</section>

<script>
  var formPanel = document.getElementById('formPanel');
  var formTitle = document.getElementById('formTitle');
  var msg = document.getElementById('formMsg');
  var all = [];
  var teachers = [], classes = [], subjects = [];

  function showForm(item) {
    formPanel.classList.remove('hidden');
    msg.classList.add('hidden');
    document.getElementById('f_id').value      = item ? item.id : '';
    document.getElementById('f_teacher').value = item ? item.teacher_id : '';
    document.getElementById('f_class').value   = item ? item.class_id : '';
    document.getElementById('f_subject').value = item ? item.subject_id : '';
    document.getElementById('f_role').value    = item ? item.role : 'primary';
    document.getElementById('f_start').value   = item ? item.start_date : new Date().toISOString().slice(0,10);
    document.getElementById('f_end').value     = item && item.end_date ? item.end_date : '';
    formTitle.textContent = item ? 'Edit Assignment' : 'New Assignment';
    formPanel.scrollIntoView({ behavior:'smooth', block:'center' });
  }
  function hideForm() { formPanel.classList.add('hidden'); }
  document.getElementById('newBtn').addEventListener('click', function () { showForm(null); });
  document.getElementById('cancelBtn').addEventListener('click', hideForm);
  document.getElementById('cancelBtn2').addEventListener('click', hideForm);

  function escHtml(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];}); }

  function render() {
    document.getElementById('rowCount').textContent = all.length + ' total';
    var tbody = document.getElementById('tbody');
    if (!all.length) { tbody.innerHTML = '<tr><td colspan="7" class="text-center text-ink-soft py-16">No assignments yet.</td></tr>'; return; }
    tbody.innerHTML = all.map(function (a) {
      var rolePill = a.role === 'primary' ? '<span class="pill pill-active">Primary</span>' : '<span class="pill pill-draft">Substitute</span>';
      var statusPill = a.is_archived == 1 ? '<span class="pill pill-archived">Archived</span>' : '<span class="pill pill-active">Active</span>';
      var teacher = (a.teacher_first || '') + ' ' + (a.teacher_last || '');
      return '<tr>' +
        '<td>'+escHtml(teacher.trim() || '—')+'</td>' +
        '<td>'+escHtml(a.class_name || '—')+' <span class="text-xs text-outline">('+escHtml(a.level_name || '')+')</span></td>' +
        '<td>'+escHtml(a.subject_name || '—')+'</td>' +
        '<td>'+rolePill+'</td>' +
        '<td class="text-xs text-outline">'+escHtml(a.start_date)+(a.end_date?' → '+escHtml(a.end_date):'')+'</td>' +
        '<td>'+statusPill+'</td>' +
        '<td class="text-right"><div class="inline-flex items-center gap-1">' +
          '<button class="btn-icon" title="Edit" data-edit="'+a.id+'"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>' +
          (a.is_archived == 1 ? '' :
            '<button class="btn-icon danger" title="Archive" data-archive="'+a.id+'"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/></svg></button>') +
        '</div></td>' +
      '</tr>';
    }).join('');
  }

  function fillSelects() {
    var ts = document.getElementById('f_teacher');
    ts.innerHTML = '<option value="">Choose a teacher…</option>' + teachers.map(function (t) {
      var name = ((t.first_name||'') + ' ' + (t.last_name||'')).trim() || ('Teacher #' + t.id);
      return '<option value="'+t.id+'">'+escHtml(name)+'</option>';
    }).join('');

    var cs = document.getElementById('f_class');
    cs.innerHTML = '<option value="">Choose a class…</option>' + classes.filter(function (c) { return c.is_archived == 0; }).map(function (c) {
      return '<option value="'+c.id+'">'+escHtml((c.track_name||'') + ' · ' + (c.level_name||'') + ' · ' + (c.name||'') + ' (' + (c.academic_year||'') + ')')+'</option>';
    }).join('');

    var ss = document.getElementById('f_subject');
    ss.innerHTML = '<option value="">Choose a subject…</option>' + subjects.filter(function (s) { return s.is_archived == 0; }).map(function (s) {
      return '<option value="'+s.id+'">'+escHtml(s.name)+'</option>';
    }).join('');
  }

  async function load() {
    try {
      var [tt, cc, ss, aa] = await Promise.all([
        gs.api('/api/admin/teachers/list.php'),
        gs.api('/api/admin/classes/index.php'),
        gs.api('/api/admin/subjects/index.php'),
        gs.api('/api/admin/assignments/index.php?include_archived=1')
      ]);
      teachers = tt.data || []; classes = cc.data || []; subjects = ss.data || [];
      all = aa.data || [];
      fillSelects();
      render();
    } catch (e) { gs.toast(e.message,'error'); }
  }

  document.getElementById('entityForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    msg.classList.add('hidden');
    var id = document.getElementById('f_id').value;
    var body = {
      teacher_id: parseInt(document.getElementById('f_teacher').value, 10),
      class_id:   parseInt(document.getElementById('f_class').value, 10),
      subject_id: parseInt(document.getElementById('f_subject').value, 10),
      role:       document.getElementById('f_role').value,
      start_date: document.getElementById('f_start').value,
      end_date:   document.getElementById('f_end').value || null,
    };
    if (!body.teacher_id || !body.class_id || !body.subject_id || !body.start_date) return;
    try {
      if (id) { body.id = parseInt(id,10); await gs.api('/api/admin/assignments/index.php', { method:'PUT', body: JSON.stringify(body) }); }
      else    await gs.api('/api/admin/assignments/index.php', { method:'POST', body: JSON.stringify(body) });
      gs.toast(id ? 'Updated' : 'Created','success'); hideForm(); load();
    } catch (err) { msg.textContent = err.message; msg.classList.remove('hidden'); }
  });

  document.addEventListener('click', async function (e) {
    var t = e.target.closest('[data-edit], [data-archive]');
    if (!t) return;
    var id = parseInt(t.dataset.edit || t.dataset.archive, 10);
    if (t.dataset.edit) { var item = all.find(function (x) { return x.id === id; }); if (item) showForm(item); }
    else {
      if (!await gs.confirm('Archive this assignment?')) return;
      try { await gs.api('/api/admin/assignments/index.php', { method:'DELETE', body: JSON.stringify({ id: id })}); gs.toast('Archived','success'); load(); }
      catch (err) { gs.toast(err.message,'error'); }
    }
  });

  load();
</script>

<?php require __DIR__ . '/_partials/page-shell-end.php'; ?>
