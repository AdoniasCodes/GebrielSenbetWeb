<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'admin') { header('Location: /'); exit; }

$page_title    = 'Reset / Data';
$page_title_am = 'ዳታ ማጽዳት';
$page_eyebrow    = 'System';
$page_eyebrow_am = 'ሲስተም';
$active_nav = 'reset-data';
require __DIR__ . '/_partials/page-shell.php';
?>

<div class="max-w-3xl space-y-6">

  <!-- Current data -->
  <section class="bg-white rounded-lg border border-outline-soft/40 p-5">
    <h2 class="font-display text-base text-ink mb-3" data-en="Current data" data-am="የአሁኑ ዳታ">Current data</h2>
    <div id="counts" class="flex flex-wrap gap-2 text-xs">
      <span class="text-ink-soft" data-en="Loading…" data-am="በመጫን ላይ…">Loading…</span>
    </div>
  </section>

  <!-- Password -->
  <section class="bg-white rounded-lg border border-outline-soft/40 p-5">
    <label for="resetPw" class="block text-xs font-semibold uppercase tracking-widestest text-outline mb-1.5" data-en="Reset password" data-am="የማጽጃ የይለፍ ቃል">Reset password</label>
    <input id="resetPw" type="password" autocomplete="off" class="w-full max-w-xs border border-outline-soft rounded px-3 py-2 text-sm" placeholder="••••••••" />
    <p class="text-[11px] text-ink-soft mt-1.5" data-en="Required for both actions below." data-am="ከታች ላሉት ሁለቱም ተግባራት ያስፈልጋል።">Required for both actions below.</p>
  </section>

  <!-- Action 1: reset with test accounts -->
  <section class="bg-white rounded-lg border border-outline-soft/40 p-5">
    <h3 class="font-display text-base text-primary mb-1" data-en="Reset with test accounts" data-am="ከሙከራ መለያዎች ጋር እንደገና አስጀምር">Reset with test accounts</h3>
    <p class="text-sm text-ink-soft mb-4" data-en="Wipes all operational data, keeps your admin + reference data (roles, churches, departments, grades, subjects), and creates one test login per role (teacher / student / parent / dept-head) wired with sample data." data-am="ሁሉንም የሥራ ዳታ ያጠፋል፣ የእርስዎን አድሚን + መሠረታዊ ዳታ (ሚናዎች፣ አብያተ ክርስቲያናት፣ ክፍሎች፣ ውጤቶች፣ ትምህርቶች) ይጠብቃል፣ እና ለእያንዳንዱ ሚና አንድ የሙከራ መለያ ይፈጥራል።">Wipes all operational data, keeps your admin + reference data, and creates one test login per role wired with sample data.</p>
    <button id="btnDemo" class="bg-primary text-white text-sm font-semibold px-4 py-2 rounded hover:bg-primary/90" data-en="Reset with test accounts" data-am="ከሙከራ መለያዎች ጋር አስጀምር">Reset with test accounts</button>
  </section>

  <!-- Action 2: wipe clean -->
  <section class="bg-white rounded-lg border-2 border-error/30 p-5">
    <h3 class="font-display text-base text-error mb-1" data-en="Wipe to clean slate" data-am="ሙሉ በሙሉ አጽዳ">Wipe to clean slate</h3>
    <p class="text-sm text-ink-soft mb-4" data-en="Wipes all operational data and keeps ONLY your admin + reference data. No test accounts. For going live." data-am="ሁሉንም የሥራ ዳታ ያጠፋል እና የእርስዎን አድሚን + መሠረታዊ ዳታ ብቻ ይጠብቃል። ምንም የሙከራ መለያ የለም። ለቀጥታ ስርጭት።">Wipes all operational data and keeps ONLY your admin + reference data. No test accounts. For going live.</p>
    <button id="btnWipe" class="bg-error text-white text-sm font-semibold px-4 py-2 rounded hover:bg-error/90" data-en="Wipe to clean slate" data-am="ሙሉ በሙሉ አጽዳ">Wipe to clean slate</button>
  </section>

  <!-- Results -->
  <section id="resultBox" class="hidden bg-gold-warm/15 border border-gold-soft/50 rounded-lg p-5">
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-display text-base text-ink" data-en="Save these now" data-am="አሁኑኑ ያስቀምጡ">Save these now</h3>
      <button id="copyBtn" class="text-xs font-semibold text-primary border border-primary/40 rounded px-3 py-1.5" data-en="Copy" data-am="ቅዳ">Copy</button>
    </div>
    <p class="text-[12px] text-ink-soft mb-3" data-en="These passwords are shown once. Copy them now." data-am="እነዚህ የይለፍ ቃላት አንድ ጊዜ ብቻ ይታያሉ። አሁን ይቅዱ።">These passwords are shown once. Copy them now.</p>
    <div id="resultBody" class="overflow-x-auto"></div>
  </section>

</div>

<script>
  function escHtml(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];});}
  var LABELS = { users:'Users', people:'People', students:'Students', teachers:'Teachers', classes:'Classes', grades:'Grades', payments:'Payments', attendance_sessions:'Attendance', notifications:'Announcements', departments:'Departments' };
  var lastAccounts = null;

  async function loadCounts(){
    var box = document.getElementById('counts');
    try {
      var d = await gs.api('/api/admin/reset-data/index.php');
      var c = d.counts || {};
      box.innerHTML = Object.keys(LABELS).map(function(k){
        return '<span class="inline-flex items-center gap-1.5 bg-surface-mid rounded px-2.5 py-1">'+
               '<span class="text-ink-soft">'+escHtml(LABELS[k])+'</span>'+
               '<span class="font-semibold text-ink">'+escHtml(c[k]==null?'–':c[k])+'</span></span>';
      }).join('');
    } catch(e){ box.innerHTML = '<span class="text-error">'+escHtml(e.message)+'</span>'; }
  }

  function pw(){ return (document.getElementById('resetPw').value || '').trim(); }

  async function runReset(action, confirmMsg){
    if (!pw()) { gs.toast('Enter the reset password first', 'error'); return; }
    if (!(await gs.confirm(confirmMsg))) return;
    try {
      var d = await gs.api('/api/admin/reset-data/index.php', { method:'POST', body: JSON.stringify({ action: action, password: pw() }) });
      if (action === 'load_demo' && d.accounts) {
        lastAccounts = d.accounts;
        var rows = d.accounts.map(function(a){
          return '<tr class="border-b border-outline-soft/30">'+
            '<td class="py-1.5 pr-4 font-medium capitalize">'+escHtml(a.role)+'</td>'+
            '<td class="py-1.5 pr-4">'+escHtml(a.email)+'</td>'+
            '<td class="py-1.5 font-mono text-primary">'+escHtml(a.password)+'</td></tr>';
        }).join('');
        document.getElementById('resultBody').innerHTML =
          '<table class="w-full text-sm"><thead><tr class="text-left text-[11px] uppercase tracking-widestest text-outline">'+
          '<th class="pb-2 pr-4">Role</th><th class="pb-2 pr-4">Email</th><th class="pb-2">Password</th></tr></thead><tbody>'+rows+'</tbody></table>';
        document.getElementById('resultBox').classList.remove('hidden');
        gs.toast('Reset complete — test accounts created', 'success');
      } else {
        lastAccounts = null;
        document.getElementById('resultBox').classList.add('hidden');
        var del = d.deleted || {};
        var total = Object.keys(del).reduce(function(s,k){ return s + (parseInt(del[k],10)||0); }, 0);
        gs.toast('Wiped clean — ' + total + ' rows deleted', 'success');
      }
      document.getElementById('resetPw').value = '';
      loadCounts();
    } catch(e){
      gs.toast(e.message === 'Invalid reset password' ? 'Invalid reset password' : e.message, 'error');
    }
  }

  document.getElementById('btnDemo').addEventListener('click', function(){
    runReset('load_demo', 'This permanently deletes all operational data and creates fresh test accounts. Continue?');
  });
  document.getElementById('btnWipe').addEventListener('click', function(){
    runReset('wipe_clean', 'This permanently deletes ALL operational data with NO test accounts. Continue?');
  });
  document.getElementById('copyBtn').addEventListener('click', function(){
    if (!lastAccounts) return;
    var text = lastAccounts.map(function(a){ return a.role + '\t' + a.email + '\t' + a.password; }).join('\n');
    navigator.clipboard.writeText(text).then(function(){ gs.toast('Copied', 'success'); });
  });

  loadCounts();
</script>

<?php require __DIR__ . '/_partials/page-shell-end.php'; ?>
