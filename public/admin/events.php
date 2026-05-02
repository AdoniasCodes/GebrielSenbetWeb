<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'admin') { header('Location: /'); exit; }

$page_title    = 'Events';
$page_title_am = 'ዝግጅቶች';
$page_eyebrow    = 'Resources';
$page_eyebrow_am = 'መርጃዎች';
$active_nav = 'events';
require __DIR__ . '/_partials/page-shell.php';
?>

<div class="flex items-center justify-between flex-wrap gap-3">
  <p class="text-sm text-ink-soft max-w-xl">Schedule services, parent meetings, retreats, and exams. Recurring events can repeat weekly, monthly, or every-X-months.</p>
  <button id="newBtn" class="btn-primary">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
    <span>New Event</span>
  </button>
</div>

<section id="formPanel" class="panel hidden">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 id="formTitle" class="font-display text-lg text-ink">New Event</h2>
    <button type="button" id="cancelBtn" class="btn-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
  </header>
  <form id="entityForm" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
    <input type="hidden" id="f_id" />
    <div class="md:col-span-2"><label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Title</label><input id="f_title" class="input-field" required /></div>
    <div class="md:col-span-2"><label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Description</label><textarea id="f_desc" class="input-field" rows="3"></textarea></div>
    <div><label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Start</label><input id="f_start" type="datetime-local" class="input-field" required /></div>
    <div><label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">End (optional)</label><input id="f_end" type="datetime-local" class="input-field" /></div>

    <div class="md:col-span-2 mt-2">
      <label class="inline-flex items-center gap-2"><input type="checkbox" id="f_recurring" class="w-4 h-4" /> <span class="text-sm">Repeats</span></label>
    </div>
    <div id="recurrenceFields" class="md:col-span-2 hidden grid grid-cols-1 md:grid-cols-4 gap-4 bg-surface-low rounded p-4">
      <div>
        <label class="block text-[10px] uppercase tracking-widestest text-ink-soft mb-2">Frequency</label>
        <select id="f_freq" class="input-field"><option value="weekly">Weekly</option><option value="monthly">Monthly</option><option value="every_x_months">Every X months</option></select>
      </div>
      <div>
        <label class="block text-[10px] uppercase tracking-widestest text-ink-soft mb-2">Interval</label>
        <input id="f_interval" type="number" min="1" value="1" class="input-field" />
      </div>
      <div>
        <label class="block text-[10px] uppercase tracking-widestest text-ink-soft mb-2">By day (optional)</label>
        <input id="f_byday" class="input-field" placeholder="e.g. SU, MO" />
      </div>
      <div>
        <label class="block text-[10px] uppercase tracking-widestest text-ink-soft mb-2">Until (optional)</label>
        <input id="f_until" type="date" class="input-field" />
      </div>
    </div>

    <div class="md:col-span-2 flex items-center gap-3">
      <button type="submit" class="btn-primary">Save</button>
      <button type="button" id="cancelBtn2" class="btn-ghost">Cancel</button>
      <p id="formMsg" class="text-sm text-error hidden"></p>
    </div>
  </form>
</section>

<section class="panel">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between flex-wrap gap-3">
    <h2 class="font-display text-lg text-ink">Events · <span id="rowCount" class="text-ink-soft text-sm">—</span></h2>
    <div class="flex items-center gap-4">
      <label class="text-xs text-ink-soft inline-flex items-center gap-2"><input id="upcomingOnly" type="checkbox" class="w-4 h-4" /> <span>Upcoming only</span></label>
      <label class="text-xs text-ink-soft inline-flex items-center gap-2"><input id="includeArchived" type="checkbox" class="w-4 h-4" /> <span>Include archived</span></label>
    </div>
  </header>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>When</th><th>Title</th><th>Repeats</th><th>Status</th><th class="text-right">&nbsp;</th></tr></thead>
      <tbody id="tbody"><tr><td colspan="5" class="text-center text-ink-soft py-12">Loading…</td></tr></tbody>
    </table>
  </div>
</section>

<script>
  var formPanel = document.getElementById('formPanel');
  var formTitle = document.getElementById('formTitle');
  var msg = document.getElementById('formMsg');
  var all = [];

  function escHtml(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];}); }
  function fmtDate(s) { return s ? gs.fmtDate(s, 'datetime') : '—'; }

  function showRecurrenceFields() {
    var on = document.getElementById('f_recurring').checked;
    document.getElementById('recurrenceFields').classList.toggle('hidden', !on);
  }
  document.getElementById('f_recurring').addEventListener('change', showRecurrenceFields);

  function showForm(item) {
    formPanel.classList.remove('hidden');
    msg.classList.add('hidden');
    document.getElementById('f_id').value = item ? item.id : '';
    document.getElementById('f_title').value = item ? item.title : '';
    document.getElementById('f_desc').value = item && item.description ? item.description : '';
    document.getElementById('f_start').value = item && item.start_datetime ? item.start_datetime.replace(' ','T').slice(0,16) : '';
    document.getElementById('f_end').value = item && item.end_datetime ? item.end_datetime.replace(' ','T').slice(0,16) : '';
    var recurring = item && item.is_recurring == 1;
    document.getElementById('f_recurring').checked = !!recurring;
    document.getElementById('f_freq').value = item && item.freq ? item.freq : 'weekly';
    document.getElementById('f_interval').value = item && item.interval_num ? item.interval_num : 1;
    document.getElementById('f_byday').value = item && item.by_day ? item.by_day : '';
    document.getElementById('f_until').value = item && item.until_date ? item.until_date : '';
    showRecurrenceFields();
    formTitle.textContent = item ? 'Edit Event' : 'New Event';
    formPanel.scrollIntoView({ behavior:'smooth', block:'center' });
  }
  function hideForm() { formPanel.classList.add('hidden'); }
  document.getElementById('newBtn').addEventListener('click', function () { showForm(null); });
  document.getElementById('cancelBtn').addEventListener('click', hideForm);
  document.getElementById('cancelBtn2').addEventListener('click', hideForm);

  function render() {
    document.getElementById('rowCount').textContent = all.length + ' total';
    var tbody = document.getElementById('tbody');
    if (!all.length) { tbody.innerHTML = '<tr><td colspan="5" class="text-center text-ink-soft py-16">No events.</td></tr>'; return; }
    tbody.innerHTML = all.map(function (e) {
      var pill = e.is_archived == 1 ? '<span class="pill pill-archived">Archived</span>' : '<span class="pill pill-active">Active</span>';
      var rec = e.is_recurring == 1 ? ('<span class="pill pill-draft">'+escHtml(e.freq||'recurring')+'</span>') : '<span class="text-outline text-xs">—</span>';
      return '<tr>' +
        '<td><p>'+escHtml(fmtDate(e.start_datetime))+'</p>' + (e.end_datetime?'<p class="text-xs text-outline">→ '+escHtml(fmtDate(e.end_datetime))+'</p>':'') + '</td>' +
        '<td><p class="font-medium">'+escHtml(e.title)+'</p>'+(e.description?'<p class="text-xs text-ink-soft mt-0.5">'+escHtml(e.description)+'</p>':'')+'</td>' +
        '<td>'+rec+'</td>' +
        '<td>'+pill+'</td>' +
        '<td class="text-right"><div class="inline-flex items-center gap-1">' +
          '<button class="btn-icon" title="Edit" data-edit="'+e.id+'"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>' +
          (e.is_archived == 1 ? '' :
            '<button class="btn-icon danger" title="Archive" data-archive="'+e.id+'"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/></svg></button>') +
        '</div></td>' +
      '</tr>';
    }).join('');
  }

  async function load() {
    var qs = [];
    if (document.getElementById('upcomingOnly').checked) qs.push('upcoming=1');
    if (document.getElementById('includeArchived').checked) qs.push('include_archived=1');
    var url = '/api/admin/events/index.php' + (qs.length ? '?' + qs.join('&') : '');
    try { var d = await gs.api(url); all = d.data || []; render(); }
    catch (e) { gs.toast(e.message,'error'); }
  }
  document.getElementById('upcomingOnly').addEventListener('change', load);
  document.getElementById('includeArchived').addEventListener('change', load);
  document.addEventListener('gs:lang-change', render);

  document.getElementById('entityForm').addEventListener('submit', async function (ev) {
    ev.preventDefault();
    msg.classList.add('hidden');
    var id = document.getElementById('f_id').value;
    var body = {
      title: document.getElementById('f_title').value.trim(),
      description: document.getElementById('f_desc').value || null,
      start_datetime: document.getElementById('f_start').value.replace('T',' '),
      end_datetime: document.getElementById('f_end').value ? document.getElementById('f_end').value.replace('T',' ') : null,
      is_recurring: document.getElementById('f_recurring').checked ? 1 : 0,
    };
    if (body.is_recurring) {
      body.recurrence = {
        freq: document.getElementById('f_freq').value,
        interval_num: parseInt(document.getElementById('f_interval').value || '1', 10),
        by_day: document.getElementById('f_byday').value || null,
        until_date: document.getElementById('f_until').value || null,
      };
    }
    try {
      if (id) { body.id = parseInt(id,10); await gs.api('/api/admin/events/index.php', { method:'PUT', body: JSON.stringify(body) }); }
      else    await gs.api('/api/admin/events/index.php', { method:'POST', body: JSON.stringify(body) });
      gs.toast(id ? 'Updated' : 'Created','success'); hideForm(); load();
    } catch (err) { msg.textContent = err.message; msg.classList.remove('hidden'); }
  });

  document.addEventListener('click', async function (e) {
    var t = e.target.closest('[data-edit], [data-archive]');
    if (!t) return;
    var id = parseInt(t.dataset.edit || t.dataset.archive, 10);
    if (t.dataset.edit) { var item = all.find(function (x) { return x.id === id; }); if (item) showForm(item); }
    else {
      if (!await gs.confirm('Archive this event?')) return;
      try { await gs.api('/api/admin/events/index.php', { method:'DELETE', body: JSON.stringify({ id: id })}); gs.toast('Archived','success'); load(); }
      catch (err) { gs.toast(err.message,'error'); }
    }
  });

  load();
</script>

<?php require __DIR__ . '/_partials/page-shell-end.php'; ?>
