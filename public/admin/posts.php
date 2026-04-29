<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'admin') { header('Location: /'); exit; }

$page_title    = 'Posts';
$page_title_am = 'ጽሑፎች';
$page_eyebrow    = 'Resources';
$page_eyebrow_am = 'መርጃዎች';
$active_nav = 'posts';
require __DIR__ . '/_partials/page-shell.php';
?>

<div class="flex items-center justify-between flex-wrap gap-3">
  <div>
    <p class="text-sm text-ink-soft max-w-xl">Posts appear on the public blog at <code class="text-primary">/blog</code> for everyone to read. Attach images or PDFs as needed.</p>
  </div>
  <button id="newBtn" class="btn-primary">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
    <span>New Post</span>
  </button>
</div>

<section id="formPanel" class="panel hidden">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 id="formTitle" class="font-display text-lg text-ink">New Post</h2>
    <button type="button" id="cancelBtn" class="btn-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
  </header>
  <form id="entityForm" class="p-6 space-y-4">
    <input type="hidden" id="f_id" />
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Title</label>
      <input id="f_title" class="input-field" required />
    </div>
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Content (markdown supported)</label>
      <textarea id="f_content" class="input-field font-mono" rows="14" required></textarea>
      <p class="text-xs text-outline mt-2">Plain text or basic markdown. Line breaks are preserved.</p>
    </div>
    <div id="attachmentsSection" class="hidden border-t border-outline-soft/40 pt-4 space-y-3">
      <p class="text-[11px] font-semibold uppercase tracking-widestest text-ink-soft">Attachments</p>
      <ul id="attachmentList" class="space-y-2 text-sm"></ul>
      <div class="flex items-center gap-3">
        <input id="f_file" type="file" accept="image/*,application/pdf,text/plain" class="input-field !w-auto" />
        <button type="button" id="uploadBtn" class="btn-ghost">Upload</button>
      </div>
    </div>
    <div class="flex items-center gap-3 pt-2">
      <button type="submit" class="btn-primary">Save</button>
      <button type="button" id="cancelBtn2" class="btn-ghost">Cancel</button>
      <p id="formMsg" class="text-sm text-error hidden"></p>
    </div>
  </form>
</section>

<section class="panel">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 class="font-display text-lg text-ink">Posts · <span id="rowCount" class="text-ink-soft text-sm">—</span></h2>
    <input id="searchInput" type="search" placeholder="Search…" class="input-field !w-64 !py-2" />
  </header>
  <ul id="listWrap" class="divide-y divide-outline-soft/30">
    <li class="px-6 py-12 text-center text-ink-soft text-sm">Loading…</li>
  </ul>
</section>

<script>
  var formPanel = document.getElementById('formPanel');
  var formTitle = document.getElementById('formTitle');
  var msg = document.getElementById('formMsg');
  var all = [];
  var current = null;

  function escHtml(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];}); }
  function fmtBytes(n) { if (n < 1024) return n+' B'; if (n < 1024*1024) return (n/1024).toFixed(1)+' KB'; return (n/1024/1024).toFixed(1)+' MB'; }

  function renderAttachments() {
    var ul = document.getElementById('attachmentList');
    var atts = (current && current.attachments) || [];
    if (!atts.length) { ul.innerHTML = '<li class="text-outline">No attachments yet.</li>'; return; }
    ul.innerHTML = atts.map(function (a) {
      return '<li class="flex items-center justify-between gap-3 bg-surface-low rounded p-2">' +
        '<div><a class="text-primary hover:underline" target="_blank" href="'+escHtml(a.file_path)+'">'+escHtml(a.original_name)+'</a>' +
        '<p class="text-xs text-outline">'+escHtml(a.mime_type)+' · '+fmtBytes(a.file_size)+'</p></div>' +
        '</li>';
    }).join('');
  }

  function showForm(item) {
    formPanel.classList.remove('hidden');
    msg.classList.add('hidden');
    current = item;
    document.getElementById('f_id').value = item ? item.id : '';
    document.getElementById('f_title').value = item ? item.title : '';
    document.getElementById('f_content').value = item ? item.content : '';
    formTitle.textContent = item ? 'Edit Post' : 'New Post';
    document.getElementById('attachmentsSection').classList.toggle('hidden', !item);
    if (item) renderAttachments();
    formPanel.scrollIntoView({ behavior:'smooth', block:'center' });
  }
  function hideForm() { formPanel.classList.add('hidden'); current = null; }
  document.getElementById('newBtn').addEventListener('click', function () { showForm(null); });
  document.getElementById('cancelBtn').addEventListener('click', hideForm);
  document.getElementById('cancelBtn2').addEventListener('click', hideForm);

  function render() {
    var q = (document.getElementById('searchInput').value || '').toLowerCase().trim();
    var rows = q ? all.filter(function (p) { return (p.title + ' ' + p.content).toLowerCase().indexOf(q) >= 0; }) : all;
    document.getElementById('rowCount').textContent = rows.length + ' total';
    var ul = document.getElementById('listWrap');
    if (!rows.length) { ul.innerHTML = '<li class="px-6 py-16 text-center text-ink-soft text-sm">No posts.</li>'; return; }
    ul.innerHTML = rows.map(function (p) {
      var snippet = (p.content || '').replace(/\s+/g,' ').slice(0, 200);
      return '<li class="px-6 py-5 hover:bg-surface-low/50 transition-colors">' +
        '<div class="flex items-start justify-between gap-3 mb-1">' +
          '<h3 class="font-display text-lg text-ink leading-tight">'+escHtml(p.title)+'</h3>' +
          '<div class="flex items-center gap-1 flex-shrink-0">' +
            '<button class="btn-icon" title="Edit" data-edit="'+p.id+'"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>' +
            '<button class="btn-icon danger" title="Archive" data-archive="'+p.id+'"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/></svg></button>' +
          '</div>' +
        '</div>' +
        '<p class="text-xs text-outline mb-2">'+escHtml(p.author_email||'')+' · '+escHtml(p.created_at)+(p.attachments && p.attachments.length ? ' · '+p.attachments.length+' attachment(s)' : '')+'</p>' +
        '<p class="text-sm text-ink-soft">'+escHtml(snippet)+(p.content && p.content.length > 200 ? '…' : '')+'</p>' +
      '</li>';
    }).join('');
  }

  async function load() {
    try { var d = await gs.api('/api/admin/posts/index.php'); all = d.data || []; render(); }
    catch (e) { gs.toast(e.message,'error'); }
  }

  document.getElementById('searchInput').addEventListener('input', render);

  document.getElementById('entityForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    msg.classList.add('hidden');
    var id = document.getElementById('f_id').value;
    var body = {
      title: document.getElementById('f_title').value.trim(),
      content: document.getElementById('f_content').value,
    };
    try {
      var res;
      if (id) { body.id = parseInt(id,10); await gs.api('/api/admin/posts/index.php', { method:'PUT', body: JSON.stringify(body) }); }
      else    { res = await gs.api('/api/admin/posts/index.php', { method:'POST', body: JSON.stringify(body) }); body.id = res.id; }
      gs.toast(id ? 'Updated' : 'Created','success');
      // After create, switch to edit mode so user can attach files
      if (!id && res && res.id) {
        document.getElementById('f_id').value = res.id;
        formTitle.textContent = 'Edit Post';
        current = { id: res.id, title: body.title, content: body.content, attachments: [] };
        document.getElementById('attachmentsSection').classList.remove('hidden');
        renderAttachments();
      } else {
        hideForm();
      }
      load();
    } catch (err) { msg.textContent = err.message; msg.classList.remove('hidden'); }
  });

  // Upload
  document.getElementById('uploadBtn').addEventListener('click', async function () {
    var f = document.getElementById('f_file').files[0];
    var pid = parseInt(document.getElementById('f_id').value, 10);
    if (!f || !pid) { gs.toast('Save the post first, then choose a file', 'error'); return; }
    var fd = new FormData();
    fd.append('post_id', pid);
    fd.append('file', f);
    try {
      var token = await gs.ensureCsrf();
      var res = await fetch('/api/admin/posts/upload.php', { method:'POST', headers: { 'X-CSRF-Token': token }, body: fd });
      var data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Upload failed');
      gs.toast('Uploaded','success');
      if (current) { current.attachments = (current.attachments || []).concat([data.attachment]); renderAttachments(); }
      document.getElementById('f_file').value = '';
      load();
    } catch (err) { gs.toast(err.message,'error'); }
  });

  document.addEventListener('click', async function (e) {
    var t = e.target.closest('[data-edit], [data-archive]');
    if (!t) return;
    var id = parseInt(t.dataset.edit || t.dataset.archive, 10);
    if (t.dataset.edit) { var item = all.find(function (x) { return x.id === id; }); if (item) showForm(item); }
    else {
      if (!await gs.confirm('Archive this post?')) return;
      try { await gs.api('/api/admin/posts/index.php', { method:'DELETE', body: JSON.stringify({ id: id })}); gs.toast('Archived','success'); load(); }
      catch (err) { gs.toast(err.message,'error'); }
    }
  });

  load();
</script>

<?php require __DIR__ . '/_partials/page-shell-end.php'; ?>
