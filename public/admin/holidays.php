<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'admin') { header('Location: /'); exit; }

$page_title    = 'Holidays & Serving';
$page_title_am = 'በዓላትና አገልግሎት';
$page_eyebrow    = 'Calendar';
$page_eyebrow_am = 'የቀን መቁጠሪያ';
$active_nav = 'holidays';
require __DIR__ . '/_partials/page-shell.php';
?>

<div class="flex items-center justify-between flex-wrap gap-3">
  <p class="text-sm text-ink-soft max-w-xl" data-en="The Ethiopian Orthodox celebration calendar. For each holiday, assign which department/level serves, at which church, and whether alongside the seniors." data-am="የኢትዮጵያ ኦርቶዶክስ የበዓላት መቁጠሪያ። ለእያንዳንዱ በዓል የትኛው ክፍል/ደረጃ፣ በየትኛው ቤተክርስቲያን፣ ከከፍተኞች ጋር መሆኑን ይመድቡ።">The Ethiopian Orthodox celebration calendar. For each holiday, assign which department/level serves, at which church, and whether alongside the seniors.</p>
  <button id="newBtn" class="btn-primary"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg><span data-en="New holiday" data-am="አዲስ በዓል">New holiday</span></button>
</div>

<section id="formPanel" class="panel hidden">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 id="formTitle" class="font-display text-lg text-ink">New holiday</h2>
    <button type="button" id="cancelBtn" class="btn-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
  </header>
  <form id="entityForm" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
    <input type="hidden" id="f_id" />
    <div><label class="lbl" data-en="Name (English)" data-am="ስም (እንግሊዝኛ)">Name (English)</label><input id="f_name" class="input-field" /></div>
    <div><label class="lbl" data-en="Name (Amharic)" data-am="ስም (አማርኛ)">Name (Amharic)</label><input id="f_name_am" class="input-field ethiopic" /></div>
    <div><label class="lbl" data-en="Date" data-am="ቀን">Date</label><input id="f_date" type="date" class="input-field" /></div>
    <div>
      <label class="lbl" data-en="Scale" data-am="መጠን">Scale</label>
      <select id="f_scale" class="input-field"><option value="major" data-en="Major" data-am="ትልቅ">Major</option><option value="minor" data-en="Minor" data-am="ትንሽ">Minor</option></select>
    </div>
    <div class="flex items-center gap-2 pt-6"><input id="f_recurring" type="checkbox" class="w-4 h-4" checked /><label for="f_recurring" class="text-sm" data-en="Recurs every year" data-am="በየዓመቱ ይደጋገማል">Recurs every year</label></div>
    <div class="md:col-span-2"><label class="lbl" data-en="Description" data-am="መግለጫ">Description</label><textarea id="f_desc" rows="2" class="input-field"></textarea></div>
    <div class="md:col-span-2 flex items-center gap-3">
      <button type="submit" class="btn-primary" data-en="Save" data-am="አስቀምጥ">Save</button>
      <button type="button" id="cancelBtn2" class="btn-ghost" data-en="Cancel" data-am="ሰርዝ">Cancel</button>
      <p id="formMsg" class="text-sm text-error hidden"></p>
    </div>
  </form>
</section>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <section class="panel lg:col-span-1 self-start">
    <header class="px-5 py-4 border-b border-outline-soft/40"><h2 class="font-display text-base" data-en="Holidays" data-am="በዓላት">Holidays</h2></header>
    <div id="holList" class="p-3 space-y-1 max-h-[70vh] overflow-y-auto"><p class="text-center text-ink-soft py-8 text-sm">Loading…</p></div>
  </section>

  <section class="lg:col-span-2 space-y-6">
    <div id="empty" class="panel p-12 text-center text-ink-soft" data-en="Select a holiday to manage who serves." data-am="የሚያገለግሉትን ለማስተዳደር በዓል ይምረጡ።">Select a holiday to manage who serves.</div>
    <div id="detail" class="hidden space-y-6">
      <div class="panel px-6 py-5 flex items-center justify-between gap-3">
        <div><h2 id="detName" class="font-display text-xl text-ink">—</h2><p id="detSub" class="text-sm text-ink-soft mt-1"></p></div>
        <div class="flex items-center gap-1"><button id="detEdit" class="btn-ghost" data-en="Edit" data-am="አርትዕ">Edit</button><button id="detArchive" class="btn-icon danger" title="Archive"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/></svg></button></div>
      </div>
      <div class="panel">
        <header class="px-6 py-4 border-b border-outline-soft/40"><h3 class="font-display text-base"><span data-en="Serving assignments" data-am="የአገልግሎት ምድቦች">Serving assignments</span> · <span id="servCount" class="text-ink-soft text-sm">—</span></h3></header>
        <div class="p-4">
          <form id="servForm" class="flex flex-wrap items-end gap-2 bg-surface-low rounded p-3 border border-outline-soft/30 mb-4">
            <div class="min-w-[150px]"><label class="lbl" data-en="Department" data-am="ክፍል">Department</label><select id="s_dept" class="input-field"></select></div>
            <div class="min-w-[130px]"><label class="lbl" data-en="Level" data-am="ደረጃ">Level</label><select id="s_level" class="input-field"><option value="">— all —</option></select></div>
            <div class="min-w-[120px]"><label class="lbl" data-en="Church" data-am="ቤተክርስቲያን">Church</label><select id="s_church" class="input-field"><option value="">— both —</option></select></div>
            <div class="flex items-center gap-2 pt-6"><input id="s_seniors" type="checkbox" class="w-4 h-4" /><label for="s_seniors" class="text-sm" data-en="With seniors" data-am="ከከፍተኞች ጋር">With seniors</label></div>
            <button type="submit" class="btn-primary" data-en="Assign" data-am="መድብ">Assign</button>
          </form>
          <div class="table-wrap"><table class="data">
            <thead><tr><th data-en="Department" data-am="ክፍል">Department</th><th data-en="Level" data-am="ደረጃ">Level</th><th data-en="Church" data-am="ቤተክርስቲያን">Church</th><th data-en="With seniors" data-am="ከከፍተኞች">With seniors</th><th class="text-right">&nbsp;</th></tr></thead>
            <tbody id="servBody"><tr><td colspan="5" class="text-center text-ink-soft py-8" data-en="No assignments yet." data-am="ምድብ የለም።">No assignments yet.</td></tr></tbody>
          </table></div>
        </div>
      </div>
    </div>
  </section>
</div>

<style>.lbl{display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.18em;color:#3f4658;margin-bottom:6px;}</style>

<script>
  var holidays=[], depts=[], churches=[], current=null, serving=[], deptLevels={};
  function escHtml(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];});}
  function curLang(){ return document.documentElement.getAttribute('data-lang')||'en'; }
  function v(id){return document.getElementById(id);}

  function renderHolList(){
    var w=v('holList');
    if(!holidays.length){ w.innerHTML='<p class="text-center text-ink-soft py-8 text-sm" data-en="No holidays yet." data-am="በዓል የለም።">No holidays yet.</p>'; return; }
    w.innerHTML=holidays.map(function(h){
      var active=current&&current.id===h.id;
      var nm=curLang()==='am'?(h.name_am||h.name):(h.name||h.name_am);
      var scalePill=h.scale==='major'?'<span class="pill pill-active" style="font-size:9px">'+(curLang()==='am'?'ትልቅ':'major')+'</span>':'';
      return '<button class="nav-item '+(active?'active':'')+'" data-hol="'+h.id+'"><span class="flex-1 '+(curLang()==='am'?'ethiopic':'')+'">'+escHtml(nm)+' '+scalePill+'</span><span class="text-[11px] text-ink-soft">'+(h.holiday_date?escHtml(h.holiday_date.slice(5)):'—')+'</span></button>';
    }).join('');
  }

  async function loadAll(keepId){
    var [h,d,c]=await Promise.all([ gs.api('/api/admin/holidays/index.php'), gs.api('/api/admin/departments/index.php'), gs.api('/api/admin/churches/index.php') ]);
    holidays=h.data||[]; depts=d.data||[]; churches=c.data||[];
    v('s_dept').innerHTML=depts.map(function(x){return '<option value="'+x.id+'">'+escHtml((x.parent_id?'↳ ':'')+(x.name_am||x.name))+'</option>';}).join('');
    v('s_church').innerHTML='<option value="">— both —</option>'+churches.map(function(x){return '<option value="'+x.id+'">'+escHtml(x.short_name||x.name)+'</option>';}).join('');
    renderHolList();
    if(keepId){ var s=holidays.find(function(x){return x.id===keepId;}); if(s) selectHol(keepId); }
    await loadDeptLevels(parseInt(v('s_dept').value,10));
  }

  async function loadDeptLevels(deptId){
    if(!deptId){ v('s_level').innerHTML='<option value="">— all —</option>'; return; }
    if(!deptLevels[deptId]){ try{ var r=await gs.api('/api/admin/departments/levels.php?department_id='+deptId); deptLevels[deptId]=r.data||[]; }catch(e){ deptLevels[deptId]=[]; } }
    v('s_level').innerHTML='<option value="">— all —</option>'+deptLevels[deptId].map(function(l){return '<option value="'+l.id+'">'+escHtml(curLang()==='am'?(l.name_am||l.name):l.name)+'</option>';}).join('');
  }

  async function selectHol(id){
    current=holidays.find(function(x){return x.id===id;}); if(!current)return;
    v('empty').classList.add('hidden'); v('detail').classList.remove('hidden');
    v('detName').textContent=curLang()==='am'?(current.name_am||current.name):(current.name||current.name_am);
    v('detSub').textContent=(current.holiday_date||'')+(current.scale==='major'?(' · '+(curLang()==='am'?'ትልቅ በዓል':'major')):'')+(current.description?(' · '+current.description):'');
    renderHolList();
    await loadServing(id);
  }

  async function loadServing(id){
    try{ var r=await gs.api('/api/admin/holidays/serving.php?holiday_id='+id); serving=r.data||[]; renderServing(); }catch(e){gs.toast(e.message,'error');}
  }
  function renderServing(){
    v('servCount').textContent=serving.length;
    var b=v('servBody');
    if(!serving.length){ b.innerHTML='<tr><td colspan="5" class="text-center text-ink-soft py-8" data-en="No assignments yet." data-am="ምድብ የለም።">No assignments yet.</td></tr>'; return; }
    b.innerHTML=serving.map(function(s){
      return '<tr><td class="font-medium ethiopic">'+escHtml(s.department_name)+'</td>'+
        '<td class="ethiopic">'+escHtml((curLang()==='am'?(s.level_name_am||s.level_name):s.level_name)||(curLang()==='am'?'ሁሉም':'all'))+'</td>'+
        '<td>'+escHtml(s.church_name||(curLang()==='am'?'ሁለቱም':'both'))+'</td>'+
        '<td>'+(s.with_seniors==1?'<span class="pill pill-active">'+(curLang()==='am'?'አዎ':'yes')+'</span>':'—')+'</td>'+
        '<td class="text-right"><button class="btn-icon danger" data-delserv="'+s.id+'" title="Remove"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button></td></tr>';
    }).join('');
  }

  // form
  var formPanel=v('formPanel'), msg=v('formMsg');
  function showForm(item){
    formPanel.classList.remove('hidden'); msg.classList.add('hidden');
    v('f_id').value=item?item.id:''; v('f_name').value=item?(item.name||''):''; v('f_name_am').value=item?(item.name_am||''):'';
    v('f_date').value=item&&item.holiday_date?item.holiday_date:''; v('f_scale').value=item?(item.scale||'minor'):'minor';
    v('f_recurring').checked=item?(item.is_recurring_annually==1):true; v('f_desc').value=item?(item.description||''):'';
    v('formTitle').textContent=item?(curLang()==='am'?'በዓል አርትዕ':'Edit holiday'):(curLang()==='am'?'አዲስ በዓል':'New holiday');
    formPanel.scrollIntoView({behavior:'smooth',block:'center'});
  }
  v('newBtn').addEventListener('click',function(){showForm(null);});
  v('cancelBtn').addEventListener('click',function(){formPanel.classList.add('hidden');});
  v('cancelBtn2').addEventListener('click',function(){formPanel.classList.add('hidden');});
  v('detEdit').addEventListener('click',function(){if(current)showForm(current);});

  v('entityForm').addEventListener('submit', async function(e){
    e.preventDefault(); msg.classList.add('hidden');
    var id=v('f_id').value;
    var body={ name:v('f_name').value.trim(), name_am:v('f_name_am').value.trim(), holiday_date:v('f_date').value||null, scale:v('f_scale').value, is_recurring_annually:v('f_recurring').checked?1:0, description:v('f_desc').value.trim() };
    if(!body.name && !body.name_am){ msg.textContent='Name required.'; msg.classList.remove('hidden'); return; }
    try{
      if(id){ body.id=parseInt(id,10); await gs.api('/api/admin/holidays/index.php',{method:'PUT',body:JSON.stringify(body)}); }
      else await gs.api('/api/admin/holidays/index.php',{method:'POST',body:JSON.stringify(body)});
      gs.toast(id?'Updated':'Created','success'); formPanel.classList.add('hidden'); loadAll(id?parseInt(id,10):null);
    }catch(err){ msg.textContent=err.message; msg.classList.remove('hidden'); }
  });

  v('detArchive').addEventListener('click', async function(){
    if(!current)return;
    if(!await gs.confirm(curLang()==='am'?'ይህን በዓል ማህደር ውስጥ ያስገቡ?':'Archive this holiday?'))return;
    try{ await gs.api('/api/admin/holidays/index.php',{method:'DELETE',body:JSON.stringify({id:current.id})}); gs.toast('Archived','success'); current=null; v('detail').classList.add('hidden'); v('empty').classList.remove('hidden'); loadAll(null); }
    catch(err){gs.toast(err.message,'error');}
  });

  v('s_dept').addEventListener('change', function(){ loadDeptLevels(parseInt(v('s_dept').value,10)); });
  v('servForm').addEventListener('submit', async function(e){
    e.preventDefault(); if(!current)return;
    var body={ holiday_id:current.id, department_id:parseInt(v('s_dept').value,10), level_id:v('s_level').value?parseInt(v('s_level').value,10):null, church_id:v('s_church').value?parseInt(v('s_church').value,10):null, with_seniors:v('s_seniors').checked?1:0 };
    try{ await gs.api('/api/admin/holidays/serving.php',{method:'POST',body:JSON.stringify(body)}); v('s_seniors').checked=false; await loadServing(current.id); loadAll(current.id); gs.toast(curLang()==='am'?'ተመድቧል':'Assigned','success'); }
    catch(err){gs.toast(err.message,'error');}
  });

  document.addEventListener('click', async function(e){
    var hb=e.target.closest('[data-hol]'); if(hb){ selectHol(parseInt(hb.dataset.hol,10)); return; }
    var ds=e.target.closest('[data-delserv]'); if(ds){ if(!await gs.confirm(curLang()==='am'?'ይህን ምድብ ያስወግዱ?':'Remove this assignment?'))return; try{await gs.api('/api/admin/holidays/serving.php',{method:'DELETE',body:JSON.stringify({id:parseInt(ds.dataset.delserv,10)})}); await loadServing(current.id); loadAll(current.id);}catch(err){gs.toast(err.message,'error');} return; }
  });
  document.addEventListener('gs:lang-change', function(){ renderHolList(); if(current){ renderServing(); } });

  loadAll(null);
</script>

<?php require __DIR__ . '/_partials/page-shell-end.php'; ?>
