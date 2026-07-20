<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'admin') { header('Location: /'); exit; }

$page_title    = 'Serving Eligibility';
$page_title_am = 'የአገልግሎት ብቁነት';
$page_eyebrow    = 'Calendar';
$page_eyebrow_am = 'የቀን መቁጠሪያ';
$active_nav = 'eligibility';
require __DIR__ . '/_partials/page-shell.php';
?>

<p class="text-sm text-ink-soft max-w-2xl" data-en="A member is eligible to serve when their academic attendance meets the minimum below. You control the threshold — change it any time. Members with no class attendance recorded show as “no data”." data-am="አንድ አባል የትምህርት መገኘቱ ከዚህ በታች ያለውን ዝቅተኛ መጠን ሲያሟላ ለማገልገል ብቁ ነው። መጠኑን እርስዎ ይቆጣጠራሉ — በማንኛውም ጊዜ ይቀይሩት።">A member is eligible to serve when their academic attendance meets the minimum below. You control the threshold — change it any time.</p>

<!-- Controls -->
<section class="panel p-4 flex flex-wrap items-end gap-4">
  <div class="min-w-[220px]">
    <label class="lbl" data-en="Department" data-am="ክፍል">Department</label>
    <select id="deptSel" class="input-field"></select>
  </div>
  <div>
    <label class="lbl" data-en="Minimum attendance %" data-am="ዝቅተኛ መገኘት %">Minimum attendance %</label>
    <div class="flex items-center gap-2">
      <input id="threshold" type="number" min="0" max="100" class="input-field" style="width:90px" />
      <button id="saveThreshold" class="btn-primary" data-en="Save" data-am="አስቀምጥ">Save</button>
    </div>
  </div>
  <div class="flex-1 text-right text-sm text-ink-soft">
    <span id="summary"></span>
  </div>
</section>

<section class="panel">
  <header class="px-6 py-5 border-b border-outline-soft/40">
    <h2 class="font-display text-lg text-ink"><span data-en="Members" data-am="አባላት">Members</span> · <span id="rowCount" class="text-ink-soft text-sm">—</span></h2>
  </header>
  <div class="table-wrap">
    <table class="data">
      <thead><tr>
        <th data-en="Name" data-am="ስም">Name</th>
        <th data-en="Level" data-am="ደረጃ">Level</th>
        <th data-en="Attendance (all-time)" data-am="መገኘት (ጠቅላላ)">Attendance (all-time)</th>
        <th data-en="Rate" data-am="መጠን">Rate</th>
        <th data-en="This term" data-am="የዚህ ወቅት">This term</th>
        <th data-en="Serving" data-am="አገልግሎት">Serving</th>
      </tr></thead>
      <tbody id="tbody"><tr><td colspan="6" class="text-center text-ink-soft py-12">Loading…</td></tr></tbody>
    </table>
  </div>
</section>

<style>.lbl{display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.18em;color:#3f4658;margin-bottom:6px;}
  .bar{height:6px;border-radius:9999px;background:#ebe8e1;overflow:hidden;width:90px;display:inline-block;vertical-align:middle;}
  .bar>span{display:block;height:100%;}
</style>

<script>
  var depts=[], data=null, threshold=75;
  function escHtml(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];});}
  function curLang(){ return document.documentElement.getAttribute('data-lang')||'en'; }
  function v(id){return document.getElementById(id);}

  function render() {
    if (!data) return;
    var m = data.members || [];
    v('rowCount').textContent = m.length + (curLang()==='am'?' አባላት':' members');
    var eligible = m.filter(function(x){return x.eligible;}).length;
    var withData = m.filter(function(x){return x.has_data;}).length;
    v('summary').textContent = (curLang()==='am'
      ? (eligible+' ብቁ · '+(withData-eligible)+' ብቁ ያልሆኑ · '+(m.length-withData)+' መረጃ የለም')
      : (eligible+' eligible · '+(withData-eligible)+' not eligible · '+(m.length-withData)+' no data'));
    var tb = v('tbody');
    if (!m.length) { tb.innerHTML = '<tr><td colspan="6" class="text-center text-ink-soft py-12" data-en="No members." data-am="አባል የለም።">No members.</td></tr>'; return; }
    tb.innerHTML = m.map(function(x){
      var lvl = curLang()==='am' ? (x.level_name_am||x.level_name) : (x.level_name);
      var serving, rateCell;
      var termCell = (x.term_total && x.term_total>0) ? (x.term_rate+'% ('+x.term_attended+'/'+x.term_total+')') : '—';
      if (!x.has_data) {
        rateCell = '<span class="text-ink-soft">—</span>';
        serving = '<span class="pill pill-archived">'+(curLang()==='am'?'መረጃ የለም':'no data')+'</span>';
      } else {
        var color = x.eligible ? '#384700' : '#ba1a1a';
        rateCell = '<span class="bar"><span style="width:'+Math.min(100,x.rate)+'%;background:'+color+'"></span></span> <span class="font-medium" style="color:'+color+'">'+x.rate+'%</span>';
        serving = x.eligible
          ? '<span class="pill pill-active">'+(curLang()==='am'?'ብቁ':'eligible')+'</span>'
          : '<span class="pill pill-unpaid">'+(curLang()==='am'?'ብቁ አይደለም':'not eligible')+'</span>';
      }
      return '<tr>' +
        '<td class="font-medium">'+escHtml(x.name)+'</td>' +
        '<td class="text-ink-soft ethiopic">'+escHtml(lvl||'—')+'</td>' +
        '<td class="text-ink-soft text-sm">'+x.attended+' / '+x.total+'</td>' +
        '<td>'+rateCell+'</td>' +
        '<td class="text-ink-soft text-sm">'+termCell+'</td>' +
        '<td>'+serving+'</td>' +
      '</tr>';
    }).join('');
  }

  async function loadDepts() {
    var d = await gs.api('/api/admin/departments/index.php');
    // departments that actually have members are most useful, but show all
    depts = d.data || [];
    v('deptSel').innerHTML = depts.map(function(x){ return '<option value="'+x.id+'"'+(x.slug==='mezmur'?' selected':'')+'>'+escHtml((x.parent_id?'↳ ':'')+(curLang()==='am'?(x.name_am||x.name):x.name))+'</option>'; }).join('');
  }

  async function loadEligibility() {
    try {
      var did = v('deptSel').value;
      var res = await gs.api('/api/admin/eligibility/index.php' + (did?('?department_id='+did):''));
      data = res; threshold = res.threshold;
      v('threshold').value = threshold;
      render();
    } catch(e){ gs.toast(e.message,'error'); }
  }

  v('saveThreshold').addEventListener('click', async function(){
    var val = parseInt(v('threshold').value,10);
    if (isNaN(val) || val<0 || val>100) { gs.toast(curLang()==='am'?'ከ0-100 ይሁን':'Enter 0–100','error'); return; }
    try {
      await gs.api('/api/admin/settings/options.php',{method:'PUT',body:JSON.stringify({key:'serving_eligibility_min_attendance', value:val})});
      gs.toast(curLang()==='am'?'መጠኑ ተቀምጧል':'Threshold saved','success');
      loadEligibility();
    } catch(e){ gs.toast(e.message,'error'); }
  });

  v('deptSel').addEventListener('change', loadEligibility);
  document.addEventListener('gs:lang-change', function(){ if(depts.length){ var cur=v('deptSel').value; v('deptSel').innerHTML=depts.map(function(x){return '<option value="'+x.id+'"'+(String(x.id)===cur?' selected':'')+'>'+escHtml((x.parent_id?'↳ ':'')+(curLang()==='am'?(x.name_am||x.name):x.name))+'</option>';}).join(''); } render(); });

  (async function(){ try { await loadDepts(); await loadEligibility(); } catch(e){ gs.toast(e.message,'error'); } })();
</script>

<?php require __DIR__ . '/_partials/page-shell-end.php'; ?>
