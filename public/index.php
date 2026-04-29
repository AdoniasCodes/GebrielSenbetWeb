<?php
// public/index.php — Public landing page (Sacred Scholarly Minimalist)

use App\Utils\Csrf;

require_once __DIR__ . '/../bootstrap.php';

$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
$role = $_SESSION['role_name'] ?? null;

if ($role === 'admin') {
    header('Location: /admin/index.php');
    exit;
} elseif ($role === 'teacher') {
    header('Location: /teacher/index.php');
    exit;
} elseif ($role === 'student') {
    header('Location: /student/index.php');
    exit;
}

$year = date('Y');
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Gebriel Senbet — Sabbath School</title>
  <meta name="description" content="Saint Gabriel Sabbath School. A modern home for our Sunday school: curriculum, grading, payments, and community announcements in one reverent place." />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,400..700&family=Plus+Jakarta+Sans:wght@400..700&family=Noto+Sans+Ethiopic:wght@400;500;700&display=swap" rel="stylesheet" />

  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            surface:        '#fcf9f2',
            'surface-low':  '#f6f3ec',
            'surface-mid':  '#f0eee7',
            'surface-high': '#ebe8e1',
            ink:            '#1c1c18',
            'ink-soft':     '#564242',
            outline:        '#897172',
            'outline-soft': '#dcc0c0',
            primary:        '#5b0617',
            'primary-soft': '#7a1f2b',
            'primary-warm': '#a13c46',
            gold:           '#795901',
            'gold-soft':    '#c9a14a',
            'gold-warm':    '#fed175',
            olive:          '#384700',
            'olive-soft':   '#a2b665',
          },
          fontFamily: {
            display:  ['Newsreader', 'serif'],
            body:     ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
            ethiopic: ['"Noto Sans Ethiopic"', 'serif'],
          },
          letterSpacing: {
            widestest: '0.18em',
          },
        },
      },
    };
  </script>

  <style>
    html, body { background: #fcf9f2; }
    body { font-feature-settings: 'kern','liga','dlig'; }

    /* Paper grain — subtle vellum texture */
    .paper {
      background-image:
        radial-gradient(circle at 1px 1px, rgba(91,6,23,0.035) 1px, transparent 0),
        radial-gradient(circle at 13px 9px, rgba(121,89,1,0.025) 1px, transparent 0);
      background-size: 24px 24px, 28px 28px;
    }

    /* Thin gold rule */
    .rule-gold {
      height: 1px;
      background: linear-gradient(to right, transparent, #c9a14a 20%, #c9a14a 80%, transparent);
    }
    .rule-gold-short { display:inline-block; width: 48px; height: 1px; background: #c9a14a; vertical-align: middle; }
    .rule-gold-tiny  { display:inline-block; width: 12px; height: 1px; background: #c9a14a; vertical-align: middle; }

    /* Manuscript card — decorative corner ornaments */
    .manuscript {
      position: relative;
      background:
        linear-gradient(180deg, #fffdf6 0%, #fbf6e9 100%);
      border: 1px solid rgba(201,161,74,0.35);
    }
    .manuscript::before, .manuscript::after,
    .manuscript .corner-tr, .manuscript .corner-bl {
      content: '';
      position: absolute;
      width: 28px; height: 28px;
      border: 1px solid #c9a14a;
      pointer-events: none;
    }
    .manuscript::before     { top: 14px;    left: 14px;   border-right: none; border-bottom: none; }
    .manuscript::after      { bottom: 14px; right: 14px;  border-left: none;  border-top: none;    }
    .manuscript .corner-tr  { top: 14px;    right: 14px;  border-left: none;  border-bottom: none; }
    .manuscript .corner-bl  { bottom: 14px; left: 14px;   border-right: none; border-top: none;    }

    /* Section eyebrow */
    .eyebrow {
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.18em;
      color: #795901;
      display: inline-flex;
      align-items: center;
      gap: 12px;
    }

    /* Ge'ez script — slightly larger optical balance with Latin */
    .ethiopic { font-family: 'Noto Sans Ethiopic', serif; font-size: 1.08em; line-height: 1.65; }

    /* Hover line animation on links */
    .link-arrow svg { transition: transform 200ms ease; }
    .link-arrow:hover svg { transform: translateX(4px); }

    /* Pattern: liturgical chevrons (subtle) for program card headers */
    .pattern-children {
      background-color: #f6f3ec;
      background-image:
        radial-gradient(circle at 20% 30%, rgba(201,161,74,0.18) 0, transparent 32px),
        radial-gradient(circle at 75% 70%, rgba(91,6,23,0.10) 0, transparent 36px),
        radial-gradient(circle at 50% 50%, rgba(56,71,0,0.08) 0, transparent 28px);
    }
    .pattern-adult {
      background-color: #f0eee7;
      background-image:
        repeating-linear-gradient(135deg, rgba(201,161,74,0.10) 0 1px, transparent 1px 14px),
        repeating-linear-gradient(45deg, rgba(91,6,23,0.06) 0 1px, transparent 1px 22px);
    }

    /* Sticky language pill segmented control */
    .seg-active {
      background: #fed175;
      color: #5b0617;
    }

    /* Scripture section: dark maroon */
    .scripture {
      background: radial-gradient(ellipse at top, #7a1f2b 0%, #5b0617 60%, #40000c 100%);
      color: #f3f0ea;
    }

    /* Smooth focus ring */
    :where(a, button, input, select, textarea):focus-visible {
      outline: 2px solid #c9a14a;
      outline-offset: 2px;
      border-radius: 2px;
    }
  </style>
</head>
<body class="bg-surface text-ink font-body antialiased">

  <!-- ============ TOP NAV ============ -->
  <header class="sticky top-0 z-50 border-b border-outline-soft/40 bg-surface/85 backdrop-blur-md">
    <div class="max-w-[1280px] mx-auto px-6 lg:px-8 h-16 flex items-center justify-between">
      <!-- Logo -->
      <a href="/" class="flex items-center gap-3 group">
        <span class="inline-flex items-center justify-center w-8 h-8 rounded-sm bg-primary text-surface">
          <!-- Stylized Ethiopian cross monogram -->
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round">
            <path d="M12 2.5v19M2.5 12h19M6.5 6.5l11 11M17.5 6.5l-11 11"/>
            <circle cx="12" cy="12" r="2.2" fill="currentColor" stroke="none"/>
          </svg>
        </span>
        <span class="font-display text-xl font-semibold tracking-tight text-primary leading-none">Gebriel Senbet</span>
      </a>

      <!-- Center nav -->
      <nav class="hidden md:flex items-center gap-8 text-[15px] text-ink-soft">
        <a class="hover:text-primary transition-colors" href="#about">About</a>
        <a class="hover:text-primary transition-colors" href="#programs">Programs</a>
        <a class="hover:text-primary transition-colors" href="#calendar">Calendar</a>
        <a class="hover:text-primary transition-colors" href="#blog">Blog</a>
      </nav>

      <!-- Right -->
      <div class="flex items-center gap-3">
        <!-- Language toggle -->
        <div role="group" aria-label="Language" class="hidden sm:flex items-center bg-surface-mid rounded-full p-0.5 border border-outline-soft/50">
          <button data-lang="en" class="seg-active px-3 py-1 text-xs font-semibold rounded-full">EN</button>
          <button data-lang="am" class="px-3 py-1 text-xs font-semibold rounded-full text-ink-soft hover:text-primary ethiopic">አማ</button>
          <button data-lang="ti" class="px-3 py-1 text-xs font-semibold rounded-full text-ink-soft hover:text-primary ethiopic">ትግ</button>
        </div>
        <a href="/login.html" class="inline-flex items-center gap-2 bg-primary text-surface px-4 py-2 rounded text-xs font-semibold uppercase tracking-widestest hover:bg-primary-soft transition-colors">
          Sign in
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
        </a>
      </div>
    </div>
  </header>

  <main>

    <!-- ============ HERO ============ -->
    <section class="paper relative overflow-hidden">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-8 py-20 lg:py-28 grid lg:grid-cols-12 gap-10 lg:gap-16 items-center">
        <!-- Copy -->
        <div class="lg:col-span-7">
          <div class="inline-flex items-center gap-2 rounded-full bg-surface-mid border border-outline-soft/60 px-3 py-1 mb-7">
            <span class="w-1.5 h-1.5 rounded-full bg-gold animate-pulse"></span>
            <span class="text-[11px] font-semibold uppercase tracking-widestest text-gold">Enrollment open · <?= $year ?></span>
          </div>

          <h1 class="font-display text-[44px] lg:text-[60px] leading-[1.05] tracking-tight text-primary font-semibold">
            A modern home for our<br/>Sabbath school.
          </h1>

          <div class="mt-5 flex items-center gap-4">
            <span class="rule-gold-short"></span>
            <p class="ethiopic text-xl text-ink-soft">ለሰንበት ት/ቤታችን ዘመናዊ ቤት</p>
          </div>

          <p class="mt-7 text-lg leading-relaxed text-ink-soft max-w-xl">
            Saint Gabriel Sabbath School brings curriculum, grading, payments, and community announcements into one focused space — built with the rhythm of our church year and the warmth of our community in mind.
          </p>

          <div class="mt-8 flex flex-col sm:flex-row gap-3">
            <a href="/login.html" class="inline-flex justify-center items-center gap-2 bg-primary text-surface px-7 py-3.5 rounded text-xs font-semibold uppercase tracking-widestest hover:bg-primary-soft transition-colors">
              Sign in
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
            </a>
            <a href="#enroll" class="inline-flex justify-center items-center gap-2 border border-outline text-primary px-7 py-3.5 rounded text-xs font-semibold uppercase tracking-widestest hover:bg-surface-mid transition-colors">
              Request enrollment
            </a>
          </div>

          <div class="mt-10 flex items-center gap-6 text-sm text-outline">
            <div class="flex items-center gap-2"><span class="w-1 h-1 rounded-full bg-gold"></span><span>Two tracks</span></div>
            <div class="flex items-center gap-2"><span class="w-1 h-1 rounded-full bg-gold"></span><span>13 levels</span></div>
            <div class="flex items-center gap-2"><span class="w-1 h-1 rounded-full bg-gold"></span><span>One faith</span></div>
          </div>
        </div>

        <!-- Manuscript card — pure CSS/SVG, no photo -->
        <div class="lg:col-span-5">
          <div class="manuscript rounded-lg aspect-[4/5] flex flex-col items-center justify-center px-8 py-12 text-center shadow-sm">
            <span class="corner-tr"></span>
            <span class="corner-bl"></span>

            <!-- Cross at top -->
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#c9a14a" stroke-width="1.4" stroke-linecap="round" class="mb-6">
              <path d="M12 2.5v19M2.5 12h19M6.5 6.5l11 11M17.5 6.5l-11 11"/>
              <circle cx="12" cy="12" r="2" fill="#c9a14a" stroke="none"/>
            </svg>

            <p class="eyebrow text-gold mb-5"><span class="rule-gold-tiny"></span>Sanctified Curriculum<span class="rule-gold-tiny"></span></p>

            <!-- Large illuminated initial — Ge'ez "ገ" (Ge / Gabriel) -->
            <div class="font-display ethiopic text-[140px] lg:text-[160px] leading-none text-primary font-bold mb-2" style="font-family: 'Noto Sans Ethiopic', serif;">ገ</div>

            <p class="ethiopic text-2xl text-primary-soft mb-1">ገብርኤል ሰንበት</p>
            <p class="ethiopic text-sm text-outline">ት/ቤት</p>

            <div class="mt-8 w-full">
              <div class="rule-gold mb-4"></div>
              <p class="text-xs uppercase tracking-widestest text-outline">Est. Saint Gabriel Parish</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ============ MISSION / ABOUT ============ -->
    <section id="about" class="bg-surface-low border-y border-outline-soft/40">
      <div class="max-w-3xl mx-auto px-6 lg:px-8 py-20 text-center">
        <p class="eyebrow"><span class="rule-gold-tiny"></span>Our Mission<span class="rule-gold-tiny"></span></p>
        <h2 class="font-display text-3xl lg:text-4xl text-primary mt-5 mb-6 leading-tight">
          Bridging heritage and<br/>everyday efficiency.
        </h2>
        <p class="text-lg text-ink-soft leading-relaxed">
          For decades, our Sabbath school has shaped the faith and scholarship of generations. Today we honor that legacy with tools that respect the gravity of the work — and the people who do it.
        </p>
      </div>
    </section>

    <!-- ============ PILLARS ============ -->
    <section class="max-w-[1280px] mx-auto px-6 lg:px-8 py-20">
      <div class="text-center mb-14">
        <p class="eyebrow"><span class="rule-gold-tiny"></span>Three Pillars<span class="rule-gold-tiny"></span></p>
        <h2 class="font-display text-3xl lg:text-4xl text-primary mt-4">What we hold up.</h2>
      </div>

      <div class="grid md:grid-cols-3 gap-px bg-outline-soft/30 rounded-lg overflow-hidden border border-outline-soft/40">
        <!-- Pillar 1 -->
        <div class="bg-surface p-8 lg:p-10">
          <div class="w-11 h-11 rounded-sm bg-primary/10 text-primary inline-flex items-center justify-center mb-5">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M12 2.5v19M2.5 12h19M6.5 6.5l11 11M17.5 6.5l-11 11"/></svg>
          </div>
          <h3 class="font-display text-xl text-primary mb-2">Spiritual Formation</h3>
          <p class="text-ink-soft leading-relaxed">Catechism, scripture, and tradition taught with reverence — for every age, in every track.</p>
        </div>
        <!-- Pillar 2 -->
        <div class="bg-surface p-8 lg:p-10">
          <div class="w-11 h-11 rounded-sm bg-gold/15 text-gold inline-flex items-center justify-center mb-5">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M2 4h6a4 4 0 0 1 4 4v13M22 4h-6a4 4 0 0 0-4 4v13"/></svg>
          </div>
          <h3 class="font-display text-xl text-primary mb-2">Scholarly Tradition</h3>
          <p class="text-ink-soft leading-relaxed">Structured progression through historical, theological, and liturgical study — paced by term.</p>
        </div>
        <!-- Pillar 3 -->
        <div class="bg-surface p-8 lg:p-10">
          <div class="w-11 h-11 rounded-sm bg-olive/15 text-olive inline-flex items-center justify-center mb-5">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </div>
          <h3 class="font-display text-xl text-primary mb-2">Community Service</h3>
          <p class="text-ink-soft leading-relaxed">A school is its people. We make it easier for families, teachers, and clergy to stay woven together.</p>
        </div>
      </div>
    </section>

    <!-- ============ PROGRAMS ============ -->
    <section id="programs" class="bg-surface-low border-y border-outline-soft/40">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-8 py-20">
        <div class="flex items-end justify-between mb-12 gap-6 flex-wrap">
          <div>
            <p class="eyebrow"><span class="rule-gold-tiny"></span>Two Tracks<span class="rule-gold-tiny"></span></p>
            <h2 class="font-display text-3xl lg:text-4xl text-primary mt-4">Programs.</h2>
          </div>
          <p class="max-w-md text-ink-soft">Every member of the parish has a place. Our curriculum begins in the nursery and continues through the five traditional stages of adult formation.</p>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
          <!-- Children's Track -->
          <article class="bg-surface rounded-lg overflow-hidden border border-outline-soft/40 hover:shadow-md transition-shadow group">
            <div class="pattern-children h-40 relative">
              <div class="absolute inset-0 bg-gradient-to-t from-surface to-transparent"></div>
              <span class="absolute top-5 left-6 text-[11px] font-semibold uppercase tracking-widestest text-gold">Track 01</span>
            </div>
            <div class="p-7">
              <h3 class="font-display text-2xl text-primary mb-1">Children's Track</h3>
              <p class="text-sm text-outline mb-5">Nursery → Grade 6</p>
              <p class="text-ink-soft leading-relaxed mb-6">Foundational catechesis through stories, song, and sacred text — paced for young hearts and curious minds.</p>
              <div class="flex flex-wrap gap-1.5 mb-6">
                <?php foreach (['Nursery','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6'] as $lvl): ?>
                  <span class="text-xs px-2.5 py-1 rounded-full bg-surface-mid text-ink-soft border border-outline-soft/50"><?= htmlspecialchars($lvl) ?></span>
                <?php endforeach; ?>
              </div>
              <a href="#" class="link-arrow inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widestest text-primary hover:text-primary-soft">
                Explore the curriculum
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
              </a>
            </div>
          </article>

          <!-- Youth & Adult Track -->
          <article class="bg-surface rounded-lg overflow-hidden border border-outline-soft/40 hover:shadow-md transition-shadow group">
            <div class="pattern-adult h-40 relative">
              <div class="absolute inset-0 bg-gradient-to-t from-surface to-transparent"></div>
              <span class="absolute top-5 left-6 text-[11px] font-semibold uppercase tracking-widestest text-gold">Track 02</span>
            </div>
            <div class="p-7">
              <h3 class="font-display text-2xl text-primary mb-1">Youth &amp; Adult Track</h3>
              <p class="ethiopic text-sm text-outline mb-5">ቀዳማይ → ሃምሳይ</p>
              <p class="text-ink-soft leading-relaxed mb-6">The five traditional stages of formation — taught in Amharic and Tigrinya — for youth, parents, and elders alike.</p>
              <div class="flex flex-wrap gap-1.5 mb-6">
                <?php foreach (['ቀዳማይ','ካላዓይ','ሳልሳይ','ራብዓይ','ሃምሳይ'] as $lvl): ?>
                  <span class="ethiopic text-xs px-2.5 py-1 rounded-full bg-surface-mid text-ink-soft border border-outline-soft/50"><?= $lvl ?></span>
                <?php endforeach; ?>
              </div>
              <a href="#" class="link-arrow inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widestest text-primary hover:text-primary-soft">
                Explore the curriculum
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
              </a>
            </div>
          </article>
        </div>
      </div>
    </section>

    <!-- ============ FEATURES GRID ============ -->
    <section class="max-w-[1280px] mx-auto px-6 lg:px-8 py-20">
      <div class="max-w-2xl mb-14">
        <p class="eyebrow"><span class="rule-gold-tiny"></span>What's Inside<span class="rule-gold-tiny"></span></p>
        <h2 class="font-display text-3xl lg:text-4xl text-primary mt-4 leading-tight">A reverent operating system for the school year.</h2>
      </div>

      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-px bg-outline-soft/30 rounded-lg overflow-hidden border border-outline-soft/40">
        <?php
          $features = [
            ['Curriculum', 'Define tracks, levels, classes, and subjects with the structure your school already uses.',
             '<path d="M2 4h6a4 4 0 0 1 4 4v13M22 4h-6a4 4 0 0 0-4 4v13"/>'],
            ['Grading & report cards', 'Teachers enter scores per subject and term. Students and families view results.',
             '<path d="M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>'],
            ['Class scheduling', 'Assign teachers as primary or substitute, with date-bounded responsibility.',
             '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>'],
            ['Payments tracking', 'Per-term payment status, partial balances, and one-glance defaulter lists.',
             '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/>'],
            ['Events & calendar', 'Recurring services, holy days, and parish events in a unified calendar.',
             '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>'],
            ['Announcements', 'Targeted notices to a class, a role, or families with outstanding payments.',
             '<path d="M3 11l18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>'],
          ];
          foreach ($features as $f): ?>
            <div class="bg-surface p-7 lg:p-8 group">
              <div class="w-9 h-9 rounded-sm bg-gold/10 text-gold inline-flex items-center justify-center mb-4 group-hover:bg-gold/20 transition-colors">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><?= $f[2] ?></svg>
              </div>
              <h3 class="font-display text-lg text-primary mb-2"><?= htmlspecialchars($f[0]) ?></h3>
              <p class="text-sm text-ink-soft leading-relaxed"><?= htmlspecialchars($f[1]) ?></p>
            </div>
          <?php endforeach; ?>
      </div>
    </section>

    <!-- ============ ROLES ============ -->
    <section class="bg-surface-low border-y border-outline-soft/40">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-8 py-20">
        <div class="text-center max-w-2xl mx-auto mb-14">
          <p class="eyebrow"><span class="rule-gold-tiny"></span>Built For Everyone<span class="rule-gold-tiny"></span></p>
          <h2 class="font-display text-3xl lg:text-4xl text-primary mt-4">A view for every seat in the room.</h2>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
          <!-- Admin -->
          <div class="bg-surface rounded-lg p-7 border border-outline-soft/40">
            <div class="flex items-center gap-3 mb-5">
              <span class="w-2 h-2 rounded-full bg-primary"></span>
              <span class="text-[11px] font-semibold uppercase tracking-widestest text-primary">Administrator</span>
            </div>
            <h3 class="font-display text-xl text-ink mb-4">For the superintendent.</h3>
            <ul class="space-y-3 text-sm text-ink-soft">
              <li class="flex gap-3"><span class="text-gold mt-1.5 inline-block w-1 h-1 rounded-full bg-gold flex-shrink-0"></span><span>Manage tracks, levels, classes, subjects, and academic terms.</span></li>
              <li class="flex gap-3"><span class="text-gold mt-1.5 inline-block w-1 h-1 rounded-full bg-gold flex-shrink-0"></span><span>Onboard teachers and students with auto-generated credentials.</span></li>
              <li class="flex gap-3"><span class="text-gold mt-1.5 inline-block w-1 h-1 rounded-full bg-gold flex-shrink-0"></span><span>Reconcile payments and broadcast announcements.</span></li>
            </ul>
          </div>

          <!-- Teacher -->
          <div class="bg-surface rounded-lg p-7 border border-outline-soft/40">
            <div class="flex items-center gap-3 mb-5">
              <span class="w-2 h-2 rounded-full bg-gold"></span>
              <span class="text-[11px] font-semibold uppercase tracking-widestest text-gold">Teacher</span>
            </div>
            <h3 class="font-display text-xl text-ink mb-4">For the instructor.</h3>
            <ul class="space-y-3 text-sm text-ink-soft">
              <li class="flex gap-3"><span class="text-gold mt-1.5 inline-block w-1 h-1 rounded-full bg-gold flex-shrink-0"></span><span>See only the classes and subjects assigned to you.</span></li>
              <li class="flex gap-3"><span class="text-gold mt-1.5 inline-block w-1 h-1 rounded-full bg-gold flex-shrink-0"></span><span>Enter and revise grades inline by term.</span></li>
              <li class="flex gap-3"><span class="text-gold mt-1.5 inline-block w-1 h-1 rounded-full bg-gold flex-shrink-0"></span><span>Post lesson notes and handouts to your roster.</span></li>
            </ul>
          </div>

          <!-- Student -->
          <div class="bg-surface rounded-lg p-7 border border-outline-soft/40">
            <div class="flex items-center gap-3 mb-5">
              <span class="w-2 h-2 rounded-full bg-olive"></span>
              <span class="text-[11px] font-semibold uppercase tracking-widestest text-olive">Student &amp; Family</span>
            </div>
            <h3 class="font-display text-xl text-ink mb-4">For the learner.</h3>
            <ul class="space-y-3 text-sm text-ink-soft">
              <li class="flex gap-3"><span class="text-gold mt-1.5 inline-block w-1 h-1 rounded-full bg-gold flex-shrink-0"></span><span>Review grades, remarks, and printable report cards.</span></li>
              <li class="flex gap-3"><span class="text-gold mt-1.5 inline-block w-1 h-1 rounded-full bg-gold flex-shrink-0"></span><span>See upcoming services, exams, and special events.</span></li>
              <li class="flex gap-3"><span class="text-gold mt-1.5 inline-block w-1 h-1 rounded-full bg-gold flex-shrink-0"></span><span>Track tuition status and read announcements.</span></li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- ============ SCRIPTURE / QUOTE ============ -->
    <section class="scripture relative overflow-hidden">
      <!-- Decorative cross watermark -->
      <svg class="absolute -right-10 -top-10 opacity-10" width="280" height="280" viewBox="0 0 24 24" fill="none" stroke="#c9a14a" stroke-width="0.6" stroke-linecap="round">
        <path d="M12 2.5v19M2.5 12h19M6.5 6.5l11 11M17.5 6.5l-11 11"/>
        <circle cx="12" cy="12" r="2.5"/>
      </svg>
      <div class="max-w-3xl mx-auto px-6 lg:px-8 py-24 text-center relative">
        <div class="rule-gold mb-10 max-w-[120px] mx-auto opacity-60"></div>
        <p class="font-display text-[28px] lg:text-[36px] leading-[1.3] italic text-surface mb-7">
          "Train up a child in the way he should go, and when he is old he will not depart from it."
        </p>
        <p class="ethiopic text-xl text-gold-warm/90 mb-10">
          ሕጻን በሚሄድበት መንገድ አስተምረው፣ ከሸመገለ ጊዜ ከዚያ አይለይም።
        </p>
        <p class="eyebrow text-gold-warm/80"><span class="rule-gold-tiny"></span>Proverbs 22:6<span class="rule-gold-tiny"></span></p>
      </div>
    </section>

    <!-- ============ CALENDAR / EVENTS PREVIEW ============ -->
    <section id="calendar" class="max-w-[1280px] mx-auto px-6 lg:px-8 py-20">
      <div class="flex items-end justify-between mb-12 gap-6 flex-wrap">
        <div>
          <p class="eyebrow"><span class="rule-gold-tiny"></span>The Year Ahead<span class="rule-gold-tiny"></span></p>
          <h2 class="font-display text-3xl lg:text-4xl text-primary mt-4">Upcoming.</h2>
        </div>
        <a href="#" class="link-arrow inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widestest text-primary">
          See full calendar
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
        </a>
      </div>

      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php
          $events = [
            ['MAY','12','Term 1 begins','Sunday · 9:00 AM','All tracks'],
            ['MAY','19','Parent orientation','Sunday · 11:30 AM','Children\'s Track'],
            ['JUN','02','Liturgical retreat','Saturday · 9:00 AM','Youth & Adult'],
            ['JUN','23','Mid-term assessments','Sunday · 9:00 AM','All tracks'],
          ];
          foreach ($events as $e): ?>
            <article class="bg-surface rounded-lg border border-outline-soft/40 p-6 hover:shadow-md transition-shadow flex gap-4">
              <div class="flex-shrink-0 w-14 text-center">
                <div class="text-[10px] font-semibold uppercase tracking-widestest text-gold"><?= $e[0] ?></div>
                <div class="font-display text-3xl text-primary leading-none mt-1"><?= $e[1] ?></div>
              </div>
              <div class="border-l border-outline-soft/40 pl-4">
                <h3 class="font-display text-base text-ink leading-tight mb-1"><?= htmlspecialchars($e[2]) ?></h3>
                <p class="text-xs text-ink-soft mb-2"><?= htmlspecialchars($e[3]) ?></p>
                <span class="text-[10px] uppercase tracking-widestest text-outline"><?= htmlspecialchars($e[4]) ?></span>
              </div>
            </article>
          <?php endforeach; ?>
      </div>
    </section>

    <!-- ============ FINAL CTA ============ -->
    <section id="enroll" class="bg-surface-low border-y border-outline-soft/40">
      <div class="max-w-3xl mx-auto px-6 lg:px-8 py-20 text-center">
        <p class="eyebrow"><span class="rule-gold-tiny"></span>Begin<span class="rule-gold-tiny"></span></p>
        <h2 class="font-display text-3xl lg:text-5xl text-primary mt-5 mb-6 leading-tight">
          A faith-formed school year awaits.
        </h2>
        <p class="ethiopic text-lg text-ink-soft mb-10">
          ለትምህርት ዓመቱ ዝግጁ ነን።
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
          <a href="/login.html" class="inline-flex justify-center items-center gap-2 bg-primary text-surface px-7 py-3.5 rounded text-xs font-semibold uppercase tracking-widestest hover:bg-primary-soft transition-colors">
            Sign in
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
          </a>
          <a href="#" class="inline-flex justify-center items-center gap-2 border border-outline text-primary px-7 py-3.5 rounded text-xs font-semibold uppercase tracking-widestest hover:bg-surface transition-colors">
            Request enrollment
          </a>
        </div>
      </div>
    </section>
  </main>

  <!-- ============ FOOTER ============ -->
  <footer class="bg-surface-mid border-t border-outline-soft/40">
    <div class="max-w-[1280px] mx-auto px-6 lg:px-8 py-16">
      <div class="grid md:grid-cols-4 gap-10 mb-12">
        <div class="md:col-span-1">
          <div class="flex items-center gap-3 mb-4">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-sm bg-primary text-surface">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round">
                <path d="M12 2.5v19M2.5 12h19M6.5 6.5l11 11M17.5 6.5l-11 11"/>
                <circle cx="12" cy="12" r="2.2" fill="currentColor" stroke="none"/>
              </svg>
            </span>
            <span class="font-display text-lg font-semibold text-primary">Gebriel Senbet</span>
          </div>
          <p class="text-sm text-ink-soft leading-relaxed mb-4">
            Saint Gabriel Sabbath School. A modern home for our community of faith and learning.
          </p>
          <p class="ethiopic text-sm text-outline">ገብርኤል ሰንበት ት/ቤት</p>
        </div>

        <div>
          <h4 class="text-[11px] font-semibold uppercase tracking-widestest text-gold mb-4">Programs</h4>
          <ul class="space-y-2.5 text-sm text-ink-soft">
            <li><a href="#programs" class="hover:text-primary transition-colors">Children's Track</a></li>
            <li><a href="#programs" class="hover:text-primary transition-colors">Youth &amp; Adult Track</a></li>
            <li><a href="#calendar" class="hover:text-primary transition-colors">Academic calendar</a></li>
            <li><a href="#" class="hover:text-primary transition-colors">Curriculum</a></li>
          </ul>
        </div>

        <div>
          <h4 class="text-[11px] font-semibold uppercase tracking-widestest text-gold mb-4">For Members</h4>
          <ul class="space-y-2.5 text-sm text-ink-soft">
            <li><a href="/login.html" class="hover:text-primary transition-colors">Sign in</a></li>
            <li><a href="#enroll" class="hover:text-primary transition-colors">Request enrollment</a></li>
            <li><a href="#" class="hover:text-primary transition-colors">Help &amp; FAQ</a></li>
            <li><a href="#" class="hover:text-primary transition-colors">Privacy</a></li>
          </ul>
        </div>

        <div>
          <h4 class="text-[11px] font-semibold uppercase tracking-widestest text-gold mb-4">Parish</h4>
          <ul class="space-y-2.5 text-sm text-ink-soft">
            <li>Saint Gabriel Church</li>
            <li>Addis Ababa, Ethiopia</li>
            <li><a href="mailto:hello@gebriel.eagleeyebgp.com" class="hover:text-primary transition-colors">hello@gebriel.eagleeyebgp.com</a></li>
          </ul>
        </div>
      </div>

      <div class="rule-gold mb-6"></div>

      <div class="flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-outline">
        <p class="uppercase tracking-widestest">© <?= $year ?> Gebriel Senbet · Made with reverence in Addis Ababa</p>
        <div class="flex items-center bg-surface rounded-full p-0.5 border border-outline-soft/50">
          <button class="seg-active px-3 py-1 text-xs font-semibold rounded-full">EN</button>
          <button class="px-3 py-1 text-xs font-semibold rounded-full text-ink-soft hover:text-primary ethiopic">አማ</button>
          <button class="px-3 py-1 text-xs font-semibold rounded-full text-ink-soft hover:text-primary ethiopic">ትግ</button>
        </div>
      </div>
    </div>
  </footer>

  <script>
    // Language toggle (visual-only for now)
    document.querySelectorAll('[role=group][aria-label=Language] button, footer .flex.items-center.bg-surface button').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const group = e.currentTarget.parentElement;
        group.querySelectorAll('button').forEach(b => b.classList.remove('seg-active'));
        e.currentTarget.classList.add('seg-active');
      });
    });
  </script>
</body>
</html>
