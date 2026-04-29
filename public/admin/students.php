<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'admin') { header('Location: /'); exit; }

$page_title    = 'Students';
$page_title_am = 'ተማሪዎች';
$page_eyebrow    = 'Registry';
$page_eyebrow_am = 'መዝገብ';
$active_nav = 'students';
require __DIR__ . '/_partials/page-shell.php';

$auto_open_new = isset($_GET['new']) && $_GET['new'] === '1';
?>

<!-- Toolbar -->
<div class="flex flex-wrap items-center gap-3 justify-between">
  <div class="flex items-center gap-2 flex-1 min-w-[200px] max-w-md">
    <div class="relative flex-1">
      <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-outline" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
      <input id="searchInput" type="search" placeholder="Search by name…" class="input-field !pl-10" />
    </div>
  </div>
  <div class="flex items-center gap-2">
    <select id="archivedFilter" class="input-field !w-auto !py-2.5">
      <option value="0">Active only</option>
      <option value="1">Include archived</option>
    </select>
    <a href="/api/admin/export/students.php" class="btn-ghost">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
      <span>Export CSV</span>
    </a>
    <button id="importBtn" class="btn-ghost">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>
      <span>Import CSV</span>
    </button>
    <input id="importFile" type="file" accept=".csv" class="hidden" />
    <button id="newBtn" class="btn-primary">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
      <span data-en="New Student" data-am="አዲስ ተማሪ">New Student</span>
    </button>
  </div>
</div>

<!-- New/Edit form panel -->
<section id="formPanel" class="panel hidden">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 id="formTitle" class="font-display text-lg text-ink">New Student</h2>
    <button type="button" id="cancelBtn" class="btn-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
  </header>
  <form id="studentForm" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
    <input type="hidden" name="id" id="f_id" />
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2" data-en="First name" data-am="መጠሪያ ስም">First name</label>
      <input name="first_name" id="f_first" class="input-field" required />
    </div>
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2" data-en="Last name" data-am="የአባት ስም">Last name</label>
      <input name="last_name" id="f_last" class="input-field" required />
    </div>
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2" data-en="Email" data-am="ኢሜይል">Email</label>
      <input name="email" id="f_email" type="email" class="input-field" required />
    </div>
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2" data-en="Phone" data-am="ስልክ">Phone</label>
      <input name="phone" id="f_phone" class="input-field" />
    </div>
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2" data-en="Guardian name" data-am="የአሳዳጊ ስም">Guardian name</label>
      <input name="guardian_name" id="f_guardian" class="input-field" />
    </div>
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2" data-en="Date of birth" data-am="የተወለዱበት ቀን">Date of birth</label>
      <input name="date_of_birth" id="f_dob" type="date" class="input-field" />
    </div>
    <div class="md:col-span-2">
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2" data-en="Address" data-am="አድራሻ">Address</label>
      <input name="address" id="f_address" class="input-field" />
    </div>
    <div class="md:col-span-2 flex items-center gap-3 pt-2">
      <button type="submit" id="saveBtn" class="btn-primary"><span data-en="Save" data-am="አስቀምጥ">Save</span></button>
      <button type="button" id="cancelBtn2" class="btn-ghost"><span data-en="Cancel" data-am="ሰርዝ">Cancel</span></button>
      <p id="formMsg" class="text-sm text-error hidden"></p>
      <p id="credMsg" class="text-sm text-olive hidden"></p>
    </div>
  </form>
</section>

<!-- Data table -->
<section class="panel">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 class="font-display text-lg text-ink"><span data-en="All students" data-am="ሁሉም ተማሪዎች">All students</span> · <span id="rowCount" class="text-ink-soft text-sm">—</span></h2>
  </header>
  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr>
          <th data-en="Name" data-am="ስም">Name</th>
          <th data-en="Email" data-am="ኢሜይል">Email</th>
          <th data-en="Class" data-am="ክፍል">Class</th>
          <th data-en="Phone" data-am="ስልክ">Phone</th>
          <th data-en="Status" data-am="ሁኔታ">Status</th>
          <th class="text-right">&nbsp;</th>
        </tr>
      </thead>
      <tbody id="tbody">
        <tr><td colspan="6" class="text-center text-ink-soft py-12">Loading…</td></tr>
      </tbody>
    </table>
  </div>
</section>

<script>
  var formPanel = document.getElementById('formPanel');
  var formTitle = document.getElementById('formTitle');
  var msg = document.getElementById('formMsg');
  var credMsg = document.getElementById('credMsg');
  var saveBtn = document.getElementById('saveBtn');
  var allStudents = [];
  var allAssignments = [];

  function showForm(student) {
    formPanel.classList.remove('hidden');
    msg.classList.add('hidden'); credMsg.classList.add('hidden');
    document.getElementById('f_id').value      = student ? student.id : '';
    document.getElementById('f_first').value   = student ? (student.first_name || '') : '';
    document.getElementById('f_last').value    = student ? (student.last_name || '') : '';
    document.getElementById('f_email').value   = student ? (student.email || '') : '';
    document.getElementById('f_phone').value   = student ? (student.phone || '') : '';
    document.getElementById('f_guardian').value= student ? (student.guardian_name || '') : '';
    document.getElementById('f_dob').value     = student ? (student.date_of_birth || '') : '';
    document.getElementById('f_address').value = student ? (student.address || '') : '';
    formTitle.textContent = student ? 'Edit Student' : 'New Student';
    document.getElementById('f_email').readOnly = !!student;
    formPanel.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
  function hideForm() { formPanel.classList.add('hidden'); }

  document.getElementById('newBtn').addEventListener('click', function () { showForm(null); });
  document.getElementById('cancelBtn').addEventListener('click', hideForm);
  document.getElementById('cancelBtn2').addEventListener('click', hideForm);

  function escHtml(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];}); }

  function renderTable() {
    var includeArchived = document.getElementById('archivedFilter').value === '1';
    var q = (document.getElementById('searchInput').value || '').trim().toLowerCase();
    var rows = allStudents.filter(function (u) {
      if (!includeArchived && u.is_archived == 1) return false;
      if (!q) return true;
      var n = ((u.profile && u.profile.first_name) || '') + ' ' + ((u.profile && u.profile.last_name) || '');
      return (u.email + ' ' + n).toLowerCase().indexOf(q) >= 0;
    });
    document.getElementById('rowCount').textContent = rows.length + ' total';
    var tbody = document.getElementById('tbody');
    if (rows.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-center text-ink-soft py-16">No students yet. Add the first one with <em>+ New Student</em>.</td></tr>';
      return;
    }
    tbody.innerHTML = rows.map(function (u) {
      var p = u.profile || {};
      var name = ((p.first_name||'') + ' ' + (p.last_name||'')).trim() || u.email;
      var initials = ((p.first_name||u.email||'?')[0] || '?').toUpperCase() + ((p.last_name||'')[0] || '').toUpperCase();
      var asn = window._asnByStudent && p.student_id ? window._asnByStudent[p.student_id] : null;
      var classCell = (asn ? escHtml(asn.class_name) + ' <span class="text-xs text-outline">(' + escHtml(asn.level_name) + ')</span>' : '<span class="text-outline text-xs">Unassigned</span>');
      var statusPill = u.is_archived == 1 ? '<span class="pill pill-archived">Archived</span>' : '<span class="pill pill-active">Active</span>';
      return '<tr>' +
        '<td><div class="flex items-center gap-3"><div class="avatar-circle bg-primary/10 text-primary">'+escHtml(initials)+'</div><div><p class="font-medium">'+escHtml(name)+'</p></div></div></td>' +
        '<td class="text-ink-soft">'+escHtml(u.email)+'</td>' +
        '<td>'+classCell+'</td>' +
        '<td class="text-ink-soft">'+escHtml(p.phone || '—')+'</td>' +
        '<td>'+statusPill+'</td>' +
        '<td class="text-right"><div class="inline-flex items-center gap-1">' +
          '<a class="btn-icon" title="View" href="/admin/student-detail.php?id='+(p.student_id||'')+'"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></a>' +
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
      var d = await gs.api('/api/admin/users/index.php?role=student&include_archived=1');
      allStudents = d.data || [];
      // Index assignments by student_id so the table can show current class
      var a = await gs.api('/api/admin/student-assignments/index.php');
      allAssignments = a.data || [];
      window._asnByStudent = {};
      allAssignments.forEach(function (asn) { window._asnByStudent[asn.student_id] = asn; });
      renderTable();
    } catch (e) {
      gs.toast(e.message, 'error');
    }
  }

  document.getElementById('searchInput').addEventListener('input', renderTable);
  document.getElementById('archivedFilter').addEventListener('change', renderTable);

  document.getElementById('studentForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    msg.classList.add('hidden'); credMsg.classList.add('hidden');
    saveBtn.disabled = true;
    var id = document.getElementById('f_id').value;
    var body = {
      role: 'student',
      first_name: document.getElementById('f_first').value.trim(),
      last_name:  document.getElementById('f_last').value.trim(),
      email:      document.getElementById('f_email').value.trim(),
      phone:      document.getElementById('f_phone').value.trim(),
      guardian_name: document.getElementById('f_guardian').value.trim(),
      date_of_birth: document.getElementById('f_dob').value,
      address:    document.getElementById('f_address').value.trim(),
    };
    try {
      var res;
      if (id) {
        body.id = parseInt(id, 10);
        res = await gs.api('/api/admin/users/index.php', { method: 'PUT', body: JSON.stringify(body) });
      } else {
        res = await gs.api('/api/admin/users/index.php', { method: 'POST', body: JSON.stringify(body) });
        if (res.generated_password) {
          credMsg.textContent = 'Created. Generated password: ' + res.generated_password + ' — share this with the student now (it will not be shown again).';
          credMsg.classList.remove('hidden');
        }
      }
      gs.toast(id ? 'Student updated' : 'Student created', 'success');
      await load();
      if (id || !res.generated_password) hideForm();
    } catch (err) {
      msg.textContent = err.message; msg.classList.remove('hidden');
    } finally { saveBtn.disabled = false; }
  });

  document.addEventListener('click', async function (e) {
    var t = e.target.closest('[data-edit], [data-archive], [data-restore]');
    if (!t) return;
    var id = parseInt(t.dataset.edit || t.dataset.archive || t.dataset.restore, 10);
    if (t.dataset.edit) {
      var u = allStudents.find(function (x) { return x.id === id; });
      if (u) showForm(Object.assign({ id: u.id, email: u.email }, u.profile || {}));
    } else if (t.dataset.archive) {
      if (!await gs.confirm('Archive this student?')) return;
      try { await gs.api('/api/admin/users/index.php', { method: 'DELETE', body: JSON.stringify({ id: id }) }); gs.toast('Archived', 'success'); await load(); }
      catch (err) { gs.toast(err.message, 'error'); }
    } else if (t.dataset.restore) {
      try { await gs.api('/api/admin/users/index.php', { method: 'PUT', body: JSON.stringify({ id: id, role: 'student', is_archived: 0 }) }); gs.toast('Restored', 'success'); await load(); }
      catch (err) { gs.toast(err.message, 'error'); }
    }
  });

  // CSV import
  document.getElementById('importBtn').addEventListener('click', function () { document.getElementById('importFile').click(); });
  document.getElementById('importFile').addEventListener('change', async function (e) {
    var f = e.target.files[0];
    if (!f) return;
    if (!await gs.confirm('Import students from "' + f.name + '"? Required columns: first_name, last_name, email.')) return;
    var fd = new FormData(); fd.append('file', f);
    try {
      var token = await gs.ensureCsrf();
      var res = await fetch('/api/admin/import/students.php', { method:'POST', headers: { 'X-CSRF-Token': token }, body: fd });
      var data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Import failed');
      var msg = 'Imported ' + data.created_count + ', skipped ' + data.skipped_count;
      if (data.error_count) msg += ', errors ' + data.error_count;
      gs.toast(msg, data.error_count ? 'error' : 'success');
      if (data.created && data.created.length) {
        // Show generated passwords once
        var html = data.created.map(function (c) {
          return c.first_name + ' ' + c.last_name + ' <' + c.email + '> · ' + c.password;
        }).join('\n');
        var w = window.open('', '_blank');
        if (w) {
          w.document.title = 'Generated passwords — save these';
          w.document.body.style.cssText = 'font-family:monospace; padding:24px; background:#fcf9f2; color:#1c1c18; white-space:pre-wrap;';
          w.document.body.textContent = 'Generated passwords (save these now — they will not be shown again):\n\n' + html;
        }
      }
      e.target.value = '';
      load();
    } catch (err) { gs.toast(err.message,'error'); }
  });

  load();

  <?php if ($auto_open_new): ?>showForm(null);<?php endif; ?>
</script>

<?php require __DIR__ . '/_partials/page-shell-end.php'; ?>
