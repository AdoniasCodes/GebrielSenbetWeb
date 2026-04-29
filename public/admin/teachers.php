<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'admin') { header('Location: /'); exit; }

$page_title    = 'Teachers';
$page_title_am = 'መምህራን';
$page_eyebrow    = 'Registry';
$page_eyebrow_am = 'መዝገብ';
$active_nav = 'teachers';
require __DIR__ . '/_partials/page-shell.php';

$auto_open_new = isset($_GET['new']) && $_GET['new'] === '1';
?>

<div class="flex flex-wrap items-center gap-3 justify-between">
  <div class="flex items-center gap-2 flex-1 min-w-[200px] max-w-md">
    <div class="relative flex-1">
      <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-outline" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
      <input id="searchInput" type="search" placeholder="Search by name or email…" class="input-field !pl-10" />
    </div>
  </div>
  <div class="flex items-center gap-2">
    <select id="archivedFilter" class="input-field !w-auto !py-2.5">
      <option value="0">Active only</option>
      <option value="1">Include archived</option>
    </select>
    <button id="newBtn" class="btn-primary">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
      <span data-en="New Teacher" data-am="አዲስ መምህር">New Teacher</span>
    </button>
  </div>
</div>

<section id="formPanel" class="panel hidden">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 id="formTitle" class="font-display text-lg text-ink">New Teacher</h2>
    <button type="button" id="cancelBtn" class="btn-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
  </header>
  <form id="entityForm" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
    <input type="hidden" name="id" id="f_id" />
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">First name</label>
      <input id="f_first" class="input-field" required />
    </div>
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Last name</label>
      <input id="f_last" class="input-field" required />
    </div>
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Email</label>
      <input id="f_email" type="email" class="input-field" required />
    </div>
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Phone</label>
      <input id="f_phone" class="input-field" />
    </div>
    <div class="md:col-span-2">
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Bio</label>
      <textarea id="f_bio" class="input-field" rows="3"></textarea>
    </div>
    <div class="md:col-span-2 flex items-center gap-3 pt-2">
      <button type="submit" id="saveBtn" class="btn-primary">Save</button>
      <button type="button" id="cancelBtn2" class="btn-ghost">Cancel</button>
      <p id="formMsg" class="text-sm text-error hidden"></p>
      <p id="credMsg" class="text-sm text-olive hidden"></p>
    </div>
  </form>
</section>

<section class="panel">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 class="font-display text-lg text-ink"><span data-en="All teachers" data-am="ሁሉም መምህራን">All teachers</span> · <span id="rowCount" class="text-ink-soft text-sm">—</span></h2>
  </header>
  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr>
          <th data-en="Name" data-am="ስም">Name</th>
          <th data-en="Email" data-am="ኢሜይል">Email</th>
          <th data-en="Phone" data-am="ስልክ">Phone</th>
          <th data-en="Status" data-am="ሁኔታ">Status</th>
          <th class="text-right">&nbsp;</th>
        </tr>
      </thead>
      <tbody id="tbody"><tr><td colspan="5" class="text-center text-ink-soft py-12">Loading…</td></tr></tbody>
    </table>
  </div>
</section>

<script>
  var formPanel = document.getElementById('formPanel');
  var formTitle = document.getElementById('formTitle');
  var msg = document.getElementById('formMsg');
  var credMsg = document.getElementById('credMsg');
  var saveBtn = document.getElementById('saveBtn');
  var allTeachers = [];

  function showForm(t) {
    formPanel.classList.remove('hidden');
    msg.classList.add('hidden'); credMsg.classList.add('hidden');
    document.getElementById('f_id').value    = t ? t.id : '';
    document.getElementById('f_first').value = t ? (t.first_name||'') : '';
    document.getElementById('f_last').value  = t ? (t.last_name||'') : '';
    document.getElementById('f_email').value = t ? (t.email||'') : '';
    document.getElementById('f_phone').value = t ? (t.phone||'') : '';
    document.getElementById('f_bio').value   = t ? (t.bio||'') : '';
    document.getElementById('f_email').readOnly = !!t;
    formTitle.textContent = t ? 'Edit Teacher' : 'New Teacher';
    formPanel.scrollIntoView({ behavior:'smooth', block:'center' });
  }
  function hideForm() { formPanel.classList.add('hidden'); }
  document.getElementById('newBtn').addEventListener('click', function () { showForm(null); });
  document.getElementById('cancelBtn').addEventListener('click', hideForm);
  document.getElementById('cancelBtn2').addEventListener('click', hideForm);

  function escHtml(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];}); }

  function renderTable() {
    var includeArchived = document.getElementById('archivedFilter').value === '1';
    var q = (document.getElementById('searchInput').value || '').trim().toLowerCase();
    var rows = allTeachers.filter(function (u) {
      if (!includeArchived && u.is_archived == 1) return false;
      if (!q) return true;
      var p = u.profile || {};
      return ((p.first_name||'') + ' ' + (p.last_name||'') + ' ' + u.email).toLowerCase().indexOf(q) >= 0;
    });
    document.getElementById('rowCount').textContent = rows.length + ' total';
    var tbody = document.getElementById('tbody');
    if (!rows.length) { tbody.innerHTML = '<tr><td colspan="5" class="text-center text-ink-soft py-16">No teachers yet. Add one with <em>+ New Teacher</em>.</td></tr>'; return; }
    tbody.innerHTML = rows.map(function (u) {
      var p = u.profile || {};
      var name = ((p.first_name||'') + ' ' + (p.last_name||'')).trim() || u.email;
      var initials = ((p.first_name||u.email||'?')[0] || '?').toUpperCase() + ((p.last_name||'')[0] || '').toUpperCase();
      var statusPill = u.is_archived == 1 ? '<span class="pill pill-archived">Archived</span>' : '<span class="pill pill-active">Active</span>';
      return '<tr>' +
        '<td><div class="flex items-center gap-3"><div class="avatar-circle bg-gold/15 text-gold">'+escHtml(initials)+'</div><div><p class="font-medium">'+escHtml(name)+'</p></div></div></td>' +
        '<td class="text-ink-soft">'+escHtml(u.email)+'</td>' +
        '<td class="text-ink-soft">'+escHtml(p.phone||'—')+'</td>' +
        '<td>'+statusPill+'</td>' +
        '<td class="text-right"><div class="inline-flex items-center gap-1">' +
          '<a class="btn-icon" title="View" href="/admin/teacher-detail.php?id='+(p.teacher_id||'')+'"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></a>' +
          '<button class="btn-icon" title="Edit" data-edit="'+u.id+'"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>' +
          (u.is_archived == 1
            ? '<button class="btn-icon" title="Restore" data-restore="'+u.id+'"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M3 7v6h6M21 17a9 9 0 1 1-2.6-13.6L21 7"/></svg></button>'
            : '<button class="btn-icon danger" title="Archive" data-archive="'+u.id+'"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg></button>'
          ) +
        '</div></td>' +
      '</tr>';
    }).join('');
  }

  async function load() {
    try {
      var d = await gs.api('/api/admin/users/index.php?role=teacher&include_archived=1');
      allTeachers = d.data || [];
      renderTable();
    } catch (e) { gs.toast(e.message, 'error'); }
  }

  document.getElementById('searchInput').addEventListener('input', renderTable);
  document.getElementById('archivedFilter').addEventListener('change', renderTable);

  document.getElementById('entityForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    msg.classList.add('hidden'); credMsg.classList.add('hidden');
    saveBtn.disabled = true;
    var id = document.getElementById('f_id').value;
    var body = {
      role: 'teacher',
      first_name: document.getElementById('f_first').value.trim(),
      last_name:  document.getElementById('f_last').value.trim(),
      email:      document.getElementById('f_email').value.trim(),
      phone:      document.getElementById('f_phone').value.trim(),
      bio:        document.getElementById('f_bio').value,
    };
    try {
      var res;
      if (id) {
        body.id = parseInt(id, 10);
        res = await gs.api('/api/admin/users/index.php', { method: 'PUT', body: JSON.stringify(body) });
      } else {
        res = await gs.api('/api/admin/users/index.php', { method: 'POST', body: JSON.stringify(body) });
        if (res.generated_password) {
          credMsg.textContent = 'Created. Generated password: ' + res.generated_password + ' — share it now (it will not be shown again).';
          credMsg.classList.remove('hidden');
        }
      }
      gs.toast(id ? 'Teacher updated' : 'Teacher created', 'success');
      await load();
      if (id || !res.generated_password) hideForm();
    } catch (err) { msg.textContent = err.message; msg.classList.remove('hidden'); }
    finally { saveBtn.disabled = false; }
  });

  document.addEventListener('click', async function (e) {
    var t = e.target.closest('[data-edit], [data-archive], [data-restore]');
    if (!t) return;
    var id = parseInt(t.dataset.edit || t.dataset.archive || t.dataset.restore, 10);
    if (t.dataset.edit) {
      var u = allTeachers.find(function (x) { return x.id === id; });
      if (u) showForm(Object.assign({ id: u.id, email: u.email }, u.profile || {}));
    } else if (t.dataset.archive) {
      if (!await gs.confirm('Archive this teacher?')) return;
      try { await gs.api('/api/admin/users/index.php', { method:'DELETE', body: JSON.stringify({ id: id }) }); gs.toast('Archived','success'); await load(); }
      catch (err) { gs.toast(err.message,'error'); }
    } else if (t.dataset.restore) {
      try { await gs.api('/api/admin/users/index.php', { method:'PUT', body: JSON.stringify({ id: id, role: 'teacher', is_archived: 0 }) }); gs.toast('Restored','success'); await load(); }
      catch (err) { gs.toast(err.message,'error'); }
    }
  });

  load();
  <?php if ($auto_open_new): ?>showForm(null);<?php endif; ?>
</script>

<?php require __DIR__ . '/_partials/page-shell-end.php'; ?>
