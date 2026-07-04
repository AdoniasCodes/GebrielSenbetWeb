<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'admin') { header('Location: /'); exit; }

$page_title    = 'Attendance';
$page_title_am = 'መገኘት';
$page_eyebrow    = 'Records';
$page_eyebrow_am = 'መዝገቦች';
$active_nav = 'attendance';
require __DIR__ . '/_partials/page-shell.php';
?>

<p class="text-sm text-ink-soft max-w-2xl" data-en="Take a roll-call for a class (academic attendance) or a department gathering/duty (service attendance). Academic attendance feeds choir-serving eligibility." data-am="ለክፍል (የትምህርት መገኘት) ወይም ለክፍል ስብሰባ/አገልግሎት (የአገልግሎት መገኘት) ያመዝግቡ። የትምህርት መገኘት ለመዝሙር አገልግሎት ብቁነት ይውላል።">Take a roll-call for a class (academic attendance) or a department gathering/duty (service attendance). Academic attendance feeds choir-serving eligibility.</p>

<!-- Session picker -->
<section class="panel p-4 flex flex-wrap items-end gap-3">
  <div>
    <label class="lbl" data-en="Type" data-am="አይነት">Type</label>
    <select id="ctxType" class="input-field">
      <option value="class" data-en="Class (academic)" data-am="ክፍል (ትምህርት)">Class (academic)</option>
      <option value="department" data-en="Department (service)" data-am="ክፍል (አገልግሎት)">Department (service)</option>
    </select>
  </div>
  <div class="flex-1 min-w-[200px]">
    <label class="lbl" data-en="Which" data-am="የትኛው">Which</label>
    <select id="ctxId" class="input-field"></select>
  </div>
  <div>
    <label class="lbl" data-en="Date" data-am="ቀን">Date</label>
    <input id="sessDate" type="date" class="input-field" />
  </div>
  <div>
    <label class="lbl" data-en="Church (optional)" data-am="ቤተክርስቲያን">Church</label>
    <select id="sessChurch" class="input-field"><option value="">—</option></select>
  </div>
  <button id="openBtn" class="btn-primary" data-en="Open roll-call" data-am="ክፍለ ጊዜ ክፈት">Open roll-call</button>
</section>

<!-- Roster -->
<section id="rosterPanel" class="panel hidden">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between flex-wrap gap-3">
    <h2 class="font-display text-lg text-ink"><span id="rosterTitle">—</span> · <span id="rosterCount" class="text-ink-soft text-sm"></span></h2>
    <div class="flex items-center gap-2">
      <button id="allPresent" class="btn-ghost" data-en="Mark all present" data-am="ሁሉም ተገኝቷል">Mark all present</button>
      <button id="saveBtn" class="btn-primary" data-en="Save attendance" data-am="መገኘት አስቀምጥ">Save attendance</button>
    </div>
  </header>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th data-en="Name" data-am="ስም">Name</th><th data-en="Status" data-am="ሁኔታ">Status</th></tr></thead>
      <tbody id="rosterBody"></tbody>
    </table>
  </div>
</section>

<!-- Recent sessions -->
<section class="panel">
  <header class="px-6 py-5 border-b border-outline-soft/40"><h2 class="font-display text-lg text-ink" data-en="Recent roll-calls" data-am="የቅርብ ክፍለ ጊዜዎች">Recent roll-calls</h2></header>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th data-en="Date" data-am="ቀን">Date</th><th data-en="For" data-am="ለ">For</th><th data-en="Present" data-am="ተገኝቷል">Present</th><th class="text-right">&nbsp;</th></tr></thead>
      <tbody id="recentBody"><tr><td colspan="4" class="text-center text-ink-soft py-10">—</td></tr></tbody>
    </table>
  </div>
</section>

<style>.lbl{display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.18em;color:#3f4658;margin-bottom:6px;}
  .seg{display:inline-flex;border:1px solid rgba(137,113,114,0.3);border-radius:6px;overflow:hidden;}
  .seg button{padding:4px 10px;font-size:12px;background:#fff;color:#3f4658;border-right:1px solid rgba(137,113,114,0.2);}
  .seg button:last-child{border-right:none;}
  .seg button.on-present{background:#384700;color:#fff;} .seg button.on-absent{background:#ba1a1a;color:#fff;}
  .seg button.on-late{background:#fed175;color:#16357e;} .seg button.on-excused{background:#6b7690;color:#fff;}
</style>

<script>
  var classes=[], depts=[], churches=[], roster=[], currentSession=null;
  var STAT=[['present','Present','ተገኝቷል'],['absent','Absent','የለም'],['late','Late','ዘግይቷል'],['excused','Excused','ተፈቅዷል']];
  function escHtml(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];});}
  function curLang(){ return document.documentElement.getAttribute('data-lang')||'en'; }
  function v(id){return document.getElementById(id);}

  function fillContext() {
    var type = v('ctxType').value;
    var sel = v('ctxId');
    if (type==='class') {
      sel.innerHTML = classes.map(function(c){ return '<option value="'+c.id+'">'+escHtml((c.level_name||'')+' · '+c.name+' ('+c.academic_year+')')+'</option>'; }).join('');
    } else {
      sel.innerHTML = depts.map(function(d){ return '<option value="'+d.id+'">'+escHtml((d.parent_id?'↳ ':'')+(curLang()==='am'?(d.name_am||d.name):d.name))+'</option>'; }).join('');
    }
  }

  function statusSeg(personId, status) {
    return '<span class="seg" data-person="'+personId+'">' + STAT.map(function(s){
      var on = status===s[0] ? ('on-'+s[0]) : '';
      return '<button type="button" class="'+on+'" data-status="'+s[0]+'">'+(curLang()==='am'?s[2]:s[1])+'</button>';
    }).join('') + '</span>';
  }

  function renderRoster() {
    v('rosterCount').textContent = roster.length + (curLang()==='am'?' ሰዎች':' people');
    var b = v('rosterBody');
    if (!roster.length) { b.innerHTML = '<tr><td colspan="2" class="text-center text-ink-soft py-10" data-en="No one on this roster." data-am="በዚህ ዝርዝር ማንም የለም።">No one on this roster.</td></tr>'; return; }
    b.innerHTML = roster.map(function(p){
      var st = p.status || 'present';
      return '<tr><td class="font-medium">'+escHtml(p.name)+'</td><td>'+statusSeg(p.person_id, st)+'</td></tr>';
    }).join('');
  }

  async function loadLookups() {
    var [c, d, ch] = await Promise.all([
      gs.api('/api/admin/classes/index.php'),
      gs.api('/api/admin/departments/index.php'),
      gs.api('/api/admin/churches/index.php'),
    ]);
    classes=c.data||[]; depts=d.data||[]; churches=ch.data||[];
    fillContext();
    v('sessChurch').innerHTML = '<option value="">—</option>' + churches.map(function(x){return '<option value="'+x.id+'">'+escHtml(x.short_name||x.name)+'</option>';}).join('');
  }

  async function loadRecent() {
    try {
      var r = await gs.api('/api/admin/attendance/index.php?context_type='+v('ctxType').value+'&context_id='+v('ctxId').value);
      var rows = r.data||[];
      var tb = v('recentBody');
      if (!rows.length) { tb.innerHTML = '<tr><td colspan="4" class="text-center text-ink-soft py-10" data-en="No roll-calls yet." data-am="እስካሁን የለም።">No roll-calls yet.</td></tr>'; return; }
      tb.innerHTML = rows.map(function(s){
        return '<tr>' +
          '<td data-iso="'+escHtml(s.session_date)+'" data-fmt-style="long">'+escHtml(s.session_date)+'</td>' +
          '<td class="text-ink-soft text-sm">'+escHtml(s.title || (s.church_name||''))+'</td>' +
          '<td>'+s.present+' / '+s.marked+'</td>' +
          '<td class="text-right"><button class="btn-ghost" data-open="'+s.id+'" data-en="Open" data-am="ክፈት">Open</button> ' +
            '<button class="btn-icon danger" data-del="'+s.id+'" title="Delete"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/></svg></button></td>' +
        '</tr>';
      }).join('');
      if (window.EC) EC.rerenderIsoNodes();
    } catch(e){ gs.toast(e.message,'error'); }
  }

  async function openSession(sessionId) {
    try {
      var r = await gs.api('/api/admin/attendance/records.php?session_id='+sessionId);
      currentSession = r.session; roster = r.roster||[];
      var label = currentSession.context_type==='class'
        ? (function(){ var c=classes.find(function(x){return x.id===currentSession.context_id;}); return c?((c.level_name||'')+' · '+c.name):'Class'; })()
        : (function(){ var d=depts.find(function(x){return x.id===currentSession.context_id;}); return d?(curLang()==='am'?(d.name_am||d.name):d.name):'Department'; })();
      v('rosterTitle').textContent = label + ' — ' + currentSession.session_date;
      v('rosterPanel').classList.remove('hidden');
      renderRoster();
      v('rosterPanel').scrollIntoView({behavior:'smooth',block:'start'});
    } catch(e){ gs.toast(e.message,'error'); }
  }

  v('openBtn').addEventListener('click', async function(){
    var body = { context_type: v('ctxType').value, context_id: parseInt(v('ctxId').value,10), session_date: v('sessDate').value, church_id: v('sessChurch').value?parseInt(v('sessChurch').value,10):null };
    if (!body.context_id || !body.session_date) { gs.toast(curLang()==='am'?'ምድብና ቀን ይምረጡ':'Pick a target and date','error'); return; }
    try { var res = await gs.api('/api/admin/attendance/index.php',{method:'POST',body:JSON.stringify(body)}); await openSession(res.id); loadRecent(); }
    catch(e){ gs.toast(e.message,'error'); }
  });

  v('allPresent').addEventListener('click', function(){ roster.forEach(function(p){p.status='present';}); renderRoster(); });

  v('saveBtn').addEventListener('click', async function(){
    if (!currentSession) return;
    var records = roster.map(function(p){ return { person_id: p.person_id, status: p.status||'present' }; });
    try { var r = await gs.api('/api/admin/attendance/records.php',{method:'PUT',body:JSON.stringify({session_id:currentSession.id, records:records})}); gs.toast((curLang()==='am'?'ተቀምጧል · ':'Saved · ')+r.marked,'success'); loadRecent(); }
    catch(e){ gs.toast(e.message,'error'); }
  });

  // roster status clicks
  v('rosterBody').addEventListener('click', function(e){
    var btn = e.target.closest('.seg button'); if (!btn) return;
    var pid = parseInt(btn.parentNode.dataset.person,10); var status = btn.dataset.status;
    var p = roster.find(function(x){return x.person_id===pid;}); if (p) { p.status = status; }
    btn.parentNode.querySelectorAll('button').forEach(function(b){ b.className=''; });
    btn.className = 'on-'+status;
  });

  // recent list actions + context change
  document.addEventListener('click', async function(e){
    var op = e.target.closest('[data-open]'); if (op) { openSession(parseInt(op.dataset.open,10)); return; }
    var dl = e.target.closest('[data-del]'); if (dl) { if(!await gs.confirm(curLang()==='am'?'ይህን ክፍለ ጊዜ ይሰርዙ?':'Delete this roll-call?'))return; try{await gs.api('/api/admin/attendance/index.php',{method:'DELETE',body:JSON.stringify({id:parseInt(dl.dataset.del,10)})}); v('rosterPanel').classList.add('hidden'); currentSession=null; loadRecent();}catch(err){gs.toast(err.message,'error');} return; }
  });
  v('ctxType').addEventListener('change', function(){ fillContext(); loadRecent(); });
  v('ctxId').addEventListener('change', loadRecent);
  document.addEventListener('gs:lang-change', function(){ fillContext(); if (roster.length) renderRoster(); });

  (async function(){
    try { await loadLookups(); v('sessDate').value = new Date().toISOString().slice(0,10); loadRecent(); }
    catch(e){ gs.toast(e.message,'error'); }
  })();
</script>

<?php require __DIR__ . '/_partials/page-shell-end.php'; ?>
