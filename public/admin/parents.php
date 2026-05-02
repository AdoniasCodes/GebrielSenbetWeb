<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'admin') { header('Location: /'); exit; }

$page_title    = 'Parents';
$page_title_am = 'ወላጆች';
$page_eyebrow    = 'Registry';
$page_eyebrow_am = 'መዝገብ';
$active_nav = 'parents';
require __DIR__ . '/_partials/page-shell.php';
?>

<div class="flex items-center justify-between flex-wrap gap-3">
  <p class="text-sm text-ink-soft max-w-xl">Each parent account links to one or more student records. Parents only see data for their linked children.</p>
  <button id="newBtn" class="btn-primary">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
    <span>New parent</span>
  </button>
</div>

<section id="formPanel" class="panel hidden">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 id="formTitle" class="font-display text-lg text-ink">New parent</h2>
    <button type="button" id="cancelBtn" class="btn-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
  </header>
  <form id="entityForm" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
    <input type="hidden" id="f_id" />
    <div><label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Email</label><input id="f_email" type="email" class="input-field" required /></div>
    <div><label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Password (leave blank to auto-generate)</label><input id="f_password" type="text" class="input-field" placeholder="auto-generate" /></div>
    <div class="md:col-span-2">
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Linked children</label>
      <div id="studentChecks" class="grid grid-cols-1 md:grid-cols-3 gap-2 max-h-72 overflow-y-auto bg-surface-low rounded p-3 border border-outline-soft/30"></div>
      <p class="text-xs text-ink-soft mt-2">Tip: a parent can be linked to multiple children. Updates here replace the full set of links.</p>
    </div>
    <div class="md:col-span-2 flex items-center gap-3">
      <button type="submit" class="btn-primary">Save</button>
      <button type="button" id="cancelBtn2" class="btn-ghost">Cancel</button>
      <p id="formMsg" class="text-sm hidden"></p>
    </div>
  </form>
</section>

<section class="panel">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 class="font-display text-lg text-ink">Parents · <span id="rowCount" class="text-ink-soft text-sm">—</span></h2>
  </header>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Email</th><th>Children</th><th># linked</th><th>Created</th><th class="text-right">&nbsp;</th></tr></thead>
      <tbody id="tbody"><tr><td colspan="5" class="text-center text-ink-soft py-12">Loading…</td></tr></tbody>
    </table>
  </div>
</section>

<script>
  var all = [], allStudents = [];
  function escHtml(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];});}

  function renderStudentChecks(checked) {
    checked = checked || [];
    var wrap = document.getElementById('studentChecks');
    wrap.innerHTML = allStudents.filter(function(s){return s.is_archived==0;}).map(function(s){
      var name = ((s.profile && s.profile.first_name) ? (s.profile.first_name + ' ' + s.profile.last_name) : s.email);
      var sid = s.profile && s.profile.student_id;
      if (!sid) return '';
      var ck = checked.indexOf(sid) >= 0 ? 'checked' : '';
      return '<label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" class="w-4 h-4 student-check" value="'+sid+'" '+ck+' /><span>'+escHtml(name)+'</span></label>';
    }).filter(Boolean).join('');
  }

  function render() {
    document.getElementById('rowCount').textContent = all.length + ' total';
    var tbody = document.getElementById('tbody');
    if (!all.length) { tbody.innerHTML = '<tr><td colspan="5" class="text-center text-ink-soft py-16">No parent accounts yet.</td></tr>'; return; }
    tbody.innerHTML = all.map(function(p){
      return '<tr>' +
        '<td><p class="font-medium">'+escHtml(p.email)+'</p></td>' +
        '<td class="text-ink-soft text-sm">'+escHtml(p.children_names || '—')+'</td>' +
        '<td>'+escHtml(p.linked_students)+'</td>' +
        '<td class="text-ink-soft text-sm" data-iso="'+escHtml(p.created_at)+'" data-fmt-style="long">'+escHtml(p.created_at)+'</td>' +
        '<td class="text-right"><div class="inline-flex items-center gap-1">' +
          '<button class="btn-icon" title="Edit" data-edit="'+p.id+'"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>' +
          '<button class="btn-icon danger" title="Archive" data-archive="'+p.id+'"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/></svg></button>' +
        '</div></td>' +
      '</tr>';
    }).join('');
    if (window.EC) EC.rerenderIsoNodes();
  }

  async function load() {
    try {
      var [pr, st] = await Promise.all([
        gs.api('/api/admin/parents/index.php'),
        gs.api('/api/admin/users/index.php?role=student'),
      ]);
      all = pr.data || [];
      allStudents = st.data || [];
      render();
    } catch(e) { gs.toast(e.message,'error'); }
  }

  var formPanel = document.getElementById('formPanel');
  var formTitle = document.getElementById('formTitle');
  var msg = document.getElementById('formMsg');
  function showForm(item) {
    formPanel.classList.remove('hidden');
    msg.className = 'text-sm hidden';
    document.getElementById('f_id').value = item ? item.id : '';
    document.getElementById('f_email').value = item ? item.email : '';
    document.getElementById('f_password').value = '';
    formTitle.textContent = item ? 'Edit parent' : 'New parent';
    renderStudentChecks(item ? (item.children_ids || []) : []);
    formPanel.scrollIntoView({behavior:'smooth', block:'center'});
  }
  function hideForm() { formPanel.classList.add('hidden'); }
  document.getElementById('newBtn').addEventListener('click', function(){ showForm(null); });
  document.getElementById('cancelBtn').addEventListener('click', hideForm);
  document.getElementById('cancelBtn2').addEventListener('click', hideForm);

  document.getElementById('entityForm').addEventListener('submit', async function(e){
    e.preventDefault();
    msg.className = 'text-sm hidden';
    var id = document.getElementById('f_id').value;
    var checked = Array.prototype.slice.call(document.querySelectorAll('.student-check:checked')).map(function(c){ return parseInt(c.value, 10); });
    var body = {
      email: document.getElementById('f_email').value.trim(),
      student_ids: checked,
    };
    var pwd = document.getElementById('f_password').value.trim();
    if (pwd) body.password = pwd;
    try {
      var res;
      if (id) {
        body.id = parseInt(id,10);
        res = await gs.api('/api/admin/parents/index.php', { method:'PUT', body: JSON.stringify(body) });
        gs.toast('Updated','success');
      } else {
        res = await gs.api('/api/admin/parents/index.php', { method:'POST', body: JSON.stringify(body) });
        gs.toast('Created','success');
        if (res.generated_password) {
          msg.className = 'text-sm';
          msg.style.color = '#384700';
          msg.textContent = 'Generated password: ' + res.generated_password + ' — share now (it will not be shown again).';
        }
      }
      load();
      if (id || !res.generated_password) hideForm();
    } catch(err) {
      msg.className = 'text-sm text-error';
      msg.textContent = err.message;
    }
  });

  document.addEventListener('click', async function(e){
    var t = e.target.closest('[data-edit], [data-archive]');
    if (!t) return;
    var id = parseInt(t.dataset.edit || t.dataset.archive, 10);
    if (t.dataset.edit) {
      var item = all.find(function(x){ return x.id === id; });
      if (item) showForm(item);
    } else {
      if (!await gs.confirm('Archive this parent account? Their access will be removed.')) return;
      try { await gs.api('/api/admin/parents/index.php', { method:'DELETE', body: JSON.stringify({ id: id })}); gs.toast('Archived','success'); load(); }
      catch(err) { gs.toast(err.message,'error'); }
    }
  });

  document.addEventListener('gs:lang-change', render);
  load();
</script>

<?php require __DIR__ . '/_partials/page-shell-end.php'; ?>
