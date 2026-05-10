<?php
require_once __DIR__ . '/../../bootstrap.php';
use App\Utils\Csrf;
$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
if (($_SESSION['role_name'] ?? null) !== 'admin') { header('Location: /'); exit; }

$page_title    = 'Videos';
$page_title_am = 'ቪዲዮዎች';
$page_eyebrow    = 'Resources';
$page_eyebrow_am = 'መርጃዎች';
$active_nav = 'videos';
require __DIR__ . '/_partials/page-shell.php';
?>
<script src="/assets/js/video-embed.js"></script>

<div class="flex items-center justify-between flex-wrap gap-3">
  <p class="text-sm text-ink-soft max-w-xl">Curate the videos that appear on the public landing page. Paste public URLs from TikTok, YouTube, or Facebook. Each "section" is a different strip on the homepage.</p>
  <button id="newBtn" class="btn-primary">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
    <span>Add video</span>
  </button>
</div>

<section id="formPanel" class="panel hidden">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between">
    <h2 id="formTitle" class="font-display text-lg text-ink">Add video</h2>
    <button type="button" id="cancelBtn" class="btn-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
  </header>
  <form id="entityForm" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
    <input type="hidden" id="f_id" />
    <div class="md:col-span-2">
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Public video URL</label>
      <input id="f_url" class="input-field" placeholder="https://www.tiktok.com/@user/video/123456..." required />
      <p id="urlNote" class="text-xs text-ink-soft mt-2">TikTok needs the full <code>/video/&lt;id&gt;</code> URL — the share-link short form (vm.tiktok.com) won't embed.</p>
    </div>
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Section</label>
      <select id="f_section" class="input-field">
        <option value="tiktok_latest">Landing — Latest on TikTok</option>
        <option value="youtube_latest">Landing — Latest on YouTube</option>
      </select>
    </div>
    <div>
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Sort order (lower = first)</label>
      <input id="f_sort" type="number" class="input-field" value="10" />
    </div>
    <div class="md:col-span-2">
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Title (optional)</label>
      <input id="f_title" class="input-field" maxlength="200" />
    </div>
    <div class="md:col-span-2">
      <label class="block text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Caption (optional)</label>
      <input id="f_caption" class="input-field" maxlength="500" />
    </div>
    <div class="md:col-span-2">
      <label class="inline-flex items-center gap-2"><input type="checkbox" id="f_active" class="w-4 h-4" checked /> <span class="text-sm">Show on landing page</span></label>
    </div>
    <div class="md:col-span-2 flex items-center gap-3">
      <button type="submit" class="btn-primary">Save</button>
      <button type="button" id="cancelBtn2" class="btn-ghost">Cancel</button>
      <p id="formMsg" class="text-sm hidden"></p>
    </div>
    <div id="previewWrap" class="md:col-span-2 hidden">
      <p class="text-[11px] font-semibold uppercase tracking-widestest text-ink-soft mb-2">Preview</p>
      <div class="bg-black rounded overflow-hidden" style="aspect-ratio:16/9;max-width:480px;">
        <iframe id="previewFrame" src="" class="w-full h-full" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" allowfullscreen loading="lazy"></iframe>
      </div>
    </div>
  </form>
</section>

<section class="panel">
  <header class="px-6 py-5 border-b border-outline-soft/40 flex items-center justify-between flex-wrap gap-3">
    <h2 class="font-display text-lg text-ink">Videos · <span id="rowCount" class="text-ink-soft text-sm">—</span></h2>
    <div class="flex items-center gap-3">
      <select id="filterSection" class="input-field" style="max-width:240px">
        <option value="">All sections</option>
        <option value="tiktok_latest">Landing — Latest on TikTok</option>
        <option value="youtube_latest">Landing — Latest on YouTube</option>
      </select>
    </div>
  </header>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Section</th><th>Platform</th><th>URL</th><th>Title</th><th>Order</th><th>Active</th><th class="text-right">&nbsp;</th></tr></thead>
      <tbody id="tbody"><tr><td colspan="7" class="text-center text-ink-soft py-12">Loading…</td></tr></tbody>
    </table>
  </div>
</section>

<script>
  var all = [];
  function escHtml(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];});}
  function shortUrl(s){ if (!s) return ''; var t = String(s); return t.length > 60 ? t.substring(0,57)+'…' : t; }

  function sectionLabel(slug) {
    if (slug === 'tiktok_latest')  return 'Latest on TikTok';
    if (slug === 'youtube_latest') return 'Latest on YouTube';
    return slug;
  }

  function render() {
    document.getElementById('rowCount').textContent = all.length + ' total';
    var tbody = document.getElementById('tbody');
    if (!all.length) { tbody.innerHTML = '<tr><td colspan="7" class="text-center text-ink-soft py-16">No videos yet.</td></tr>'; return; }
    tbody.innerHTML = all.map(function(v){
      return '<tr>' +
        '<td>'+escHtml(sectionLabel(v.section))+'</td>' +
        '<td><span class="pill pill-active">'+escHtml(v.platform)+'</span></td>' +
        '<td><a href="'+escHtml(v.video_url)+'" target="_blank" rel="noopener" class="text-primary hover:underline">'+escHtml(shortUrl(v.video_url))+'</a></td>' +
        '<td>'+escHtml(v.title || '')+'</td>' +
        '<td>'+escHtml(v.sort_order)+'</td>' +
        '<td>'+(v.is_active==1 ? '<span class="pill pill-active">on</span>' : '<span class="pill pill-archived">off</span>')+'</td>' +
        '<td class="text-right"><div class="inline-flex items-center gap-1">' +
          '<button class="btn-icon" title="Edit" data-edit="'+v.id+'"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>' +
          '<button class="btn-icon danger" title="Archive" data-archive="'+v.id+'"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/></svg></button>' +
        '</div></td>' +
      '</tr>';
    }).join('');
  }

  async function load() {
    var sec = document.getElementById('filterSection').value;
    var qs = '?include_archived=0' + (sec ? '&section=' + encodeURIComponent(sec) : '');
    try {
      var d = await gs.api('/api/admin/videos/index.php' + qs);
      all = d.data || [];
      render();
    } catch(e){ gs.toast(e.message,'error'); }
  }

  var formPanel = document.getElementById('formPanel');
  var formTitle = document.getElementById('formTitle');
  var msg = document.getElementById('formMsg');
  var previewWrap = document.getElementById('previewWrap');
  var previewFrame = document.getElementById('previewFrame');

  function refreshPreview() {
    var url = document.getElementById('f_url').value.trim();
    var meta = url && window.VideoEmbed ? VideoEmbed.buildEmbedUrl(url) : null;
    if (meta) {
      previewFrame.src = meta.embedUrl;
      previewWrap.classList.remove('hidden');
    } else {
      previewFrame.src = '';
      previewWrap.classList.add('hidden');
    }
  }

  document.getElementById('f_url').addEventListener('input', refreshPreview);

  function showForm(item) {
    formPanel.classList.remove('hidden');
    msg.className = 'text-sm hidden';
    document.getElementById('f_id').value      = item ? item.id : '';
    document.getElementById('f_url').value     = item ? item.video_url : '';
    document.getElementById('f_section').value = item ? item.section : 'tiktok_latest';
    document.getElementById('f_sort').value    = item ? item.sort_order : 10;
    document.getElementById('f_title').value   = item && item.title ? item.title : '';
    document.getElementById('f_caption').value = item && item.caption ? item.caption : '';
    document.getElementById('f_active').checked = item ? (item.is_active == 1) : true;
    formTitle.textContent = item ? 'Edit video' : 'Add video';
    refreshPreview();
    formPanel.scrollIntoView({behavior:'smooth', block:'center'});
  }
  function hideForm() {
    formPanel.classList.add('hidden');
    previewFrame.src = '';
    previewWrap.classList.add('hidden');
  }
  document.getElementById('newBtn').addEventListener('click', function(){ showForm(null); });
  document.getElementById('cancelBtn').addEventListener('click', hideForm);
  document.getElementById('cancelBtn2').addEventListener('click', hideForm);
  document.getElementById('filterSection').addEventListener('change', load);

  document.getElementById('entityForm').addEventListener('submit', async function(e){
    e.preventDefault();
    msg.className = 'text-sm hidden';
    var id = document.getElementById('f_id').value;
    var body = {
      video_url:  document.getElementById('f_url').value.trim(),
      section:    document.getElementById('f_section').value,
      sort_order: parseInt(document.getElementById('f_sort').value || '0', 10),
      title:      document.getElementById('f_title').value.trim() || null,
      caption:    document.getElementById('f_caption').value.trim() || null,
      is_active:  document.getElementById('f_active').checked ? 1 : 0,
    };
    try {
      if (id) {
        body.id = parseInt(id, 10);
        await gs.api('/api/admin/videos/index.php', { method:'PUT', body: JSON.stringify(body) });
      } else {
        await gs.api('/api/admin/videos/index.php', { method:'POST', body: JSON.stringify(body) });
      }
      gs.toast(id ? 'Updated' : 'Added','success');
      hideForm(); load();
    } catch(err){
      msg.className = 'text-sm text-error';
      msg.textContent = err.message;
    }
  });

  document.addEventListener('click', async function(e){
    var t = e.target.closest('[data-edit], [data-archive]');
    if (!t) return;
    var id = parseInt(t.dataset.edit || t.dataset.archive, 10);
    if (t.dataset.edit) {
      var item = all.find(function(x){ return x.id === id; });
      if (item) showForm(item);
    } else {
      if (!await gs.confirm('Archive this video?')) return;
      try { await gs.api('/api/admin/videos/index.php', { method:'DELETE', body: JSON.stringify({ id: id })}); gs.toast('Archived','success'); load(); }
      catch(err){ gs.toast(err.message,'error'); }
    }
  });

  load();
</script>

<?php require __DIR__ . '/_partials/page-shell-end.php'; ?>
