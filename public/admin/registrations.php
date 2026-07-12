<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'admin') { header('Location: /'); exit; }

$page_title    = 'Registrations';
$page_title_am = 'ምዝገባዎች';
$page_eyebrow    = 'Community';
$page_eyebrow_am = 'ማህበረሰብ';
$active_nav = 'registrations';
require __DIR__ . '/_partials/page-shell.php';
?>

<!-- Blocking error modal (project rule: errors are modals, not toasts) -->
<div id="errModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-ink/40 px-4">
  <div class="panel max-w-md w-full p-6">
    <h3 class="font-display text-lg text-error mb-2" data-en="Something went wrong" data-am="ችግር ተፈጥሯል">Something went wrong</h3>
    <p id="errModalMsg" class="text-sm text-ink-soft whitespace-pre-wrap mb-5"></p>
    <div class="text-right"><button id="errModalOk" class="btn-primary" data-en="OK" data-am="እሺ">OK</button></div>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-[320px_1fr] gap-6">

  <!-- Forms list -->
  <section class="panel self-start">
    <header class="px-5 py-4 border-b border-outline-soft/40 flex items-center justify-between">
      <h2 class="font-display text-base text-ink" data-en="Forms" data-am="ቅጾች">Forms</h2>
      <button id="newFormBtn" class="btn-icon" title="New form"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></button>
    </header>
    <div id="formList" class="p-3 space-y-1"><p class="text-center text-ink-soft py-8 text-sm">Loading…</p></div>
  </section>

  <!-- Detail -->
  <section class="space-y-6">
    <div id="emptyDetail" class="panel p-12 text-center text-ink-soft" data-en="Select a form to view submissions and customize its fields." data-am="ምዝገባዎችን ለማየትና ጥያቄዎችን ለማስተካከል ቅጽ ይምረጡ።">Select a form to view submissions and customize its fields.</div>

    <div id="detail" class="hidden space-y-6">

      <!-- Form settings -->
      <div class="panel">
        <header class="px-6 py-4 border-b border-outline-soft/40 flex items-center justify-between">
          <h3 class="font-display text-base" data-en="Form settings" data-am="የቅጽ ማስተካከያ">Form settings</h3>
          <button id="archiveFormBtn" class="text-xs font-semibold text-error hover:underline" data-en="Archive form" data-am="ቅጽ አስቀምጥ">Archive form</button>
        </header>
        <form id="formSettings" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div><label class="lbl" data-en="Title (English)" data-am="ርዕስ (እንግሊዝኛ)">Title (English)</label><input id="fs_title_en" class="input-field" required /></div>
          <div><label class="lbl" data-en="Title (Amharic)" data-am="ርዕስ (አማርኛ)">Title (Amharic)</label><input id="fs_title_am" class="input-field ethiopic" /></div>
          <div><label class="lbl" data-en="Description (English)" data-am="መግለጫ (እንግሊዝኛ)">Description (English)</label><textarea id="fs_desc_en" class="input-field" rows="2"></textarea></div>
          <div><label class="lbl" data-en="Description (Amharic)" data-am="መግለጫ (አማርኛ)">Description (Amharic)</label><textarea id="fs_desc_am" class="input-field ethiopic" rows="2"></textarea></div>
          <div><label class="lbl" data-en="Department" data-am="ክፍል">Department</label><select id="fs_dept" class="input-field"><option value="">—</option></select></div>
          <div class="grid grid-cols-2 gap-3">
            <div><label class="lbl" data-en="Status" data-am="ሁኔታ">Status</label>
              <select id="fs_status" class="input-field">
                <option value="open" data-en="Open" data-am="ክፍት">Open</option>
                <option value="limited" data-en="Limited" data-am="የተወሰነ">Limited</option>
                <option value="closed" data-en="Closed" data-am="ዝግ">Closed</option>
              </select></div>
            <div><label class="lbl" data-en="Order" data-am="ቅደም ተከተል">Order</label><input id="fs_sort" type="number" class="input-field" value="0" /></div>
          </div>
          <div class="md:col-span-2 flex items-center gap-3">
            <button type="submit" class="btn-primary" data-en="Save settings" data-am="አስቀምጥ">Save settings</button>
            <span id="fs_slug" class="text-xs text-outline"></span>
          </div>
        </form>
      </div>

      <!-- Field builder -->
      <div class="panel">
        <header class="px-6 py-4 border-b border-outline-soft/40 flex items-center justify-between">
          <h3 class="font-display text-base"><span data-en="Fields" data-am="ጥያቄዎች">Fields</span> · <span id="fieldCount" class="text-ink-soft text-sm">—</span></h3>
        </header>
        <div class="p-4">
          <div id="fieldList" class="space-y-2 mb-4"></div>
          <form id="fieldForm" class="bg-surface-low rounded p-4 border border-outline-soft/30 space-y-3">
            <p class="lbl" id="fieldFormTitle" data-en="Add a field" data-am="ጥያቄ ጨምር">Add a field</p>
            <input type="hidden" id="ff_id" value="" />
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div><label class="lbl" data-en="Label (English)" data-am="መለያ (እንግሊዝኛ)">Label (English)</label><input id="ff_label_en" class="input-field" /></div>
              <div><label class="lbl" data-en="Label (Amharic)" data-am="መለያ (አማርኛ)">Label (Amharic)</label><input id="ff_label_am" class="input-field ethiopic" /></div>
              <div><label class="lbl" data-en="Type" data-am="ዓይነት">Type</label>
                <select id="ff_type" class="input-field">
                  <option value="text" data-en="Short text" data-am="አጭር ጽሑፍ">Short text</option>
                  <option value="textarea" data-en="Long text" data-am="ረጅም ጽሑፍ">Long text</option>
                  <option value="email" data-en="Email" data-am="ኢሜይል">Email</option>
                  <option value="phone" data-en="Phone" data-am="ስልክ">Phone</option>
                  <option value="number" data-en="Number" data-am="ቁጥር">Number</option>
                  <option value="date" data-en="Date" data-am="ቀን">Date</option>
                  <option value="select" data-en="Dropdown" data-am="ተቆልቋይ">Dropdown</option>
                  <option value="radio" data-en="Single choice" data-am="ነጠላ ምርጫ">Single choice</option>
                  <option value="checkbox" data-en="Multiple choice" data-am="ብዙ ምርጫ">Multiple choice</option>
                </select></div>
              <div class="flex items-end gap-4">
                <label class="inline-flex items-center gap-2 pb-2"><input id="ff_required" type="checkbox" class="w-4 h-4" /><span class="text-sm text-ink-soft" data-en="Required" data-am="የግድ">Required</span></label>
              </div>
              <div><label class="lbl" data-en="Placeholder (English)" data-am="ማሳያ (እንግሊዝኛ)">Placeholder (English)</label><input id="ff_ph_en" class="input-field" /></div>
              <div><label class="lbl" data-en="Placeholder (Amharic)" data-am="ማሳያ (አማርኛ)">Placeholder (Amharic)</label><input id="ff_ph_am" class="input-field ethiopic" /></div>
            </div>
            <div id="ff_optionsWrap" class="hidden">
              <label class="lbl" data-en="Options" data-am="አማራጮች">Options</label>
              <div id="ff_options" class="space-y-2"></div>
              <button type="button" id="ff_addOption" class="btn-ghost mt-2" data-en="Add option" data-am="አማራጭ ጨምር">Add option</button>
            </div>
            <div class="flex items-center gap-3">
              <button type="submit" class="btn-primary" id="ff_submit" data-en="Add field" data-am="ጥያቄ ጨምር">Add field</button>
              <button type="button" id="ff_cancel" class="btn-ghost hidden" data-en="Cancel" data-am="ሰርዝ">Cancel</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Submissions -->
      <div class="panel">
        <header class="px-6 py-4 border-b border-outline-soft/40 flex items-center justify-between flex-wrap gap-2">
          <h3 class="font-display text-base"><span data-en="Submissions" data-am="ምዝገባዎች">Submissions</span> · <span id="subTotal" class="text-ink-soft text-sm">—</span></h3>
          <select id="subFilter" class="input-field" style="width:auto;padding:6px 10px">
            <option value="" data-en="All statuses" data-am="ሁሉም ሁኔታ">All statuses</option>
            <option value="new" data-en="New" data-am="አዲስ">New</option>
            <option value="seen" data-en="Seen" data-am="የታየ">Seen</option>
            <option value="contacted" data-en="Contacted" data-am="የተገናኘ">Contacted</option>
          </select>
        </header>
        <div class="table-wrap">
          <table class="data">
            <thead><tr>
              <th data-en="Name" data-am="ስም">Name</th>
              <th data-en="Phone" data-am="ስልክ">Phone</th>
              <th data-en="Date" data-am="ቀን">Date</th>
              <th data-en="Status" data-am="ሁኔታ">Status</th>
              <th class="text-right">&nbsp;</th>
            </tr></thead>
            <tbody id="subBody"><tr><td colspan="5" class="text-center text-ink-soft py-8">—</td></tr></tbody>
          </table>
        </div>
        <div class="px-6 py-3 flex items-center justify-between border-t border-outline-soft/30">
          <button id="subPrev" class="btn-ghost" data-en="Previous" data-am="ቀዳሚ">Previous</button>
          <span id="subPageLbl" class="text-sm text-ink-soft"></span>
          <button id="subNext" class="btn-ghost" data-en="Next" data-am="ቀጣይ">Next</button>
        </div>
      </div>

    </div>
  </section>
</div>

<style>.lbl{display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.18em;color:#3f4658;margin-bottom:6px;}</style>

<script>
  var API = '/api/admin/registrations/index.php';
  var forms = [], depts = [], current = null, subPage = 1, subMeta = {total:0};
  function escHtml(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]; }); }
  function curLang(){ return document.documentElement.getAttribute('data-lang')||'en'; }
  function v(id){ return document.getElementById(id); }

  // Blocking error modal (never a vanishing toast for errors)
  function showError(msg){ v('errModalMsg').textContent = msg; var m=v('errModal'); m.classList.remove('hidden'); m.classList.add('flex'); }
  v('errModalOk').addEventListener('click', function(){ var m=v('errModal'); m.classList.add('hidden'); m.classList.remove('flex'); });

  var STATUS_CHIP = {
    open:{bg:'rgba(56,71,0,0.10)',fg:'#384700',en:'Open',am:'ክፍት'},
    limited:{bg:'rgba(254,209,117,0.4)',fg:'#5c4300',en:'Limited',am:'የተወሰነ'},
    closed:{bg:'rgba(186,26,26,0.10)',fg:'#ba1a1a',en:'Closed',am:'ዝግ'}
  };
  function statusChip(s){ var c=STATUS_CHIP[s]||STATUS_CHIP.open; return '<span class="pill" style="background:'+c.bg+';color:'+c.fg+'">'+(curLang()==='am'?c.am:c.en)+'</span>'; }
  function flabel(f){ return curLang()==='am'?(f.title_am||f.title_en):(f.title_en||f.title_am); }
  function dlabel(d){ return curLang()==='am'?(d.name_am||d.name):(d.name||d.name_am); }

  function renderFormList(){
    var w = v('formList');
    if(!forms.length){ w.innerHTML='<p class="text-center text-ink-soft py-8 text-sm" data-en="No forms yet." data-am="ገና ቅጽ የለም።">No forms yet.</p>'; return; }
    w.innerHTML = forms.map(function(f){
      var dept = f.department_name ? (curLang()==='am'?(f.department_name_am||f.department_name):f.department_name) : '—';
      var newBadge = f.new_count>0 ? '<span class="pill pill-unpaid text-[10px] ml-1">'+f.new_count+' '+(curLang()==='am'?'አዲስ':'new')+'</span>' : '';
      return '<button class="nav-item flex-col !items-start gap-1 '+(current&&current.id===f.id?'active':'')+'" data-form="'+f.id+'">'+
        '<span class="font-medium '+(curLang()==='am'?'ethiopic':'')+'">'+escHtml(flabel(f))+'</span>'+
        '<span class="flex items-center gap-2 text-[11px] text-ink-soft">'+statusChip(f.status)+'<span class="'+(curLang()==='am'?'ethiopic':'')+'">'+escHtml(dept)+'</span>·<span>'+f.submission_count+'</span>'+newBadge+'</span>'+
        '</button>';
    }).join('');
  }

  function fillDeptSelect(){
    var sel=v('fs_dept'); if(!sel) return;
    sel.innerHTML='<option value="">'+(curLang()==='am'?'ክፍል የለም':'No department')+'</option>'+depts.filter(function(d){return d.is_archived==0;}).map(function(d){
      return '<option value="'+d.id+'">'+escHtml(dlabel(d))+'</option>';
    }).join('');
  }

  function selectForm(id){
    current = forms.find(function(f){return f.id===id;}); if(!current) return;
    v('emptyDetail').classList.add('hidden'); v('detail').classList.remove('hidden');
    renderFormList();
    v('fs_title_en').value=current.title_en||''; v('fs_title_am').value=current.title_am||'';
    v('fs_desc_en').value=current.description_en||''; v('fs_desc_am').value=current.description_am||'';
    v('fs_dept').value=current.department_id||''; v('fs_status').value=current.status||'open';
    v('fs_sort').value=current.sort_order||0; v('fs_slug').textContent='/'+current.slug;
    resetFieldForm();
    renderFields();
    subPage=1; loadSubs();
  }

  // ---- Fields ----
  var TYPE_LABEL={text:'Short text',textarea:'Long text',email:'Email',phone:'Phone',number:'Number',date:'Date',select:'Dropdown',radio:'Single choice',checkbox:'Multiple choice'};
  function renderFields(){
    var fields = current.fields || [];
    v('fieldCount').textContent = fields.length;
    var w=v('fieldList');
    if(!fields.length){ w.innerHTML='<p class="text-sm text-ink-soft" data-en="No fields yet. Add one below." data-am="ገና ጥያቄ የለም። ከታች ይጨምሩ።">No fields yet. Add one below.</p>'; return; }
    w.innerHTML = fields.map(function(f, i){
      var lbl = curLang()==='am'?(f.label_am||f.label_en):f.label_en;
      var req = f.is_required==1 ? '<span class="pill pill-unpaid text-[10px]">'+(curLang()==='am'?'የግድ':'required')+'</span>' : '';
      var opts = (f.options&&f.options.length) ? '<span class="text-[11px] text-ink-soft">· '+f.options.length+' '+(curLang()==='am'?'አማራጮች':'options')+'</span>' : '';
      return '<div class="flex items-center justify-between gap-2 bg-surface-low rounded px-3 py-2 border border-outline-soft/30">'+
        '<div class="flex items-center gap-2 min-w-0"><span class="text-sm font-medium truncate '+(curLang()==='am'?'ethiopic':'')+'">'+escHtml(lbl)+'</span>'+
          '<span class="text-[11px] uppercase tracking-widestest text-outline">'+escHtml(TYPE_LABEL[f.field_type]||f.field_type)+'</span>'+req+opts+'</div>'+
        '<div class="flex items-center gap-1 flex-shrink-0">'+
          '<button class="btn-icon" title="Up" data-fmove="up" data-fid="'+f.id+'" '+(i===0?'disabled style="opacity:.3"':'')+'><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 15l-6-6-6 6"/></svg></button>'+
          '<button class="btn-icon" title="Down" data-fmove="down" data-fid="'+f.id+'" '+(i===fields.length-1?'disabled style="opacity:.3"':'')+'><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg></button>'+
          '<button class="btn-icon" title="Edit" data-fedit="'+f.id+'"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg></button>'+
          '<button class="btn-icon danger" title="Remove" data-fdel="'+f.id+'"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>'+
        '</div></div>';
    }).join('');
  }

  function optionRow(o){
    o=o||{value:'',label_en:'',label_am:''};
    return '<div class="grid grid-cols-[1fr_1fr_1fr_auto] gap-2 items-center opt-row">'+
      '<input class="input-field opt-value" placeholder="value" value="'+escHtml(o.value)+'" />'+
      '<input class="input-field opt-en" placeholder="English" value="'+escHtml(o.label_en)+'" />'+
      '<input class="input-field opt-am ethiopic" placeholder="አማርኛ" value="'+escHtml(o.label_am||'')+'" />'+
      '<button type="button" class="btn-icon danger opt-del" title="Remove"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button></div>';
  }
  function toggleOptionsUI(){
    var t=v('ff_type').value, choice=(t==='select'||t==='radio'||t==='checkbox');
    v('ff_optionsWrap').classList.toggle('hidden', !choice);
    if(choice && !v('ff_options').children.length){ v('ff_options').insertAdjacentHTML('beforeend', optionRow()); }
  }
  function collectOptions(){
    var out=[];
    v('ff_options').querySelectorAll('.opt-row').forEach(function(r){
      var val=r.querySelector('.opt-value').value.trim();
      if(!val) return;
      out.push({value:val,label_en:r.querySelector('.opt-en').value.trim()||val,label_am:r.querySelector('.opt-am').value.trim()});
    });
    return out;
  }
  function resetFieldForm(){
    v('ff_id').value=''; v('ff_label_en').value=''; v('ff_label_am').value='';
    v('ff_type').value='text'; v('ff_required').checked=false; v('ff_ph_en').value=''; v('ff_ph_am').value='';
    v('ff_options').innerHTML=''; toggleOptionsUI();
    v('ff_submit').setAttribute('data-en','Add field'); v('ff_submit').setAttribute('data-am','ጥያቄ ጨምር'); v('ff_submit').textContent=curLang()==='am'?'ጥያቄ ጨምር':'Add field';
    v('fieldFormTitle').setAttribute('data-en','Add a field'); v('fieldFormTitle').setAttribute('data-am','ጥያቄ ጨምር'); v('fieldFormTitle').textContent=curLang()==='am'?'ጥያቄ ጨምር':'Add a field';
    v('ff_cancel').classList.add('hidden');
  }
  function editField(id){
    var f=(current.fields||[]).find(function(x){return x.id===id;}); if(!f) return;
    v('ff_id').value=f.id; v('ff_label_en').value=f.label_en||''; v('ff_label_am').value=f.label_am||'';
    v('ff_type').value=f.field_type; v('ff_required').checked=f.is_required==1;
    v('ff_ph_en').value=f.placeholder_en||''; v('ff_ph_am').value=f.placeholder_am||'';
    v('ff_options').innerHTML=''; (f.options||[]).forEach(function(o){ v('ff_options').insertAdjacentHTML('beforeend', optionRow(o)); });
    toggleOptionsUI();
    v('ff_submit').textContent=curLang()==='am'?'አዘምን':'Update field';
    v('fieldFormTitle').textContent=curLang()==='am'?'ጥያቄ አስተካክል':'Edit field';
    v('ff_cancel').classList.remove('hidden');
    v('fieldForm').scrollIntoView({behavior:'smooth',block:'center'});
  }

  // ---- Submissions ----
  var SUB_STATUS = {new:{en:'New',am:'አዲስ'},seen:{en:'Seen',am:'የታየ'},contacted:{en:'Contacted',am:'የተገናኘ'}};
  async function loadSubs(){
    try{
      var url = API+'?resource=submissions&form_id='+current.id+'&page='+subPage;
      var f=v('subFilter').value; if(f) url+='&status='+f;
      var d = await gs.api(url);
      subMeta=d; renderSubs(d);
    }catch(e){ showError(e.message); }
  }
  function renderSubs(d){
    v('subTotal').textContent = d.total;
    var body=v('subBody'), rows=d.data||[];
    if(!rows.length){ body.innerHTML='<tr><td colspan="5" class="text-center text-ink-soft py-8" data-en="No submissions." data-am="ምዝገባ የለም።">No submissions.</td></tr>'; }
    else {
      body.innerHTML = rows.map(function(s){
        var opts=['new','seen','contacted'].map(function(k){ return '<option value="'+k+'" '+(s.status===k?'selected':'')+'>'+(curLang()==='am'?SUB_STATUS[k].am:SUB_STATUS[k].en)+'</option>'; }).join('');
        var detail = s.items.map(function(i){ return '<div class="py-1"><span class="text-[11px] uppercase tracking-widestest text-outline '+(curLang()==='am'?'ethiopic':'')+'">'+escHtml(curLang()==='am'?(i.label_am||i.label_en):i.label_en)+'</span><div class="text-sm">'+escHtml(i.value||'—')+'</div></div>'; }).join('');
        return '<tr class="cursor-pointer" data-subrow="'+s.id+'"><td class="font-medium">'+escHtml(s.applicant_name||'—')+'</td>'+
          '<td class="text-ink-soft">'+escHtml(s.applicant_phone||'—')+'</td>'+
          '<td class="text-ink-soft text-sm" data-iso="'+escHtml(s.created_at)+'">'+escHtml(gs.fmtDate(s.created_at,'datetime'))+'</td>'+
          '<td><select class="input-field" style="padding:5px 8px" data-substatus="'+s.id+'">'+opts+'</select></td>'+
          '<td class="text-right"><button class="btn-icon danger" title="Archive" data-subdel="'+s.id+'"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/></svg></button></td></tr>'+
          '<tr class="hidden" data-subdetail="'+s.id+'"><td colspan="5" class="bg-surface-low"><div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8">'+detail+'</div></td></tr>';
      }).join('');
    }
    var pages=Math.max(1,Math.ceil(d.total/d.per_page));
    v('subPageLbl').textContent=(curLang()==='am'?'ገጽ ':'Page ')+d.page+' / '+pages;
    v('subPrev').disabled=d.page<=1; v('subNext').disabled=d.page>=pages;
    v('subPrev').style.opacity=d.page<=1?'.4':'1'; v('subNext').style.opacity=d.page>=pages?'.4':'1';
  }

  // ---- Loaders ----
  async function loadForms(keepId){
    try{
      var d = await gs.api(API);
      forms = d.data||[]; renderFormList();
      if(keepId){ var f=forms.find(function(x){return x.id===keepId;}); if(f){ current=f; selectForm(keepId); return; } }
      if(current){ var still=forms.find(function(x){return x.id===current.id;}); if(!still){ current=null; v('detail').classList.add('hidden'); v('emptyDetail').classList.remove('hidden'); } }
    }catch(e){ showError(e.message); }
  }

  async function init(){
    try{
      var dd = await gs.api('/api/admin/departments/index.php');
      depts = dd.data||dd||[]; fillDeptSelect();
    }catch(e){ /* dept select optional */ }
    loadForms();
  }

  // ---- Events ----
  v('newFormBtn').addEventListener('click', async function(){
    var title = prompt(curLang()==='am'?'የአዲስ ቅጽ ርዕስ (እንግሊዝኛ):':'New form title (English):');
    if(!title) return;
    try{ var r=await gs.api(API,{method:'POST',body:JSON.stringify({action:'form.create',title_en:title})}); await loadForms(r.data.id); gs.toast(curLang()==='am'?'ተፈጠረ':'Created','success'); }
    catch(e){ showError(e.message); }
  });

  v('formSettings').addEventListener('submit', async function(e){ e.preventDefault(); if(!current) return;
    var body={action:'form.update',id:current.id,
      title_en:v('fs_title_en').value.trim(),title_am:v('fs_title_am').value.trim(),
      description_en:v('fs_desc_en').value.trim(),description_am:v('fs_desc_am').value.trim(),
      department_id:v('fs_dept').value?parseInt(v('fs_dept').value,10):null,
      status:v('fs_status').value,sort_order:parseInt(v('fs_sort').value||'0',10)};
    try{ await gs.api(API,{method:'POST',body:JSON.stringify(body)}); await loadForms(current.id); gs.toast(curLang()==='am'?'ተቀምጧል':'Saved','success'); }
    catch(err){ showError(err.message); }
  });

  v('archiveFormBtn').addEventListener('click', async function(){ if(!current) return;
    if(!await gs.confirm(curLang()==='am'?'ይህን ቅጽ ማስቀመጥ ይፈልጋሉ?':'Archive this form? It will disappear from the public site.')) return;
    try{ await gs.api(API,{method:'POST',body:JSON.stringify({action:'form.archive',id:current.id})}); current=null; await loadForms(); v('detail').classList.add('hidden'); v('emptyDetail').classList.remove('hidden'); gs.toast(curLang()==='am'?'ተቀምጧል':'Archived','success'); }
    catch(e){ showError(e.message); }
  });

  v('ff_type').addEventListener('change', toggleOptionsUI);
  v('ff_addOption').addEventListener('click', function(){ v('ff_options').insertAdjacentHTML('beforeend', optionRow()); });
  v('ff_cancel').addEventListener('click', resetFieldForm);
  v('ff_options').addEventListener('click', function(e){ var d=e.target.closest('.opt-del'); if(d){ d.closest('.opt-row').remove(); } });

  v('fieldForm').addEventListener('submit', async function(e){ e.preventDefault(); if(!current) return;
    var labelEn=v('ff_label_en').value.trim();
    if(!labelEn){ showError(curLang()==='am'?'የእንግሊዝኛ መለያ ያስፈልጋል':'English label is required'); return; }
    var type=v('ff_type').value;
    var body={form_id:current.id,label_en:labelEn,label_am:v('ff_label_am').value.trim(),
      field_type:type,is_required:v('ff_required').checked?1:0,
      placeholder_en:v('ff_ph_en').value.trim(),placeholder_am:v('ff_ph_am').value.trim()};
    if(type==='select'||type==='radio'||type==='checkbox'){ body.options=collectOptions(); if(!body.options.length){ showError(curLang()==='am'?'ቢያንስ አንድ አማራጭ ያስፈልጋል':'Add at least one option'); return; } }
    var id=v('ff_id').value;
    body.action = id ? 'field.update' : 'field.create';
    if(id) body.id=parseInt(id,10);
    try{ await gs.api(API,{method:'POST',body:JSON.stringify(body)}); await loadForms(current.id); resetFieldForm(); gs.toast(curLang()==='am'?'ተቀምጧል':'Saved','success'); }
    catch(err){ showError(err.message); }
  });

  v('subFilter').addEventListener('change', function(){ subPage=1; loadSubs(); });
  v('subPrev').addEventListener('click', function(){ if(subPage>1){ subPage--; loadSubs(); } });
  v('subNext').addEventListener('click', function(){ subPage++; loadSubs(); });

  document.addEventListener('click', async function(e){
    var fb=e.target.closest('[data-form]'); if(fb){ selectForm(parseInt(fb.dataset.form,10)); return; }
    var ed=e.target.closest('[data-fedit]'); if(ed){ editField(parseInt(ed.dataset.fedit,10)); return; }
    var del=e.target.closest('[data-fdel]'); if(del){ if(!await gs.confirm(curLang()==='am'?'ይህን ጥያቄ ያስወግዱ?':'Remove this field?'))return; try{ await gs.api(API,{method:'POST',body:JSON.stringify({action:'field.archive',id:parseInt(del.dataset.fdel,10)})}); await loadForms(current.id); }catch(err){ showError(err.message); } return; }
    var mv=e.target.closest('[data-fmove]'); if(mv){ moveField(parseInt(mv.dataset.fid,10), mv.dataset.fmove); return; }
    var sd=e.target.closest('[data-subdel]'); if(sd){ if(!await gs.confirm(curLang()==='am'?'ይህን ምዝገባ ያስቀምጡ?':'Archive this submission?'))return; try{ await gs.api(API,{method:'POST',body:JSON.stringify({action:'submission.archive',id:parseInt(sd.dataset.subdel,10)})}); loadSubs(); loadForms(current.id); }catch(err){ showError(err.message); } return; }
    var row=e.target.closest('[data-subrow]'); if(row && !e.target.closest('select') && !e.target.closest('button')){ var dr=document.querySelector('[data-subdetail="'+row.dataset.subrow+'"]'); if(dr) dr.classList.toggle('hidden'); }
  });

  document.addEventListener('change', async function(e){
    var ss=e.target.closest('[data-substatus]'); if(ss){ try{ await gs.api(API,{method:'POST',body:JSON.stringify({action:'submission.status',id:parseInt(ss.dataset.substatus,10),status:ss.value})}); loadForms(current.id); gs.toast(curLang()==='am'?'ተዘምኗል':'Updated','success'); }catch(err){ showError(err.message); loadSubs(); } }
  });

  async function moveField(id, dir){
    var fields=(current.fields||[]).slice(); var idx=fields.findIndex(function(f){return f.id===id;});
    if(idx<0) return; var swap=dir==='up'?idx-1:idx+1; if(swap<0||swap>=fields.length) return;
    var tmp=fields[idx]; fields[idx]=fields[swap]; fields[swap]=tmp;
    var order=fields.map(function(f){return f.id;});
    try{ await gs.api(API,{method:'POST',body:JSON.stringify({action:'field.reorder',form_id:current.id,order:order})}); await loadForms(current.id); }
    catch(err){ showError(err.message); }
  }

  // Re-render on language switch so newly built DOM is localized.
  document.addEventListener('gs:lang-change', function(){ renderFormList(); if(current){ fillDeptSelect(); v('fs_dept').value=current.department_id||''; renderFields(); if(subMeta.data) renderSubs(subMeta); } });

  init();
</script>

<?php require __DIR__ . '/_partials/page-shell-end.php'; ?>
