<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'admin') { header('Location: /'); exit; }

$page_title    = 'Payments';
$page_title_am = 'ክፍያዎች';
$page_eyebrow    = 'Records';
$page_eyebrow_am = 'መዝገቦች';
$active_nav = 'payments';
require __DIR__ . '/_partials/page-shell.php';
?>

<!-- Filter + bulk-generate -->
<section class="panel">
  <div class="px-6 py-5 grid grid-cols-1 md:grid-cols-[1fr_1fr_1fr_auto_auto] gap-3 items-end">
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Term</label>
      <select id="filterTerm" class="input-field"><option value="">All terms</option></select>
    </div>
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Class</label>
      <select id="filterClass" class="input-field"><option value="">All classes</option></select>
    </div>
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Status</label>
      <select id="filterStatus" class="input-field">
        <option value="">All</option>
        <option value="paid">Paid</option>
        <option value="partial">Partial</option>
        <option value="unpaid">Unpaid</option>
      </select>
    </div>
    <button id="reloadBtn" class="btn-ghost">Refresh</button>
    <button id="generateBtn" class="btn-primary">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
      <span>Generate for term</span>
    </button>
  </div>
</section>

<!-- Stats summary -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
  <div class="stat-card border-t-olive">
    <p class="text-[10px] font-semibold uppercase tracking-widestest text-ink-soft mb-3">Expected</p>
    <p class="font-display text-3xl text-ink leading-none">ETB <span id="totExpected">—</span></p>
  </div>
  <div class="stat-card border-t-gold-soft">
    <p class="text-[10px] font-semibold uppercase tracking-widestest text-ink-soft mb-3">Paid</p>
    <p class="font-display text-3xl text-ink leading-none">ETB <span id="totPaid">—</span></p>
  </div>
  <div class="stat-card" style="border-top-color:#ba1a1a;">
    <p class="text-[10px] font-semibold uppercase tracking-widestest text-ink-soft mb-3">Outstanding</p>
    <p class="font-display text-3xl text-error leading-none">ETB <span id="totOutstanding">—</span></p>
  </div>
</div>

<section class="panel">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 class="font-display text-lg text-ink">Payments · <span id="rowCount" class="text-ink-soft text-sm">—</span></h2>
  </header>
  <div class="table-wrap">
    <table class="data">
      <thead><tr>
        <th>Student</th><th>Class</th><th>Term</th><th>Expected</th><th>Paid</th><th>Status</th><th class="text-right">&nbsp;</th>
      </tr></thead>
      <tbody id="tbody"><tr><td colspan="7" class="text-center text-ink-soft py-12">Loading…</td></tr></tbody>
    </table>
  </div>
</section>

<!-- Edit panel -->
<section id="editPanel" class="panel hidden">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 class="font-display text-lg text-ink">Edit payment</h2>
    <button type="button" id="cancelBtn" class="btn-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
  </header>
  <form id="editForm" class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
    <input type="hidden" id="f_id" />
    <div><label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Expected (ETB)</label><input id="f_amount" type="number" step="0.01" min="0" class="input-field" required /></div>
    <div><label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Paid (ETB)</label><input id="f_paid" type="number" step="0.01" min="0" class="input-field" /></div>
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Status</label>
      <select id="f_status" class="input-field">
        <option value="">Auto-derive</option><option value="paid">Paid</option><option value="partial">Partial</option><option value="unpaid">Unpaid</option>
      </select>
    </div>
    <div class="md:col-span-3"><label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Notes</label><textarea id="f_notes" class="input-field" rows="2"></textarea></div>
    <div class="md:col-span-3 flex items-center gap-3">
      <button type="submit" class="btn-primary">Save</button>
      <button type="button" id="cancelBtn2" class="btn-ghost">Cancel</button>
      <button type="button" id="markPaid" class="btn-ghost">Mark paid in full</button>
      <p id="formMsg" class="text-sm text-error hidden"></p>
    </div>
  </form>
</section>

<script>
  var all = [], terms = [], classes = [];
  function escHtml(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];}); }
  function fmt(n) { return parseFloat(n||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }

  function fillFilters() {
    var ts = document.getElementById('filterTerm');
    ts.innerHTML = '<option value="">All terms</option>' + terms.filter(function(t){return t.is_archived==0;}).map(function(t){
      return '<option value="'+t.id+'"'+(t.is_current==1?' selected':'')+'>'+escHtml(t.academic_year)+' · '+escHtml(t.name)+'</option>';
    }).join('');
    var cs = document.getElementById('filterClass');
    cs.innerHTML = '<option value="">All classes</option>' + classes.filter(function(c){return c.is_archived==0;}).map(function(c){
      return '<option value="'+c.id+'">'+escHtml((c.track_name||'') + ' · ' + (c.level_name||'') + ' · ' + c.name + ' (' + c.academic_year + ')')+'</option>';
    }).join('');
  }

  function render() {
    document.getElementById('rowCount').textContent = all.length + ' rows';
    var tbody = document.getElementById('tbody');
    if (!all.length) { tbody.innerHTML = '<tr><td colspan="7" class="text-center text-ink-soft py-16">No payments. Generate rows from a term, or wait until students are assigned to classes.</td></tr>'; return; }
    tbody.innerHTML = all.map(function (p) {
      var name = ((p.first_name||'') + ' ' + (p.last_name||'')).trim();
      var cls = p.class_name ? (escHtml(p.class_name)+' <span class="text-xs text-outline">('+escHtml(p.level_name||'')+')</span>') : '<span class="text-outline text-xs">Unassigned</span>';
      var pill = '<span class="pill pill-' + escHtml(p.status) + '">' + escHtml(p.status) + '</span>';
      return '<tr>' +
        '<td><a class="font-medium hover:text-primary" href="/admin/student-detail.php?id='+p.student_id+'">'+escHtml(name)+'</a></td>' +
        '<td>'+cls+'</td>' +
        '<td class="text-ink-soft">'+escHtml(p.academic_year)+' · '+escHtml(p.term_name)+'</td>' +
        '<td>ETB '+fmt(p.amount)+'</td>' +
        '<td>ETB '+fmt(p.paid_amount)+'</td>' +
        '<td>'+pill+'</td>' +
        '<td class="text-right"><button class="btn-icon" title="Edit" data-edit="'+p.id+'"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button></td>' +
      '</tr>';
    }).join('');
  }

  function renderTotals(t) {
    document.getElementById('totExpected').textContent = fmt(t.expected || 0);
    document.getElementById('totPaid').textContent = fmt(t.paid || 0);
    document.getElementById('totOutstanding').textContent = fmt(t.outstanding || 0);
  }

  async function load() {
    var qs = [];
    var t = document.getElementById('filterTerm').value;
    var c = document.getElementById('filterClass').value;
    var s = document.getElementById('filterStatus').value;
    if (t) qs.push('term_id='+encodeURIComponent(t));
    if (c) qs.push('class_id='+encodeURIComponent(c));
    if (s) qs.push('status='+encodeURIComponent(s));
    try {
      var d = await gs.api('/api/admin/payments/index.php' + (qs.length?'?'+qs.join('&'):''));
      all = d.data || []; renderTotals(d.totals || {}); render();
    } catch (e) { gs.toast(e.message,'error'); }
  }

  async function init() {
    try {
      var [tr, cl] = await Promise.all([
        gs.api('/api/admin/terms/index.php'),
        gs.api('/api/admin/classes/index.php')
      ]);
      terms = tr.data || []; classes = cl.data || []; fillFilters(); load();
    } catch (e) { gs.toast(e.message,'error'); }
  }

  ['filterTerm','filterClass','filterStatus'].forEach(function(id){ document.getElementById(id).addEventListener('change', load); });
  document.getElementById('reloadBtn').addEventListener('click', load);

  // Generate payments for a term
  document.getElementById('generateBtn').addEventListener('click', async function () {
    var termId = document.getElementById('filterTerm').value;
    if (!termId) { gs.toast('Pick a term in the filter first', 'error'); return; }
    var classId = document.getElementById('filterClass').value || 0;
    if (!await gs.confirm('Generate payment rows for this term using its default tuition?')) return;
    try {
      var r = await gs.api('/api/admin/payments/generate.php', { method:'POST', body: JSON.stringify({ term_id: parseInt(termId,10), class_id: parseInt(classId||0,10) }) });
      gs.toast('Created ' + r.created + ', skipped ' + r.skipped, 'success');
      load();
    } catch (e) { gs.toast(e.message,'error'); }
  });

  // Edit
  var editPanel = document.getElementById('editPanel');
  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-edit]');
    if (!t) return;
    var id = parseInt(t.dataset.edit, 10);
    var p = all.find(function (x) { return x.id === id; });
    if (!p) return;
    document.getElementById('f_id').value = p.id;
    document.getElementById('f_amount').value = p.amount;
    document.getElementById('f_paid').value = p.paid_amount;
    document.getElementById('f_status').value = '';
    document.getElementById('f_notes').value = p.notes || '';
    editPanel.classList.remove('hidden');
    editPanel.scrollIntoView({ behavior:'smooth', block:'center' });
  });
  document.getElementById('cancelBtn').addEventListener('click', function(){ editPanel.classList.add('hidden'); });
  document.getElementById('cancelBtn2').addEventListener('click', function(){ editPanel.classList.add('hidden'); });
  document.getElementById('markPaid').addEventListener('click', function () {
    var amt = document.getElementById('f_amount').value;
    document.getElementById('f_paid').value = amt;
    document.getElementById('f_status').value = 'paid';
  });

  document.getElementById('editForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    var msg = document.getElementById('formMsg'); msg.classList.add('hidden');
    var body = {
      id: parseInt(document.getElementById('f_id').value, 10),
      amount: parseFloat(document.getElementById('f_amount').value || '0'),
      paid_amount: parseFloat(document.getElementById('f_paid').value || '0'),
      status: document.getElementById('f_status').value,
      notes: document.getElementById('f_notes').value,
    };
    try {
      await gs.api('/api/admin/payments/index.php', { method:'PUT', body: JSON.stringify(body) });
      gs.toast('Saved','success');
      editPanel.classList.add('hidden');
      load();
    } catch (err) { msg.textContent = err.message; msg.classList.remove('hidden'); }
  });

  init();
</script>

<?php require __DIR__ . '/_partials/page-shell-end.php'; ?>
