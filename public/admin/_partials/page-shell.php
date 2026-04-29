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
  ['label_en' => 'Registry', 'label_am' => 'መዝገብ', 'items' => [
    ['slug'=>'students','href'=>'/admin/students.php','en'=>'Students','am'=>'ተማሪዎች','svg'=>'<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>'],
    ['slug'=>'teachers','href'=>'/admin/teachers.php','en'=>'Teachers','am'=>'መምህራን','svg'=>'<path d="M14 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 11l-3-3m3 3l-3 3m3-3h-7"/>'],
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
  ]],
  ['label_en' => 'Resources', 'label_am' => 'መርጃዎች', 'items' => [
    ['slug'=>'announcements','href'=>'/admin/announcements.php','en'=>'Announcements','am'=>'ማስታወቂያዎች','svg'=>'<path d="M3 11l18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>'],
    ['slug'=>'events','href'=>'/admin/events.php','en'=>'Events','am'=>'ዝግጅቶች','svg'=>'<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M8 14h.01M12 14h.01M16 14h.01"/>'],
    ['slug'=>'posts','href'=>'/admin/posts.php','en'=>'Posts','am'=>'ጽሑፎች','svg'=>'<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>'],
  ]],
];
?>
<!DOCTYPE html>
<html lang="en" data-lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($page_title ?? 'Admin') ?> · Gebriel Senbet</title>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,400..700&family=Plus+Jakarta+Sans:wght@400..700&family=Noto+Sans+Ethiopic:wght@400;500;700&family=Noto+Serif+Ethiopic:wght@400;600;700&display=swap" rel="stylesheet" />

  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <script>
    tailwind.config = {
      theme: { extend: {
        colors: {
          surface:'#fcf9f2','surface-low':'#f6f3ec','surface-mid':'#f0eee7','surface-high':'#ebe8e1',
          ink:'#1c1c18','ink-soft':'#564242',outline:'#897172','outline-soft':'#dcc0c0',
          primary:'#5b0617','primary-soft':'#7a1f2b','primary-warm':'#a13c46',
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

  <style>
    html, body { background: #fcf9f2; }
    .font-display { font-family: 'Newsreader','Noto Serif Ethiopic',serif; }
    .ethiopic { font-family: 'Noto Sans Ethiopic', serif; }
    .seg-active { background:#fed175; color:#5b0617; }
    :where(a, button, input, select, textarea):focus-visible {
      outline: 2px solid #c9a14a; outline-offset: 2px; border-radius: 2px;
    }

    .nav-item { display:flex; align-items:center; gap:12px; padding:9px 14px; border-radius:4px; color:#564242; font-size:14px; transition:all 150ms ease; position:relative; }
    .nav-item:hover { background: rgba(91,6,23,0.04); color:#5b0617; }
    .nav-item.active { color:#5b0617; font-weight:600; background: rgba(91,6,23,0.06); }
    .nav-item.active::before { content:''; position:absolute; left:-12px; top:6px; bottom:6px; width:2px; background:#c9a14a; border-radius:1px; }
    .nav-section-label { font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:0.18em; color:#897172; padding:0 14px; margin:18px 0 6px; }

    .stat-card { background:#fff; border:1px solid rgba(220,192,192,0.4); border-top-width:3px; border-radius:8px; padding:22px 22px 20px; transition: box-shadow 150ms ease; }
    .stat-card:hover { box-shadow: 0 4px 12px -4px rgba(91,6,23,0.08); }

    .panel { background:#fff; border:1px solid rgba(220,192,192,0.4); border-radius:8px; }

    .pill { display:inline-flex; align-items:center; gap:6px; padding:3px 10px; border-radius:9999px; font-size:11px; font-weight:600; }
    .pill-published { background: rgba(56,71,0,0.10); color:#384700; }
    .pill-draft     { background: rgba(254,209,117,0.4); color:#5c4300; }
    .pill-active    { background: rgba(56,71,0,0.10); color:#384700; }
    .pill-archived  { background: rgba(137,113,114,0.15); color:#564242; }
    .pill-paid      { background: rgba(56,71,0,0.10); color:#384700; }
    .pill-unpaid    { background: rgba(186,26,26,0.10); color:#ba1a1a; }
    .pill-partial   { background: rgba(254,209,117,0.4); color:#5c4300; }

    .quick-actions { background: linear-gradient(180deg,#7a1f2b 0%,#5b0617 100%); color:#f3f0ea; }

    .avatar { width:36px; height:36px; border-radius:9999px; background: linear-gradient(135deg,#a2b665 0%,#384700 100%); color:#fcf9f2; font-weight:700; font-size:13px; display:inline-flex; align-items:center; justify-content:center; letter-spacing:0.05em; }
    .avatar-circle { width:40px; height:40px; border-radius:9999px; display:inline-flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; }

    .bell { position:relative; }
    .bell.has-unread::after { content:''; position:absolute; top:6px; right:6px; width:6px; height:6px; border-radius:9999px; background:#ba1a1a; box-shadow:0 0 0 2px #fcf9f2; }

    .input-field { width:100%; padding:10px 12px; background:#fff; border:1px solid rgba(137,113,114,0.25); border-radius:4px; font-size:14px; color:#1c1c18; transition:all 150ms ease; }
    .input-field:focus { outline:none; border-color:#c9a14a; box-shadow:0 0 0 3px rgba(201,161,74,0.12); }
    .input-field::placeholder { color:#897172; }

    .btn-primary { display:inline-flex; align-items:center; justify-content:center; gap:8px; background:#5b0617; color:#fcf9f2; padding:10px 18px; border-radius:4px; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.18em; transition: background 150ms ease; cursor:pointer; }
    .btn-primary:hover { background:#7a1f2b; }
    .btn-primary:disabled { opacity:0.6; cursor:not-allowed; }
    .btn-ghost { display:inline-flex; align-items:center; justify-content:center; gap:8px; background:#f0eee7; color:#5b0617; padding:10px 18px; border:1px solid rgba(220,192,192,0.5); border-radius:4px; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.18em; transition: background 150ms ease; cursor:pointer; }
    .btn-ghost:hover { background:#ebe8e1; }
    .btn-icon { width:32px; height:32px; border-radius:4px; display:inline-flex; align-items:center; justify-content:center; color:#564242; transition: all 150ms ease; }
    .btn-icon:hover { background:#f0eee7; color:#5b0617; }
    .btn-icon.danger:hover { background: rgba(186,26,26,0.1); color:#ba1a1a; }

    .table-wrap { overflow-x: auto; }
    table.data { width:100%; border-collapse:collapse; font-size:14px; }
    table.data thead th { padding:12px 24px; text-align:left; font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:0.18em; color:#564242; border-bottom:1px solid rgba(220,192,192,0.4); background:#f6f3ec; }
    table.data tbody td { padding:14px 24px; border-bottom:1px solid rgba(220,192,192,0.3); color:#1c1c18; }
    table.data tbody tr:hover td { background: rgba(246,243,236,0.5); }
    table.data tbody tr:last-child td { border-bottom: none; }
  </style>
</head>
<body class="bg-surface text-ink font-body antialiased">

<div class="flex min-h-screen">

  <!-- ============ SIDEBAR ============ -->
  <aside class="w-60 flex-shrink-0 bg-surface border-r border-outline-soft/40 px-5 py-6 flex flex-col sticky top-0 h-screen overflow-y-auto">
    <a href="/admin/index.php" class="flex items-center gap-3 mb-1 px-2">
      <span class="inline-flex items-center justify-center w-9 h-9 rounded-sm bg-primary text-surface">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round">
          <path d="M12 2.5v19M2.5 12h19M6.5 6.5l11 11M17.5 6.5l-11 11"/>
          <circle cx="12" cy="12" r="2.2" fill="currentColor" stroke="none"/>
        </svg>
      </span>
      <div class="flex flex-col">
        <span class="font-display text-lg font-semibold text-primary leading-none" data-en="Gebriel Senbet" data-am="ገብርኤል ሰንበት">Gebriel Senbet</span>
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

        <button class="bell w-10 h-10 rounded-full bg-surface-mid border border-outline-soft/50 inline-flex items-center justify-center text-ink-soft hover:text-primary transition-colors">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10 21h4"/></svg>
        </button>

        <a href="/admin/settings.php" class="avatar hover:opacity-90 transition-opacity"><?= htmlspecialchars($initials) ?></a>
      </div>
    </header>

    <main class="flex-1 px-8 py-8 space-y-6">
