<?php
// Admin shell — head + sidebar + topbar opening.
// Including page must set:
//   $page_title, $page_title_am
//   $page_eyebrow, $page_eyebrow_am
//   $active_nav (one of: dashboard, students, teachers, tracks, levels, subjects, classes, terms, assignments, payments, grades, announcements, events, posts, settings)
// And must have already done bootstrap + admin guard.

$initials = strtoupper(substr($_SESSION['user_email'] ?? 'GS', 0, 2));
$year = date('Y');
$nav = $active_nav ?? '';

$nav_groups = [
  ['label_en' => 'Community', 'label_am' => 'ማህበረሰብ', 'items' => [
    ['slug'=>'people','href'=>'/admin/people.php','en'=>'People','am'=>'አባላት','svg'=>'<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>'],
    ['slug'=>'departments','href'=>'/admin/departments.php','en'=>'Departments','am'=>'ክፍሎች','svg'=>'<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>'],
    ['slug'=>'resources','href'=>'/admin/resources.php','en'=>'Resources','am'=>'መርጃዎች','svg'=>'<path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>'],
    ['slug'=>'registrations','href'=>'/admin/registrations.php','en'=>'Registrations','am'=>'ምዝገባዎች','svg'=>'<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13l2 2 4-4"/>'],
  ]],
  ['label_en' => 'Registry', 'label_am' => 'መዝገብ', 'items' => [
    ['slug'=>'students','href'=>'/admin/students.php','en'=>'Students','am'=>'ተማሪዎች','svg'=>'<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>'],
    ['slug'=>'teachers','href'=>'/admin/teachers.php','en'=>'Teachers','am'=>'መምህራን','svg'=>'<path d="M14 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 11l-3-3m3 3l-3 3m3-3h-7"/>'],
    ['slug'=>'parents','href'=>'/admin/parents.php','en'=>'Parents','am'=>'ወላጆች','svg'=>'<path d="M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM15 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2M15 15h2a4 4 0 0 1 4 4v2"/>'],
  ]],
  ['label_en' => 'Classroom', 'label_am' => 'ክፍል', 'items' => [
    ['slug'=>'tracks','href'=>'/admin/tracks.php','en'=>'Tracks','am'=>'ኮርሶች','svg'=>'<path d="M2 4h6a4 4 0 0 1 4 4v13M22 4h-6a4 4 0 0 0-4 4v13"/>'],
    ['slug'=>'levels','href'=>'/admin/levels.php','en'=>'Levels','am'=>'ደረጃዎች','svg'=>'<path d="M3 21V9l9-6 9 6v12M9 21V12h6v9"/>'],
    ['slug'=>'subjects','href'=>'/admin/subjects.php','en'=>'Subjects','am'=>'ትምህርቶች','svg'=>'<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>'],
    ['slug'=>'classes','href'=>'/admin/classes.php','en'=>'Classes','am'=>'ክፍሎች','svg'=>'<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2zM9 22V12h6v10"/>'],
    ['slug'=>'terms','href'=>'/admin/terms.php','en'=>'Terms','am'=>'ወቅቶች','svg'=>'<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>'],
    ['slug'=>'assignments','href'=>'/admin/assignments.php','en'=>'Teacher assignments','am'=>'የመምህር ምድቦች','svg'=>'<path d="M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>'],
  ]],
  ['label_en' => 'Records', 'label_am' => 'መዝገቦች', 'items' => [
    ['slug'=>'payments','href'=>'/admin/payments.php','en'=>'Payments','am'=>'ክፍያዎች','svg'=>'<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/>'],
    ['slug'=>'grades','href'=>'/admin/grades.php','en'=>'Grades','am'=>'ውጤቶች','svg'=>'<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>'],
    ['slug'=>'attendance','href'=>'/admin/attendance.php','en'=>'Attendance','am'=>'መገኘት','svg'=>'<path d="M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>'],
  ]],
  ['label_en' => 'Calendar', 'label_am' => 'የቀን መቁጠሪያ', 'items' => [
    ['slug'=>'holidays','href'=>'/admin/holidays.php','en'=>'Holidays & Serving','am'=>'በዓላትና አገልግሎት','svg'=>'<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="M12 14l1.5 3 3 .3-2.2 2 .7 3-3-1.6-3 1.6.7-3-2.2-2 3-.3z"/>'],
    ['slug'=>'eligibility','href'=>'/admin/eligibility.php','en'=>'Serving Eligibility','am'=>'የአገልግሎት ብቁነት','svg'=>'<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/>'],
  ]],
  ['label_en' => 'Resources', 'label_am' => 'መርጃዎች', 'items' => [
    ['slug'=>'announcements','href'=>'/admin/announcements.php','en'=>'Announcements','am'=>'ማስታወቂያዎች','svg'=>'<path d="M3 11l18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>'],
    ['slug'=>'events','href'=>'/admin/events.php','en'=>'Events','am'=>'ዝግጅቶች','svg'=>'<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M8 14h.01M12 14h.01M16 14h.01"/>'],
    ['slug'=>'posts','href'=>'/admin/posts.php','en'=>'Posts','am'=>'ጽሑፎች','svg'=>'<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>'],
    ['slug'=>'videos','href'=>'/admin/videos.php','en'=>'Videos','am'=>'ቪዲዮዎች','svg'=>'<path d="M23 7l-7 5 7 5V7zM1 5h15v14H1z"/>'],
  ]],
  ['label_en' => 'System', 'label_am' => 'ሲስተም', 'items' => [
    ['slug'=>'settings','href'=>'/admin/settings.php','en'=>'Settings','am'=>'ማስተካከያ','svg'=>'<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>'],
    ['slug'=>'reset-data','href'=>'/admin/reset-data.php','en'=>'Reset / Data','am'=>'ዳታ ማጽዳት','svg'=>'<path d="M3 12a9 9 0 1 0 9-9 9 9 0 0 0-6.36 2.64L3 8"/><path d="M3 3v5h5"/>'],
  ]],
];
?>
<!DOCTYPE html>
<html lang="en" data-lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($page_title ?? 'Admin') ?> · Mekane Selam Senbet School</title>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,400..700&family=Plus+Jakarta+Sans:wght@400..700&family=Noto+Sans+Ethiopic:wght@400;500;700&family=Noto+Serif+Ethiopic:wght@400;600;700&display=swap" rel="stylesheet" />
  <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32.png">
  <link rel="icon" type="image/png" sizes="64x64" href="/images/favicon-64.png">
  <link rel="apple-touch-icon" href="/images/logo-mekane-selam-192.png">

  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <script src="/assets/js/ec-date.js"></script>
  <script>
    tailwind.config = {
      theme: { extend: {
        colors: {
          surface:'#f4f7fc','surface-low':'#eef2fa','surface-mid':'#e5ecf7','surface-high':'#ebe8e1',
          ink:'#141824','ink-soft':'#3f4658',outline:'#6b7690','outline-soft':'#c4d0e4',
          primary:'#16357e','primary-soft':'#2f52a6','primary-warm':'#3f66c4',
          gold:'#795901','gold-soft':'#c9a14a','gold-warm':'#fed175',
          olive:'#384700','olive-soft':'#a2b665',
          error:'#ba1a1a',
        },
        fontFamily: {
          display: ['Newsreader','"Noto Serif Ethiopic"','serif'],
          body: ['"Plus Jakarta Sans"','"Noto Sans Ethiopic"','system-ui','sans-serif'],
          ethiopic: ['"Noto Sans Ethiopic"','serif'],
        },
        letterSpacing: { widestest: '0.18em' },
      }}
    };
  </script>

  <script>
    // === CSRF + auth helpers (shared) — defined in <head> so page scripts can
    // call gs.api() during initial load (they run before page-shell-end.php). ===
    window.gs = window.gs || {};

    // Lang-aware date formatter. Uses Ethiopian calendar in Amharic mode.
    gs.fmtDate = function (input, style) {
      if (window.EC && typeof EC.fmtDate === 'function') return EC.fmtDate(input, style);
      if (!input) return '—';
      var d = new Date(String(input).replace(' ', 'T'));
      return isNaN(d) ? String(input) : d.toLocaleString();
    };

    gs.ensureCsrf = async function () {
      var t = sessionStorage.getItem('csrf_token');
      if (!t) {
        var r = await fetch('/api/auth/csrf.php');
        var d = await r.json();
        t = d.csrf_token;
        sessionStorage.setItem('csrf_token', t);
      }
      return t;
    };

    gs.api = async function (url, opts) {
      opts = opts || {};
      opts.headers = opts.headers || {};
      if (opts.body && !opts.headers['Content-Type']) opts.headers['Content-Type'] = 'application/json';
      if (['POST','PUT','PATCH','DELETE'].indexOf((opts.method||'GET').toUpperCase()) >= 0) {
        opts.headers['X-CSRF-Token'] = await gs.ensureCsrf();
      }
      var res = await fetch(url, opts);
      var data;
      try { data = await res.json(); } catch (e) { data = {}; }
      if (!res.ok) throw new Error(data.error || ('HTTP ' + res.status));
      return data;
    };

    gs.toast = function (msg, type) {
      type = type || 'info';
      var bg = { info: '#384700', error: '#ba1a1a', success: '#384700' }[type] || '#384700';
      var t = document.createElement('div');
      t.style.cssText = 'position:fixed;bottom:24px;right:24px;background:'+bg+';color:#fff;padding:14px 20px;border-radius:6px;z-index:9999;box-shadow:0 8px 24px -8px rgba(0,0,0,0.3);font-size:14px;max-width:340px;';
      t.textContent = msg;
      document.body.appendChild(t);
      setTimeout(function () { t.style.opacity = '0'; t.style.transition = 'opacity 300ms'; setTimeout(function () { t.remove(); }, 300); }, 3000);
    };

    gs.confirm = function (msg) { return Promise.resolve(window.confirm(msg)); };
  </script>

  <style>
    html, body { background: #f4f7fc; }
    .font-display { font-family: 'Newsreader','Noto Serif Ethiopic',serif; }
    .ethiopic { font-family: 'Noto Sans Ethiopic', serif; }
    .seg-active { background:#fed175; color:#16357e; }
    :where(a, button, input, select, textarea):focus-visible {
      outline: 2px solid #c9a14a; outline-offset: 2px; border-radius: 2px;
    }

    .nav-item { display:flex; align-items:center; gap:12px; padding:9px 14px; border-radius:4px; color:#3f4658; font-size:14px; transition:all 150ms ease; position:relative; }
    .nav-item:hover { background: rgba(91,6,23,0.04); color:#16357e; }
    .nav-item.active { color:#16357e; font-weight:600; background: rgba(91,6,23,0.06); }
    .nav-item.active::before { content:''; position:absolute; left:-12px; top:6px; bottom:6px; width:2px; background:#c9a14a; border-radius:1px; }
    .nav-section-label { font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:0.18em; color:#6b7690; padding:0 14px; margin:18px 0 6px; }

    .stat-card { background:#fff; border:1px solid rgba(220,192,192,0.4); border-top-width:3px; border-radius:8px; padding:22px 22px 20px; transition: box-shadow 150ms ease; }
    .stat-card:hover { box-shadow: 0 4px 12px -4px rgba(91,6,23,0.08); }

    .panel { background:#fff; border:1px solid rgba(220,192,192,0.4); border-radius:8px; }

    .pill { display:inline-flex; align-items:center; gap:6px; padding:3px 10px; border-radius:9999px; font-size:11px; font-weight:600; }
    .pill-published { background: rgba(56,71,0,0.10); color:#384700; }
    .pill-draft     { background: rgba(254,209,117,0.4); color:#5c4300; }
    .pill-active    { background: rgba(56,71,0,0.10); color:#384700; }
    .pill-archived  { background: rgba(137,113,114,0.15); color:#3f4658; }
    .pill-paid      { background: rgba(56,71,0,0.10); color:#384700; }
    .pill-unpaid    { background: rgba(186,26,26,0.10); color:#ba1a1a; }
    .pill-partial   { background: rgba(254,209,117,0.4); color:#5c4300; }

    .quick-actions { background: linear-gradient(180deg,#2f52a6 0%,#16357e 100%); color:#f3f0ea; }

    .avatar { width:36px; height:36px; border-radius:9999px; background: linear-gradient(135deg,#a2b665 0%,#384700 100%); color:#f4f7fc; font-weight:700; font-size:13px; display:inline-flex; align-items:center; justify-content:center; letter-spacing:0.05em; }
    .avatar-circle { width:40px; height:40px; border-radius:9999px; display:inline-flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; }

    .bell { position:relative; }
    .bell.has-unread::after { content:''; position:absolute; top:6px; right:6px; width:6px; height:6px; border-radius:9999px; background:#ba1a1a; box-shadow:0 0 0 2px #f4f7fc; }

    .input-field { width:100%; padding:10px 12px; background:#fff; border:1px solid rgba(137,113,114,0.25); border-radius:4px; font-size:14px; color:#141824; transition:all 150ms ease; }
    .input-field:focus { outline:none; border-color:#c9a14a; box-shadow:0 0 0 3px rgba(201,161,74,0.12); }
    .input-field::placeholder { color:#6b7690; }

    .btn-primary { display:inline-flex; align-items:center; justify-content:center; gap:8px; background:#16357e; color:#f4f7fc; padding:10px 18px; border-radius:4px; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.18em; transition: background 150ms ease; cursor:pointer; }
    .btn-primary:hover { background:#2f52a6; }
    .btn-primary:disabled { opacity:0.6; cursor:not-allowed; }
    .btn-ghost { display:inline-flex; align-items:center; justify-content:center; gap:8px; background:#e5ecf7; color:#16357e; padding:10px 18px; border:1px solid rgba(220,192,192,0.5); border-radius:4px; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.18em; transition: background 150ms ease; cursor:pointer; }
    .btn-ghost:hover { background:#ebe8e1; }
    .btn-icon { width:32px; height:32px; border-radius:4px; display:inline-flex; align-items:center; justify-content:center; color:#3f4658; transition: all 150ms ease; }
    .btn-icon:hover { background:#e5ecf7; color:#16357e; }
    .btn-icon.danger:hover { background: rgba(186,26,26,0.1); color:#ba1a1a; }

    .table-wrap { overflow-x: auto; }
    table.data { width:100%; border-collapse:collapse; font-size:14px; }
    table.data thead th { padding:12px 24px; text-align:left; font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:0.18em; color:#3f4658; border-bottom:1px solid rgba(220,192,192,0.4); background:#eef2fa; }
    table.data tbody td { padding:14px 24px; border-bottom:1px solid rgba(220,192,192,0.3); color:#141824; }
    table.data tbody tr:hover td { background: rgba(246,243,236,0.5); }
    table.data tbody tr:last-child td { border-bottom: none; }
  </style>
</head>
<body class="bg-surface text-ink font-body antialiased">

<div class="flex min-h-screen">

  <!-- ============ SIDEBAR ============ -->
  <aside class="w-60 flex-shrink-0 bg-surface border-r border-outline-soft/40 px-5 py-6 flex flex-col sticky top-0 h-screen overflow-y-auto">
    <a href="/admin/index.php" class="flex items-center gap-3 mb-1 px-2">
      <img src="/images/logo-mekane-selam.webp" alt="Mekane Selam Sunday School" class="h-9 w-9 rounded-full object-cover">
      <div class="flex flex-col">
        <span class="font-display text-lg font-semibold text-primary leading-none" data-en="Mekane Selam Senbet School" data-am="መካነ ሰላም ሰንበት ት/ቤት">Mekane Selam Senbet School</span>
        <span class="text-[10px] font-semibold uppercase tracking-widestest text-gold mt-1" data-en="Sacred Administration" data-am="ቅዱስ አስተዳደር">Sacred Administration</span>
      </div>
    </a>

    <nav class="flex-1 mt-8 -ml-3 pl-3 space-y-0.5">
      <a href="/admin/index.php" class="nav-item <?= $nav==='dashboard'?'active':'' ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
        <span data-en="Dashboard" data-am="ዋና ገጽ">Dashboard</span>
      </a>

      <?php foreach ($nav_groups as $grp): ?>
        <p class="nav-section-label" data-en="<?= htmlspecialchars($grp['label_en']) ?>" data-am="<?= htmlspecialchars($grp['label_am']) ?>"><?= htmlspecialchars($grp['label_en']) ?></p>
        <?php foreach ($grp['items'] as $item): ?>
          <a href="<?= $item['href'] ?>" class="nav-item <?= $nav===$item['slug']?'active':'' ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><?= $item['svg'] ?></svg>
            <span data-en="<?= htmlspecialchars($item['en']) ?>" data-am="<?= htmlspecialchars($item['am']) ?>"><?= htmlspecialchars($item['en']) ?></span>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>

    <div class="-ml-3 pl-3 pt-4 mt-4 border-t border-outline-soft/40 space-y-0.5">
      <a href="/admin/settings.php" class="nav-item <?= $nav==='settings'?'active':'' ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        <span data-en="Settings" data-am="ቅንብር">Settings</span>
      </a>
      <button id="logoutBtn" class="nav-item w-full text-left">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
        <span data-en="Sign out" data-am="ውጣ">Sign out</span>
      </button>
    </div>
  </aside>

  <!-- ============ MAIN ============ -->
  <div class="flex-1 min-w-0 flex flex-col">

    <header class="h-20 border-b border-outline-soft/40 px-8 flex items-center justify-between bg-surface/85 backdrop-blur-md sticky top-0 z-40">
      <div>
        <p class="text-[10px] font-semibold uppercase tracking-widestest text-gold mb-1" data-en="<?= htmlspecialchars($page_eyebrow ?? '') ?>" data-am="<?= htmlspecialchars($page_eyebrow_am ?? '') ?>"><?= htmlspecialchars($page_eyebrow ?? '') ?></p>
        <h1 class="font-display text-2xl text-ink leading-none" data-en="<?= htmlspecialchars($page_title ?? '') ?>" data-am="<?= htmlspecialchars($page_title_am ?? '') ?>"><?= htmlspecialchars($page_title ?? '') ?></h1>
      </div>

      <div class="flex items-center gap-3">
        <button id="termPill" class="inline-flex items-center gap-2 bg-surface-mid border border-outline-soft/50 rounded-full px-4 py-2 text-sm text-ink hover:bg-surface-high transition-colors">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
          <span id="termPillLabel"><?= htmlspecialchars($year) ?> · <span data-en="Term" data-am="ኮርስ">Term</span></span>
        </button>

        <div data-lang-toggle class="flex items-center bg-surface-mid rounded-full p-0.5 border border-outline-soft/50">
          <button data-lang="en" class="seg-active px-3 py-1 text-xs font-semibold rounded-full">EN</button>
          <button data-lang="am" class="px-3 py-1 text-xs font-semibold rounded-full text-ink-soft hover:text-primary ethiopic">አማ</button>
        </div>

        <div class="relative">
          <button id="notifBell" class="bell relative w-10 h-10 rounded-full bg-surface-mid border border-outline-soft/50 inline-flex items-center justify-center text-ink-soft hover:text-primary transition-colors" aria-label="Notifications">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10 21h4"/></svg>
            <span id="notifBadge" class="hidden absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-1 rounded-full bg-error text-white text-[10px] font-bold flex items-center justify-center">0</span>
          </button>
          <div id="notifPanel" class="hidden absolute right-0 mt-2 w-80 max-h-[420px] overflow-y-auto bg-surface border border-outline-soft/50 rounded-lg shadow-xl z-50">
            <header class="px-4 py-3 border-b border-outline-soft/40 flex items-center justify-between sticky top-0 bg-surface">
              <h2 class="font-display text-sm text-ink" data-en="Notifications" data-am="ማሳወቂያዎች">Notifications</h2>
            </header>
            <ul id="notifList" class="divide-y divide-outline-soft/20 px-4"></ul>
          </div>
        </div>

        <a href="/admin/settings.php" class="avatar hover:opacity-90 transition-opacity"><?= htmlspecialchars($initials) ?></a>
      </div>
    </header>

    <main class="flex-1 px-8 py-8 space-y-6">
