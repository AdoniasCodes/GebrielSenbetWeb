<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;

$config = app_config();
Csrf::ensureSession($config['app']['session_name']);

if (($_SESSION['role_name'] ?? null) !== 'teacher') {
    header('Location: /'); exit;
}
$email = $_SESSION['user_email'] ?? '';
?><!DOCTYPE html>
<html lang="en" data-lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Teacher Portal · Mekane Selam Senbet School</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,400..700&family=Plus+Jakarta+Sans:wght@400..700&family=Noto+Sans+Ethiopic:wght@400;500;700&family=Noto+Serif+Ethiopic:wght@400;600;700&display=swap" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <script src="/assets/js/ec-date.js"></script>
  <script>
    tailwind.config = { theme: { extend: {
      colors: {
        surface:'#fcf9f2','surface-low':'#f6f3ec','surface-mid':'#f0eee7',
        ink:'#1c1c18','ink-soft':'#564242',outline:'#897172','outline-soft':'#dcc0c0',
        primary:'#5b0617','primary-soft':'#7a1f2b',
        gold:'#795901','gold-soft':'#c9a14a','gold-warm':'#fed175',
        olive:'#384700','olive-soft':'#a2b665',error:'#9b1c1c',
      },
      fontFamily: { display:['Newsreader','serif'], body:['Plus Jakarta Sans','Noto Sans Ethiopic','sans-serif'] },
      letterSpacing: { widestest: '0.18em' }
    }}};
  </script>
  <style>
    body{font-family:'Plus Jakarta Sans','Noto Sans Ethiopic',sans-serif;background:#fcf9f2;color:#1c1c18;}
    .panel{background:#fff;border:1px solid rgba(220,192,192,0.4);border-radius:8px;}
    .seg-active{background:#fed175;color:#5b0617;}
    .input-sm{border:1px solid #dcc0c0;border-radius:6px;padding:6px 10px;font-size:14px;background:#fff;}
    .input-sm:focus{outline:2px solid #c9a14a;outline-offset:1px;}
    .stat-btn{padding:5px 10px;font-size:12px;font-weight:600;border-radius:5px;color:#564242;}
  </style>
</head>
<body>
  <header class="sticky top-0 z-40 border-b border-outline-soft/40 bg-surface/85 backdrop-blur-md">
    <div class="max-w-[1100px] mx-auto px-6 h-16 flex items-center justify-between">
      <a href="/" class="flex items-center gap-3">
        <span class="inline-flex items-center justify-center w-8 h-8 rounded-sm bg-primary text-surface">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 2.5v19M2.5 12h19M6.5 6.5l11 11M17.5 6.5l-11 11"/><circle cx="12" cy="12" r="2.2" fill="currentColor" stroke="none"/></svg>
        </span>
        <span class="font-display text-lg font-semibold text-primary" data-en="Mekane Selam Senbet School · Teacher" data-am="መካነ ሰላም · መምህር">Mekane Selam Senbet School · Teacher</span>
      </a>
      <div class="flex items-center gap-3">
        <div data-lang-toggle class="hidden sm:flex items-center bg-surface-mid rounded-full p-0.5 border border-outline-soft/50">
          <button data-lang="en" class="seg-active px-3 py-1 text-xs font-semibold rounded-full">EN</button>
          <button data-lang="am" class="px-3 py-1 text-xs font-semibold rounded-full text-ink-soft">አማ</button>
        </div>
        <span class="text-xs text-ink-soft hidden sm:inline"><?= htmlspecialchars($email) ?></span>
        <button id="logoutBtn" class="text-xs font-semibold uppercase tracking-widestest text-primary border border-outline-soft px-3 py-2 rounded hover:bg-surface-mid">Logout</button>
      </div>
    </div>
  </header>

  <main class="max-w-[1100px] mx-auto px-6 py-10 space-y-8">
    <section>
      <p class="text-[11px] font-semibold uppercase tracking-widestest text-gold mb-2" data-en="Welcome" data-am="እንኳን ደህና መጡ">Welcome</p>
      <h1 class="font-display text-3xl text-primary" data-en="My classes" data-am="የእኔ ክፍሎች">My classes</h1>
      <p class="text-sm text-ink-soft mt-2" data-en="Pick a class to enter grades or take attendance." data-am="ውጤት ለማስገባት ወይም መገኘት ለመመዝገብ ክፍል ይምረጡ።">Pick a class to enter grades or take attendance.</p>
    </section>

    <section id="classWrap" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <p class="text-sm text-ink-soft col-span-full text-center py-8" data-en="Loading…" data-am="በመጫን ላይ…">Loading…</p>
    </section>

    <!-- Workspace -->
    <section id="workspace" class="panel hidden">
      <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between gap-4 flex-wrap">
        <div>
          <h2 id="wsTitle" class="font-display text-lg text-ink">—</h2>
          <p id="wsSub" class="text-xs text-outline mt-0.5"></p>
        </div>
        <div class="flex items-center gap-2">
          <div class="flex bg-surface-mid rounded-md p-0.5 border border-outline-soft/50">
            <button id="tabGrades" class="stat-btn seg-active" data-en="Grades" data-am="ውጤቶች">Grades</button>
            <button id="tabAtt" class="stat-btn" data-en="Attendance" data-am="መገኘት">Attendance</button>
          </div>
          <button id="wsClose" class="text-ink-soft hover:text-primary p-1.5" aria-label="Close"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
        </div>
      </header>

      <!-- Grades pane -->
      <div id="paneGrades" class="p-6">
        <div class="flex items-center gap-3 mb-4">
          <label class="text-xs font-semibold uppercase tracking-widestest text-outline" data-en="Term" data-am="ወቅት">Term</label>
          <select id="termSel" class="input-sm"></select>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-[11px] uppercase tracking-widestest text-outline border-b border-outline-soft/30">
                <th class="py-2 pr-4" data-en="Student" data-am="ተማሪ">Student</th>
                <th class="py-2 pr-4 w-28" data-en="Score" data-am="ውጤት">Score</th>
                <th class="py-2" data-en="Remarks" data-am="አስተያየት">Remarks</th>
              </tr>
            </thead>
            <tbody id="gradesBody"></tbody>
          </table>
        </div>
        <div class="mt-5 flex items-center gap-3">
          <button id="saveGrades" class="bg-primary text-surface px-5 py-2.5 rounded text-xs font-semibold uppercase tracking-widestest hover:bg-primary-soft transition-colors" data-en="Save grades" data-am="ውጤቶችን አስቀምጥ">Save grades</button>
          <span id="gradesMsg" class="text-sm"></span>
        </div>
      </div>

      <!-- Attendance pane -->
      <div id="paneAtt" class="p-6 hidden">
        <div class="flex items-center gap-3 mb-4 flex-wrap">
          <label class="text-xs font-semibold uppercase tracking-widestest text-outline" data-en="Date" data-am="ቀን">Date</label>
          <input id="attDate" type="date" class="input-sm" />
          <button id="allPresent" class="text-xs font-semibold text-olive border border-olive-soft/60 px-3 py-1.5 rounded hover:bg-olive/5" data-en="Mark all present" data-am="ሁሉንም እንደተገኙ">Mark all present</button>
        </div>
        <ul id="attBody" class="divide-y divide-outline-soft/20"></ul>
        <div class="mt-5 flex items-center gap-3">
          <button id="saveAtt" class="bg-primary text-surface px-5 py-2.5 rounded text-xs font-semibold uppercase tracking-widestest hover:bg-primary-soft transition-colors" data-en="Save attendance" data-am="መገኘት አስቀምጥ">Save attendance</button>
          <span id="attMsg" class="text-sm"></span>
        </div>
      </div>
    </section>
  </main>

<script>
  function escHtml(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];});}
  function isAm(){ return document.documentElement.getAttribute('data-lang')==='am'; }
  async function ensureCsrf(){
    var t = sessionStorage.getItem('csrf_token');
    if(!t){ var r=await fetch('/api/auth/csrf.php'); var d=await r.json(); t=d.csrf_token; sessionStorage.setItem('csrf_token',t); }
    return t;
  }
  async function api(url, opts){
    opts=opts||{}; opts.headers=opts.headers||{};
    if(opts.body && !opts.headers['Content-Type']) opts.headers['Content-Type']='application/json';
    if(['POST','PUT','PATCH','DELETE'].indexOf((opts.method||'GET').toUpperCase())>=0) opts.headers['X-CSRF-Token']=await ensureCsrf();
    var res=await fetch(url,opts); var data; try{data=await res.json();}catch(e){data={};}
    if(!res.ok) throw new Error(data.error||('HTTP '+res.status));
    return data;
  }

  var TERMS=[], CURRENT=null, ROSTER=[], ATT=[];

  function name(r){ return ((r.first_name||'')+' '+(r.last_name||'')).trim(); }

  async function loadClasses(){
    var wrap=document.getElementById('classWrap');
    try{
      var d=await api('/api/teacher/classes/index.php');
      TERMS=d.terms||[];
      var rows=d.data||[];
      if(!rows.length){
        wrap.innerHTML='<p class="text-sm text-ink-soft col-span-full text-center py-8" data-en="No classes assigned to you yet. Contact the admin." data-am="ገና ምንም ክፍል አልተመደበልዎትም። እባክዎ አስተዳዳሪውን ያነጋግሩ።">No classes assigned to you yet. Contact the admin.</p>';
        return;
      }
      wrap.innerHTML=rows.map(function(c){
        var lvl=isAm()&&c.level_name_am?c.level_name_am:c.level_name;
        var subj=isAm()&&c.subject_name_am?c.subject_name_am:c.subject_name;
        return '<button class="panel p-6 text-left hover:shadow-md transition-shadow" '+
          'data-class="'+c.class_id+'" data-subject="'+c.subject_id+'" '+
          'data-cn="'+escHtml(c.class_name)+'" data-sn="'+escHtml(subj)+'" data-lvl="'+escHtml([lvl,c.academic_year].filter(Boolean).join(" · "))+'">'+
          '<p class="text-[10px] font-semibold uppercase tracking-widestest text-gold mb-2">'+escHtml(subj)+'</p>'+
          '<h3 class="font-display text-xl text-primary mb-1">'+escHtml(c.class_name)+'</h3>'+
          '<p class="text-xs text-outline mb-3">'+escHtml([lvl,c.academic_year].filter(Boolean).join(" · "))+'</p>'+
          '<span class="text-xs text-ink-soft">'+(c.student_count||0)+' '+(isAm()?'ተማሪዎች':'students')+'</span>'+
        '</button>';
      }).join('');
    }catch(e){ wrap.innerHTML='<p class="text-sm text-error col-span-full text-center py-8">'+escHtml(e.message)+'</p>'; }
  }

  document.getElementById('classWrap').addEventListener('click', function(e){
    var b=e.target.closest('[data-class]'); if(!b) return;
    CURRENT={ class_id:parseInt(b.dataset.class,10), subject_id:parseInt(b.dataset.subject,10), cn:b.dataset.cn, sn:b.dataset.sn, lvl:b.dataset.lvl };
    document.getElementById('wsTitle').textContent=CURRENT.cn+' · '+CURRENT.sn;
    document.getElementById('wsSub').textContent=CURRENT.lvl;
    document.getElementById('workspace').classList.remove('hidden');
    // term options
    var ts=document.getElementById('termSel');
    ts.innerHTML=TERMS.map(function(t){ return '<option value="'+t.id+'"'+(Number(t.is_current)===1?' selected':'')+'>'+escHtml(t.name+' · '+t.academic_year)+'</option>'; }).join('');
    showTab('grades');
    document.getElementById('workspace').scrollIntoView({behavior:'smooth',block:'start'});
  });
  document.getElementById('wsClose').addEventListener('click', function(){ document.getElementById('workspace').classList.add('hidden'); CURRENT=null; });

  function showTab(which){
    var g=which==='grades';
    document.getElementById('tabGrades').classList.toggle('seg-active',g);
    document.getElementById('tabAtt').classList.toggle('seg-active',!g);
    document.getElementById('paneGrades').classList.toggle('hidden',!g);
    document.getElementById('paneAtt').classList.toggle('hidden',g);
    if(g) loadRoster(); else loadAttendance();
  }
  document.getElementById('tabGrades').addEventListener('click', function(){ showTab('grades'); });
  document.getElementById('tabAtt').addEventListener('click', function(){ showTab('att'); });
  document.getElementById('termSel').addEventListener('change', loadRoster);

  // ---- Grades ----
  async function loadRoster(){
    if(!CURRENT) return;
    var tb=document.getElementById('gradesBody');
    document.getElementById('gradesMsg').textContent='';
    tb.innerHTML='<tr><td colspan="3" class="py-8 text-center text-ink-soft">'+(isAm()?'በመጫን ላይ…':'Loading…')+'</td></tr>';
    var term=document.getElementById('termSel').value;
    try{
      var d=await api('/api/teacher/roster/index.php?class_id='+CURRENT.class_id+'&subject_id='+CURRENT.subject_id+'&term_id='+term);
      ROSTER=d.data||[];
      if(!ROSTER.length){ tb.innerHTML='<tr><td colspan="3" class="py-8 text-center text-ink-soft">'+(isAm()?'በዚህ ክፍል ተማሪ የለም።':'No students in this class.')+'</td></tr>'; return; }
      tb.innerHTML=ROSTER.map(function(r){
        return '<tr class="border-b border-outline-soft/15" data-sid="'+r.student_id+'" data-gid="'+(r.grade_id||'')+'">'+
          '<td class="py-2 pr-4 font-medium">'+escHtml(name(r))+'</td>'+
          '<td class="py-2 pr-4"><input type="number" min="0" max="100" step="0.01" class="input-sm w-24 js-score" value="'+(r.score!=null?escHtml(r.score):'')+'" /></td>'+
          '<td class="py-2"><input type="text" class="input-sm w-full js-remarks" value="'+escHtml(r.remarks||'')+'" /></td>'+
        '</tr>';
      }).join('');
    }catch(e){ tb.innerHTML='<tr><td colspan="3" class="py-8 text-center text-error">'+escHtml(e.message)+'</td></tr>'; }
  }

  document.getElementById('saveGrades').addEventListener('click', async function(){
    if(!CURRENT) return;
    var term=parseInt(document.getElementById('termSel').value,10);
    var msg=document.getElementById('gradesMsg'); msg.className='text-sm text-ink-soft'; msg.textContent=isAm()?'በማስቀመጥ ላይ…':'Saving…';
    var rows=Array.prototype.slice.call(document.querySelectorAll('#gradesBody tr[data-sid]'));
    var ok=0, fail=0;
    for(var i=0;i<rows.length;i++){
      var tr=rows[i]; var scoreEl=tr.querySelector('.js-score'); var remEl=tr.querySelector('.js-remarks');
      var score=scoreEl.value.trim(); var gid=tr.dataset.gid;
      if(score===''){ continue; } // skip blanks (don't create empty grades)
      try{
        if(gid){ await api('/api/teacher/grades/index.php',{method:'PUT',body:JSON.stringify({id:parseInt(gid,10),score:parseFloat(score),remarks:remEl.value})}); }
        else {
          var r=await api('/api/teacher/grades/index.php',{method:'POST',body:JSON.stringify({student_id:parseInt(tr.dataset.sid,10),subject_id:CURRENT.subject_id,class_id:CURRENT.class_id,term_id:term,score:parseFloat(score),remarks:remEl.value})});
          if(r.id) tr.dataset.gid=r.id;
        }
        ok++;
      }catch(e){ fail++; }
    }
    msg.className='text-sm '+(fail?'text-error':'text-olive');
    msg.textContent=(isAm()?('ተቀምጧል: '+ok):(ok+' saved'))+(fail?(' · '+fail+(isAm()?' አልተሳካም':' failed')):'');
  });

  // ---- Attendance ----
  var ATT_STATUSES=[['present','Present','የተገኘ','#384700'],['late','Late','የዘገየ','#795901'],['absent','Absent','ቀሪ','#9b1c1c'],['excused','Excused','በፍቃድ','#564242']];
  function statusLabel(s){ var f=ATT_STATUSES.find(function(x){return x[0]===s;}); return f?(isAm()?f[2]:f[1]):s; }

  async function loadAttendance(){
    if(!CURRENT) return;
    var ul=document.getElementById('attBody'); document.getElementById('attMsg').textContent='';
    var dt=document.getElementById('attDate');
    if(!dt.value){ var t=new Date(); dt.value=t.getFullYear()+'-'+String(t.getMonth()+1).padStart(2,'0')+'-'+String(t.getDate()).padStart(2,'0'); }
    ul.innerHTML='<li class="py-8 text-center text-ink-soft text-sm">'+(isAm()?'በመጫን ላይ…':'Loading…')+'</li>';
    try{
      var d=await api('/api/teacher/attendance/index.php?class_id='+CURRENT.class_id+'&date='+dt.value);
      ATT=d.roster||[];
      if(!ATT.length){ ul.innerHTML='<li class="py-8 text-center text-ink-soft text-sm">'+(isAm()?'በዚህ ክፍል ተማሪ የለም።':'No students in this class.')+'</li>'; return; }
      renderAtt();
    }catch(e){ ul.innerHTML='<li class="py-8 text-center text-error text-sm">'+escHtml(e.message)+'</li>'; }
  }
  function renderAtt(){
    var ul=document.getElementById('attBody');
    ul.innerHTML=ATT.map(function(r,idx){
      var noProfile = r.person_id==null;
      var segs=ATT_STATUSES.map(function(s){
        var on=r.status===s[0];
        return '<button data-idx="'+idx+'" data-st="'+s[0]+'" class="att-seg px-2.5 py-1 text-xs font-semibold rounded '+(on?'text-surface':'text-ink-soft')+'" style="'+(on?('background:'+s[3]+';'):'')+'" '+(noProfile?'disabled':'')+'>'+(isAm()?s[2]:s[1])+'</button>';
      }).join('');
      return '<li class="py-3 flex items-center justify-between gap-4">'+
        '<span class="font-medium">'+escHtml(name(r))+(noProfile?' <span class="text-[10px] text-outline">('+(isAm()?'መገለጫ የለም':'no profile')+')</span>':'')+'</span>'+
        '<div class="flex gap-1 flex-wrap '+(noProfile?'opacity-40':'')+'">'+segs+'</div>'+
      '</li>';
    }).join('');
  }
  document.getElementById('attBody').addEventListener('click', function(e){
    var b=e.target.closest('.att-seg'); if(!b||b.disabled) return;
    ATT[parseInt(b.dataset.idx,10)].status=b.dataset.st; renderAtt();
  });
  document.getElementById('attDate').addEventListener('change', loadAttendance);
  document.getElementById('allPresent').addEventListener('click', function(){ ATT.forEach(function(r){ if(r.person_id!=null) r.status='present'; }); renderAtt(); });

  document.getElementById('saveAtt').addEventListener('click', async function(){
    if(!CURRENT) return;
    var msg=document.getElementById('attMsg'); msg.className='text-sm text-ink-soft'; msg.textContent=isAm()?'በማስቀመጥ ላይ…':'Saving…';
    var records=ATT.filter(function(r){ return r.person_id!=null; }).map(function(r){ return {person_id:r.person_id,status:r.status}; });
    if(!records.length){ msg.className='text-sm text-error'; msg.textContent=isAm()?'ምንም የሚቀመጥ የለም':'Nothing to save (no student profiles)'; return; }
    try{
      var r=await api('/api/teacher/attendance/index.php',{method:'POST',body:JSON.stringify({class_id:CURRENT.class_id,date:document.getElementById('attDate').value,records:records})});
      msg.className='text-sm text-olive'; msg.textContent=(isAm()?'ተቀምጧል: ':'Saved ')+(r.saved||0);
    }catch(e){ msg.className='text-sm text-error'; msg.textContent=e.message; }
  });

  // lang toggle
  (function(){
    function applyLang(lang){
      if(lang!=='en'&&lang!=='am') lang='en';
      document.documentElement.setAttribute('data-lang',lang);
      document.querySelectorAll('[data-en],[data-am]').forEach(function(el){ var v=el.getAttribute('data-'+lang); if(v!==null) el.innerHTML=v; });
      document.querySelectorAll('[data-lang-toggle] button').forEach(function(btn){ btn.classList.toggle('seg-active',btn.dataset.lang===lang); btn.classList.toggle('text-ink-soft',btn.dataset.lang!==lang); });
      try{ localStorage.setItem('gs_lang',lang); }catch(e){}
      // re-render dynamic bits in the new language
      loadClasses();
      if(CURRENT){ if(!document.getElementById('paneGrades').classList.contains('hidden')) loadRoster(); else renderAtt(); }
    }
    document.querySelectorAll('[data-lang-toggle] button').forEach(function(btn){ btn.addEventListener('click',function(){ applyLang(btn.dataset.lang); }); });
    var saved='en'; try{ saved=localStorage.getItem('gs_lang')||'en'; }catch(e){}
    document.documentElement.setAttribute('data-lang',saved);
    document.querySelectorAll('[data-en],[data-am]').forEach(function(el){ var v=el.getAttribute('data-'+saved); if(v!==null) el.innerHTML=v; });
    document.querySelectorAll('[data-lang-toggle] button').forEach(function(btn){ btn.classList.toggle('seg-active',btn.dataset.lang===saved); btn.classList.toggle('text-ink-soft',btn.dataset.lang!==saved); });
  })();

  document.getElementById('logoutBtn').addEventListener('click', async function(){
    try{ await api('/api/auth/logout.php',{method:'POST'}); window.location.href='/'; }catch(e){ alert(e.message); }
  });

  loadClasses();
</script>
</body>
</html>
