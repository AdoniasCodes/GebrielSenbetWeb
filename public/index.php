<?php
// public/index.php — Public landing page (Sacred Scholarly Minimalist), bilingual EN/አማ

use App\Utils\Csrf;

require_once __DIR__ . '/../bootstrap.php';

$config = app_config();
Csrf::ensureSession($config['app']['session_name']);
$role = $_SESSION['role_name'] ?? null;

$dashboard_href = null;
$dashboard_label_en = null;
$dashboard_label_am = null;
if ($role === 'admin')   { $dashboard_href = '/admin/index.php';   $dashboard_label_en = 'Admin dashboard';   $dashboard_label_am = 'የአስተዳዳሪ ዳሽቦርድ'; }
elseif ($role === 'teacher') { $dashboard_href = '/teacher/index.php'; $dashboard_label_en = 'Teacher portal'; $dashboard_label_am = 'የመምህር ፖርታል'; }
elseif ($role === 'student') { $dashboard_href = '/student/index.php'; $dashboard_label_en = 'Student portal'; $dashboard_label_am = 'የተማሪ ፖርታል'; }
elseif ($role === 'parent')  { $dashboard_href = '/parent/index.php';  $dashboard_label_en = 'Parent portal';  $dashboard_label_am = 'የወላጅ ፖርታል'; }
elseif ($role === 'staff')   { $dashboard_href = '/staff/index.php';   $dashboard_label_en = 'Staff dashboard';   $dashboard_label_am = 'የሠራተኞች ዳሽቦርድ'; }

$year = date('Y');
?>
<!DOCTYPE html>
<html lang="en" data-lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mekane Selam Senbet School — Sabbath School</title>
  <meta name="description" content="Mekane Selam Senbet School. A modern home for our Sunday school: curriculum, grading, payments, and community announcements in one reverent place." />

  <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32.png" />
  <link rel="icon" type="image/png" sizes="64x64" href="/images/favicon-64.png" />
  <link rel="apple-touch-icon" href="/images/logo-mekane-selam-192.png" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,400..700&family=Plus+Jakarta+Sans:wght@400..700&family=Noto+Sans+Ethiopic:wght@400;500;700&family=Noto+Serif+Ethiopic:wght@400;600;700&display=swap" rel="stylesheet" />

  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <script src="/assets/js/ec-date.js"></script>
  <script src="/assets/js/video-embed.js"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            surface:        '#f4f7fc',
            'surface-low':  '#eef2fa',
            'surface-mid':  '#e5ecf7',
            'surface-high': '#ebe8e1',
            ink:            '#141824',
            'ink-soft':     '#3f4658',
            outline:        '#6b7690',
            'outline-soft': '#c4d0e4',
            primary:        '#16357e',
            'primary-soft': '#2f52a6',
            'primary-warm': '#3f66c4',
            gold:           '#795901',
            'gold-soft':    '#c9a14a',
            'gold-warm':    '#fed175',
            olive:          '#384700',
            'olive-soft':   '#a2b665',
          },
          fontFamily: {
            display:  ['Newsreader', '"Noto Serif Ethiopic"', 'serif'],
            body:     ['"Plus Jakarta Sans"', '"Noto Sans Ethiopic"', 'system-ui', 'sans-serif'],
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
    html, body { background: #f4f7fc; }
    body { font-feature-settings: 'kern','liga','dlig'; }

    /* Latin → Newsreader; Ethiopic → Noto Serif Ethiopic, automatic fallback */
    .font-display { font-family: 'Newsreader', 'Noto Serif Ethiopic', serif; }
    .font-body    { font-family: 'Plus Jakarta Sans', 'Noto Sans Ethiopic', system-ui, sans-serif; }

    /* Slightly tighter line-height for Ethiopic-heavy sections */
    html[data-lang="am"] body { line-height: 1.55; }

    .paper {
      background-image:
        radial-gradient(circle at 1px 1px, rgba(91,6,23,0.035) 1px, transparent 0),
        radial-gradient(circle at 13px 9px, rgba(121,89,1,0.025) 1px, transparent 0);
      background-size: 24px 24px, 28px 28px;
    }
    .rule-gold { height: 1px; background: linear-gradient(to right, transparent, #c9a14a 20%, #c9a14a 80%, transparent); }
    .rule-gold-short { display:inline-block; width:48px; height:1px; background:#c9a14a; vertical-align:middle; }
    .rule-gold-tiny  { display:inline-block; width:12px; height:1px; background:#c9a14a; vertical-align:middle; }

    .manuscript {
      position: relative;
      background: linear-gradient(180deg, #fffdf6 0%, #fbf6e9 100%);
      border: 1px solid rgba(201,161,74,0.35);
    }
    .manuscript::before, .manuscript::after,
    .manuscript .corner-tr, .manuscript .corner-bl {
      content: ''; position: absolute; width: 28px; height: 28px;
      border: 1px solid #c9a14a; pointer-events: none;
    }
    .manuscript::before     { top:14px;    left:14px;   border-right:none; border-bottom:none; }
    .manuscript::after      { bottom:14px; right:14px;  border-left:none;  border-top:none;    }
    .manuscript .corner-tr  { top:14px;    right:14px;  border-left:none;  border-bottom:none; }
    .manuscript .corner-bl  { bottom:14px; left:14px;   border-right:none; border-top:none;    }

    .eyebrow {
      font-family: 'Plus Jakarta Sans','Noto Sans Ethiopic',sans-serif;
      font-size: 12px; font-weight: 600; text-transform: uppercase;
      letter-spacing: 0.18em; color: #795901;
      display: inline-flex; align-items: center; gap: 12px;
    }

    .ethiopic { font-family: 'Noto Sans Ethiopic', serif; font-size: 1.08em; line-height: 1.65; }

    .link-arrow svg { transition: transform 200ms ease; }
    .link-arrow:hover svg { transform: translateX(4px); }

    .pattern-children {
      background-color: #eef2fa;
      background-image:
        radial-gradient(circle at 20% 30%, rgba(201,161,74,0.18) 0, transparent 32px),
        radial-gradient(circle at 75% 70%, rgba(91,6,23,0.10) 0, transparent 36px),
        radial-gradient(circle at 50% 50%, rgba(56,71,0,0.08) 0, transparent 28px);
    }
    .pattern-adult {
      background-color: #e5ecf7;
      background-image:
        repeating-linear-gradient(135deg, rgba(201,161,74,0.10) 0 1px, transparent 1px 14px),
        repeating-linear-gradient(45deg, rgba(91,6,23,0.06) 0 1px, transparent 1px 22px);
    }

    .seg-active { background: #fed175; color: #16357e; }

    #buildSlider::-webkit-scrollbar { display: none; }

    .scripture { background: radial-gradient(ellipse at top, #2f52a6 0%, #16357e 60%, #0a1f4d 100%); color: #f3f0ea; }

    :where(a, button, input, select, textarea):focus-visible {
      outline: 2px solid #c9a14a; outline-offset: 2px; border-radius: 2px;
    }

    /* Paschal greeting — a typed call-and-response between two Christians */
    #paschalGreeting { min-height: 7.5rem; }
    .pg-line { opacity: 0; transition: opacity .5s ease, transform .5s ease; }
    .pg-l { transform: translateX(-22px); }
    .pg-r { transform: translateX(22px); text-align: right; }
    .pg-line.pg-show { opacity: 1; transform: translateX(0); }
    .pg-l .pg-text { color: #fed175; }            /* speaker A — gold */
    .pg-r .pg-text { color: #fffdf8; }            /* speaker B — light */
    .pg-text::after { content: '▏'; color: #c9a14a; opacity: .8; }
    .pg-line.pg-done .pg-text::after { content: ''; }
    @media (prefers-reduced-motion: reduce) { .pg-line { transition: none; } }
  </style>
</head>
<body class="bg-surface text-ink font-body antialiased">

  <!-- ============ TOP NAV ============ -->
  <header class="sticky top-0 z-50 border-b border-outline-soft/40 bg-surface/85 backdrop-blur-md">
    <div class="max-w-[1280px] mx-auto px-6 lg:px-8 h-16 flex items-center justify-between">
      <a href="/" class="flex items-center gap-3 group">
        <img src="/images/logo-mekane-selam.webp" class="h-10 w-10 rounded-full object-cover" alt="Mekane Selam Sunday School logo" width="40" height="40" />
        <span class="font-display text-xl font-semibold tracking-tight text-primary leading-none" data-en="Mekane Selam Senbet School" data-am="መካነ ሰላም ሰንበት ት/ቤት">Mekane Selam Senbet School</span>
      </a>

      <nav class="hidden md:flex items-center gap-8 text-[15px] text-ink-soft">
        <a class="hover:text-primary transition-colors" href="#about" data-en="About" data-am="ስለ እኛ">About</a>
        <a class="text-primary font-semibold hover:text-primary-soft transition-colors" href="#build" data-en="Our Building" data-am="ሕንፃችን">Our Building</a>
        <a class="hover:text-primary transition-colors" href="#programs" data-en="Programs" data-am="ፕሮግራሞች">Programs</a>
        <a class="hover:text-primary transition-colors" href="#calendar" data-en="Calendar" data-am="የቀን መቁጠሪያ">Calendar</a>
        <a class="hover:text-primary transition-colors" href="/blog.php" data-en="Blog" data-am="ብሎግ">Blog</a>
      </nav>

      <div class="flex items-center gap-3">
        <div data-lang-toggle class="hidden sm:flex items-center bg-surface-mid rounded-full p-0.5 border border-outline-soft/50">
          <button data-lang="en" class="seg-active px-3 py-1 text-xs font-semibold rounded-full">EN</button>
          <button data-lang="am" class="px-3 py-1 text-xs font-semibold rounded-full text-ink-soft hover:text-primary ethiopic">አማ</button>
        </div>
        <?php if ($dashboard_href): ?>
          <a href="<?= htmlspecialchars($dashboard_href) ?>" class="inline-flex items-center gap-2 bg-primary text-surface px-4 py-2 rounded text-xs font-semibold uppercase tracking-widestest hover:bg-primary-soft transition-colors">
            <span data-en="<?= htmlspecialchars($dashboard_label_en) ?>" data-am="<?= htmlspecialchars($dashboard_label_am) ?>"><?= htmlspecialchars($dashboard_label_en) ?></span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
          </a>
        <?php else: ?>
          <a href="/login.html" class="inline-flex items-center gap-2 bg-primary text-surface px-4 py-2 rounded text-xs font-semibold uppercase tracking-widestest hover:bg-primary-soft transition-colors">
            <span data-en="Sign in" data-am="ግባ">Sign in</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main>

    <!-- ============ HERO ============ -->
    <section class="relative overflow-hidden">
      <!-- full-bleed feast-day choir photo + readability gradients -->
      <div class="absolute inset-0">
        <img src="/images/photo_2026-06-14-17.28.07.webp" width="1280" height="853" fetchpriority="high" decoding="async"
             alt="The Mekane Selam Senbet School choir gathered in blue and white robes on a feast day"
             class="w-full h-full object-cover object-[50%_30%]" />
        <div class="absolute inset-0 bg-gradient-to-r from-primary/95 via-primary/75 to-primary/35"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-ink/75 via-transparent to-ink/25"></div>
      </div>

      <div class="relative max-w-[1280px] mx-auto px-6 lg:px-8 py-24 lg:py-36">
        <div class="max-w-2xl">
          <div class="inline-flex items-center gap-2 rounded-full bg-surface/15 border border-surface/25 px-3 py-1 mb-7 backdrop-blur-sm">
            <span class="w-1.5 h-1.5 rounded-full bg-gold-warm animate-pulse"></span>
            <span class="text-[11px] font-semibold uppercase tracking-widestest text-gold-warm" data-en="Enrollment open · <?= $year ?>" data-am="ምዝገባ ክፍት ነው · <?= $year ?>">Enrollment open · <?= $year ?></span>
          </div>

          <h1 class="font-display text-[44px] lg:text-[64px] leading-[1.04] tracking-tight text-surface font-semibold drop-shadow-sm" data-en="Raising a Generation Rooted in Orthodox Faith." data-am="በኦርቶዶክሳዊት እምነት የታነጸ ትውልድን ማፍራት።">Raising a Generation Rooted in Orthodox Faith.</h1>

          <div id="paschalGreeting" class="mt-6 max-w-md font-display ethiopic text-lg lg:text-xl leading-relaxed" aria-label="Paschal greeting — ክርስቶስ ተንሥአ እሙታን"></div>

          <p class="mt-7 text-lg leading-relaxed text-surface/85 max-w-xl" data-en="Welcome to Mekane Selam Sunday School, where we learn, live, and defend our Holy Orthodox Tewahedo Faith." data-am="እንኳን ወደ መካነ ሰላም ሰንበት ትምህርት ቤት በሰላም መጡ፤ እምነታችንን የምንማርበት፣ የምንኖርበትና የምንመሰክርበት ቅዱስ ስፍራ።">
            Welcome to Mekane Selam Sunday School, where we learn, live, and defend our Holy Orthodox Tewahedo Faith.
          </p>

          <div class="mt-8 flex flex-col sm:flex-row gap-3">
            <?php if ($dashboard_href): ?>
              <a href="<?= htmlspecialchars($dashboard_href) ?>" class="inline-flex justify-center items-center gap-2 bg-surface text-primary px-7 py-3.5 rounded text-xs font-semibold uppercase tracking-widestest hover:bg-surface-mid transition-colors">
                <span data-en="<?= htmlspecialchars($dashboard_label_en) ?>" data-am="<?= htmlspecialchars($dashboard_label_am) ?>"><?= htmlspecialchars($dashboard_label_en) ?></span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
              </a>
            <?php else: ?>
              <a href="/login.html" class="inline-flex justify-center items-center gap-2 bg-surface text-primary px-7 py-3.5 rounded text-xs font-semibold uppercase tracking-widestest hover:bg-surface-mid transition-colors">
                <span data-en="Sign in" data-am="ግባ">Sign in</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
              </a>
            <?php endif; ?>
            <a href="#register" class="inline-flex justify-center items-center gap-2 border border-surface/40 text-surface px-7 py-3.5 rounded text-xs font-semibold uppercase tracking-widestest hover:bg-surface/10 transition-colors" data-en="Request enrollment" data-am="ምዝገባ ይጠይቁ">Request enrollment</a>
          </div>

          <div class="mt-10 flex items-center gap-6 text-sm text-surface/75">
            <div class="flex items-center gap-2"><span class="w-1 h-1 rounded-full bg-gold-warm"></span><span data-en="Two tracks" data-am="ሁለት ኮርሶች">Two tracks</span></div>
            <div class="flex items-center gap-2"><span class="w-1 h-1 rounded-full bg-gold-warm"></span><span data-en="13 levels" data-am="13 ደረጃዎች">13 levels</span></div>
            <div class="flex items-center gap-2"><span class="w-1 h-1 rounded-full bg-gold-warm"></span><span data-en="One faith" data-am="አንድ እምነት">One faith</span></div>
          </div>
        </div>
      </div>
    </section>

    <!-- ============ MISSION / ABOUT ============ -->
    <section id="about" class="bg-surface-low border-y border-outline-soft/40">
      <div class="max-w-3xl mx-auto px-6 lg:px-8 py-20 text-center">
        <p class="eyebrow"><span class="rule-gold-tiny"></span><span data-en="Core Mission &amp; End Goal" data-am="የአገልግሎት ዓላማችን">Core Mission &amp; End Goal</span><span class="rule-gold-tiny"></span></p>
        <h2 class="font-display text-3xl lg:text-4xl text-primary mt-5 mb-6 leading-tight" data-en="Spreading the Gospel, forming the faithful." data-am="ወንጌልን ማስፋፋት፣ ምዕመናንን መገንባት።">
          Spreading the Gospel, forming the faithful.
        </h2>
        <p class="text-lg text-ink-soft leading-relaxed" data-en="Our ultimate mission is to spread the Gospel of Jesus Christ, raise Orthodox Christians who deeply know and defend their faith, and guide all our members to actively participate in the Holy Communion (Holy Qurban)." data-am="ዋናውና የመጨረሻው ግባችን የወንጌልን ብርሃን ማስፋፋት፣ እምነታቸውን ጠንቅቀው የሚያውቁና የሚከላከሉ የኦርቶዶክስ ክርስቲያኖችን ማፍራት እንዲሁም አባላቶቻችን በሙሉ የቅዱስ ቁርባን ተሳታፊ እንዲሆኑ መምራት ነው።">
          Our ultimate mission is to spread the Gospel of Jesus Christ, raise Orthodox Christians who deeply know and defend their faith, and guide all our members to actively participate in the Holy Communion (Holy Qurban).
        </p>
      </div>
    </section>

    <!-- ============ BUILDING CAMPAIGN ============ -->
    <section id="build" class="scripture relative overflow-hidden">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-8 py-20 lg:py-24">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-start">

          <!-- story -->
          <div>
            <p class="eyebrow text-gold-warm"><span class="rule-gold-tiny"></span><span data-en="49 years · one home left to finish" data-am="49 ዓመታት · የቀረን አንድ ቤት">49 years · one home left to finish</span></p>
            <h2 class="ethiopic font-display text-3xl lg:text-5xl text-surface mt-5 leading-tight">የጀመርነውን እንጨርሰው።</h2>
            <p class="mt-3 text-gold-warm text-lg font-medium" data-en="Let's finish what we started." data-am="የጀመርነውን እንጨርሰው።">Let's finish what we started.</p>

            <p class="mt-7 text-surface/85 leading-relaxed" data-en="For 49 years — as one of the oldest Sabbath schools in Addis Ababa — we have taught and served without a home of our own. For a decade we have been building one: a state-of-the-art G+2 complex with classrooms, a two-level hall, and more — designed by our own members and overseen by our building committee. After years dormant, the work has reached its final stage; only the finishing remains — gypsum, paint, installations, interiors, and furniture. We are determined to complete it for our 50th anniversary." data-am="ለ49 ዓመታት — በአዲስ አበባ ካሉት ጥንታዊ ሰንበት ት/ቤቶች አንዱ ሆነን — የራሳችን ቤት ሳይኖረን አስተምረናል፣ አገልግለናል። ለአንድ አስርት ዓመት የራሳችንን እየገነባን ነው፦ በመማሪያ ክፍሎች፣ ባለ ሁለት ፎቅ አዳራሽና በሌሎችም የተሟላ ዘመናዊ ጂ+2 ሕንፃ — በራሳችን አባላት ተነድፎ በህንፃ አሰሪ ኮሚቴያችን እየተመራ። ለዓመታት ከቆመ በኋላ ሥራው የመጨረሻ ደረጃ ላይ ደርሷል፤ የቀረው የማጠናቀቂያ ሥራ ብቻ ነው — ጂፕሰም፣ ቀለም፣ ተከላዎች፣ የውስጥ ዲዛይንና ፈርኒቸር። ለ50ኛ ዓመት ኢዮቤልዩ ለማጠናቀቅ ቆርጠናል።">
              For 49 years — as one of the oldest Sabbath schools in Addis Ababa — we have taught and served without a home of our own. For a decade we have been building one: a state-of-the-art G+2 complex with classrooms, a two-level hall, and more — designed by our own members and overseen by our building committee. After years dormant, the work has reached its final stage; only the finishing remains — gypsum, paint, installations, interiors, and furniture. We are determined to complete it for our 50th anniversary.
            </p>

            <div class="mt-9 grid grid-cols-3 gap-4 max-w-md">
              <div><p class="font-display text-3xl lg:text-4xl text-gold-warm">49</p><p class="text-[11px] uppercase tracking-widestest text-surface/60 mt-1" data-en="Years" data-am="ዓመታት">Years</p></div>
              <div><p class="font-display text-3xl lg:text-4xl text-gold-warm">G+2</p><p class="text-[11px] uppercase tracking-widestest text-surface/60 mt-1" data-en="Building" data-am="ሕንፃ">Building</p></div>
              <div><p class="font-display text-3xl lg:text-4xl text-gold-warm">1000+</p><p class="text-[11px] uppercase tracking-widestest text-surface/60 mt-1" data-en="Daily givers" data-am="የቀን ለጋሾች">Daily givers</p></div>
            </div>

            <blockquote class="mt-8 border-l-2 border-gold-warm/60 pl-5">
              <p class="ethiopic text-surface/90 text-lg leading-relaxed">“የምንማርበትን፣ የምናድገበትን፣ የምናገለግልበትን፣ የምንኖርበትን ሰንበት ት/ቤታችን ጨርሱልን”</p>
              <footer class="mt-2 text-xs uppercase tracking-widestest text-surface/50" data-en="— our children" data-am="— ልጆቻችን">— our children</footer>
            </blockquote>
          </div>

          <!-- donate card -->
          <div class="lg:pt-4">
            <div class="rounded-lg bg-surface p-7 lg:p-8 shadow-2xl border-t-4 border-gold">
              <p class="eyebrow text-gold"><span class="rule-gold-tiny"></span><span data-en="Contribute to the build" data-am="ለሕንፃው አስተዋፅኦ ያድርጉ">Contribute to the build</span></p>
              <h3 class="font-display text-2xl text-primary mt-3">Commercial Bank of Ethiopia</h3>
              <p class="ethiopic text-sm text-ink-soft mb-5">የመካነ ሰላም ሰ/ት/ቤት የሕንፃ አሰሪ ኮሚቴ የገቢ ማሰባሰቢያ</p>

              <div class="rounded bg-surface-mid border border-outline-soft/50 p-4 flex items-center justify-between gap-4">
                <div class="min-w-0">
                  <p class="text-[10px] uppercase tracking-widestest text-outline" data-en="Account number" data-am="የሂሳብ ቁጥር">Account number</p>
                  <p class="font-display text-xl lg:text-2xl text-primary tracking-wide">1000469573382</p>
                  <p class="text-sm text-ink-soft mt-1">Adamu Henok Daniel</p>
                </div>
                <button type="button"
                  onclick="(function(b){if(navigator.clipboard){navigator.clipboard.writeText('1000469573382').then(function(){var o=b.getAttribute('data-en')||'Copy';b.textContent='Copied ✓';setTimeout(function(){b.textContent=o;},1600);});}})(this)"
                  data-en="Copy" data-am="ቅዳ"
                  class="shrink-0 text-xs font-semibold uppercase tracking-widestest text-primary border border-outline-soft px-3 py-2 rounded hover:bg-surface transition-colors">Copy</button>
              </div>

              <div class="mt-5 flex flex-wrap gap-2">
                <span class="ethiopic text-xs px-3 py-1.5 rounded-full bg-primary/5 text-primary border border-outline-soft/50">አስራቴን ለሰንበት ት/ቤቴ</span>
                <span class="ethiopic text-xs px-3 py-1.5 rounded-full bg-primary/5 text-primary border border-outline-soft/50">ቁርሴን ለሰንበቴ</span>
              </div>

              <div class="mt-6 pt-5 border-t border-outline-soft/40">
                <p class="text-[10px] uppercase tracking-widestest text-outline mb-2" data-en="For more information" data-am="ለበለጠ መረጃ">For more information</p>
                <div class="flex flex-wrap gap-x-5 gap-y-1.5 text-sm">
                  <a href="tel:+251960262777" class="text-primary hover:text-primary-soft font-medium">0960 262 777</a>
                  <a href="tel:+251910030756" class="text-primary hover:text-primary-soft font-medium">0910 030 756</a>
                  <a href="tel:+251911101364" class="text-primary hover:text-primary-soft font-medium">0911 101 364</a>
                  <a href="tel:+251988727374" class="text-primary hover:text-primary-soft font-medium">0988 727 374</a>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- Building progress / final design slider — touch-swipeable, native scroll-snap -->
        <div class="mt-16 lg:mt-20">
          <div class="flex items-end justify-between gap-4 mb-5">
            <p class="eyebrow text-gold-warm"><span class="rule-gold-tiny"></span><span data-en="Watch it rise" data-am="ግንባታውን ይመልከቱ">Watch it rise</span></p>
            <div class="hidden sm:flex items-center gap-2">
              <button type="button" id="buildPrev" aria-label="Previous photo" class="w-9 h-9 rounded-full border border-surface/30 text-surface/80 hover:text-surface hover:border-surface/60 inline-flex items-center justify-center transition-colors">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M15 5l-7 7 7 7"/></svg>
              </button>
              <button type="button" id="buildNext" aria-label="Next photo" class="w-9 h-9 rounded-full border border-surface/30 text-surface/80 hover:text-surface hover:border-surface/60 inline-flex items-center justify-center transition-colors">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M9 5l7 7-7 7"/></svg>
              </button>
            </div>
          </div>

          <div id="buildSlider" class="flex gap-4 overflow-x-auto snap-x snap-mandatory pb-2" style="scroll-behavior:smooth; -webkit-overflow-scrolling:touch; scrollbar-width:none;">
            <?php
              $buildSlides = [
                ['building-progress-1.w800.webp', 'Current construction progress at the new Mekane Selam Sunday School building, view 1 · የመካነ ሰላም ሰንበት ት/ቤት ሕንፃ አሁን ያለበት ደረጃ፣ ፎቶ 1', 'Current Progress', 'አሁን ያለንበት ደረጃ'],
                ['building-progress-2.w800.webp', 'Current construction progress at the new Mekane Selam Sunday School building, view 2 · የመካነ ሰላም ሰንበት ት/ቤት ሕንፃ አሁን ያለበት ደረጃ፣ ፎቶ 2', 'Current Progress', 'አሁን ያለንበት ደረጃ'],
                ['building-render-1.w800.webp', 'Architectural render of the finished Mekane Selam Sunday School building, view 1 · የመጨረሻው ሕንፃ ንድፍ፣ ፎቶ 1', 'Final Design', 'የመጨረሻው ንድፍ'],
                ['building-render-2.w800.webp', 'Architectural render of the finished Mekane Selam Sunday School building, view 2 · የመጨረሻው ሕንፃ ንድፍ፣ ፎቶ 2', 'Final Design', 'የመጨረሻው ንድፍ'],
                ['building-render-3.w800.webp', 'Architectural render of the finished Mekane Selam Sunday School building, view 3 · የመጨረሻው ሕንፃ ንድፍ፣ ፎቶ 3', 'Final Design', 'የመጨረሻው ንድፍ'],
                ['building-render-4.w800.webp', 'Architectural render of the finished Mekane Selam Sunday School building, view 4 · የመጨረሻው ሕንፃ ንድፍ፣ ፎቶ 4', 'Final Design', 'የመጨረሻው ንድፍ'],
              ];
              foreach ($buildSlides as $s):
            ?>
              <figure class="snap-start shrink-0 w-[82%] sm:w-[55%] lg:w-[31%] relative rounded-lg overflow-hidden border border-surface/15">
                <img src="/images/<?= htmlspecialchars($s[0]) ?>" loading="lazy" decoding="async" alt="<?= htmlspecialchars($s[1]) ?>"
                     class="w-full h-64 lg:h-72 object-cover" width="800" height="533" />
                <span class="absolute top-3 left-3 text-[10px] font-semibold uppercase tracking-widestest text-primary bg-surface/90 px-2.5 py-1 rounded-full" data-en="<?= htmlspecialchars($s[2]) ?>" data-am="<?= htmlspecialchars($s[3]) ?>"><?= htmlspecialchars($s[2]) ?></span>
              </figure>
            <?php endforeach; ?>
          </div>

          <div id="buildDots" class="flex justify-center gap-2 mt-4"></div>
        </div>

      </div>
    </section>

    <!-- ============ GALLERY / LIFE TOGETHER ============ -->
    <section id="life" class="bg-surface border-b border-outline-soft/40">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-8 py-20">
        <div class="max-w-2xl mb-12">
          <p class="eyebrow"><span class="rule-gold-tiny"></span><span data-en="Life Together" data-am="በአንድነት">Life Together</span></p>
          <h2 class="font-display text-3xl lg:text-4xl text-primary mt-4 leading-tight" data-en="Our family, in worship." data-am="ቤተሰባችን ፣ በአምልኮ።">Our family, in worship.</h2>
          <p class="mt-4 text-lg text-ink-soft leading-relaxed" data-en="Feast days, learning, and service. Moments from the life of our Sunday School." data-am="የበዓል ቀናት፣ በትምህርት እና በአገልግሎት ላይ ከሰንብት ትምህርት ቤታችን የተወሰዱ ቅጽበቶች።">Feast days, learning, and service. Moments from the life of our Sunday School.</p>
        </div>

        <!-- Mosaic: a flush rectangle on desktop (4×4 tiled), tiles vary in size; 2-col stack on mobile -->
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4 lg:[grid-auto-rows:11rem]">
          <?php
            // [thumb, caption, lg grid-area: row-start/col-start/row-end/col-end]
            $gallery = [
              ['photo-2026-06-21-09-51-04.w800.webp', 'Choristers playing sistrums and holding prayer staffs against the sky', 'lg:[grid-area:1/1/3/3]'],
              ['img_7799.w800.webp', 'A young chorister singing during the feast', 'lg:[grid-area:1/3/2/4]'],
              ['img_6443.w800.webp', 'A moment of worship at the parish', 'lg:[grid-area:1/4/2/5]'],
              ['dsc-1689.w800.webp', 'A row of choir members singing in patterned robes and caps', 'lg:[grid-area:2/3/3/5]'],
              ['dsc-1619.w800.webp', 'A soloist singing into the microphone in a red and gold cape', 'lg:[grid-area:3/1/5/2]'],
              ['img-7791.w800.webp', 'Young women of the choir singing with hands raised in praise', 'lg:[grid-area:3/2/4/4]'],
              ['dsc-1634.w800.webp', 'Choir members standing in prayer during the celebration', 'lg:[grid-area:3/4/4/5]'],
              ['img_7755.w800.webp', 'Choir members in procession seen from behind beneath the flags', 'lg:[grid-area:4/2/5/3]'],
              ['img_7758.w800.webp', 'The choir in procession with prayer staffs and festival flags', 'lg:[grid-area:4/3/5/4]'],
              ['img_7795.w800.webp', 'A chorister holding a prayer staff', 'lg:[grid-area:4/4/5/5]'],
            ];
            foreach ($gallery as $g):
          ?>
            <button type="button" class="group/ph overflow-hidden rounded-lg border border-outline-soft/40 bg-surface-mid <?= $g[2] ?>"
                    data-full="/images/<?= str_replace('.w800', '', $g[0]) ?>" data-caption="<?= htmlspecialchars($g[1]) ?>" aria-label="<?= htmlspecialchars($g[1]) ?>">
              <img src="/images/<?= $g[0] ?>" loading="lazy" decoding="async" alt="<?= htmlspecialchars($g[1]) ?>"
                   class="w-full object-cover aspect-[4/3] lg:aspect-auto lg:h-full cursor-zoom-in transition-transform duration-700 ease-out group-hover/ph:scale-[1.05]" />
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- ============ LIGHTBOX ============ -->
    <div id="lightbox" class="fixed inset-0 z-[60] hidden items-center justify-center bg-ink/90 backdrop-blur-sm p-4" role="dialog" aria-modal="true" aria-label="Photo viewer">
      <button id="lbClose" class="absolute top-4 right-4 text-surface/80 hover:text-surface p-2" aria-label="Close">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
      </button>
      <button id="lbPrev" class="absolute left-3 sm:left-6 top-1/2 -translate-y-1/2 text-surface/80 hover:text-surface p-2" aria-label="Previous photo">
        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"><path d="M15 5l-7 7 7 7"/></svg>
      </button>
      <button id="lbNext" class="absolute right-3 sm:right-6 top-1/2 -translate-y-1/2 text-surface/80 hover:text-surface p-2" aria-label="Next photo">
        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"><path d="M9 5l7 7-7 7"/></svg>
      </button>
      <figure class="flex flex-col items-center">
        <img id="lbImg" src="" alt="" class="max-w-[92vw] max-h-[80vh] object-contain rounded shadow-2xl" />
        <figcaption id="lbCap" class="mt-3 text-sm text-surface/80 text-center max-w-[80vw]"></figcaption>
      </figure>
    </div>
    <script>
    (function(){
      var triggers = Array.prototype.slice.call(document.querySelectorAll('#life [data-full]'));
      if(!triggers.length) return;
      var lb=document.getElementById('lightbox'), img=document.getElementById('lbImg'), cap=document.getElementById('lbCap'), idx=0;
      function show(i){ idx=(i+triggers.length)%triggers.length; var t=triggers[idx]; img.src=t.getAttribute('data-full'); var c=t.getAttribute('data-caption')||''; img.alt=c; cap.textContent=c; }
      function open(i){ show(i); lb.classList.remove('hidden'); lb.classList.add('flex'); document.body.style.overflow='hidden'; }
      function close(){ lb.classList.add('hidden'); lb.classList.remove('flex'); document.body.style.overflow=''; img.src=''; }
      triggers.forEach(function(t,i){ t.addEventListener('click', function(){ open(i); }); });
      document.getElementById('lbClose').addEventListener('click', close);
      document.getElementById('lbNext').addEventListener('click', function(){ show(idx+1); });
      document.getElementById('lbPrev').addEventListener('click', function(){ show(idx-1); });
      lb.addEventListener('click', function(e){ if(e.target===lb) close(); });
      document.addEventListener('keydown', function(e){ if(lb.classList.contains('hidden')) return; if(e.key==='Escape') close(); else if(e.key==='ArrowRight') show(idx+1); else if(e.key==='ArrowLeft') show(idx-1); });
    })();
    </script>

    <!-- ============ JOIN THE SERVICE (registration announcements) ============ -->
    <section id="join" class="max-w-[1280px] mx-auto px-6 lg:px-8 py-20">
      <div class="text-center mb-14">
        <p class="eyebrow"><span class="rule-gold-tiny"></span><span data-en="Join the Service" data-am="አገልግሎቱን ይቀላቀሉ">Join the Service</span><span class="rule-gold-tiny"></span></p>
        <h2 class="font-display text-3xl lg:text-4xl text-primary mt-4" data-en="Register today." data-am="ዛሬ ይመዝገቡ።">Register today.</h2>
      </div>

      <div class="grid md:grid-cols-3 gap-6">
        <div class="reg-announce-card bg-surface rounded-lg p-7 lg:p-8 border border-outline-soft/40 flex flex-col" data-reg-slug="sunday-school">
          <div class="flex items-start justify-between gap-3 mb-5">
            <div class="w-11 h-11 rounded-sm bg-primary/10 text-primary inline-flex items-center justify-center">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M2 4h6a4 4 0 0 1 4 4v13M22 4h-6a4 4 0 0 0-4 4v13"/></svg>
            </div>
            <span class="reg-status-badge text-[11px] font-semibold uppercase tracking-widestest px-2.5 py-1 rounded-full bg-olive/15 text-olive whitespace-nowrap">
              <span data-en="Open" data-am="ክፍት ነው">Open</span>
            </span>
          </div>
          <h3 class="font-display text-xl text-primary mb-2" data-en="Sunday School Academic Registration" data-am="የሰንበት ትምህርት ቤት ምዝገባ">Sunday School Academic Registration</h3>
          <p class="text-sm text-ink-soft leading-relaxed flex-1" data-en="Enroll in this year's catechism and academic track." data-am="ለዘንድሮው ሃይማኖታዊ እና አካዳሚያዊ ኮርስ ይመዝገቡ።">Enroll in this year's catechism and academic track.</p>
          <button type="button" class="reg-cta-btn mt-6 inline-flex justify-center items-center gap-2 bg-primary text-surface px-5 py-3 rounded text-xs font-semibold uppercase tracking-widestest hover:bg-primary-soft transition-colors">
            <span data-en="Register Online" data-am="ይመዝገቡ">Register Online</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
          </button>
        </div>

        <div class="reg-announce-card bg-surface rounded-lg p-7 lg:p-8 border border-outline-soft/40 flex flex-col" data-reg-slug="begena">
          <div class="flex items-start justify-between gap-3 mb-5">
            <div class="w-11 h-11 rounded-sm bg-gold/15 text-gold inline-flex items-center justify-center">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M9 18V5l12-2v13M9 18a3 3 0 1 1-6 0 3 3 0 0 1 6 0zM21 16a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg>
            </div>
            <span class="reg-status-badge text-[11px] font-semibold uppercase tracking-widestest px-2.5 py-1 rounded-full bg-olive/15 text-olive whitespace-nowrap">
              <span data-en="Open" data-am="ክፍት ነው">Open</span>
            </span>
          </div>
          <h3 class="font-display text-xl text-primary mb-2" data-en="Begena Classes" data-am="የበገና ስልጠና ምዝገባ">Begena Classes</h3>
          <p class="text-sm text-ink-soft leading-relaxed flex-1" data-en="Learn the sacred harp of David with our begena instructors." data-am="ከበገና አስተማሪዎቻችን ጋር የዳዊትን በገና ይማሩ።">Learn the sacred harp of David with our begena instructors.</p>
          <button type="button" class="reg-cta-btn mt-6 inline-flex justify-center items-center gap-2 bg-primary text-surface px-5 py-3 rounded text-xs font-semibold uppercase tracking-widestest hover:bg-primary-soft transition-colors">
            <span data-en="Sign Up" data-am="ይመዝገቡ">Sign Up</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
          </button>
        </div>

        <div class="reg-announce-card bg-surface rounded-lg p-7 lg:p-8 border border-outline-soft/40 flex flex-col" data-reg-slug="gishen-pilgrimage">
          <div class="flex items-start justify-between gap-3 mb-5">
            <div class="w-11 h-11 rounded-sm bg-olive/15 text-olive inline-flex items-center justify-center">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M3 21l6-16 3 8 3-8 6 16H3z"/></svg>
            </div>
            <span class="reg-status-badge text-[11px] font-semibold uppercase tracking-widestest px-2.5 py-1 rounded-full bg-gold/15 text-gold whitespace-nowrap">
              <span data-en="Limited Spots" data-am="ውስን ቦታዎች">Limited Spots</span>
            </span>
          </div>
          <h3 class="font-display text-xl text-primary mb-2" data-en="Spiritual Pilgrimage to Gishen Mariam" data-am="የግሸን ማርያም ጉዞ ምዝገባ">Spiritual Pilgrimage to Gishen Mariam</h3>
          <p class="text-sm text-ink-soft leading-relaxed flex-1" data-en="Join our community pilgrimage to the holy mount of Gishen Mariam." data-am="ወደ ቅዱስ ግሸን ማርያም ተራራ ከማኅበረሰባችን ጋር ይጓዙ።">Join our community pilgrimage to the holy mount of Gishen Mariam.</p>
          <button type="button" class="reg-cta-btn mt-6 inline-flex justify-center items-center gap-2 bg-primary text-surface px-5 py-3 rounded text-xs font-semibold uppercase tracking-widestest hover:bg-primary-soft transition-colors">
            <span data-en="Reserve Seat" data-am="ቦታ ይያዙ">Reserve Seat</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
          </button>
        </div>
      </div>
    </section>

    <!-- ============ PROGRAMS ============ -->
    <section id="programs" class="bg-surface-low border-y border-outline-soft/40">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-8 py-20">
        <div class="flex items-end justify-between mb-12 gap-6 flex-wrap">
          <div>
            <p class="eyebrow"><span class="rule-gold-tiny"></span><span data-en="Two Tracks" data-am="ሁለት ኮርሶች">Two Tracks</span><span class="rule-gold-tiny"></span></p>
            <h2 class="font-display text-3xl lg:text-4xl text-primary mt-4" data-en="Programs." data-am="ፕሮግራሞች።">Programs.</h2>
          </div>
          <p class="max-w-md text-ink-soft" data-en="Every member of the parish has a place. Our curriculum begins in the nursery and continues through the five traditional stages of adult formation." data-am="የቤተ ክርስቲያኒቱ አባል ሁሉ ቦታ አለው። ሥርዓተ ትምህርታችን ከሕፃናት ክፍል ይጀምር እና በአምስቱ ባህላዊ የአዋቂ ምስረታ ደረጃዎች ይቀጥላል።">Every member of the parish has a place. Our curriculum begins in the nursery and continues through the five traditional stages of adult formation.</p>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
          <article class="bg-surface rounded-lg overflow-hidden border border-outline-soft/40 hover:shadow-md transition-shadow group">
            <div class="h-48 relative overflow-hidden">
              <img src="/images/track-children.w800.webp" width="800" height="533" loading="lazy" decoding="async"
                   alt="Children of the Sunday school in blue capes and patterned caps"
                   class="absolute inset-0 w-full h-full object-cover object-[50%_35%] transition-transform duration-700 group-hover:scale-105" />
              <div class="absolute inset-0 bg-gradient-to-t from-surface via-surface/15 to-transparent"></div>
              <span class="absolute top-5 left-6 text-[11px] font-semibold uppercase tracking-widestest text-gold-warm drop-shadow-md" data-en="Track 01" data-am="ኮርስ 01">Track 01</span>
            </div>
            <div class="p-7">
              <h3 class="font-display text-2xl text-primary mb-1" data-en="Children's Track" data-am="የልጆች ኮርስ">Children's Track</h3>
              <p class="text-sm text-outline mb-5" data-en="Nursery → Grade 6" data-am="ሕጻናት → 6ኛ ክፍል">Nursery → Grade 6</p>
              <p class="text-ink-soft leading-relaxed mb-6" data-en="Foundational catechesis through stories, song, and sacred text — paced for young hearts and curious minds." data-am="በታሪኮች፣ በመዝሙር እና በቅዱስ ጽሑፍ የተመሠረተ መሠረታዊ ትምህርት — ለልጆች ልብ እና ለማወቅ ለሚፈልጉ አእምሮዎች።">Foundational catechesis through stories, song, and sacred text — paced for young hearts and curious minds.</p>
              <div class="flex flex-wrap gap-1.5 mb-6">
                <?php
                  $children_levels_en = ['Nursery','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6'];
                  $children_levels_am = ['ሕጻናት','1ኛ ክፍል','2ኛ ክፍል','3ኛ ክፍል','4ኛ ክፍል','5ኛ ክፍል','6ኛ ክፍል'];
                  foreach ($children_levels_en as $i => $en):
                    $am = $children_levels_am[$i];
                ?>
                  <span class="text-xs px-2.5 py-1 rounded-full bg-surface-mid text-ink-soft border border-outline-soft/50" data-en="<?= htmlspecialchars($en) ?>" data-am="<?= htmlspecialchars($am) ?>"><?= htmlspecialchars($en) ?></span>
                <?php endforeach; ?>
              </div>
              <a href="#" class="link-arrow inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widestest text-primary hover:text-primary-soft">
                <span data-en="Explore the curriculum" data-am="ሥርዓተ ትምህርቱን ይመልከቱ">Explore the curriculum</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
              </a>
            </div>
          </article>

          <article class="bg-surface rounded-lg overflow-hidden border border-outline-soft/40 hover:shadow-md transition-shadow group">
            <div class="h-48 relative overflow-hidden">
              <img src="/images/track-youth.w800.webp" width="800" height="533" loading="lazy" decoding="async"
                   alt="Young men of the choir playing kebero drums during a celebration"
                   class="absolute inset-0 w-full h-full object-cover object-[50%_40%] transition-transform duration-700 group-hover:scale-105" />
              <div class="absolute inset-0 bg-gradient-to-t from-surface via-surface/15 to-transparent"></div>
              <span class="absolute top-5 left-6 text-[11px] font-semibold uppercase tracking-widestest text-gold-warm drop-shadow-md" data-en="Track 02" data-am="ኮርስ 02">Track 02</span>
            </div>
            <div class="p-7">
              <h3 class="font-display text-2xl text-primary mb-1" data-en="Youth &amp; Adult Track" data-am="የወጣቶች እና አዋቂዎች ኮርስ">Youth &amp; Adult Track</h3>
              <p class="ethiopic text-sm text-outline mb-5">ቀዳማይ → ሃምሳይ</p>
              <p class="text-ink-soft leading-relaxed mb-6" data-en="The five traditional stages of formation — taught in Amharic and Tigrinya — for youth, parents, and elders alike." data-am="የአምስቱ ባህላዊ የምስረታ ደረጃዎች — በአማርኛ እና በትግርኛ የሚሰጥ — ለወጣቶች፣ ለወላጆች እና ለአዛውንቶች።">The five traditional stages of formation — taught in Amharic and Tigrinya — for youth, parents, and elders alike.</p>
              <div class="flex flex-wrap gap-1.5 mb-6">
                <?php foreach (['ቀዳማይ','ካላዓይ','ሳልሳይ','ራብዓይ','ሃምሳይ'] as $lvl): ?>
                  <span class="ethiopic text-xs px-2.5 py-1 rounded-full bg-surface-mid text-ink-soft border border-outline-soft/50"><?= $lvl ?></span>
                <?php endforeach; ?>
              </div>
              <a href="#" class="link-arrow inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widestest text-primary hover:text-primary-soft">
                <span data-en="Explore the curriculum" data-am="ሥርዓተ ትምህርቱን ይመልከቱ">Explore the curriculum</span>
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
        <p class="eyebrow"><span class="rule-gold-tiny"></span><span data-en="Core Academic Subjects" data-am="ዋና ዋና የትምህርት ዓይነቶች">Core Academic Subjects</span><span class="rule-gold-tiny"></span></p>
        <h2 class="font-display text-3xl lg:text-4xl text-primary mt-4 leading-tight" data-en="What we teach, term after term." data-am="ከወቅት ወቅት የምናስተምረው።">What we teach, term after term.</h2>
      </div>

      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-px bg-outline-soft/30 rounded-lg overflow-hidden border border-outline-soft/40">
        <?php
          $features = [
            [
              'en_title' => 'Geez Language',
              'am_title' => 'ግዕዝ',
              'en_desc'  => 'The root language of our liturgy and heritage.',
              'am_desc'  => 'የአምልኮታችን እና የቅርሳችን መሠረት ቋንቋ።',
              'svg'      => '<path d="M4 19.5V6.2A2.2 2.2 0 0 1 6.2 4H20v15.5M4 19.5A2.2 2.2 0 0 0 6.2 21.7H20v-2.2M4 19.5H20"/>',
            ],
            [
              'en_title' => 'History of Orthodoxy & EOTC',
              'am_title' => 'የቤተክርስቲያን ታሪክ',
              'en_desc'  => 'Understanding the journey of Christianity globally and locally in Ethiopia.',
              'am_desc'  => 'የክርስትናን ጉዞ በዓለም አቀፍና በኢትዮጵያ ደረጃ መረዳት።',
              'svg'      => '<path d="M4 19.5A2.2 2.2 0 0 1 6.2 17.3H20M4 19.5V4h14a2 2 0 0 1 2 2v13.5M8 8h8M8 12h5"/>',
            ],
            [
              'en_title' => 'Dogmatics',
              'am_title' => 'ነገረ ሃይማኖት',
              'en_desc'  => 'Learning the core dogmas, doctrines, and pillars of our faith.',
              'am_desc'  => 'የእምነታችንን መሠረታዊ ትምህርቶች እና ምሰሶዎች መማር።',
              'svg'      => '<path d="M12 2.5l2.4 6.9H21l-5.6 4.3 2.1 7.1L12 16.6l-5.5 4.2 2.1-7.1L3 9.4h6.6z"/>',
            ],
            [
              'en_title' => 'Ecclesiology & Liturgical Canons',
              'am_title' => 'ስርዓተ ቤተክርስቲያን',
              'en_desc'  => 'The order, rites, and administrative canons of the church.',
              'am_desc'  => 'የቤተክርስቲያኒቱ ሥርዓት፣ ሥነ ሥርዓቶች እና የአስተዳደር ደንቦች።',
              'svg'      => '<path d="M12 2.5v6M4 21V11l8-4 8 4v10M7 21v-6h3v6M14 21v-6h3v6"/>',
            ],
            [
              'en_title' => 'Hagiology',
              'am_title' => 'ነገረ ቅድሳን',
              'en_desc'  => 'The study of the lives of the holy saints, martyrs, and angels.',
              'am_desc'  => 'የቅዱሳን፣ የሰማዕታት እና የመላእክት ሕይወት ጥናት።',
              'svg'      => '<circle cx="12" cy="8" r="3.2"/><path d="M12 2.5a5.7 5.7 0 0 1 0 0M5 21c0-3.6 3.1-6.5 7-6.5s7 2.9 7 6.5"/>',
            ],
            [
              'en_title' => 'Mariology',
              'am_title' => 'ነገረ ማርያም',
              'en_desc'  => 'The theological study of our Holy Virgin Mother Mary.',
              'am_desc'  => 'ስለ ቅድስት ድንግል ማርያም ሥነ መለኮታዊ ጥናት።',
              'svg'      => '<path d="M12 20.5s-7.5-4.7-7.5-10.3A4.2 4.2 0 0 1 12 7.4a4.2 4.2 0 0 1 7.5 2.8c0 5.6-7.5 10.3-7.5 10.3z"/>',
            ],
            [
              'en_title' => 'Christology',
              'am_title' => 'ነገረ ክርስቶስ',
              'en_desc'  => 'The study of the person, nature, and redemptive work of Jesus Christ.',
              'am_desc'  => 'ስለ ኢየሱስ ክርስቶስ ማንነት፣ ባህርይና የቤዛነት ሥራ ጥናት።',
              'svg'      => '<path d="M12 2.5v19M6.5 6.5h11"/>',
            ],
          ];
          foreach ($features as $f): ?>
            <div class="bg-surface p-7 lg:p-8 group">
              <div class="w-9 h-9 rounded-sm bg-gold/10 text-gold inline-flex items-center justify-center mb-4 group-hover:bg-gold/20 transition-colors">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><?= $f['svg'] ?></svg>
              </div>
              <h3 class="font-display text-lg text-primary mb-2" data-en="<?= htmlspecialchars($f['en_title']) ?>" data-am="<?= htmlspecialchars($f['am_title']) ?>"><?= htmlspecialchars($f['en_title']) ?></h3>
              <p class="text-sm text-ink-soft leading-relaxed" data-en="<?= htmlspecialchars($f['en_desc']) ?>" data-am="<?= htmlspecialchars($f['am_desc']) ?>"><?= htmlspecialchars($f['en_desc']) ?></p>
            </div>
          <?php endforeach; ?>
      </div>
    </section>

    <!-- ============ ABNET TRADITIONAL EDUCATION ============ -->
    <section class="bg-surface-low border-y border-outline-soft/40">
      <div class="max-w-3xl mx-auto px-6 lg:px-8 py-20 text-center">
        <p class="eyebrow"><span class="rule-gold-tiny"></span><span data-en="Abnet Traditional Education" data-am="የአብነት ትምህርት">Abnet Traditional Education</span><span class="rule-gold-tiny"></span></p>
        <h2 class="font-display text-3xl lg:text-4xl text-primary mt-5 mb-6 leading-tight" data-en="Abnet Traditional Education" data-am="የአብነት ትምህርት">Abnet Traditional Education</h2>
        <p class="text-lg text-ink-soft leading-relaxed" data-en="Mekane Selam offers traditional Abnet (liturgical/clerical) education for students who wish to excel further in their spiritual scholarship. This pathway nurtures, trains, and prepares dedicated students to eventually be ordained as Deacons (ዲያቆናት) and Priests (ካህናት) to serve our holy church." data-am="ሰንበት ትምህርት ቤታችን በመደበኛው ትምህርት ጎን ለጎን የአብነት ትምህርትን ይሰጣል። ተማሪዎች በመንፈሳዊ እውቀታቸው በልጠው እንዲገኙና ውሎ አድሮም ዲያቆናትና ካህናት በመሆን ቅድስት ቤተክርስቲያንን በቅንነት እንዲያገለግሉ የሚያስችል ስልጠና ይሰጣል።">
          Mekane Selam offers traditional Abnet (liturgical/clerical) education for students who wish to excel further in their spiritual scholarship. This pathway nurtures, trains, and prepares dedicated students to eventually be ordained as Deacons (ዲያቆናት) and Priests (ካህናት) to serve our holy church.
        </p>
      </div>
    </section>

    <!-- ============ SCRIPTURE / QUOTE ============ -->
    <section class="scripture relative overflow-hidden">
      <svg class="absolute -right-10 -top-10 opacity-10" width="280" height="280" viewBox="0 0 24 24" fill="none" stroke="#c9a14a" stroke-width="0.6" stroke-linecap="round">
        <path d="M12 2.5v19M2.5 12h19M6.5 6.5l11 11M17.5 6.5l-11 11"/>
        <circle cx="12" cy="12" r="2.5"/>
      </svg>
      <div class="max-w-3xl mx-auto px-6 lg:px-8 py-24 text-center relative">
        <div class="rule-gold mb-10 max-w-[120px] mx-auto opacity-60"></div>
        <p class="font-display text-[28px] lg:text-[36px] leading-[1.3] italic text-surface mb-7" data-en="&ldquo;Train up a child in the way he should go, and when he is old he will not depart from it.&rdquo;" data-am="&ldquo;ሕጻን በሚሄድበት መንገድ አስተምረው፣ ከሸመገለ ጊዜም ከእርሱ አይለይም።&rdquo;">
          &ldquo;Train up a child in the way he should go, and when he is old he will not depart from it.&rdquo;
        </p>
        <p class="ethiopic text-xl text-gold-warm/90 mb-10" data-en="ሕጻን በሚሄድበት መንገድ አስተምረው፣ ከሸመገለ ጊዜ ከዚያ አይለይም።" data-am="—">
          ሕጻን በሚሄድበት መንገድ አስተምረው፣ ከሸመገለ ጊዜ ከዚያ አይለይም።
        </p>
        <p class="eyebrow text-gold-warm/80"><span class="rule-gold-tiny"></span><span data-en="Proverbs 22:6" data-am="ምሳሌ 22፥6">Proverbs 22:6</span><span class="rule-gold-tiny"></span></p>
      </div>
    </section>

    <!-- ============ CALENDAR / EVENTS PREVIEW ============ -->
    <section id="calendar" class="max-w-[1280px] mx-auto px-6 lg:px-8 py-20">
      <div class="flex items-end justify-between mb-12 gap-6 flex-wrap">
        <div>
          <p class="eyebrow"><span class="rule-gold-tiny"></span><span data-en="The Year Ahead" data-am="መጪው ዓመት">The Year Ahead</span><span class="rule-gold-tiny"></span></p>
          <h2 class="font-display text-3xl lg:text-4xl text-primary mt-4" data-en="Upcoming." data-am="መጪ ዝግጅቶች።">Upcoming.</h2>
        </div>
        <a href="#" class="link-arrow inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widestest text-primary">
          <span data-en="See full calendar" data-am="ሙሉ የቀን መቁጠሪያ ይመልከቱ">See full calendar</span>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
        </a>
      </div>

      <div id="liveEventsGrid" class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <p class="text-sm text-ink-soft col-span-full text-center py-8" data-en="Loading upcoming events…" data-am="መጪ ዝግጅቶችን በመጫን ላይ…">Loading upcoming events…</p>
      </div>
    </section>

    <!-- ============ ANNOUNCEMENTS ============ -->
    <section id="liveAnnouncementsSection" class="max-w-[1280px] mx-auto px-6 lg:px-8 py-12 hidden">
      <div class="flex items-end justify-between mb-8 gap-6 flex-wrap">
        <div>
          <p class="eyebrow"><span class="rule-gold-tiny"></span><span data-en="Notice Board" data-am="የማስታወቂያ ሰሌዳ">Notice Board</span><span class="rule-gold-tiny"></span></p>
          <h2 class="font-display text-3xl lg:text-4xl text-primary mt-4" data-en="Announcements." data-am="ማስታወቂያዎች።">Announcements.</h2>
        </div>
      </div>
      <div id="liveAnnouncementsGrid" class="grid md:grid-cols-2 gap-4"></div>
    </section>

    <!-- ============ LATEST POSTS ============ -->
    <section id="livePostsSection" class="max-w-[1280px] mx-auto px-6 lg:px-8 py-16 hidden">
      <div class="flex items-end justify-between mb-8 gap-6 flex-wrap">
        <div>
          <p class="eyebrow"><span class="rule-gold-tiny"></span><span data-en="From the School" data-am="ከት/ቤታችን">From the School</span><span class="rule-gold-tiny"></span></p>
          <h2 class="font-display text-3xl lg:text-4xl text-primary mt-4" data-en="Latest reflections." data-am="የቅርብ ጊዜ ጽሑፎች።">Latest reflections.</h2>
        </div>
        <a href="/blog.php" class="link-arrow inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widestest text-primary">
          <span data-en="Read the blog" data-am="ብሎጉን ያንብቡ">Read the blog</span>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
        </a>
      </div>
      <div id="livePostsGrid" class="grid md:grid-cols-3 gap-5"></div>
    </section>

    <!-- ============ LATEST ON TIKTOK ============ -->
    <section id="tiktokSection" class="max-w-[1280px] mx-auto px-6 lg:px-8 py-16 hidden">
      <div class="flex items-end justify-between mb-8 gap-6 flex-wrap">
        <div>
          <p class="eyebrow"><span class="rule-gold-tiny"></span><span data-en="Social" data-am="ማኅበራዊ">Social</span><span class="rule-gold-tiny"></span></p>
          <h2 class="font-display text-3xl lg:text-4xl text-primary mt-4" data-en="Latest on TikTok." data-am="በቲክቶክ ላይ የቅርብ ጊዜ።">Latest on TikTok.</h2>
        </div>
        <a href="https://www.tiktok.com/@mekaneselamm" target="_blank" rel="noopener" class="link-arrow inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widestest text-primary">
          <span data-en="Follow on TikTok" data-am="በቲክቶክ ይከተሉ">Follow on TikTok</span>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
        </a>
      </div>
      <div id="tiktokGrid" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6"></div>
    </section>

    <!-- ============ LATEST ON YOUTUBE ============ -->
    <section id="youtubeSection" class="max-w-[1280px] mx-auto px-6 lg:px-8 py-16 hidden">
      <div class="flex items-end justify-between mb-8 gap-6 flex-wrap">
        <div>
          <p class="eyebrow"><span class="rule-gold-tiny"></span><span data-en="Watch" data-am="ይመልከቱ">Watch</span><span class="rule-gold-tiny"></span></p>
          <h2 class="font-display text-3xl lg:text-4xl text-primary mt-4" data-en="Latest on YouTube." data-am="በዩቲዩብ ላይ የቅርብ ጊዜ።">Latest on YouTube.</h2>
        </div>
      </div>
      <div id="youtubeGrid" class="max-w-3xl mx-auto"></div>
    </section>

    <!-- ============ FINAL CTA ============ -->
    <section id="enroll" class="bg-surface-low border-y border-outline-soft/40">
      <div class="max-w-3xl mx-auto px-6 lg:px-8 py-20 text-center">
        <p class="eyebrow"><span class="rule-gold-tiny"></span><span data-en="Begin" data-am="ጀምር">Begin</span><span class="rule-gold-tiny"></span></p>
        <h2 class="font-display text-3xl lg:text-5xl text-primary mt-5 mb-6 leading-tight" data-en="A faith-formed school year awaits." data-am="በእምነት የተቀረጸ ትምህርት ዓመት ይጠብቅዎታል።">A faith-formed school year awaits.</h2>
        <p class="ethiopic text-lg text-ink-soft mb-10" data-en="ለትምህርት ዓመቱ ዝግጁ ነን።" data-am="ለትምህርት ዓመቱ ዝግጁ ነን።">ለትምህርት ዓመቱ ዝግጁ ነን።</p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
          <a href="/login.html" class="inline-flex justify-center items-center gap-2 bg-primary text-surface px-7 py-3.5 rounded text-xs font-semibold uppercase tracking-widestest hover:bg-primary-soft transition-colors">
            <span data-en="Sign in" data-am="ግባ">Sign in</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
          </a>
          <a href="#register" class="inline-flex justify-center items-center gap-2 border border-outline text-primary px-7 py-3.5 rounded text-xs font-semibold uppercase tracking-widestest hover:bg-surface transition-colors" data-en="Request enrollment" data-am="ምዝገባ ይጠይቁ">Request enrollment</a>
        </div>
      </div>
    </section>

    <!-- ============ PUBLIC REGISTRATION ============ -->
    <section id="register" class="max-w-[1280px] mx-auto px-6 lg:px-8 py-20 hidden">
      <div class="max-w-2xl mb-10">
        <p class="eyebrow"><span class="rule-gold-tiny"></span><span data-en="Registration" data-am="ምዝገባ">Registration</span><span class="rule-gold-tiny"></span></p>
        <h2 class="font-display text-3xl lg:text-4xl text-primary mt-4 leading-tight" data-en="Register for a program." data-am="ለፕሮግራም ይመዝገቡ።">Register for a program.</h2>
      </div>

      <div id="regTabs" class="flex flex-wrap gap-3 mb-8" role="tablist"></div>

      <div id="regFormHost" class="max-w-2xl"></div>
    </section>

    <!-- ============ REGISTRATION RESULT MODAL ============ -->
    <div id="regModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-ink/70 backdrop-blur-sm p-4" role="alertdialog" aria-modal="true" aria-label="Registration result">
      <div id="regModalCard" class="bg-surface rounded-lg max-w-sm w-full p-8 text-center shadow-2xl border-t-4 border-primary">
        <p id="regModalMsg" class="text-ink leading-relaxed"></p>
        <button id="regModalOk" type="button" class="mt-7 inline-flex justify-center items-center gap-2 bg-primary text-surface px-6 py-3 rounded text-xs font-semibold uppercase tracking-widestest hover:bg-primary-soft transition-colors">
          <span data-en="OK" data-am="እሺ">OK</span>
        </button>
      </div>
    </div>
  </main>

  <!-- ============ FOOTER ============ -->
  <footer class="bg-surface-mid border-t border-outline-soft/40">
    <div class="max-w-[1280px] mx-auto px-6 lg:px-8 py-16">
      <div class="grid md:grid-cols-4 gap-10 mb-12">
        <div class="md:col-span-1">
          <div class="flex items-center gap-3 mb-4">
            <img src="/images/logo-mekane-selam.webp" class="h-10 w-10 rounded-full object-cover" alt="Mekane Selam Sunday School logo" width="40" height="40" />
            <span class="font-display text-lg font-semibold text-primary" data-en="Mekane Selam Senbet School" data-am="መካነ ሰላም ሰንበት ት/ቤት">Mekane Selam Senbet School</span>
          </div>
          <p class="text-sm text-ink-soft leading-relaxed mb-4" data-en="Mekane Selam Senbet School. A modern home for our community of faith and learning." data-am="መካነ ሰላም ሰንበት ት/ቤት። ለእምነት እና ለትምህርት ማኅበረሰባችን ዘመናዊ ቤት።">Mekane Selam Senbet School. A modern home for our community of faith and learning.</p>
          <p class="ethiopic text-sm text-outline">መካነ ሰላም ሰንበት ት/ቤት</p>
        </div>

        <div>
          <h4 class="text-[11px] font-semibold uppercase tracking-widestest text-gold mb-4" data-en="Programs" data-am="ፕሮግራሞች">Programs</h4>
          <ul class="space-y-2.5 text-sm text-ink-soft">
            <li><a href="#programs" class="hover:text-primary transition-colors" data-en="Children's Track" data-am="የልጆች ኮርስ">Children's Track</a></li>
            <li><a href="#programs" class="hover:text-primary transition-colors" data-en="Youth &amp; Adult Track" data-am="የወጣቶች እና አዋቂዎች ኮርስ">Youth &amp; Adult Track</a></li>
            <li><a href="#calendar" class="hover:text-primary transition-colors" data-en="Academic calendar" data-am="የትምህርት የቀን መቁጠሪያ">Academic calendar</a></li>
            <li><a href="#" class="hover:text-primary transition-colors" data-en="Curriculum" data-am="ሥርዓተ ትምህርት">Curriculum</a></li>
          </ul>
        </div>

        <div>
          <h4 class="text-[11px] font-semibold uppercase tracking-widestest text-gold mb-4" data-en="For Members" data-am="ለአባላት">For Members</h4>
          <ul class="space-y-2.5 text-sm text-ink-soft">
            <li><a href="/login.html" class="hover:text-primary transition-colors" data-en="Sign in" data-am="ግባ">Sign in</a></li>
            <li><a href="#enroll" class="hover:text-primary transition-colors" data-en="Request enrollment" data-am="ምዝገባ ይጠይቁ">Request enrollment</a></li>
            <li><a href="#" class="hover:text-primary transition-colors" data-en="Help &amp; FAQ" data-am="እርዳታ">Help &amp; FAQ</a></li>
            <li><a href="#" class="hover:text-primary transition-colors" data-en="Privacy" data-am="ግላዊነት">Privacy</a></li>
          </ul>
        </div>

        <div>
          <h4 class="text-[11px] font-semibold uppercase tracking-widestest text-gold mb-4" data-en="Parish" data-am="ቤተ ክርስቲያን">Parish</h4>
          <ul class="space-y-2.5 text-sm text-ink-soft">
            <li data-en="Mekane Selam Church" data-am="የመካነ ሰላም ቤተ ክርስቲያን">Mekane Selam Church</li>
            <li data-en="Addis Ababa, Ethiopia" data-am="አዲስ አበባ፣ ኢትዮጵያ">Addis Ababa, Ethiopia</li>
            <li><a href="mailto:hello@gebriel.eagleeyebgp.com" class="hover:text-primary transition-colors">hello@gebriel.eagleeyebgp.com</a></li>
          </ul>
        </div>
      </div>

      <div class="rule-gold mb-6"></div>

      <div class="flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-outline">
        <p class="uppercase tracking-widestest" data-en="© <?= $year ?> Mekane Selam Senbet School · Made with reverence in Addis Ababa" data-am="© <?= $year ?> መካነ ሰላም ሰንበት ት/ቤት · በአዲስ አበባ በክብር የተሠራ">© <?= $year ?> Mekane Selam Senbet School · Made with reverence in Addis Ababa</p>
        <div data-lang-toggle class="flex items-center bg-surface rounded-full p-0.5 border border-outline-soft/50">
          <button data-lang="en" class="seg-active px-3 py-1 text-xs font-semibold rounded-full">EN</button>
          <button data-lang="am" class="px-3 py-1 text-xs font-semibold rounded-full text-ink-soft hover:text-primary ethiopic">አማ</button>
        </div>
      </div>
    </div>
  </footer>

  <script>
    function escHtml(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];}); }

    var EN_MONTHS_SHORT = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
    var AM_MONTHS_SHORT = ['መስከ','ጥቅም','ኅዳር','ታኅሳ','ጥር','የካቲ','መጋቢ','ሚያዝ','ግንቦት','ሰኔ','ሐምሌ','ነሐሴ','ጳጉሜ'];

    function parseDt(s) { if (!s) return null; var d = new Date(String(s).replace(' ','T')); return isNaN(d) ? null : d; }

    // Build a card whose date pill carries data-iso so EC.rerenderIsoNodes
    // refreshes it on language change.
    function eventCardHtml(e) {
      var d = parseDt(e.start_datetime);
      if (!d) return '';
      var moEn = EN_MONTHS_SHORT[d.getMonth()];
      var ec = window.EC ? EC.gregorianToEC(d) : null;
      var moAm = ec ? AM_MONTHS_SHORT[ec.month - 1] : EN_MONTHS_SHORT[d.getMonth()];
      var dayEn = String(d.getDate());
      var dayAm = ec ? String(ec.day) : dayEn;
      var iso = e.start_datetime;
      var title = escHtml(e.title || '');
      var desc  = escHtml(e.description || '');
      return '<article class="bg-surface rounded-lg border border-outline-soft/40 p-6 hover:shadow-md transition-shadow flex gap-4">' +
        '<div class="flex-shrink-0 w-14 text-center">' +
          '<div class="text-[10px] font-semibold uppercase tracking-widestest text-gold" data-en="'+moEn+'" data-am="'+moAm+'">'+moEn+'</div>' +
          '<div class="font-display text-3xl text-primary leading-none mt-1" data-en="'+dayEn+'" data-am="'+dayAm+'">'+dayEn+'</div>' +
        '</div>' +
        '<div class="border-l border-outline-soft/40 pl-4">' +
          '<h3 class="font-display text-base text-ink leading-tight mb-1">'+title+'</h3>' +
          '<p class="text-xs text-ink-soft mb-2" data-iso="'+escHtml(iso)+'" data-fmt-style="datetime">'+escHtml(iso)+'</p>' +
          (desc ? '<span class="text-[10px] uppercase tracking-widestest text-outline">'+desc.substring(0, 48)+(desc.length > 48 ? '…' : '')+'</span>' : '') +
        '</div>' +
      '</article>';
    }

    function announcementCardHtml(n) {
      return '<article class="bg-surface rounded-lg border border-outline-soft/40 p-5">' +
        '<h3 class="font-display text-base text-ink mb-2">'+escHtml(n.title || '')+'</h3>' +
        '<p class="text-sm text-ink-soft whitespace-pre-wrap">'+escHtml(n.message || '')+'</p>' +
      '</article>';
    }

    function postCardHtml(p) {
      var snippet = (p.content || '').replace(/\s+/g, ' ').trim();
      if (snippet.length > 140) snippet = snippet.substring(0, 140) + '…';
      return '<a href="/blog.php" class="block bg-surface rounded-lg border border-outline-soft/40 p-5 hover:shadow-md transition-shadow">' +
        '<h3 class="font-display text-base text-ink mb-2">'+escHtml(p.title || '')+'</h3>' +
        '<p class="text-sm text-ink-soft">'+escHtml(snippet)+'</p>' +
      '</a>';
    }

    function tiktokCardHtml(v) {
      var meta = window.VideoEmbed ? VideoEmbed.buildEmbedUrl(v.video_url) : null;
      if (!meta) return '';
      var caption = v.caption ? '<p class="text-sm text-ink-soft mt-3">'+escHtml(v.caption)+'</p>' : '';
      var title = v.title ? '<p class="font-display text-base text-ink mt-3">'+escHtml(v.title)+'</p>' : '';
      return '<article class="bg-surface rounded-lg border border-outline-soft/40 overflow-hidden">' +
        '<div class="relative bg-black" style="aspect-ratio:9/16;">' +
          '<iframe src="'+escHtml(meta.embedUrl)+'" class="absolute inset-0 w-full h-full" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" allowfullscreen loading="lazy"></iframe>' +
        '</div>' +
        (title || caption ? '<div class="p-4 pt-0">'+title+caption+'</div>' : '') +
      '</article>';
    }

    function youtubeCardHtml(v) {
      var meta = window.VideoEmbed ? VideoEmbed.buildEmbedUrl(v.video_url) : null;
      if (!meta) return '';
      var caption = v.caption ? '<p class="text-sm text-ink-soft mt-4 text-center">'+escHtml(v.caption)+'</p>' : '';
      return '<div>' +
        '<div class="relative bg-black rounded-lg overflow-hidden border border-outline-soft/40" style="aspect-ratio:16/9;">' +
          '<iframe src="'+escHtml(meta.embedUrl)+'" class="absolute inset-0 w-full h-full" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" allowfullscreen loading="lazy"></iframe>' +
        '</div>' +
        caption +
      '</div>';
    }

    function loadLiveContent() {
      fetch('/api/events/index.php?limit=8').then(function (r) { return r.json(); }).then(function (d) {
        var grid = document.getElementById('liveEventsGrid');
        var rows = (d && d.data) || [];
        if (!rows.length) {
          grid.innerHTML = '<p class="text-sm text-ink-soft col-span-full text-center py-8" data-en="No upcoming events yet." data-am="ገና የተመዘገቡ ዝግጅቶች የሉም።">No upcoming events yet.</p>';
        } else {
          grid.innerHTML = rows.slice(0, 8).map(eventCardHtml).join('');
        }
        if (window._applyLang) window._applyLang(window._currentLang || 'en');
        if (window.EC) EC.rerenderIsoNodes();
      }).catch(function () {});

      fetch('/api/announcements/index.php?limit=4').then(function (r) { return r.json(); }).then(function (d) {
        var rows = (d && d.data) || [];
        if (!rows.length) return;
        document.getElementById('liveAnnouncementsSection').classList.remove('hidden');
        document.getElementById('liveAnnouncementsGrid').innerHTML = rows.map(announcementCardHtml).join('');
      }).catch(function () {});

      fetch('/api/posts/index.php?limit=3').then(function (r) { return r.json(); }).then(function (d) {
        var rows = (d && d.data) || [];
        if (!rows.length) return;
        document.getElementById('livePostsSection').classList.remove('hidden');
        document.getElementById('livePostsGrid').innerHTML = rows.map(postCardHtml).join('');
      }).catch(function () {});

      fetch('/api/videos/index.php?section=tiktok_latest&limit=3').then(function (r) { return r.json(); }).then(function (d) {
        var rows = (d && d.data) || [];
        if (!rows.length) return;
        var html = rows.map(tiktokCardHtml).filter(Boolean).join('');
        if (!html) return;
        document.getElementById('tiktokSection').classList.remove('hidden');
        document.getElementById('tiktokGrid').innerHTML = html;
      }).catch(function () {});

      fetch('/api/videos/index.php?section=youtube_latest&limit=1').then(function (r) { return r.json(); }).then(function (d) {
        var rows = (d && d.data) || [];
        if (!rows.length) return;
        var html = rows.map(youtubeCardHtml).filter(Boolean).join('');
        if (!html) return;
        document.getElementById('youtubeSection').classList.remove('hidden');
        document.getElementById('youtubeGrid').innerHTML = html;
      }).catch(function () {});
    }

    (function () {
      function applyLang(lang) {
        if (lang !== 'en' && lang !== 'am') lang = 'en';
        document.documentElement.setAttribute('data-lang', lang);
        document.documentElement.lang = lang;

        document.querySelectorAll('[data-en], [data-am]').forEach(function (el) {
          var attr = el.getAttribute('data-' + lang);
          if (attr !== null && attr !== '—') {
            el.innerHTML = attr;
          }
        });

        document.querySelectorAll('[data-lang-toggle]').forEach(function (group) {
          group.querySelectorAll('button').forEach(function (btn) {
            btn.classList.toggle('seg-active', btn.dataset.lang === lang);
            btn.classList.toggle('text-ink-soft', btn.dataset.lang !== lang);
          });
        });

        window._currentLang = lang;
        try { localStorage.setItem('gs_lang', lang); } catch (e) {}
      }
      window._applyLang = applyLang;
      // (paschal greeting animation is set up separately below)

      document.querySelectorAll('[data-lang-toggle] button').forEach(function (btn) {
        btn.addEventListener('click', function () { applyLang(btn.dataset.lang); });
      });

      var saved = 'en';
      try { saved = localStorage.getItem('gs_lang') || 'en'; } catch (e) {}
      applyLang(saved);
      loadLiveContent();
    })();
  </script>

  <script>
    // Paschal greeting: a typed call-and-response between two Christians.
    // Speaker A (gold) types in from the left, speaker B (light) from the right.
    (function () {
      var host = document.getElementById('paschalGreeting');
      if (!host) return;
      var lines = [
        'ክርስቶስ ተንሥአ እሙታን', 'በዓቢይ ኃይል ወሥልጣን',
        'አሠሮ ለሰይጣን', 'አግዓዞ ለአዳም',
        'ሰላም', 'እምይዕዜሰ', 'ኮነ', 'ፍሥሐ ወሰላም ! !'
      ];
      var nodes = lines.map(function (t, i) {
        var d = document.createElement('div');
        d.className = 'pg-line ' + (i % 2 === 0 ? 'pg-l' : 'pg-r');
        d.innerHTML = '<span class="pg-text"></span>';
        d.dataset.t = t;
        host.appendChild(d);
        return d;
      });
      var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      if (reduce) {
        nodes.forEach(function (n) { n.classList.add('pg-show', 'pg-done'); n.querySelector('.pg-text').textContent = n.dataset.t; });
        return;
      }
      var li = 0;
      function typeLine() {
        if (li >= nodes.length) return;
        var node = nodes[li], span = node.querySelector('.pg-text'), txt = node.dataset.t, ci = 0;
        node.classList.add('pg-show');
        setTimeout(function step() {
          span.textContent = txt.slice(0, ci);
          if (ci++ < txt.length) { setTimeout(step, 55); }
          else { node.classList.add('pg-done'); li++; setTimeout(typeLine, 320); }
        }, 260); // let the slide-in settle before typing
      }
      typeLine();
    })();
  </script>

  <script>
    // Building progress / final design slider — native overflow-x scroll-snap
    // (touch-swipeable by default), with prev/next arrows + dots on desktop.
    (function () {
      var slider = document.getElementById('buildSlider');
      if (!slider) return;
      var slides = Array.prototype.slice.call(slider.children);
      var dotsHost = document.getElementById('buildDots');
      var prevBtn = document.getElementById('buildPrev');
      var nextBtn = document.getElementById('buildNext');
      if (!slides.length) return;

      slides.forEach(function (s, i) {
        var d = document.createElement('button');
        d.type = 'button';
        d.setAttribute('aria-label', 'Go to photo ' + (i + 1));
        d.className = 'build-dot w-2 h-2 rounded-full bg-surface/30 transition-colors';
        d.addEventListener('click', function () { scrollToSlide(i); });
        dotsHost.appendChild(d);
      });
      var dots = Array.prototype.slice.call(dotsHost.children);

      function scrollToSlide(i) {
        i = Math.max(0, Math.min(slides.length - 1, i));
        slider.scrollTo({ left: slides[i].offsetLeft - slider.offsetLeft, behavior: 'smooth' });
      }

      function currentIndex() {
        var pos = slider.scrollLeft + slider.clientWidth / 2;
        var best = 0, bestDist = Infinity;
        slides.forEach(function (s, i) {
          var c = (s.offsetLeft - slider.offsetLeft) + s.offsetWidth / 2;
          var dist = Math.abs(c - pos);
          if (dist < bestDist) { bestDist = dist; best = i; }
        });
        return best;
      }

      function updateDots() {
        var idx = currentIndex();
        dots.forEach(function (d, i) { d.classList.toggle('bg-surface/90', i === idx); d.classList.toggle('bg-surface/30', i !== idx); });
      }

      if (prevBtn) prevBtn.addEventListener('click', function () { scrollToSlide(currentIndex() - 1); });
      if (nextBtn) nextBtn.addEventListener('click', function () { scrollToSlide(currentIndex() + 1); });

      var scrollTimer;
      slider.addEventListener('scroll', function () {
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(updateDots, 80);
      }, { passive: true });

      updateDots();
    })();
  </script>

  <script>
    // Public registration: dynamic multi-form renderer against /api/registrations/*.
    // Frozen contract — see spec. Hides gracefully if the backend isn't deployed yet.
    (function () {
      var section = document.getElementById('register');
      var tabsHost = document.getElementById('regTabs');
      var formHost = document.getElementById('regFormHost');
      var modal = document.getElementById('regModal');
      var modalCard = document.getElementById('regModalCard');
      var modalMsg = document.getElementById('regModalMsg');
      var modalOk = document.getElementById('regModalOk');
      if (!section || !tabsHost || !formHost || !modal) return;

      var forms = [];
      var activeIndex = -1;
      var pendingSlug = null;
      var lastSuccess = false;

      function curLang() { return window._currentLang || 'en'; }
      function t(en, am) { return curLang() === 'am' ? am : en; }

      function ensureCsrf() {
        var tok = null;
        try { tok = sessionStorage.getItem('csrf_token'); } catch (e) {}
        if (tok) return Promise.resolve(tok);
        return fetch('/api/auth/csrf.php').then(function (r) { return r.json(); }).then(function (d) {
          try { sessionStorage.setItem('csrf_token', d.csrf_token); } catch (e) {}
          return d.csrf_token;
        });
      }

      function statusMeta(status) {
        if (status === 'open') return { en: 'Open', am: 'ክፍት ነው', cls: 'bg-olive/15 text-olive' };
        if (status === 'limited') return { en: 'Limited Spots', am: 'ውስን ቦታዎች', cls: 'bg-gold/15 text-gold' };
        return { en: 'Closed', am: 'ዝግ ነው', cls: 'bg-outline-soft/50 text-outline' };
      }

      function updateAnnounceBadges() {
        document.querySelectorAll('.reg-announce-card[data-reg-slug]').forEach(function (card) {
          var slug = card.getAttribute('data-reg-slug');
          var match = null;
          forms.forEach(function (f) { if (f.slug === slug) match = f; });
          if (!match) return;
          var badge = card.querySelector('.reg-status-badge');
          var btn = card.querySelector('.reg-cta-btn');
          var m = statusMeta(match.status);
          if (badge) {
            badge.className = 'reg-status-badge text-[11px] font-semibold uppercase tracking-widestest px-2.5 py-1 rounded-full whitespace-nowrap ' + m.cls;
            var span = badge.querySelector('span');
            if (span) {
              span.setAttribute('data-en', m.en);
              span.setAttribute('data-am', m.am);
              span.textContent = t(m.en, m.am);
            }
          }
          if (btn) {
            if (match.status === 'closed') { btn.disabled = true; btn.classList.add('opacity-50', 'cursor-not-allowed'); }
            else { btn.disabled = false; btn.classList.remove('opacity-50', 'cursor-not-allowed'); }
          }
        });
      }

      function renderTabs() {
        tabsHost.innerHTML = forms.map(function (f, i) {
          var m = statusMeta(f.status);
          var active = i === activeIndex;
          return '<button type="button" role="tab" aria-selected="' + (active ? 'true' : 'false') + '" data-idx="' + i + '" ' +
            'class="reg-tab-btn inline-flex items-center gap-2 px-4 py-2.5 rounded-full border text-sm font-medium transition-colors ' +
            (active ? 'bg-primary text-surface border-primary' : 'bg-surface text-ink-soft border-outline-soft/50 hover:border-primary/40') + '">' +
            '<span>' + escHtml(t(f.title_en, f.title_am)) + '</span>' +
            '<span class="text-[10px] font-semibold uppercase tracking-widestest px-2 py-0.5 rounded-full ' + (active ? 'bg-surface/20 text-surface' : m.cls) + '">' + escHtml(t(m.en, m.am)) + '</span>' +
            '</button>';
        }).join('');
        Array.prototype.slice.call(tabsHost.querySelectorAll('.reg-tab-btn')).forEach(function (btn) {
          btn.addEventListener('click', function () { selectForm(parseInt(btn.getAttribute('data-idx'), 10)); });
        });
      }

      function fieldInputName(f) { return 'field_' + f.id; }

      function renderField(f) {
        var label = escHtml(t(f.label_en, f.label_am));
        var placeholder = escHtml(t(f.placeholder_en || '', f.placeholder_am || ''));
        var req = f.required ? ' <span class="text-primary">*</span>' : '';
        var name = fieldInputName(f);
        var common = 'class="reg-field-input w-full rounded border border-outline-soft/50 bg-surface px-4 py-2.5 text-sm text-ink focus:border-primary" data-field-id="' + f.id + '"';
        var inputHtml = '';
        switch (f.type) {
          case 'textarea':
            inputHtml = '<textarea name="' + name + '" rows="4" placeholder="' + placeholder + '" ' + common + (f.required ? ' required' : '') + '></textarea>';
            break;
          case 'select':
            var opts = '<option value="">' + escHtml(t('Choose…', 'ይምረጡ…')) + '</option>' +
              (f.options || []).map(function (o) { return '<option value="' + escHtml(o.value) + '">' + escHtml(t(o.label_en, o.label_am)) + '</option>'; }).join('');
            inputHtml = '<select name="' + name + '" ' + common + (f.required ? ' required' : '') + '>' + opts + '</select>';
            break;
          case 'radio':
            inputHtml = '<div class="flex flex-col gap-2">' + (f.options || []).map(function (o) {
              return '<label class="flex items-center gap-2 text-sm text-ink-soft"><input type="radio" name="' + name + '" value="' + escHtml(o.value) + '" class="reg-field-input" data-field-id="' + f.id + '"' + (f.required ? ' required' : '') + '> ' + escHtml(t(o.label_en, o.label_am)) + '</label>';
            }).join('') + '</div>';
            break;
          case 'checkbox':
            inputHtml = '<div class="flex flex-col gap-2">' + (f.options || []).map(function (o) {
              return '<label class="flex items-center gap-2 text-sm text-ink-soft"><input type="checkbox" name="' + name + '[]" value="' + escHtml(o.value) + '" class="reg-field-input" data-field-id="' + f.id + '"> ' + escHtml(t(o.label_en, o.label_am)) + '</label>';
            }).join('') + '</div>';
            break;
          default:
            var htmlType = ({ text: 'text', email: 'email', phone: 'tel', number: 'number', date: 'date' })[f.type] || 'text';
            inputHtml = '<input type="' + htmlType + '" name="' + name + '" placeholder="' + placeholder + '" ' + common + (f.required ? ' required' : '') + ' />';
        }
        return '<div class="reg-field mb-5">' +
          '<label class="block text-sm font-medium text-ink mb-1.5">' + label + req + '</label>' +
          inputHtml +
          '</div>';
      }

      function renderClosedNotice() {
        return '<div class="rounded-lg border border-outline-soft/40 bg-surface-mid p-6 text-center">' +
          '<p class="text-ink-soft">' + escHtml(t('Registration for this program is currently closed.', 'ለዚህ ፕሮግራም ምዝገባ በአሁኑ ጊዜ ዝግ ነው።')) + '</p>' +
          '</div>';
      }

      function selectForm(i) {
        if (i < 0 || i >= forms.length) return;
        activeIndex = i;
        renderTabs();
        var f = forms[i];

        if (f.status === 'closed') {
          formHost.innerHTML = '<p class="text-ink-soft mb-6">' + escHtml(t(f.description_en, f.description_am)) + '</p>' + renderClosedNotice();
          return;
        }

        var fields = (f.fields || []).slice().sort(function (a, b) { return (a.sort_order || 0) - (b.sort_order || 0); });
        formHost.innerHTML =
          '<p class="text-ink-soft mb-6">' + escHtml(t(f.description_en, f.description_am)) + '</p>' +
          '<form id="regForm" novalidate>' +
            '<div class="reg-honeypot" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;" aria-hidden="true">' +
              '<label>Website<input type="text" name="website" tabindex="-1" autocomplete="off" /></label>' +
            '</div>' +
            fields.map(renderField).join('') +
            '<button type="submit" id="regSubmitBtn" class="w-full inline-flex justify-center items-center gap-2 bg-primary text-surface px-6 py-3.5 rounded text-xs font-semibold uppercase tracking-widestest hover:bg-primary-soft transition-colors">' +
              '<span>' + escHtml(t('Submit registration', 'ምዝገባ ላክ')) + '</span>' +
            '</button>' +
          '</form>';

        var formEl = document.getElementById('regForm');
        formEl.addEventListener('submit', function (e) { handleSubmit(e, f, fields); });
      }

      function validate(fields, formEl) {
        for (var i = 0; i < fields.length; i++) {
          var f = fields[i];
          if (!f.required) continue;
          var name = fieldInputName(f);
          if (f.type === 'checkbox') {
            var checked = formEl.querySelectorAll('input[name="' + name + '[]"]:checked');
            if (!checked.length) return t('Please complete: ', 'እባክዎ ይሙሉ፦ ') + t(f.label_en, f.label_am);
          } else if (f.type === 'radio') {
            var rchecked = formEl.querySelector('input[name="' + name + '"]:checked');
            if (!rchecked) return t('Please complete: ', 'እባክዎ ይሙሉ፦ ') + t(f.label_en, f.label_am);
          } else {
            var el = formEl.querySelector('[name="' + name + '"]');
            if (!el || !String(el.value || '').trim()) return t('Please complete: ', 'እባክዎ ይሙሉ፦ ') + t(f.label_en, f.label_am);
          }
        }
        return null;
      }

      function collectAnswers(fields, formEl) {
        var answers = {};
        fields.forEach(function (f) {
          var name = fieldInputName(f);
          if (f.type === 'checkbox') {
            answers[f.id] = Array.prototype.slice.call(formEl.querySelectorAll('input[name="' + name + '[]"]:checked')).map(function (el) { return el.value; });
          } else if (f.type === 'radio') {
            var rchecked = formEl.querySelector('input[name="' + name + '"]:checked');
            answers[f.id] = rchecked ? rchecked.value : '';
          } else {
            var el = formEl.querySelector('[name="' + name + '"]');
            answers[f.id] = el ? el.value : '';
          }
        });
        return answers;
      }

      function showModal(msg, isError) {
        lastSuccess = !isError;
        modalMsg.textContent = msg;
        modalCard.classList.remove('border-primary', 'border-gold');
        modalCard.classList.add(isError ? 'border-gold' : 'border-primary');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
      }

      function hideModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (lastSuccess && activeIndex >= 0) selectForm(activeIndex);
      }

      if (modalOk) modalOk.addEventListener('click', hideModal);
      modal.addEventListener('click', function (e) { if (e.target === modal) hideModal(); });

      function handleSubmit(e, f, fields) {
        e.preventDefault();
        var formEl = e.target;
        var err = validate(fields, formEl);
        if (err) { showModal(err, true); return; }

        var honeypot = formEl.querySelector('input[name="website"]');
        var answers = collectAnswers(fields, formEl);
        var btn = document.getElementById('regSubmitBtn');
        var originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span>' + escHtml(t('Sending…', 'በመላክ ላይ…')) + '</span>';

        ensureCsrf().then(function (token) {
          return fetch('/api/registrations/submit.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
            body: JSON.stringify({ form_id: f.id, answers: answers, website: honeypot ? honeypot.value : '' })
          });
        }).then(function (r) {
          return r.json().then(function (d) { return { ok: r.ok, data: d }; }).catch(function () { return { ok: r.ok, data: {} }; });
        }).then(function (res) {
          btn.disabled = false;
          btn.innerHTML = originalHtml;
          if (!res.ok || !(res.data && res.data.data && res.data.data.ok)) {
            throw new Error((res.data && res.data.error) || t('Something went wrong. Please try again.', 'የሆነ ስህተት ተከስቷል። እባክዎ እንደገና ይሞክሩ።'));
          }
          showModal(t('Registration received. God bless you.', 'ምዝገባዎ ደርሶናል። እግዚአብሔር ይባርክዎ።'), false);
        }).catch(function (err2) {
          btn.disabled = false;
          btn.innerHTML = originalHtml;
          showModal(err2.message || t('Something went wrong. Please try again.', 'የሆነ ስህተት ተከስቷል። እባክዎ እንደገና ይሞክሩ።'), true);
        });
      }

      function selectBySlug(slug) {
        var idx = -1;
        forms.forEach(function (f, i) { if (f.slug === slug) idx = i; });
        if (idx >= 0) selectForm(idx); else if (forms.length) selectForm(0);
      }

      document.querySelectorAll('.reg-cta-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var card = btn.closest('[data-reg-slug]');
          var slug = card ? card.getAttribute('data-reg-slug') : null;
          pendingSlug = slug;
          if (forms.length) selectBySlug(slug);
          section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
      });

      window.__regRerenderPlaceholders = function () {
        if (activeIndex < 0) return;
        renderTabs();
        selectForm(activeIndex);
        updateAnnounceBadges();
      };

      fetch('/api/registrations/index.php').then(function (r) {
        if (!r.ok) throw new Error('bad status');
        return r.json();
      }).then(function (d) {
        forms = (d && d.data) || [];
        if (!forms.length) { section.classList.add('hidden'); return; }
        section.classList.remove('hidden');
        updateAnnounceBadges();
        renderTabs();
        if (pendingSlug) selectBySlug(pendingSlug); else selectForm(0);
      }).catch(function () {
        section.classList.add('hidden');
      });

      // Re-render dynamic tab/form copy (and re-check announcement badges) on language toggle.
      var origApplyLang = window._applyLang;
      if (typeof origApplyLang === 'function') {
        window._applyLang = function (lang) {
          origApplyLang(lang);
          if (window.__regRerenderPlaceholders) window.__regRerenderPlaceholders();
        };
      }
    })();
  </script>
</body>
</html>
