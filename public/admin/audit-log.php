<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'admin') { header('Location: /'); exit; }

$page_title    = 'Activity Log';
$page_title_am = 'የእንቅስቃሴ መዝገብ';
$page_eyebrow    = 'Account';
$page_eyebrow_am = 'መለያ';
$active_nav = 'settings';
require __DIR__ . '/_partials/page-shell.php';
?>

<div class="flex items-center justify-between flex-wrap gap-3">
  <p class="text-sm text-ink-soft max-w-xl">Tracks who did what, and when. Admin actions only — login/logout aren't recorded yet.</p>
  <div class="flex items-center gap-2">
    <select id="filterAction" class="input-field !w-auto !py-2.5">
      <option value="">All actions</option>
      <option>user.create</option>
      <option>user.update</option>
      <option>user.archive</option>
      <option>payment.create</option>
      <option>payment.update</option>
      <option>payment.archive</option>
      <option>payment.generate</option>
      <option>announcement.send</option>
      <option>student_assignment.create</option>
      <option>settings.current_term</option>
      <option>settings.password_change</option>
    </select>
    <button id="reloadBtn" class="btn-ghost">Refresh</button>
  </div>
</div>

<section class="panel">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 class="font-display text-lg text-ink">Recent activity · <span id="rowCount" class="text-ink-soft text-sm">—</span></h2>
  </header>
  <div class="table-wrap">
    <table class="data">
      <thead><tr>
        <th>When</th><th>Actor</th><th>Action</th><th>Entity</th><th>Details</th><th>IP</th>
      </tr></thead>
      <tbody id="tbody"><tr><td colspan="6" class="text-center text-ink-soft py-12">Loading…</td></tr></tbody>
    </table>
  </div>
</section>

<script>
  function escHtml(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];}); }
  function fmt(s) { return s ? gs.fmtDate(s, 'datetime') : ''; }
  document.addEventListener('gs:lang-change', load);

  async function load() {
    var qs = '';
    var a = document.getElementById('filterAction').value;
    if (a) qs = '?action=' + encodeURIComponent(a);
    try {
      var d = await gs.api('/api/admin/audit-log/index.php' + qs);
      var rows = d.data || [];
      document.getElementById('rowCount').textContent = rows.length + ' rows';
      var tbody = document.getElementById('tbody');
      if (!rows.length) { tbody.innerHTML = '<tr><td colspan="6" class="text-center text-ink-soft py-16">No activity yet.</td></tr>'; return; }
      tbody.innerHTML = rows.map(function (r) {
        var meta = r.metadata ? Object.keys(r.metadata).map(function (k) { return k + ': ' + JSON.stringify(r.metadata[k]); }).join(', ') : '';
        var entity = (r.entity_type || '') + (r.entity_id ? ' #' + r.entity_id : '');
        return '<tr>' +
          '<td class="text-ink-soft text-sm">' + escHtml(fmt(r.created_at)) + '</td>' +
          '<td>' + escHtml(r.actor_email || '—') + '</td>' +
          '<td><span class="pill pill-active">' + escHtml(r.action) + '</span></td>' +
          '<td class="text-ink-soft">' + escHtml(entity || '—') + '</td>' +
          '<td class="text-ink-soft text-sm">' + escHtml(meta || '—') + '</td>' +
          '<td class="text-xs text-outline">' + escHtml(r.ip_addr || '') + '</td>' +
        '</tr>';
      }).join('');
    } catch (e) { gs.toast(e.message,'error'); }
  }

  document.getElementById('filterAction').addEventListener('change', load);
  document.getElementById('reloadBtn').addEventListener('click', load);
  load();
</script>

<?php require __DIR__ . '/_partials/page-shell-end.php'; ?>
