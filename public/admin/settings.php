<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'admin') { header('Location: /'); exit; }

$page_title    = 'Settings';
$page_title_am = 'ቅንብር';
$page_eyebrow    = 'Account';
$page_eyebrow_am = 'መለያ';
$active_nav = 'settings';
$user_email = $_SESSION['user_email'] ?? '—';
require __DIR__ . '/_partials/page-shell.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

  <!-- Profile / account info -->
  <section class="panel">
    <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
      <h2 class="font-display text-lg text-ink" data-en="Your account" data-am="የእርስዎ መለያ">Your account</h2>
      <a href="/admin/audit-log.php" class="text-xs font-semibold uppercase tracking-widestest text-gold hover:text-primary inline-flex items-center gap-2">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 8v4l3 3M22 12c0 5.523-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2s10 4.477 10 10z"/></svg>
        <span data-en="Activity log" data-am="የእንቅስቃሴ መዝገብ">Activity log</span>
      </a>
    </header>
    <div class="p-6 space-y-3">
      <dl class="grid grid-cols-2 gap-4 text-sm">
        <div><dt class="text-[10px] uppercase tracking-widestest text-ink-soft mb-1" data-en="Signed in as" data-am="የገቡት እንደ">Signed in as</dt><dd><?= htmlspecialchars($user_email) ?></dd></div>
        <div><dt class="text-[10px] uppercase tracking-widestest text-ink-soft mb-1" data-en="Role" data-am="ሚና">Role</dt><dd>Administrator</dd></div>
      </dl>
    </div>
  </section>

  <!-- Change password -->
  <section class="panel">
    <header class="px-6 py-5 border-b border-outline-soft/40">
      <h2 class="font-display text-lg text-ink" data-en="Change password" data-am="የይለፍ ቃል ይቀይሩ">Change password</h2>
    </header>
    <form id="pwForm" class="p-6 space-y-4">
      <div>
        <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2" data-en="Current password" data-am="የአሁኑ የይለፍ ቃል">Current password</label>
        <input id="pw_current" type="password" autocomplete="current-password" class="input-field" required />
      </div>
      <div>
        <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2" data-en="New password" data-am="አዲስ የይለፍ ቃል">New password</label>
        <input id="pw_new" type="password" autocomplete="new-password" class="input-field" minlength="8" required />
        <p class="text-xs text-outline mt-2">Minimum 8 characters.</p>
      </div>
      <div>
        <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2" data-en="Confirm new password" data-am="አዲስ የይለፍ ቃል ያረጋግጡ">Confirm new password</label>
        <input id="pw_confirm" type="password" autocomplete="new-password" class="input-field" required />
      </div>
      <div class="flex items-center gap-3">
        <button type="submit" id="pwSaveBtn" class="btn-primary">Update password</button>
        <p id="pwMsg" class="text-sm hidden"></p>
      </div>
    </form>
  </section>

  <!-- Current term -->
  <section class="panel lg:col-span-2">
    <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
      <h2 class="font-display text-lg text-ink" data-en="Current term" data-am="ወቅታዊ ኮርስ">Current term</h2>
      <a href="/admin/terms.php" class="text-xs font-semibold uppercase tracking-widestest text-gold hover:text-primary" data-en="Manage terms" data-am="ወቅቶችን ያስተዳድሩ">Manage terms</a>
    </header>
    <div class="p-6 space-y-4">
      <p class="text-sm text-ink-soft">The term marked here drives the dashboard's "unpaid this term" stat and other term-bound views.</p>
      <div id="currentTermPanel" class="bg-surface-low border border-outline-soft/40 rounded p-4 text-sm">Loading…</div>
      <form id="termForm" class="flex items-end gap-3 flex-wrap">
        <div class="flex-1 min-w-[240px]">
          <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Set as current</label>
          <select id="termSelect" class="input-field" required>
            <option value="">Choose a term…</option>
          </select>
        </div>
        <button type="submit" class="btn-primary">Apply</button>
      </form>
    </div>
  </section>
</div>

<script>
  // === Password change ===
  document.getElementById('pwForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    var msg = document.getElementById('pwMsg');
    msg.className = 'text-sm hidden'; msg.textContent = '';
    var c = document.getElementById('pw_current').value;
    var n = document.getElementById('pw_new').value;
    var cf = document.getElementById('pw_confirm').value;
    if (n !== cf) { msg.className = 'text-sm text-error'; msg.textContent = 'New password and confirmation do not match.'; return; }
    try {
      await gs.api('/api/admin/settings/password.php', {
        method: 'PUT',
        body: JSON.stringify({ current_password: c, new_password: n })
      });
      gs.toast('Password updated', 'success');
      document.getElementById('pwForm').reset();
    } catch (err) {
      msg.className = 'text-sm text-error'; msg.textContent = err.message;
    }
  });

  // === Current term ===
  function escHtml(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];}); }

  function renderCurrentTerm(t) {
    var el = document.getElementById('currentTermPanel');
    if (!t) { el.innerHTML = '<span class="text-outline">No current term set yet.</span>'; return; }
    el.innerHTML =
      '<p class="text-[10px] uppercase tracking-widestest text-gold mb-1">Active</p>' +
      '<p class="font-display text-xl text-primary">'+escHtml(t.name)+'</p>' +
      '<p class="text-sm text-ink-soft">'+escHtml(t.academic_year)+' · '+escHtml(t.start_date)+' → '+escHtml(t.end_date)+'</p>';
  }

  async function loadTerms() {
    try {
      var [cur, all] = await Promise.all([
        gs.api('/api/admin/settings/current-term.php'),
        gs.api('/api/admin/terms/index.php')
      ]);
      renderCurrentTerm(cur.term);
      var sel = document.getElementById('termSelect');
      (all.data || []).filter(function (t) { return t.is_archived == 0; }).forEach(function (t) {
        var opt = document.createElement('option');
        opt.value = t.id;
        opt.textContent = t.academic_year + ' · ' + t.name + ' (' + t.start_date + ' → ' + t.end_date + ')';
        sel.appendChild(opt);
      });
    } catch (e) { gs.toast(e.message, 'error'); }
  }

  document.getElementById('termForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    var id = parseInt(document.getElementById('termSelect').value, 10);
    if (!id) return;
    try {
      await gs.api('/api/admin/settings/current-term.php', { method: 'PUT', body: JSON.stringify({ id: id }) });
      gs.toast('Current term updated', 'success');
      var cur = await gs.api('/api/admin/settings/current-term.php');
      renderCurrentTerm(cur.term);
    } catch (err) { gs.toast(err.message, 'error'); }
  });

  loadTerms();
</script>

<?php require __DIR__ . '/_partials/page-shell-end.php'; ?>
