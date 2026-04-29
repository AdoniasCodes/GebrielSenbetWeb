<?php
require_once __DIR__ . '/../../bootstrap.php';

use App\Utils\Csrf;

$config = app_config();
Csrf::ensureSession($config['app']['session_name']);

if (($_SESSION['role_name'] ?? null) !== 'admin') {
    header('Location: /');
    exit;
}
$csrf = Csrf::getToken();
$initials = strtoupper(substr($_SESSION['user_email'] ?? 'GS', 0, 2));
$year = date('Y');
?>
<!DOCTYPE html>
<html lang="en" data-lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard · Gebriel Senbet</title>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,400..700&family=Plus+Jakarta+Sans:wght@400..700&family=Noto+Sans+Ethiopic:wght@400;500;700&family=Noto+Serif+Ethiopic:wght@400;600;700&display=swap" rel="stylesheet" />

  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
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
        }
      }
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

    /* Sidebar nav item */
    .nav-item {
      display: flex; align-items: center; gap: 12px;
      padding: 9px 14px; border-radius: 4px;
      color: #564242; font-size: 14px;
      transition: all 150ms ease;
      position: relative;
    }
    .nav-item:hover { background: rgba(91,6,23,0.04); color: #5b0617; }
    .nav-item.active {
      color: #5b0617; font-weight: 600;
      background: rgba(91,6,23,0.06);
    }
    .nav-item.active::before {
      content: ''; position: absolute;
      left: -12px; top: 6px; bottom: 6px;
      width: 2px; background: #c9a14a;
      border-radius: 1px;
    }
    .nav-section-label {
      font-size: 10px; font-weight: 600; text-transform: uppercase;
      letter-spacing: 0.18em; color: #897172;
      padding: 0 14px; margin: 18px 0 6px;
    }

    /* Stat card with colored top border */
    .stat-card {
      background: #ffffff;
      border: 1px solid rgba(220,192,192,0.4);
      border-top-width: 3px;
      border-radius: 8px;
      padding: 22px 22px 20px;
      transition: box-shadow 150ms ease;
    }
    .stat-card:hover { box-shadow: 0 4px 12px -4px rgba(91,6,23,0.08); }

    /* Dashboard cards */
    .panel {
      background: #ffffff;
      border: 1px solid rgba(220,192,192,0.4);
      border-radius: 8px;
    }

    /* Status pills */
    .pill { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 9999px; font-size: 11px; font-weight: 600; }
    .pill-published { background: rgba(56,71,0,0.10); color: #384700; }
    .pill-draft { background: rgba(254,209,117,0.4); color: #5c4300; }

    /* Quick actions card maroon */
    .quick-actions {
      background: linear-gradient(180deg,#7a1f2b 0%,#5b0617 100%);
      color: #f3f0ea;
    }

    /* Avatar */
    .avatar {
      width: 36px; height: 36px; border-radius: 9999px;
      background: linear-gradient(135deg,#a2b665 0%,#384700 100%);
      color: #fcf9f2; font-weight: 700; font-size: 13px;
      display: inline-flex; align-items: center; justify-content: center;
      letter-spacing: 0.05em;
    }

    /* Notification bell with dot */
    .bell { position: relative; }
    .bell::after {
      content: ''; position: absolute; top: 6px; right: 6px;
      width: 6px; height: 6px; border-radius: 9999px; background: #ba1a1a;
      box-shadow: 0 0 0 2px #fcf9f2;
    }

    /* Trend up/down */
    .trend-up { color: #384700; }
    .trend-flat { color: #897172; }
    .trend-down { color: #ba1a1a; }

    /* Timeline dot */
    .timeline-dot {
      position: relative;
    }
    .timeline-dot::before {
      content:''; position: absolute;
      left: 5px; top: 18px;
      width: 1px; height: calc(100% + 4px);
      background: rgba(220,192,192,0.5);
    }
    .timeline-item:last-child .timeline-dot::before { display: none; }
  </style>
</head>
<body class="bg-surface text-ink font-body antialiased">

  <div class="flex min-h-screen">

    <!-- ============ SIDEBAR ============ -->
    <aside class="w-60 flex-shrink-0 bg-surface border-r border-outline-soft/40 px-5 py-6 flex flex-col sticky top-0 h-screen">
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
        <a href="/admin/index.php" class="nav-item active">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
          <span data-en="Dashboard" data-am="ዋና ገጽ">Dashboard</span>
        </a>

        <p class="nav-section-label" data-en="Registry" data-am="መዝገብ">Registry</p>
        <a href="/admin/users.php" class="nav-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/></svg>
          <span data-en="Students" data-am="ተማሪዎች">Students</span>
        </a>
        <a href="/admin/users.php" class="nav-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M14 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 11l-3-3m3 3l-3 3m3-3h-7"/></svg>
          <span data-en="Teachers" data-am="መምህራን">Teachers</span>
        </a>

        <p class="nav-section-label" data-en="Classroom" data-am="ክፍል">Classroom</p>
        <a href="/admin/legacy.php" class="nav-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M2 4h6a4 4 0 0 1 4 4v13M22 4h-6a4 4 0 0 0-4 4v13"/></svg>
          <span data-en="Curriculum" data-am="ሥርዓተ ትምህርት">Curriculum</span>
        </a>
        <a href="/admin/terms.php" class="nav-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
          <span data-en="Terms" data-am="የትምህርት ወቅቶች">Terms</span>
        </a>
        <a href="/admin/assignments.php" class="nav-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          <span data-en="Assignments" data-am="ምድቦች">Assignments</span>
        </a>

        <p class="nav-section-label" data-en="Records" data-am="መዝገቦች">Records</p>
        <a href="#" class="nav-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/></svg>
          <span data-en="Payments" data-am="ክፍያዎች">Payments</span>
        </a>
        <a href="#" class="nav-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
          <span data-en="Grades" data-am="ውጤቶች">Grades</span>
        </a>

        <p class="nav-section-label" data-en="Resources" data-am="መርጃዎች">Resources</p>
        <a href="#" class="nav-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M3 11l18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>
          <span data-en="Announcements" data-am="ማስታወቂያዎች">Announcements</span>
        </a>
        <a href="#" class="nav-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
          <span data-en="Events" data-am="ዝግጅቶች">Events</span>
        </a>
        <a href="#" class="nav-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
          <span data-en="Posts" data-am="ጽሑፎች">Posts</span>
        </a>
      </nav>

      <div class="-ml-3 pl-3 pt-4 mt-4 border-t border-outline-soft/40 space-y-0.5">
        <a href="#" class="nav-item">
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

      <!-- Top bar -->
      <header class="h-20 border-b border-outline-soft/40 px-8 flex items-center justify-between bg-surface/85 backdrop-blur-md sticky top-0 z-40">
        <div>
          <p class="text-[10px] font-semibold uppercase tracking-widestest text-gold mb-1" data-en="Overview" data-am="አጠቃላይ እይታ">Overview</p>
          <h1 class="font-display text-2xl text-ink leading-none" data-en="Dashboard" data-am="ዋና ገጽ">Dashboard</h1>
        </div>

        <div class="flex items-center gap-3">
          <!-- Term selector -->
          <button class="inline-flex items-center gap-2 bg-surface-mid border border-outline-soft/50 rounded-full px-4 py-2 text-sm text-ink hover:bg-surface-high transition-colors">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            <span><?= $year ?> · <span data-en="Term 1" data-am="ኮርስ 1">Term 1</span></span>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
          </button>

          <!-- Lang toggle -->
          <div data-lang-toggle class="flex items-center bg-surface-mid rounded-full p-0.5 border border-outline-soft/50">
            <button data-lang="en" class="seg-active px-3 py-1 text-xs font-semibold rounded-full">EN</button>
            <button data-lang="am" class="px-3 py-1 text-xs font-semibold rounded-full text-ink-soft hover:text-primary ethiopic">አማ</button>
          </div>

          <!-- Notification bell -->
          <button class="bell w-10 h-10 rounded-full bg-surface-mid border border-outline-soft/50 inline-flex items-center justify-center text-ink-soft hover:text-primary transition-colors">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10 21h4"/></svg>
          </button>

          <!-- Avatar -->
          <div class="avatar"><?= htmlspecialchars($initials) ?></div>
        </div>
      </header>

      <!-- Content -->
      <main class="flex-1 px-8 py-8 space-y-6">

        <!-- Stat cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
          <div class="stat-card border-t-primary">
            <p class="text-[10px] font-semibold uppercase tracking-widestest text-ink-soft flex items-center justify-between" >
              <span data-en="Total Students" data-am="ጠቅላላ ተማሪዎች">Total Students</span>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#897172" stroke-width="1.5" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </p>
            <p class="font-display text-5xl text-ink mt-3 mb-3 leading-none">420</p>
            <p class="text-xs trend-up flex items-center gap-1.5">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 17l5-5 5 5M7 7l5 5 5-5" transform="rotate(180 12 12)"/></svg>
              <span data-en="+12 this term" data-am="+12 በዚህ ኮርስ">+12 this term</span>
            </p>
          </div>

          <div class="stat-card border-t-gold-soft">
            <p class="text-[10px] font-semibold uppercase tracking-widestest text-ink-soft flex items-center justify-between">
              <span data-en="Active Classes" data-am="ንቁ ክፍሎች">Active Classes</span>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#897172" stroke-width="1.5" stroke-linecap="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            </p>
            <p class="font-display text-5xl text-ink mt-3 mb-3 leading-none">18</p>
            <p class="text-xs trend-flat flex items-center gap-1.5">
              <span class="w-3 h-px bg-current"></span>
              <span data-en="No change" data-am="ምንም ለውጥ የለም">No change</span>
            </p>
          </div>

          <div class="stat-card" style="border-top-color:#ba1a1a;">
            <p class="text-[10px] font-semibold uppercase tracking-widestest text-ink-soft flex items-center justify-between">
              <span data-en="Unpaid This Term" data-am="ያልተከፈለ በዚህ ኮርስ">Unpaid This Term</span>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#897172" stroke-width="1.5" stroke-linecap="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
            </p>
            <p class="font-display text-5xl text-error mt-3 mb-3 leading-none">24</p>
            <p class="text-xs text-error flex items-center gap-1.5">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
              <span data-en="Requires attention" data-am="ትኩረት ይፈልጋል">Requires attention</span>
            </p>
          </div>

          <div class="stat-card border-t-olive">
            <p class="text-[10px] font-semibold uppercase tracking-widestest text-ink-soft flex items-center justify-between">
              <span data-en="Posts This Month" data-am="ጽሑፎች በዚህ ወር">Posts This Month</span>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#897172" stroke-width="1.5" stroke-linecap="round"><path d="M3 11l18-5v12L3 14v-3z"/></svg>
            </p>
            <p class="font-display text-5xl text-ink mt-3 mb-3 leading-none">12</p>
            <p class="text-xs trend-up flex items-center gap-1.5">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 17l5-5 5 5M7 7l5 5 5-5" transform="rotate(180 12 12)"/></svg>
              <span data-en="+3 vs last month" data-am="+3 ካለፈው ወር">+3 vs last month</span>
            </p>
          </div>
        </div>

        <!-- Two-column: tables left | rail right -->
        <div class="grid grid-cols-1 xl:grid-cols-[1fr_320px] gap-6">

          <!-- Left column: tables -->
          <div class="space-y-6 min-w-0">

            <!-- Recent Grade Entries -->
            <section class="panel">
              <header class="px-6 py-5 flex items-center justify-between border-b border-outline-soft/40">
                <h2 class="font-display text-lg text-ink" data-en="Recent Grade Entries" data-am="የቅርብ ጊዜ ውጤቶች">Recent Grade Entries</h2>
                <a href="#" class="text-xs font-semibold uppercase tracking-widestest text-gold hover:text-primary" data-en="View all" data-am="ሁሉንም ይመልከቱ">View all</a>
              </header>
              <div class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead>
                    <tr class="text-left">
                      <th class="px-6 py-3 text-[10px] font-semibold uppercase tracking-widestest text-ink-soft" data-en="Class" data-am="ክፍል">Class</th>
                      <th class="px-6 py-3 text-[10px] font-semibold uppercase tracking-widestest text-ink-soft" data-en="Teacher" data-am="መምህር">Teacher</th>
                      <th class="px-6 py-3 text-[10px] font-semibold uppercase tracking-widestest text-ink-soft" data-en="Assessment" data-am="ፈተና">Assessment</th>
                      <th class="px-6 py-3 text-[10px] font-semibold uppercase tracking-widestest text-ink-soft" data-en="Status" data-am="ሁኔታ">Status</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-outline-soft/30">
                    <tr class="hover:bg-surface-low/50 transition-colors">
                      <td class="px-6 py-4 text-ink" data-en="Liturgy Basics (Level 1)" data-am="ሥርዓተ አምልኮ መሠረታዊ (ደረጃ 1)">Liturgy Basics (Level 1)</td>
                      <td class="px-6 py-4 text-ink-soft">Deacon Yared</td>
                      <td class="px-6 py-4 text-ink-soft" data-en="Mid-Term Chanting" data-am="የመካከለኛ ዜማ">Mid-Term Chanting</td>
                      <td class="px-6 py-4"><span class="pill pill-published" data-en="Published" data-am="ታትሟል">Published</span></td>
                    </tr>
                    <tr class="hover:bg-surface-low/50 transition-colors">
                      <td class="px-6 py-4 text-ink ethiopic">Ge'ez Grammar</td>
                      <td class="px-6 py-4 text-ink-soft">Memhir Dawit</td>
                      <td class="px-6 py-4 text-ink-soft" data-en="Weekly Quiz 4" data-am="ሳምንታዊ ፈተና 4">Weekly Quiz 4</td>
                      <td class="px-6 py-4"><span class="pill pill-published" data-en="Published" data-am="ታትሟል">Published</span></td>
                    </tr>
                    <tr class="hover:bg-surface-low/50 transition-colors">
                      <td class="px-6 py-4 text-ink" data-en="Church History (Level 3)" data-am="የቤተ ክርስቲያን ታሪክ (ደረጃ 3)">Church History (Level 3)</td>
                      <td class="px-6 py-4 text-ink-soft">Aba Samuel</td>
                      <td class="px-6 py-4 text-ink-soft" data-en="Final Essay" data-am="የመጨረሻ ድርሰት">Final Essay</td>
                      <td class="px-6 py-4"><span class="pill pill-draft" data-en="Draft" data-am="ረቂቅ">Draft</span></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </section>

            <!-- Recent Enrollments -->
            <section class="panel">
              <header class="px-6 py-5 flex items-center justify-between border-b border-outline-soft/40">
                <h2 class="font-display text-lg text-ink" data-en="Recent Enrollments" data-am="የቅርብ ጊዜ ምዝገባዎች">Recent Enrollments</h2>
                <a href="/admin/users.php" class="text-xs font-semibold uppercase tracking-widestest text-gold hover:text-primary" data-en="Manage" data-am="ያስተዳድሩ">Manage</a>
              </header>
              <ul class="divide-y divide-outline-soft/30">
                <li class="px-6 py-4 flex items-center justify-between hover:bg-surface-low/50 transition-colors">
                  <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-primary/10 text-primary inline-flex items-center justify-center font-bold text-sm">AM</div>
                    <div>
                      <p class="text-ink font-medium">Amanuel Mekonnen</p>
                      <p class="text-xs text-ink-soft" data-en="Enrolled in: Liturgy Basics" data-am="ተመዝግቧል፥ ሥርዓተ አምልኮ መሠረታዊ">Enrolled in: Liturgy Basics</p>
                    </div>
                  </div>
                  <span class="text-xs text-outline" data-en="2 hours ago" data-am="ከ2 ሰዓት በፊት">2 hours ago</span>
                </li>
                <li class="px-6 py-4 flex items-center justify-between hover:bg-surface-low/50 transition-colors">
                  <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-gold/15 text-gold inline-flex items-center justify-center font-bold text-sm">ST</div>
                    <div>
                      <p class="text-ink font-medium">Sara Tadesse</p>
                      <p class="text-xs text-ink-soft ethiopic" data-en="Enrolled in: Ge'ez Grammar" data-am="ተመዝግቧል፥ የግዕዝ ሰዋስው">Enrolled in: Ge'ez Grammar</p>
                    </div>
                  </div>
                  <span class="text-xs text-outline" data-en="Yesterday" data-am="ትናንት">Yesterday</span>
                </li>
                <li class="px-6 py-4 flex items-center justify-between hover:bg-surface-low/50 transition-colors">
                  <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-olive/15 text-olive inline-flex items-center justify-center font-bold text-sm">HG</div>
                    <div>
                      <p class="text-ink font-medium">Hanna Gebre</p>
                      <p class="text-xs text-ink-soft" data-en="Enrolled in: Church History (Level 3)" data-am="ተመዝግቧል፥ የቤተ ክርስቲያን ታሪክ (ደረጃ 3)">Enrolled in: Church History (Level 3)</p>
                    </div>
                  </div>
                  <span class="text-xs text-outline" data-en="2 days ago" data-am="ከ2 ቀን በፊት">2 days ago</span>
                </li>
              </ul>
            </section>
          </div>

          <!-- Right rail -->
          <div class="space-y-6">

            <!-- This Week -->
            <section class="panel p-6">
              <header class="flex items-center justify-between mb-5">
                <h2 class="font-display text-lg text-ink" data-en="This Week" data-am="በዚህ ሳምንት">This Week</h2>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#795901" stroke-width="1.6" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
              </header>
              <ul class="space-y-5">
                <li class="timeline-item flex gap-3">
                  <div class="timeline-dot relative pt-1.5">
                    <span class="block w-2.5 h-2.5 rounded-full bg-primary"></span>
                  </div>
                  <div class="flex-1 pb-1">
                    <p class="text-[10px] font-semibold uppercase tracking-widestest text-gold mb-0.5"><span data-en="Today" data-am="ዛሬ">Today</span>, 4:00 PM</p>
                    <p class="font-display text-ink leading-snug" data-en="Faculty Meeting" data-am="የመምህራን ስብሰባ">Faculty Meeting</p>
                    <p class="text-xs text-ink-soft mt-0.5" data-en="Main Hall" data-am="ዋና አዳራሽ">Main Hall</p>
                  </div>
                </li>
                <li class="timeline-item flex gap-3">
                  <div class="timeline-dot relative pt-1.5">
                    <span class="block w-2.5 h-2.5 rounded-full bg-outline-soft"></span>
                  </div>
                  <div class="flex-1 pb-1">
                    <p class="text-[10px] font-semibold uppercase tracking-widestest text-outline mb-0.5"><span data-en="Tomorrow" data-am="ነገ">Tomorrow</span>, 9:00 AM</p>
                    <p class="font-display text-ink leading-snug" data-en="Registration Deadline" data-am="የምዝገባ መጨረሻ ቀን">Registration Deadline</p>
                    <p class="text-xs text-ink-soft mt-0.5" data-en="Term 1 Late Additions" data-am="የኮርስ 1 ዘግይተው የተጨመሩ">Term 1 Late Additions</p>
                  </div>
                </li>
                <li class="timeline-item flex gap-3">
                  <div class="timeline-dot relative pt-1.5">
                    <span class="block w-2.5 h-2.5 rounded-full bg-outline-soft"></span>
                  </div>
                  <div class="flex-1">
                    <p class="text-[10px] font-semibold uppercase tracking-widestest text-outline mb-0.5"><span data-en="Friday" data-am="አርብ">Friday</span>, 2:00 PM</p>
                    <p class="font-display text-ink leading-snug" data-en="System Maintenance" data-am="የሥርዓት ጥገና">System Maintenance</p>
                    <p class="text-xs text-ink-soft mt-0.5" data-en="Portal downtime expected" data-am="የመግቢያ መቋረጥ ይጠበቃል">Portal downtime expected</p>
                  </div>
                </li>
              </ul>
              <a href="#" class="mt-6 block w-full text-center bg-surface-mid border border-outline-soft/50 text-xs font-semibold uppercase tracking-widestest text-ink py-2.5 rounded hover:bg-surface-high transition-colors" data-en="View Full Calendar" data-am="ሙሉ የቀን መቁጠሪያ">View Full Calendar</a>
            </section>

            <!-- Quick Actions -->
            <section class="quick-actions rounded-lg p-6 relative overflow-hidden">
              <svg class="absolute -right-6 -top-6 opacity-15" width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="#c9a14a" stroke-width="0.5"><path d="M12 2.5v19M2.5 12h19M6.5 6.5l11 11M17.5 6.5l-11 11"/></svg>
              <h2 class="font-display text-lg mb-4" data-en="Quick Actions" data-am="ፈጣን እርምጃዎች">Quick Actions</h2>
              <div class="grid grid-cols-2 gap-3">
                <a href="/admin/users.php" class="bg-primary/40 hover:bg-primary/60 transition-colors rounded p-4 flex flex-col items-center gap-2 text-center border border-gold-soft/30">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M8.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM20 8v6M23 11h-6"/></svg>
                  <span class="text-xs font-semibold uppercase tracking-widestest" data-en="New Student" data-am="አዲስ ተማሪ">New Student</span>
                </a>
                <a href="#" class="bg-primary/40 hover:bg-primary/60 transition-colors rounded p-4 flex flex-col items-center gap-2 text-center border border-gold-soft/30">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                  <span class="text-xs font-semibold uppercase tracking-widestest" data-en="Create Post" data-am="ጽሑፍ ይፍጠሩ">Create Post</span>
                </a>
              </div>
            </section>
          </div>
        </div>
      </main>
    </div>
  </div>

  <script>
    // Bilingual swap (shared with landing/login)
    (function () {
      function applyLang(lang) {
        if (lang !== 'en' && lang !== 'am') lang = 'en';
        document.documentElement.setAttribute('data-lang', lang);
        document.documentElement.lang = lang;
        document.querySelectorAll('[data-en], [data-am]').forEach(function (el) {
          var v = el.getAttribute('data-' + lang);
          if (v !== null) el.innerHTML = v;
        });
        document.querySelectorAll('[data-lang-toggle]').forEach(function (group) {
          group.querySelectorAll('button').forEach(function (btn) {
            btn.classList.toggle('seg-active', btn.dataset.lang === lang);
            btn.classList.toggle('text-ink-soft', btn.dataset.lang !== lang);
          });
        });
        try { localStorage.setItem('gs_lang', lang); } catch (e) {}
      }
      document.querySelectorAll('[data-lang-toggle] button').forEach(function (btn) {
        btn.addEventListener('click', function () { applyLang(btn.dataset.lang); });
      });
      var saved = 'en';
      try { saved = localStorage.getItem('gs_lang') || 'en'; } catch (e) {}
      applyLang(saved);
    })();

    // Logout
    async function ensureCsrf() {
      var t = sessionStorage.getItem('csrf_token');
      if (!t) {
        var r = await fetch('/api/auth/csrf.php');
        var d = await r.json();
        t = d.csrf_token;
        sessionStorage.setItem('csrf_token', t);
      }
      return t;
    }
    document.getElementById('logoutBtn').addEventListener('click', async function () {
      var token = await ensureCsrf();
      var res = await fetch('/api/auth/logout.php', { method: 'POST', headers: { 'X-CSRF-Token': token } });
      if (res.ok) window.location.href = '/';
    });
  </script>
</body>
</html>
