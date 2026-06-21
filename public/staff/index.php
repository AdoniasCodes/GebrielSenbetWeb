<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
$role = $_SESSION['role_name'] ?? null;
if ($role !== 'staff' && $role !== 'admin') { header('Location: /'); exit; }
$initials = strtoupper(substr($_SESSION['user_email'] ?? 'GS', 0, 2));
?>
<!DOCTYPE html>
<html lang="en" data-lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Department · Mekane Selam Senbet School</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,400..700&family=Plus+Jakarta+Sans:wght@400..700&family=Noto+Sans+Ethiopic:wght@400;500;700&family=Noto+Serif+Ethiopic:wght@400;600;700&display=swap" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <script>
    tailwind.config = { theme: { extend: {
      colors: { surface:'#fcf9f2','surface-low':'#f6f3ec','surface-mid':'#f0eee7','surface-high':'#ebe8e1', ink:'#1c1c18','ink-soft':'#564242', outline:'#897172','outline-soft':'#dcc0c0', primary:'#5b0617','primary-soft':'#7a1f2b', gold:'#795901','gold-soft':'#c9a14a','gold-warm':'#fed175', olive:'#384700','olive-soft':'#a2b665', error:'#ba1a1a' },
      fontFamily: { display:['Newsreader','"Noto Serif Ethiopic"','serif'], body:['"Plus Jakarta Sans"','"Noto Sans Ethiopic"','system-ui','sans-serif'], ethiopic:['"Noto Sans Ethiopic"','serif'] },
      letterSpacing:{ widestest:'0.18em' } } } };
  </script>
  <script>
    // shared gs.* helpers (defined early so page scripts can use them)
    window.gs = {};
    gs.ensureCsrf = async function(){ var t=sessionStorage.getItem('csrf_token'); if(!t){ var r=await fetch('/api/auth/csrf.php'); t=(await r.json()).csrf_token; sessionStorage.setItem('csrf_token',t);} return t; };
    gs.api = async function(url,opts){ opts=opts||{}; opts.headers=opts.headers||{}; if(opts.body&&!opts.headers['Content-Type'])opts.headers['Content-Type']='application/json'; if(['POST','PUT','PATCH','DELETE'].indexOf((opts.method||'GET').toUpperCase())>=0)opts.headers['X-CSRF-Token']=await gs.ensureCsrf(); var res=await fetch(url,opts); var d; try{d=await res.json();}catch(e){d={};} if(!res.ok)throw new Error(d.error||('HTTP '+res.status)); return d; };
    gs.toast = function(msg,type){ var bg={info:'#384700',error:'#ba1a1a',success:'#384700'}[type]||'#384700'; var t=document.createElement('div'); t.style.cssText='position:fixed;bottom:24px;right:24px;background:'+bg+';color:#fff;padding:14px 20px;border-radius:6px;z-index:9999;font-size:14px;'; t.textContent=msg; document.body.appendChild(t); setTimeout(function(){t.remove();},3000); };
    gs.confirm = function(m){ return Promise.resolve(window.confirm(m)); };
  </script>
  <style>
    html,body{background:#fcf9f2;} .font-display{font-family:'Newsreader','Noto Serif Ethiopic',serif;} .ethiopic{font-family:'Noto Sans Ethiopic',serif;}
    .seg-active{background:#fed175;color:#5b0617;}
    .panel{background:#fff;border:1px solid rgba(220,192,192,0.4);border-radius:8px;}
    .pill{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:9999px;font-size:11px;font-weight:600;}
    .pill-active{background:rgba(56,71,0,0.10);color:#384700;} .pill-archived{background:rgba(137,113,114,0.15);color:#564242;}
    .input-field{width:100%;padding:9px 12px;background:#fff;border:1px solid rgba(137,113,114,0.25);border-radius:4px;font-size:14px;color:#1c1c18;}
    .input-field:focus{outline:none;border-color:#c9a14a;box-shadow:0 0 0 3px rgba(201,161,74,0.12);}
    .btn-primary{display:inline-flex;align-items:center;gap:8px;background:#5b0617;color:#fcf9f2;padding:9px 16px;border-radius:4px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.18em;cursor:pointer;}
    .btn-primary:hover{background:#7a1f2b;}
    .btn-ghost{display:inline-flex;align-items:center;gap:8px;background:#f0eee7;color:#5b0617;padding:9px 16px;border:1px solid rgba(220,192,192,0.5);border-radius:4px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.18em;cursor:pointer;}
    .btn-icon{width:30px;height:30px;border-radius:4px;display:inline-flex;align-items:center;justify-content:center;color:#564242;}
    .btn-icon:hover{background:#f0eee7;color:#5b0617;} .btn-icon.danger:hover{background:rgba(186,26,26,0.1);color:#ba1a1a;}
    .nav-item{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 14px;border-radius:4px;color:#564242;font-size:14px;cursor:pointer;width:100%;text-align:left;}
    .nav-item:hover{background:rgba(91,6,23,0.04);color:#5b0617;} .nav-item.active{color:#5b0617;font-weight:600;background:rgba(91,6,23,0.06);}
    table.data{width:100%;border-collapse:collapse;font-size:14px;} table.data thead th{padding:10px 16px;text-align:left;font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.18em;color:#564242;border-bottom:1px solid rgba(220,192,192,0.4);background:#f6f3ec;}
    table.data tbody td{padding:12px 16px;border-bottom:1px solid rgba(220,192,192,0.3);}
  </style>
</head>
<body class="font-body text-ink">
<div class="max-w-6xl mx-auto px-6 py-8">
  <header class="flex items-center justify-between mb-8">
    <div>
      <p class="text-[10px] font-semibold uppercase tracking-widestest text-gold mb-1" data-en="Department Head" data-am="የክፍል ሃላፊ">Department Head</p>
      <h1 class="font-display text-2xl text-primary" data-en="My Departments" data-am="የእኔ ክፍሎች">My Departments</h1>
    </div>
    <div class="flex items-center gap-3">
      <div class="flex items-center bg-surface-mid rounded-full p-0.5 border border-outline-soft/50">
        <button data-lang="en" class="seg-active px-3 py-1 text-xs font-semibold rounded-full">EN</button>
        <button data-lang="am" class="px-3 py-1 text-xs font-semibold rounded-full text-ink-soft ethiopic">አማ</button>
      </div>
      <?php if ($role === 'admin'): ?><a href="/admin/index.php" class="btn-ghost" data-en="Admin" data-am="አስተዳዳሪ">Admin</a><?php endif; ?>
      <button id="logoutBtn" class="btn-ghost" data-en="Sign out" data-am="ውጣ">Sign out</button>
    </div>
  </header>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <section class="panel lg:col-span-1 self-start">
      <header class="px-5 py-4 border-b border-outline-soft/40"><h2 class="font-display text-base" data-en="Departments I lead" data-am="የምመራቸው ክፍሎች">Departments I lead</h2></header>
      <div id="deptList" class="p-3 space-y-1"><p class="text-center text-ink-soft py-8 text-sm">Loading…</p></div>
    </section>

    <section class="lg:col-span-2 space-y-6">
      <div id="empty" class="panel p-12 text-center text-ink-soft" data-en="Select a department to manage its members and levels." data-am="አባላትና ደረጃዎችን ለማስተዳደር ክፍል ይምረጡ።">Select a department to manage its members and levels.</div>
      <div id="detail" class="hidden space-y-6">
        <div class="panel px-6 py-5"><h2 id="detName" class="font-display text-xl text-ink">—</h2><p id="detSub" class="text-sm text-ink-soft mt-1"></p></div>

        <div class="panel">
          <header class="px-6 py-4 border-b border-outline-soft/40 flex items-center justify-between"><h3 class="font-display text-base"><span data-en="Levels" data-am="ደረጃዎች">Levels</span> · <span id="lvlCount" class="text-ink-soft text-sm">—</span></h3></header>
          <div class="p-4">
            <div id="levelList" class="space-y-2 mb-3"></div>
            <form id="levelForm" class="flex flex-wrap items-end gap-2 bg-surface-low rounded p-3 border border-outline-soft/30">
              <div class="flex-1 min-w-[110px]"><label class="lbl" data-en="Name" data-am="ስም">Name</label><input id="lvl_name" class="input-field" /></div>
              <div class="flex-1 min-w-[110px]"><label class="lbl" data-en="Amharic" data-am="አማርኛ">Amharic</label><input id="lvl_name_am" class="input-field ethiopic" /></div>
              <div class="w-20"><label class="lbl" data-en="Rank" data-am="ደረጃ">Rank</label><input id="lvl_rank" type="number" class="input-field" value="0" /></div>
              <button type="submit" class="btn-ghost" data-en="Add" data-am="ጨምር">Add</button>
            </form>
          </div>
        </div>

        <div class="panel">
          <header class="px-6 py-4 border-b border-outline-soft/40 flex items-center justify-between"><h3 class="font-display text-base"><span data-en="Serving eligibility" data-am="የአገልግሎት ብቁነት">Serving eligibility</span> · <span id="eligNote" class="text-ink-soft text-sm"></span></h3></header>
          <div class="p-4">
            <div style="overflow-x:auto"><table class="data">
              <thead><tr><th data-en="Name" data-am="ስም">Name</th><th data-en="Level" data-am="ደረጃ">Level</th><th data-en="Attendance" data-am="መገኘት">Attendance</th><th data-en="Serving" data-am="አገልግሎት">Serving</th></tr></thead>
              <tbody id="eligBody"><tr><td colspan="4" class="text-center text-ink-soft py-6">—</td></tr></tbody>
            </table></div>
          </div>
        </div>

        <div class="panel">
          <header class="px-6 py-4 border-b border-outline-soft/40 flex items-center justify-between"><h3 class="font-display text-base"><span data-en="Members" data-am="አባላት">Members</span> · <span id="memCount" class="text-ink-soft text-sm">—</span></h3></header>
          <div class="p-4">
            <form id="addMemberForm" class="flex flex-wrap items-end gap-2 bg-surface-low rounded p-3 border border-outline-soft/30 mb-4">
              <div class="flex-1 min-w-[180px]"><label class="lbl" data-en="Add person" data-am="ሰው ጨምር">Add person</label><select id="am_person" class="input-field"><option value="">—</option></select></div>
              <div class="min-w-[130px]"><label class="lbl" data-en="Level" data-am="ደረጃ">Level</label><select id="am_level" class="input-field"><option value="">—</option></select></div>
              <div class="min-w-[110px]"><label class="lbl" data-en="Title" data-am="ሚና">Title</label><input id="am_title" class="input-field" /></div>
              <button type="submit" class="btn-primary" data-en="Add" data-am="ጨምር">Add</button>
            </form>
            <div style="overflow-x:auto"><table class="data">
              <thead><tr><th data-en="Name" data-am="ስም">Name</th><th data-en="Level" data-am="ደረጃ">Level</th><th data-en="Title" data-am="ሚና">Title</th><th class="text-right">&nbsp;</th></tr></thead>
              <tbody id="memBody"><tr><td colspan="4" class="text-center text-ink-soft py-8" data-en="No members yet." data-am="አባል የለም።">No members yet.</td></tr></tbody>
            </table></div>
          </div>
        </div>
      </div>
    </section>
  </div>
</div>

<style>.lbl{display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.18em;color:#564242;margin-bottom:6px;}</style>

<script>
  var depts=[], people=[], current=null, levels=[], roster=[];
  function escHtml(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];});}
  function curLang(){ return document.documentElement.getAttribute('data-lang')||'en'; }
  function dlabel(d){ return curLang()==='am'?(d.name_am||d.name):(d.name||d.name_am); }
  function v(id){return document.getElementById(id);}

  function applyLang(lang){ if(lang!=='en'&&lang!=='am')lang='en'; document.documentElement.setAttribute('data-lang',lang);
    document.querySelectorAll('[data-en],[data-am]').forEach(function(el){ var t=el.getAttribute('data-'+lang); if(t!==null)el.innerHTML=t; });
    document.querySelectorAll('[data-lang]').forEach(function(b){ if(b.tagName==='BUTTON'){ b.classList.toggle('seg-active',b.dataset.lang===lang); } });
    renderDeptList(); if(current){ renderLevels(); fillLevelSelect(); renderRoster(); renderEligibility(); }
  }
  document.querySelectorAll('header [data-lang]').forEach(function(b){ b.addEventListener('click',function(){applyLang(b.dataset.lang);}); });

  function renderDeptList(){
    var w=v('deptList');
    if(!depts.length){ w.innerHTML='<p class="text-center text-ink-soft py-8 text-sm" data-en="You don\'t lead any department yet." data-am="እስካሁን የሚመሩት ክፍል የለም።">You don\'t lead any department yet.</p>'; return; }
    w.innerHTML=depts.map(function(d){ return '<button class="nav-item '+(current&&current.id===d.id?'active':'')+'" data-dept="'+d.id+'"><span class="'+(curLang()==='am'?'ethiopic':'')+'">'+escHtml(dlabel(d))+'</span><span class="text-[11px] text-ink-soft">'+d.member_count+'</span></button>'; }).join('');
  }

  async function selectDept(id){
    current=depts.find(function(d){return d.id===id;}); if(!current)return;
    v('empty').classList.add('hidden'); v('detail').classList.remove('hidden');
    v('detName').textContent=dlabel(current); v('detSub').textContent=current.description||'';
    renderDeptList();
    await Promise.all([loadLevels(id), loadRoster(id), loadEligibility(id)]);
  }

  var eligibility=null;
  async function loadEligibility(id){ try{ var r=await gs.api('/api/staff/eligibility.php?department_id='+id); eligibility=r; renderEligibility(); }catch(e){ eligibility=null; renderEligibility(); } }
  function renderEligibility(){
    var b=v('eligBody'); if(!b) return;
    if(!eligibility){ b.innerHTML='<tr><td colspan="4" class="text-center text-ink-soft py-6">—</td></tr>'; v('eligNote').textContent=''; return; }
    var m=eligibility.members||[];
    v('eligNote').textContent=(curLang()==='am'?'ዝቅተኛ መገኘት ':'min ')+eligibility.threshold+'%';
    if(!m.length){ b.innerHTML='<tr><td colspan="4" class="text-center text-ink-soft py-6" data-en="No members." data-am="አባል የለም።">No members.</td></tr>'; return; }
    b.innerHTML=m.map(function(x){
      var lvl=curLang()==='am'?(x.level_name_am||x.level_name):x.level_name;
      var serving;
      if(!x.has_data){ serving='<span class="pill pill-archived">'+(curLang()==='am'?'መረጃ የለም':'no data')+'</span>'; }
      else if(x.eligible){ serving='<span class="pill pill-active">'+(curLang()==='am'?'ብቁ':'eligible')+'</span>'; }
      else { serving='<span class="pill" style="background:rgba(186,26,26,0.1);color:#ba1a1a">'+(curLang()==='am'?'ብቁ አይደለም':'not eligible')+'</span>'; }
      var rate=x.has_data?(x.rate+'% ('+x.attended+'/'+x.total+')'):'—';
      return '<tr><td class="font-medium">'+escHtml(x.name)+'</td><td class="ethiopic text-ink-soft">'+escHtml(lvl||'—')+'</td><td class="text-ink-soft text-sm">'+rate+'</td><td>'+serving+'</td></tr>';
    }).join('');
  }

  async function loadLevels(id){ try{ var r=await gs.api('/api/staff/levels.php?department_id='+id); levels=r.data||[]; renderLevels(); fillLevelSelect(); }catch(e){gs.toast(e.message,'error');} }
  function renderLevels(){ v('lvlCount').textContent=levels.length; var w=v('levelList');
    if(!levels.length){ w.innerHTML='<p class="text-sm text-ink-soft" data-en="No levels." data-am="ደረጃ የለም።">No levels.</p>'; return; }
    w.innerHTML=levels.map(function(l){ return '<div class="flex items-center justify-between gap-2 bg-surface-low rounded px-3 py-2 border border-outline-soft/30"><span class="text-sm '+(curLang()==='am'?'ethiopic':'')+'">#'+l.rank+' '+escHtml(curLang()==='am'?(l.name_am||l.name):l.name)+' <span class="text-xs text-ink-soft">('+l.member_count+')</span></span><button class="btn-icon danger" data-dellevel="'+l.id+'" title="Remove"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button></div>'; }).join('');
  }
  function fillLevelSelect(){ v('am_level').innerHTML='<option value="">'+(curLang()==='am'?'— ደረጃ የለም':'— No level')+'</option>'+levels.map(function(l){return '<option value="'+l.id+'">'+escHtml(curLang()==='am'?(l.name_am||l.name):l.name)+'</option>';}).join(''); }

  async function loadRoster(id){ try{ var r=await gs.api('/api/staff/members.php?department_id='+id); roster=r.data||[]; renderRoster(); fillPersonSelect(); }catch(e){gs.toast(e.message,'error');} }
  function renderRoster(){ v('memCount').textContent=roster.length; var b=v('memBody');
    if(!roster.length){ b.innerHTML='<tr><td colspan="4" class="text-center text-ink-soft py-8" data-en="No members yet." data-am="አባል የለም።">No members yet.</td></tr>'; return; }
    b.innerHTML=roster.map(function(m){ var lo='<option value="">—</option>'+levels.map(function(l){return '<option value="'+l.id+'" '+(m.level_id===l.id?'selected':'')+'>'+escHtml(curLang()==='am'?(l.name_am||l.name):l.name)+'</option>';}).join('');
      return '<tr><td><span class="font-medium">'+escHtml(m.person_name)+'</span>'+(m.is_head?' <span class="pill pill-active">'+(curLang()==='am'?'ሃላፊ':'Head')+'</span>':'')+'</td>'+
        '<td><select class="input-field" style="padding:5px 8px" data-setlevel="'+m.id+'" '+(levels.length?'':'disabled')+'>'+lo+'</select></td>'+
        '<td><input class="input-field" style="padding:5px 8px;max-width:130px" value="'+escHtml(m.title||'')+'" data-settitle="'+m.id+'" /></td>'+
        '<td class="text-right"><button class="btn-icon danger" data-delmem="'+m.id+'" title="Remove"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/></svg></button></td></tr>'; }).join('');
  }
  function fillPersonSelect(){ var inR=roster.map(function(m){return m.person_id;}); v('am_person').innerHTML='<option value="">—</option>'+people.filter(function(p){return inR.indexOf(p.id)<0;}).map(function(p){return '<option value="'+p.id+'">'+escHtml(p.first_name+' '+p.last_name)+(p.phone?(' · '+escHtml(p.phone)):'')+'</option>';}).join(''); }

  v('levelForm').addEventListener('submit', async function(e){ e.preventDefault(); if(!current)return;
    var body={department_id:current.id,name:v('lvl_name').value.trim(),name_am:v('lvl_name_am').value.trim(),rank:parseInt(v('lvl_rank').value||'0',10)};
    if(!body.name){gs.toast('Name required','error');return;}
    try{ await gs.api('/api/staff/levels.php',{method:'POST',body:JSON.stringify(body)}); v('lvl_name').value='';v('lvl_name_am').value='';v('lvl_rank').value='0'; await loadLevels(current.id); reloadDepts(current.id); }catch(err){gs.toast(err.message,'error');}
  });
  v('addMemberForm').addEventListener('submit', async function(e){ e.preventDefault(); if(!current)return;
    var pid=v('am_person').value; if(!pid){gs.toast('Pick a person','error');return;}
    var body={person_id:parseInt(pid,10),department_id:current.id,level_id:v('am_level').value?parseInt(v('am_level').value,10):null,title:v('am_title').value.trim()};
    try{ await gs.api('/api/staff/members.php',{method:'POST',body:JSON.stringify(body)}); v('am_person').value='';v('am_level').value='';v('am_title').value=''; await loadRoster(current.id); reloadDepts(current.id); gs.toast('Added','success'); }catch(err){gs.toast(err.message,'error');}
  });

  document.addEventListener('click', async function(e){
    var d=e.target.closest('[data-dept]'); if(d){ selectDept(parseInt(d.dataset.dept,10)); return; }
    var rm=e.target.closest('[data-delmem]'); if(rm){ if(!await gs.confirm(curLang()==='am'?'ከክፍሉ ያስወግዱ?':'Remove from department?'))return; try{await gs.api('/api/staff/members.php',{method:'DELETE',body:JSON.stringify({id:parseInt(rm.dataset.delmem,10)})});await loadRoster(current.id);reloadDepts(current.id);}catch(err){gs.toast(err.message,'error');} return; }
    var rl=e.target.closest('[data-dellevel]'); if(rl){ if(!await gs.confirm(curLang()==='am'?'ይህን ደረጃ ያስወግዱ?':'Remove this level?'))return; try{await gs.api('/api/staff/levels.php',{method:'DELETE',body:JSON.stringify({id:parseInt(rl.dataset.dellevel,10)})});await loadLevels(current.id);await loadRoster(current.id);}catch(err){gs.toast(err.message,'error');} return; }
  });
  document.addEventListener('change', async function(e){
    var sl=e.target.closest('[data-setlevel]'); if(sl){ try{await gs.api('/api/staff/members.php',{method:'PUT',body:JSON.stringify({id:parseInt(sl.dataset.setlevel,10),level_id:sl.value?parseInt(sl.value,10):null})});await loadRoster(current.id);await loadLevels(current.id);}catch(err){gs.toast(err.message,'error');} }
  });
  document.addEventListener('blur', async function(e){
    var ti=e.target.closest('[data-settitle]'); if(ti){ try{await gs.api('/api/staff/members.php',{method:'PUT',body:JSON.stringify({id:parseInt(ti.dataset.settitle,10),title:ti.value.trim()})});}catch(err){gs.toast(err.message,'error');} }
  }, true);

  async function reloadDepts(keepId){ var r=await gs.api('/api/staff/departments.php'); depts=r.data||[]; renderDeptList(); if(keepId){ var s=depts.find(function(d){return d.id===keepId;}); if(s)selectDept(keepId); } }

  v('logoutBtn').addEventListener('click', async function(){ try{ await gs.api('/api/auth/logout.php',{method:'POST'}); }catch(e){} window.location.href='/'; });

  (async function(){
    try{
      var [d,p]=await Promise.all([ gs.api('/api/staff/departments.php'), gs.api('/api/staff/people.php') ]);
      depts=d.data||[]; people=p.data||[]; renderDeptList();
    }catch(e){ gs.toast(e.message,'error'); }
  })();
  applyLang('en');
</script>
</body>
</html>
