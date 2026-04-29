<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'admin') { header('Location: /'); exit; }

$page_title    = 'Tracks';
$page_title_am = 'ኮርሶች';
$page_eyebrow    = 'Classroom';
$page_eyebrow_am = 'ክፍል';
$active_nav = 'tracks';
require __DIR__ . '/_partials/page-shell.php';
?>

<div class="flex items-center justify-between flex-wrap gap-3">
  <p class="text-sm text-ink-soft max-w-xl" data-en="Education tracks group your levels and classes. Most schools have two: a children's track and a youth/adult track." data-am="የትምህርት ኮርሶች ደረጃዎችን እና ክፍሎችን ያቧድናሉ።">Education tracks group your levels and classes. Most schools have two: a children's track and a youth/adult track.</p>
  <button id="newBtn" class="btn-primary">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
    <span data-en="New Track" data-am="አዲስ ኮርስ">New Track</span>
  </button>
</div>

<section id="formPanel" class="panel hidden">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 id="formTitle" class="font-display text-lg text-ink">New Track</h2>
    <button type="button" id="cancelBtn" class="btn-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
  </header>
  <form id="entityForm" class="p-6 grid grid-cols-1 gap-4">
    <input type="hidden" id="f_id" />
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Track name</label>
      <input id="f_name" class="input-field" required placeholder="e.g. Children's Track" />
    </div>
    <div class="flex items-center gap-3">
      <button type="submit" class="btn-primary">Save</button>
      <button type="button" id="cancelBtn2" class="btn-ghost">Cancel</button>
      <p id="formMsg" class="text-sm text-error hidden"></p>
    </div>
  </form>
</section>

<section class="panel">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 class="font-display text-lg text-ink">All tracks · <span id="rowCount" class="text-ink-soft text-sm">—</span></h2>
  </header>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Name</th><th>Status</th><th class="text-right">&nbsp;</th></tr></thead>
      <tbody id="tbody"><tr><td colspan="3" class="text-center text-ink-soft py-12">Loading…</td></tr></tbody>
    </table>
  </div>
</section>

<script>
  var formPanel = document.getElementById('formPanel');
  var formTitle = document.getElementById('formTitle');
  var msg = document.getElementById('formMsg');
  var all = [];

  function showForm(item) {
    formPanel.classList.remove('hidden');
    msg.classList.add('hidden');
    document.getElementById('f_id').value = item ? item.id : '';
    document.getElementById('f_name').value = item ? item.name : '';
    formTitle.textContent = item ? 'Edit Track' : 'New Track';
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
    if (!all.length) { tbody.innerHTML = '<tr><td colspan="3" class="text-center text-ink-soft py-16">No tracks yet.</td></tr>'; return; }
    tbody.innerHTML = all.map(function (t) {
      var pill = t.is_archived == 1 ? '<span class="pill pill-archived">Archived</span>' : '<span class="pill pill-active">Active</span>';
      return '<tr>' +
        '<td class="font-medium">'+escHtml(t.name)+'</td>' +
        '<td>'+pill+'</td>' +
        '<td class="text-right"><div class="inline-flex items-center gap-1">' +
          '<button class="btn-icon" title="Edit" data-edit="'+t.id+'"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>' +
          (t.is_archived == 1 ? '' :
            '<button class="btn-icon danger" title="Archive" data-archive="'+t.id+'"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/></svg></button>') +
        '</div></td>' +
      '</tr>';
    }).join('');
  }

  async function load() {
    try { var d = await gs.api('/api/admin/tracks/index.php'); all = d.data || []; render(); }
    catch (e) { gs.toast(e.message, 'error'); }
  }

  document.getElementById('entityForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    msg.classList.add('hidden');
    var id = document.getElementById('f_id').value;
    var name = document.getElementById('f_name').value.trim();
    if (!name) return;
    try {
      if (id) await gs.api('/api/admin/tracks/index.php', { method:'PUT', body: JSON.stringify({ id: parseInt(id,10), name: name })});
      else    await gs.api('/api/admin/tracks/index.php', { method:'POST', body: JSON.stringify({ name: name })});
      gs.toast(id ? 'Updated' : 'Created', 'success'); hideForm(); load();
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
      if (!await gs.confirm('Archive this track?')) return;
      try { await gs.api('/api/admin/tracks/index.php', { method:'DELETE', body: JSON.stringify({ id: id })}); gs.toast('Archived','success'); load(); }
      catch (err) { gs.toast(err.message,'error'); }
    }
  });

  load();
</script>

<?php require __DIR__ . '/_partials/page-shell-end.php'; ?>
