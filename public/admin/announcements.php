<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'admin') { header('Location: /'); exit; }

$page_title    = 'Announcements';
$page_title_am = 'ማስታወቂያዎች';
$page_eyebrow    = 'Resources';
$page_eyebrow_am = 'መርጃዎች';
$active_nav = 'announcements';
require __DIR__ . '/_partials/page-shell.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-6">

  <!-- Composer -->
  <section class="panel">
    <header class="px-6 py-5 border-b border-outline-soft/40">
      <h2 class="font-display text-lg text-ink" data-en="Compose announcement" data-am="ማስታወቂያ ጻፍ">Compose announcement</h2>
    </header>
    <form id="composeForm" class="p-6 space-y-4">
      <div>
        <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Title</label>
        <input id="f_title" class="input-field" required maxlength="200" />
      </div>
      <div>
        <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Message</label>
        <textarea id="f_message" class="input-field" rows="6" required></textarea>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Send to</label>
          <select id="f_target" class="input-field">
            <option value="role">A role</option>
            <option value="department">A department</option>
            <option value="class">A class</option>
            <option value="user">A specific person</option>
          </select>
        </div>
        <div id="payloadWrap">
          <!-- Dynamic per target_type -->
        </div>
      </div>

      <label class="inline-flex items-center gap-2 mt-1">
        <input id="f_public" type="checkbox" class="w-4 h-4" />
        <span class="text-sm text-ink-soft" data-en="Also show on the public site" data-am="በይፋዊ ጣቢያ ላይም አሳይ">Also show on the public site</span>
      </label>

      <div class="flex items-center gap-3">
        <button type="submit" class="btn-primary">Send</button>
        <p id="msg" class="text-sm hidden"></p>
      </div>
    </form>
  </section>

  <!-- Sent list -->
  <section class="panel">
    <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
      <h2 class="font-display text-lg text-ink" data-en="Recent" data-am="የቅርብ ጊዜ">Recent</h2>
      <button id="reloadBtn" class="btn-icon" title="Refresh"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg></button>
    </header>
    <ul id="listWrap" class="divide-y divide-outline-soft/30 max-h-[600px] overflow-y-auto">
      <li class="px-6 py-12 text-center text-ink-soft text-sm">Loading…</li>
    </ul>
  </section>
</div>

<script>
  var classes = [], departments = [], users = [];
  function escHtml(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];}); }

  function renderPayloadForm() {
    var t = document.getElementById('f_target').value;
    var w = document.getElementById('payloadWrap');
    if (t === 'role') {
      w.innerHTML = '<label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Role</label>' +
        '<select id="p_role" class="input-field"><option value="student">Students</option><option value="parent">Parents</option><option value="teacher">Teachers</option><option value="staff">Staff / dept heads</option><option value="admin">Admins</option></select>';
    } else if (t === 'department') {
      w.innerHTML = '<label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Department</label>' +
        '<select id="p_dept" class="input-field">' + departments.filter(function(d){return d.is_archived==0;}).map(function(d){
          return '<option value="'+d.id+'">'+escHtml(d.name + (d.name_am ? ' · ' + d.name_am : ''))+'</option>';
        }).join('') + '</select>';
    } else if (t === 'class') {
      w.innerHTML = '<label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Class</label>' +
        '<select id="p_class" class="input-field">' + classes.filter(function(c){return c.is_archived==0;}).map(function(c){
          return '<option value="'+c.id+'">'+escHtml((c.track_name||'') + ' · ' + (c.level_name||'') + ' · ' + c.name + ' (' + c.academic_year + ')')+'</option>';
        }).join('') + '</select>';
    } else {
      w.innerHTML = '<label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Person</label>' +
        '<select id="p_user" class="input-field">' + users.filter(function(u){return u.is_archived==0;}).map(function(u){
          return '<option value="'+u.id+'">'+escHtml(u.email + ' (' + u.role + ')')+'</option>';
        }).join('') + '</select>';
    }
  }

  function buildPayload() {
    var t = document.getElementById('f_target').value;
    if (t === 'role') return { role: document.getElementById('p_role').value };
    if (t === 'department') return { department_id: parseInt(document.getElementById('p_dept').value, 10) };
    if (t === 'class') return { class_id: parseInt(document.getElementById('p_class').value, 10) };
    if (t === 'user') return { user_id: parseInt(document.getElementById('p_user').value, 10) };
    return null;
  }

  function roleLabel(r) { return { student:'Students', parent:'Parents', teacher:'Teachers', staff:'Staff', admin:'Admins' }[r] || (r + 's'); }
  function describeTarget(n) {
    var p = n.target_payload || {};
    if (n.target_type === 'role') return 'All ' + roleLabel(p.role);
    if (n.target_type === 'department') { var d = departments.filter(function(x){return x.id==p.department_id;})[0]; return 'Dept · ' + (d ? d.name : '#' + (p.department_id||'?')); }
    if (n.target_type === 'class') { var c = classes.filter(function(x){return x.id==p.class_id;})[0]; return 'Class · ' + (c ? c.name : '#' + (p.class_id||'?')); }
    if (n.target_type === 'user') { var u = users.filter(function(x){return x.id==p.user_id;})[0]; return 'Person · ' + (u ? u.email : '#' + (p.user_id||'?')); }
    return n.target_type;
  }

  async function loadList() {
    try {
      var d = await gs.api('/api/admin/announcements/index.php');
      var rows = d.data || [];
      var ul = document.getElementById('listWrap');
      if (!rows.length) { ul.innerHTML = '<li class="px-6 py-12 text-center text-ink-soft text-sm">No announcements yet.</li>'; return; }
      ul.innerHTML = rows.map(function (n) {
        var publicPill = n.is_public == 1 ? '<span class="pill pill-active text-[10px] ml-2">Public</span>' : '';
        return '<li class="px-6 py-4">' +
          '<div class="flex items-start justify-between gap-3 mb-1">' +
            '<p class="font-medium leading-tight">'+escHtml(n.title)+publicPill+'</p>' +
            '<button class="btn-icon danger flex-shrink-0" title="Archive" data-archive="'+n.id+'"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/></svg></button>' +
          '</div>' +
          '<p class="text-xs text-outline mb-2">'+escHtml(describeTarget(n))+' · '+escHtml(gs.fmtDate(n.created_at, "datetime"))+'</p>' +
          '<p class="text-sm text-ink-soft whitespace-pre-wrap">'+escHtml(n.message)+'</p>' +
        '</li>';
      }).join('');
    } catch (e) { gs.toast(e.message,'error'); }
  }

  async function init() {
    try {
      var [c, d, u] = await Promise.all([
        gs.api('/api/admin/classes/index.php'),
        gs.api('/api/admin/departments/index.php'),
        gs.api('/api/admin/users/index.php')
      ]);
      classes = c.data || []; departments = d.data || []; users = u.data || [];
      renderPayloadForm();
      loadList();
    } catch (e) { gs.toast(e.message,'error'); }
  }

  document.getElementById('f_target').addEventListener('change', renderPayloadForm);
  document.getElementById('reloadBtn').addEventListener('click', loadList);

  document.getElementById('composeForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    var msg = document.getElementById('msg'); msg.className = 'text-sm hidden';
    var body = {
      title: document.getElementById('f_title').value.trim(),
      message: document.getElementById('f_message').value,
      target_type: document.getElementById('f_target').value,
      target_payload: buildPayload(),
      is_public: document.getElementById('f_public').checked ? 1 : 0,
    };
    try {
      await gs.api('/api/admin/announcements/index.php', { method:'POST', body: JSON.stringify(body) });
      gs.toast('Sent','success');
      document.getElementById('composeForm').reset();
      renderPayloadForm();
      loadList();
    } catch (err) { msg.className = 'text-sm text-error'; msg.textContent = err.message; }
  });

  document.addEventListener('click', async function (e) {
    var t = e.target.closest('[data-archive]');
    if (!t) return;
    var id = parseInt(t.dataset.archive, 10);
    if (!await gs.confirm('Archive this announcement?')) return;
    try { await gs.api('/api/admin/announcements/index.php', { method:'DELETE', body: JSON.stringify({ id: id })}); gs.toast('Archived','success'); loadList(); }
    catch (err) { gs.toast(err.message,'error'); }
  });

  init();
</script>

<?php require __DIR__ . '/_partials/page-shell-end.php'; ?>
