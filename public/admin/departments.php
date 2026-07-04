<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'admin') { header('Location: /'); exit; }

$page_title    = 'Departments';
$page_title_am = 'ክፍሎች';
$page_eyebrow    = 'Community';
$page_eyebrow_am = 'ማህበረሰብ';
$active_nav = 'departments';
require __DIR__ . '/_partials/page-shell.php';
?>

<div class="flex items-center justify-between flex-wrap gap-3">
  <p class="text-sm text-ink-soft max-w-xl" data-en="The school's departments (ክፍል) and their sub-departments. Pick one to manage its advancement levels and its roster of members." data-am="የትምህርት ቤቱ ክፍሎችና ንዑስ ክፍሎች። የእድገት ደረጃዎቹንና አባላቱን ለማስተዳደር አንዱን ይምረጡ።">The school's departments (ክፍል) and their sub-departments. Pick one to manage its advancement levels and its roster of members.</p>
  <button id="newDeptBtn" class="btn-primary">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
    <span data-en="New department" data-am="አዲስ ክፍል">New department</span>
  </button>
</div>

<!-- New/Edit department form -->
<section id="deptForm" class="panel hidden">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 id="deptFormTitle" class="font-display text-lg text-ink">New department</h2>
    <button type="button" id="deptCancel" class="btn-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
  </header>
  <form id="deptEntityForm" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
    <input type="hidden" id="d_id" />
    <div><label class="lbl" data-en="Name (English)" data-am="ስም (እንግሊዝኛ)">Name (English)</label><input id="d_name" type="text" class="input-field" /></div>
    <div><label class="lbl" data-en="Name (Amharic)" data-am="ስም (አማርኛ)">Name (Amharic)</label><input id="d_name_am" type="text" class="input-field ethiopic" /></div>
    <div>
      <label class="lbl" data-en="Parent department" data-am="ዋና ክፍል">Parent department</label>
      <select id="d_parent" class="input-field"><option value="" data-en="— None (top level)" data-am="— የለም (ዋና)">— None (top level)</option></select>
    </div>
    <div><label class="lbl" data-en="Sort order" data-am="ቅደም ተከተል">Sort order</label><input id="d_sort" type="number" class="input-field" value="0" /></div>
    <div class="md:col-span-2"><label class="lbl" data-en="Description" data-am="መግለጫ">Description</label><textarea id="d_desc" rows="2" class="input-field"></textarea></div>
    <div class="md:col-span-2 flex items-center gap-3">
      <button type="submit" class="btn-primary" data-en="Save" data-am="አስቀምጥ">Save</button>
      <button type="button" id="deptCancel2" class="btn-ghost" data-en="Cancel" data-am="ሰርዝ">Cancel</button>
      <p id="deptFormMsg" class="text-sm hidden"></p>
    </div>
  </form>
</section>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <!-- LEFT: department list -->
  <section class="panel lg:col-span-1 self-start">
    <header class="px-5 py-4 border-b border-outline-soft/40">
      <h2 class="font-display text-base text-ink" data-en="All departments" data-am="ሁሉም ክፍሎች">All departments</h2>
    </header>
    <div id="deptList" class="p-3 space-y-1 max-h-[70vh] overflow-y-auto">
      <p class="text-center text-ink-soft py-8 text-sm">Loading…</p>
    </div>
  </section>

  <!-- RIGHT: detail -->
  <section class="lg:col-span-2 space-y-6">
    <div id="detailEmpty" class="panel p-12 text-center text-ink-soft">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" class="mx-auto mb-3 opacity-40"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      <p data-en="Select a department to manage its levels and members." data-am="ደረጃዎቹንና አባላቱን ለማስተዳደር ክፍል ይምረጡ።">Select a department to manage its levels and members.</p>
    </div>

    <div id="detail" class="hidden space-y-6">
      <!-- Header -->
      <div class="panel px-6 py-5 flex items-center justify-between gap-3">
        <div>
          <h2 id="detName" class="font-display text-xl text-ink">—</h2>
          <p id="detSub" class="text-sm text-ink-soft mt-1"></p>
        </div>
        <div class="flex items-center gap-1">
          <button id="detEdit" class="btn-ghost" data-en="Edit" data-am="አርትዕ">Edit</button>
          <button id="detArchive" class="btn-icon danger" title="Archive"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/></svg></button>
        </div>
      </div>

      <!-- Levels -->
      <div class="panel">
        <header class="px-6 py-4 border-b border-outline-soft/40 flex items-center justify-between">
          <h3 class="font-display text-base text-ink"><span data-en="Advancement levels" data-am="የእድገት ደረጃዎች">Advancement levels</span> · <span id="lvlCount" class="text-ink-soft text-sm">—</span></h3>
        </header>
        <div class="p-4">
          <p class="text-xs text-ink-soft mb-3" data-en="Levels are ordered by rank (1 = most senior). Used by departments like the choir." data-am="ደረጃዎች በደረጃ ቅደም ተከተል (1 = ከፍተኛ)። እንደ መዝሙር ክፍል ይጠቀማሉ።">Levels are ordered by rank (1 = most senior). Used by departments like the choir.</p>
          <div id="levelList" class="space-y-2 mb-4"></div>
          <form id="levelForm" class="flex flex-wrap items-end gap-2 bg-surface-low rounded p-3 border border-outline-soft/30">
            <div class="flex-1 min-w-[120px]"><label class="lbl" data-en="Name (EN)" data-am="ስም">Name (EN)</label><input id="lvl_name" class="input-field" /></div>
            <div class="flex-1 min-w-[120px]"><label class="lbl" data-en="Name (AM)" data-am="ስም (አማ)">Name (AM)</label><input id="lvl_name_am" class="input-field ethiopic" /></div>
            <div class="w-24"><label class="lbl" data-en="Rank" data-am="ደረጃ">Rank</label><input id="lvl_rank" type="number" class="input-field" value="0" /></div>
            <button type="submit" class="btn-ghost" data-en="Add level" data-am="ደረጃ ጨምር">Add level</button>
          </form>
        </div>
      </div>

      <!-- Roster -->
      <div class="panel">
        <header class="px-6 py-4 border-b border-outline-soft/40 flex items-center justify-between">
          <h3 class="font-display text-base text-ink"><span data-en="Members" data-am="አባላት">Members</span> · <span id="memCount" class="text-ink-soft text-sm">—</span></h3>
        </header>
        <div class="p-4">
          <form id="addMemberForm" class="flex flex-wrap items-end gap-2 bg-surface-low rounded p-3 border border-outline-soft/30 mb-4">
            <div class="flex-1 min-w-[180px]"><label class="lbl" data-en="Add person" data-am="ሰው ጨምር">Add person</label><select id="am_person" class="input-field"><option value="">—</option></select></div>
            <div class="min-w-[140px]"><label class="lbl" data-en="Level" data-am="ደረጃ">Level</label><select id="am_level" class="input-field"><option value="">—</option></select></div>
            <div class="min-w-[120px]"><label class="lbl" data-en="Title" data-am="ሚና">Title</label><input id="am_title" class="input-field" placeholder="member" /></div>
            <button type="submit" class="btn-primary" data-en="Add" data-am="ጨምር">Add</button>
          </form>
          <div class="table-wrap">
            <table class="data">
              <thead><tr>
                <th data-en="Name" data-am="ስም">Name</th>
                <th data-en="Level" data-am="ደረጃ">Level</th>
                <th data-en="Title" data-am="ሚና">Title</th>
                <th data-en="Head" data-am="ሃላፊ">Head</th>
                <th class="text-right">&nbsp;</th>
              </tr></thead>
              <tbody id="memBody"><tr><td colspan="5" class="text-center text-ink-soft py-10" data-en="No members yet." data-am="እስካሁን አባል የለም።">No members yet.</td></tr></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<style>.lbl{display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.18em;color:#3f4658;margin-bottom:6px;}</style>

<script>
  var depts = [], people = [], current = null, levels = [], roster = [];
  function escHtml(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];});}
  function curLang(){ return document.documentElement.getAttribute('data-lang') || 'en'; }
  function dlabel(d){ return curLang()==='am' ? (d.name_am||d.name) : (d.name||d.name_am); }
  function v(id){return document.getElementById(id);}

  // ---- Department list (tree) ----
  function renderDeptList() {
    var wrap = v('deptList');
    if (!depts.length) { wrap.innerHTML = '<p class="text-center text-ink-soft py-8 text-sm">No departments.</p>'; return; }
    var tops = depts.filter(function(d){return !d.parent_id;});
    wrap.innerHTML = tops.map(function(d){
      var subs = depts.filter(function(s){return s.parent_id===d.id;});
      return deptRow(d, false) + subs.map(function(s){return deptRow(s, true);}).join('');
    }).join('');
  }
  function deptRow(d, isSub) {
    var active = current && current.id===d.id;
    return '<button class="w-full text-left nav-item '+(active?'active':'')+'" '+(isSub?'style="padding-left:28px"':'')+' data-dept="'+d.id+'">' +
      '<span class="flex-1 '+(curLang()==='am'?'ethiopic':'')+'">'+(isSub?'↳ ':'')+escHtml(dlabel(d))+'</span>' +
      '<span class="text-[11px] text-ink-soft">'+d.member_count+'</span>' +
    '</button>';
  }

  function fillParentSelect(excludeId) {
    var sel = v('d_parent');
    var tops = depts.filter(function(d){return !d.parent_id && d.id!==excludeId;});
    sel.innerHTML = '<option value="">— None (top level)</option>' + tops.map(function(d){ return '<option value="'+d.id+'">'+escHtml(d.name_am||d.name)+'</option>'; }).join('');
  }

  // ---- Detail ----
  async function selectDept(id) {
    current = depts.find(function(d){return d.id===id;});
    if (!current) return;
    v('detailEmpty').classList.add('hidden');
    v('detail').classList.remove('hidden');
    v('detName').textContent = dlabel(current);
    var parent = current.parent_id ? depts.find(function(d){return d.id===current.parent_id;}) : null;
    v('detSub').textContent = (parent ? ((curLang()==='am'?'ንዑስ ክፍል · ':'Sub-department of ')+dlabel(parent)+' · ') : '') + (current.description||'');
    renderDeptList();
    await Promise.all([loadLevels(id), loadRoster(id)]);
  }

  async function loadLevels(id) {
    try { var r = await gs.api('/api/admin/departments/levels.php?department_id='+id); levels = r.data||[]; renderLevels(); fillLevelSelect(); }
    catch(e){ gs.toast(e.message,'error'); }
  }
  function renderLevels() {
    v('lvlCount').textContent = levels.length;
    var wrap = v('levelList');
    if (!levels.length) { wrap.innerHTML = '<p class="text-sm text-ink-soft" data-en="No levels — this department doesn\'t use an advancement ladder." data-am="ደረጃ የለም።">No levels — this department doesn\'t use an advancement ladder.</p>'; return; }
    wrap.innerHTML = levels.map(function(l){
      return '<div class="flex items-center justify-between gap-2 bg-surface-low rounded px-3 py-2 border border-outline-soft/30">' +
        '<div class="flex items-center gap-3"><span class="avatar-circle bg-primary/10 text-primary text-xs" style="width:26px;height:26px">'+l.rank+'</span>' +
        '<span class="text-sm '+(curLang()==='am'?'ethiopic':'')+'">'+escHtml(curLang()==='am'?(l.name_am||l.name):l.name)+'</span>' +
        '<span class="text-xs text-ink-soft">'+l.member_count+' '+(curLang()==='am'?'አባላት':'members')+'</span></div>' +
        '<button class="btn-icon danger" title="Remove level" data-dellevel="'+l.id+'"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>' +
      '</div>';
    }).join('');
  }
  function fillLevelSelect() {
    var none = curLang()==='am'?'— ደረጃ የለም':'— No level';
    var opts = '<option value="">'+none+'</option>' + levels.map(function(l){ return '<option value="'+l.id+'">'+escHtml((curLang()==='am'?(l.name_am||l.name):l.name))+'</option>'; }).join('');
    v('am_level').innerHTML = opts;
  }

  async function loadRoster(id) {
    try { var r = await gs.api('/api/admin/departments/members.php?department_id='+id); roster = r.data||[]; renderRoster(); fillPersonSelect(); }
    catch(e){ gs.toast(e.message,'error'); }
  }
  function renderRoster() {
    v('memCount').textContent = roster.length;
    var tbody = v('memBody');
    if (!roster.length) { tbody.innerHTML = '<tr><td colspan="5" class="text-center text-ink-soft py-10" data-en="No members yet." data-am="እስካሁን አባል የለም።">No members yet.</td></tr>'; return; }
    tbody.innerHTML = roster.map(function(m){
      var lvlOpts = '<option value="">—</option>' + levels.map(function(l){ return '<option value="'+l.id+'" '+(m.level_id===l.id?'selected':'')+'>'+escHtml(curLang()==='am'?(l.name_am||l.name):l.name)+'</option>'; }).join('');
      return '<tr>' +
        '<td><p class="font-medium">'+escHtml(m.person_name)+'</p>'+(m.phone?'<p class="text-xs text-ink-soft">'+escHtml(m.phone)+'</p>':'')+'</td>' +
        '<td><select class="input-field py-1.5 text-sm" data-setlevel="'+m.id+'" '+(levels.length?'':'disabled')+'>'+lvlOpts+'</select></td>' +
        '<td><input class="input-field py-1.5 text-sm" value="'+escHtml(m.title||'')+'" data-settitle="'+m.id+'" style="max-width:140px" /></td>' +
        '<td><button class="pill '+(m.is_head?'pill-active':'pill-archived')+'" data-toghead="'+m.id+'" data-val="'+(m.is_head?1:0)+'">'+(m.is_head?(curLang()==='am'?'ሃላፊ':'Head'):'—')+'</button></td>' +
        '<td class="text-right"><button class="btn-icon danger" title="Remove" data-delmem="'+m.id+'"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/></svg></button></td>' +
      '</tr>';
    }).join('');
  }
  function fillPersonSelect() {
    var inRoster = roster.map(function(m){return m.person_id;});
    var avail = people.filter(function(p){ return inRoster.indexOf(p.id)<0; });
    v('am_person').innerHTML = '<option value="">—</option>' + avail.map(function(p){
      return '<option value="'+p.id+'">'+escHtml(p.first_name+' '+p.last_name)+(p.phone?(' · '+escHtml(p.phone)):'')+'</option>';
    }).join('');
  }

  async function reloadDepts(keepId) {
    var r = await gs.api('/api/admin/departments/index.php');
    depts = r.data || [];
    renderDeptList();
    if (keepId) { var still = depts.find(function(d){return d.id===keepId;}); if (still) selectDept(keepId); else { current=null; v('detail').classList.add('hidden'); v('detailEmpty').classList.remove('hidden'); } }
  }

  // ---- New/Edit department form ----
  var deptFormEl = v('deptForm'), dmsg = v('deptFormMsg');
  function showDeptForm(item) {
    deptFormEl.classList.remove('hidden'); dmsg.className='text-sm hidden';
    fillParentSelect(item ? item.id : 0);
    v('d_id').value = item?item.id:''; v('d_name').value = item?(item.name||''):''; v('d_name_am').value = item?(item.name_am||''):'';
    v('d_parent').value = item&&item.parent_id?item.parent_id:''; v('d_sort').value = item?item.sort_order:0; v('d_desc').value = item?(item.description||''):'';
    v('deptFormTitle').textContent = item?(curLang()==='am'?'ክፍል አርትዕ':'Edit department'):(curLang()==='am'?'አዲስ ክፍል':'New department');
    deptFormEl.scrollIntoView({behavior:'smooth',block:'center'});
  }
  v('newDeptBtn').addEventListener('click', function(){ showDeptForm(null); });
  v('deptCancel').addEventListener('click', function(){ deptFormEl.classList.add('hidden'); });
  v('deptCancel2').addEventListener('click', function(){ deptFormEl.classList.add('hidden'); });
  v('detEdit').addEventListener('click', function(){ if(current) showDeptForm(current); });

  v('deptEntityForm').addEventListener('submit', async function(e){
    e.preventDefault(); dmsg.className='text-sm hidden';
    var id = v('d_id').value;
    var body = { name: v('d_name').value.trim(), name_am: v('d_name_am').value.trim(), parent_id: v('d_parent').value?parseInt(v('d_parent').value,10):null, sort_order: parseInt(v('d_sort').value||'0',10), description: v('d_desc').value.trim() };
    if (!body.name && !body.name_am) { dmsg.className='text-sm text-error'; dmsg.textContent='Name is required.'; return; }
    try {
      if (id) { body.id=parseInt(id,10); await gs.api('/api/admin/departments/index.php',{method:'PUT',body:JSON.stringify(body)}); gs.toast('Updated','success'); }
      else { await gs.api('/api/admin/departments/index.php',{method:'POST',body:JSON.stringify(body)}); gs.toast('Created','success'); }
      deptFormEl.classList.add('hidden');
      await reloadDepts(id?parseInt(id,10):null);
    } catch(err){ dmsg.className='text-sm text-error'; dmsg.textContent=err.message; }
  });

  v('detArchive').addEventListener('click', async function(){
    if (!current) return;
    if (!await gs.confirm(curLang()==='am'?'ይህን ክፍል ማህደር ውስጥ ያስገቡ?':'Archive this department? Its members will be unassigned.')) return;
    try { await gs.api('/api/admin/departments/index.php',{method:'DELETE',body:JSON.stringify({id:current.id})}); gs.toast('Archived','success'); var was=current.id; current=null; v('detail').classList.add('hidden'); v('detailEmpty').classList.remove('hidden'); await reloadDepts(null); }
    catch(err){ gs.toast(err.message,'error'); }
  });

  // ---- Level add ----
  v('levelForm').addEventListener('submit', async function(e){
    e.preventDefault(); if(!current) return;
    var body = { department_id: current.id, name: v('lvl_name').value.trim(), name_am: v('lvl_name_am').value.trim(), rank: parseInt(v('lvl_rank').value||'0',10) };
    if (!body.name) { gs.toast(curLang()==='am'?'ስም ያስፈልጋል':'Name required','error'); return; }
    try { await gs.api('/api/admin/departments/levels.php',{method:'POST',body:JSON.stringify(body)}); v('lvl_name').value='';v('lvl_name_am').value='';v('lvl_rank').value='0'; await loadLevels(current.id); await reloadDepts(current.id); }
    catch(err){ gs.toast(err.message,'error'); }
  });

  // ---- Add member ----
  v('addMemberForm').addEventListener('submit', async function(e){
    e.preventDefault(); if(!current) return;
    var pid = v('am_person').value; if(!pid){ gs.toast(curLang()==='am'?'ሰው ይምረጡ':'Pick a person','error'); return; }
    var body = { person_id: parseInt(pid,10), department_id: current.id, level_id: v('am_level').value?parseInt(v('am_level').value,10):null, title: v('am_title').value.trim() };
    try { await gs.api('/api/admin/departments/members.php',{method:'POST',body:JSON.stringify(body)}); v('am_person').value='';v('am_level').value='';v('am_title').value=''; await loadRoster(current.id); await reloadDepts(current.id); gs.toast(curLang()==='am'?'ተጨምሯል':'Added','success'); }
    catch(err){ gs.toast(err.message,'error'); }
  });

  // ---- Delegated clicks/changes ----
  document.addEventListener('click', async function(e){
    var dl = e.target.closest('[data-dept]'); if (dl) { selectDept(parseInt(dl.dataset.dept,10)); return; }
    var rm = e.target.closest('[data-delmem]'); if (rm) { if(!await gs.confirm(curLang()==='am'?'ከክፍሉ ያስወግዱ?':'Remove from this department?'))return; try{await gs.api('/api/admin/departments/members.php',{method:'DELETE',body:JSON.stringify({id:parseInt(rm.dataset.delmem,10)})});await loadRoster(current.id);await reloadDepts(current.id);}catch(err){gs.toast(err.message,'error');} return; }
    var rl = e.target.closest('[data-dellevel]'); if (rl) { if(!await gs.confirm(curLang()==='am'?'ይህን ደረጃ ያስወግዱ?':'Remove this level?'))return; try{await gs.api('/api/admin/departments/levels.php',{method:'DELETE',body:JSON.stringify({id:parseInt(rl.dataset.dellevel,10)})});await loadLevels(current.id);await loadRoster(current.id);}catch(err){gs.toast(err.message,'error');} return; }
    var th = e.target.closest('[data-toghead]'); if (th) { var nv = th.dataset.val==='1'?0:1; try{await gs.api('/api/admin/departments/members.php',{method:'PUT',body:JSON.stringify({id:parseInt(th.dataset.toghead,10),is_head:nv})});await loadRoster(current.id);}catch(err){gs.toast(err.message,'error');} return; }
  });
  document.addEventListener('change', async function(e){
    var sl = e.target.closest('[data-setlevel]'); if (sl) { try{await gs.api('/api/admin/departments/members.php',{method:'PUT',body:JSON.stringify({id:parseInt(sl.dataset.setlevel,10),level_id:sl.value?parseInt(sl.value,10):null})});await loadRoster(current.id);await loadLevels(current.id);}catch(err){gs.toast(err.message,'error');} }
  });
  document.addEventListener('blur', async function(e){
    var ti = e.target.closest('[data-settitle]'); if (ti) { try{await gs.api('/api/admin/departments/members.php',{method:'PUT',body:JSON.stringify({id:parseInt(ti.dataset.settitle,10),title:ti.value.trim()})});}catch(err){gs.toast(err.message,'error');} }
  }, true);

  document.addEventListener('gs:lang-change', function(){ renderDeptList(); if(current){ renderLevels(); fillLevelSelect(); renderRoster(); } });

  // Init
  (async function(){
    try {
      var [d, p] = await Promise.all([ gs.api('/api/admin/departments/index.php'), gs.api('/api/admin/people/index.php') ]);
      depts = d.data || []; people = p.data || [];
      renderDeptList();
    } catch(e){ gs.toast(e.message,'error'); }
  })();
</script>

<?php require __DIR__ . '/_partials/page-shell-end.php'; ?>
