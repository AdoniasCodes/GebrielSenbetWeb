<?php
// public/index.php — Public landing page (Sacred Scholarly Minimalist), bilingual EN/አማ

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
<html lang="en" data-lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Gebriel Senbet — Sabbath School</title>
  <meta name="description" content="Saint Gabriel Sabbath School. A modern home for our Sunday school: curriculum, grading, payments, and community announcements in one reverent place." />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,400..700&family=Plus+Jakarta+Sans:wght@400..700&family=Noto+Sans+Ethiopic:wght@400;500;700&family=Noto+Serif+Ethiopic:wght@400;600;700&display=swap" rel="stylesheet" />

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
    html, body { background: #fcf9f2; }
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

    .seg-active { background: #fed175; color: #5b0617; }

    .scripture { background: radial-gradient(ellipse at top, #7a1f2b 0%, #5b0617 60%, #40000c 100%); color: #f3f0ea; }

    :where(a, button, input, select, textarea):focus-visible {
      outline: 2px solid #c9a14a; outline-offset: 2px; border-radius: 2px;
    }
  </style>
</head>
<body class="bg-surface text-ink font-body antialiased">

  <!-- ============ TOP NAV ============ -->
  <header class="sticky top-0 z-50 border-b border-outline-soft/40 bg-surface/85 backdrop-blur-md">
    <div class="max-w-[1280px] mx-auto px-6 lg:px-8 h-16 flex items-center justify-between">
      <a href="/" class="flex items-center gap-3 group">
        <span class="inline-flex items-center justify-center w-8 h-8 rounded-sm bg-primary text-surface">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round">
            <path d="M12 2.5v19M2.5 12h19M6.5 6.5l11 11M17.5 6.5l-11 11"/>
            <circle cx="12" cy="12" r="2.2" fill="currentColor" stroke="none"/>
          </svg>
        </span>
        <span class="font-display text-xl font-semibold tracking-tight text-primary leading-none" data-en="Gebriel Senbet" data-am="ገብርኤል ሰንበት">Gebriel Senbet</span>
      </a>

      <nav class="hidden md:flex items-center gap-8 text-[15px] text-ink-soft">
        <a class="hover:text-primary transition-colors" href="#about" data-en="About" data-am="ስለ እኛ">About</a>
        <a class="hover:text-primary transition-colors" href="#programs" data-en="Programs" data-am="ፕሮግራሞች">Programs</a>
        <a class="hover:text-primary transition-colors" href="#calendar" data-en="Calendar" data-am="የቀን መቁጠሪያ">Calendar</a>
        <a class="hover:text-primary transition-colors" href="/blog.php" data-en="Blog" data-am="ብሎግ">Blog</a>
      </nav>

      <div class="flex items-center gap-3">
        <div data-lang-toggle class="hidden sm:flex items-center bg-surface-mid rounded-full p-0.5 border border-outline-soft/50">
          <button data-lang="en" class="seg-active px-3 py-1 text-xs font-semibold rounded-full">EN</button>
          <button data-lang="am" class="px-3 py-1 text-xs font-semibold rounded-full text-ink-soft hover:text-primary ethiopic">አማ</button>
        </div>
        <a href="/login.html" class="inline-flex items-center gap-2 bg-primary text-surface px-4 py-2 rounded text-xs font-semibold uppercase tracking-widestest hover:bg-primary-soft transition-colors">
          <span data-en="Sign in" data-am="ግባ">Sign in</span>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
        </a>
      </div>
    </div>
  </header>

  <main>

    <!-- ============ HERO ============ -->
    <section class="paper relative overflow-hidden">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-8 py-20 lg:py-28 grid lg:grid-cols-12 gap-10 lg:gap-16 items-center">
        <div class="lg:col-span-7">
          <div class="inline-flex items-center gap-2 rounded-full bg-surface-mid border border-outline-soft/60 px-3 py-1 mb-7">
            <span class="w-1.5 h-1.5 rounded-full bg-gold animate-pulse"></span>
            <span class="text-[11px] font-semibold uppercase tracking-widestest text-gold" data-en="Enrollment open · <?= $year ?>" data-am="ምዝገባ ክፍት ነው · <?= $year ?>">Enrollment open · <?= $year ?></span>
          </div>

          <h1 class="font-display text-[44px] lg:text-[60px] leading-[1.05] tracking-tight text-primary font-semibold" data-en="A modern home for our<br/>Sabbath school." data-am="ለሰንበት ት/ቤታችን<br/>ዘመናዊ ቤት።">A modern home for our<br/>Sabbath school.</h1>

          <div class="mt-5 flex items-center gap-4">
            <span class="rule-gold-short"></span>
            <p class="ethiopic text-xl text-ink-soft" data-en="ለሰንበት ት/ቤታችን ዘመናዊ ቤት" data-am="ቅዱስ ገብርኤል ሰንበት ት/ቤት">ለሰንበት ት/ቤታችን ዘመናዊ ቤት</p>
          </div>

          <p class="mt-7 text-lg leading-relaxed text-ink-soft max-w-xl" data-en="Saint Gabriel Sabbath School brings curriculum, grading, payments, and community announcements into one focused space — built with the rhythm of our church year and the warmth of our community in mind." data-am="የቅዱስ ገብርኤል ሰንበት ት/ቤት ሥርዓተ ትምህርትን፣ ውጤት መለያን፣ ክፍያዎችን እና ማኅበረሰብ ማስታወቂያዎችን ሁሉ በአንድ ቦታ ያሰባስባል — ከቤተ ክርስቲያናችን ዓመታዊ ምት እና ከማኅበረሰባችን ሙቀት ጋር ተስማምቶ የተሰራ።">
            Saint Gabriel Sabbath School brings curriculum, grading, payments, and community announcements into one focused space — built with the rhythm of our church year and the warmth of our community in mind.
          </p>

          <div class="mt-8 flex flex-col sm:flex-row gap-3">
            <a href="/login.html" class="inline-flex justify-center items-center gap-2 bg-primary text-surface px-7 py-3.5 rounded text-xs font-semibold uppercase tracking-widestest hover:bg-primary-soft transition-colors">
              <span data-en="Sign in" data-am="ግባ">Sign in</span>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
            </a>
            <a href="#enroll" class="inline-flex justify-center items-center gap-2 border border-outline text-primary px-7 py-3.5 rounded text-xs font-semibold uppercase tracking-widestest hover:bg-surface-mid transition-colors" data-en="Request enrollment" data-am="ምዝገባ ይጠይቁ">Request enrollment</a>
          </div>

          <div class="mt-10 flex items-center gap-6 text-sm text-outline">
            <div class="flex items-center gap-2"><span class="w-1 h-1 rounded-full bg-gold"></span><span data-en="Two tracks" data-am="ሁለት ኮርሶች">Two tracks</span></div>
            <div class="flex items-center gap-2"><span class="w-1 h-1 rounded-full bg-gold"></span><span data-en="13 levels" data-am="13 ደረጃዎች">13 levels</span></div>
            <div class="flex items-center gap-2"><span class="w-1 h-1 rounded-full bg-gold"></span><span data-en="One faith" data-am="አንድ እምነት">One faith</span></div>
          </div>
        </div>

        <div class="lg:col-span-5">
          <div class="manuscript rounded-lg aspect-[4/5] flex flex-col items-center justify-center px-8 py-12 text-center shadow-sm">
            <span class="corner-tr"></span>
            <span class="corner-bl"></span>

            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#c9a14a" stroke-width="1.4" stroke-linecap="round" class="mb-6">
              <path d="M12 2.5v19M2.5 12h19M6.5 6.5l11 11M17.5 6.5l-11 11"/>
              <circle cx="12" cy="12" r="2" fill="#c9a14a" stroke="none"/>
            </svg>

            <p class="eyebrow text-gold mb-5"><span class="rule-gold-tiny"></span><span data-en="Sanctified Curriculum" data-am="ቅዱስ ሥርዓተ ትምህርት">Sanctified Curriculum</span><span class="rule-gold-tiny"></span></p>

            <div class="font-display ethiopic text-[140px] lg:text-[160px] leading-none text-primary font-bold mb-2" style="font-family: 'Noto Serif Ethiopic', 'Noto Sans Ethiopic', serif;">ገ</div>

            <p class="ethiopic text-2xl text-primary-soft mb-1">ገብርኤል ሰንበት</p>
            <p class="ethiopic text-sm text-outline">ት/ቤት</p>

            <div class="mt-8 w-full">
              <div class="rule-gold mb-4"></div>
              <p class="text-xs uppercase tracking-widestest text-outline" data-en="Est. Saint Gabriel Parish" data-am="የቅዱስ ገብርኤል ቤተ ክርስቲያን">Est. Saint Gabriel Parish</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ============ MISSION / ABOUT ============ -->
    <section id="about" class="bg-surface-low border-y border-outline-soft/40">
      <div class="max-w-3xl mx-auto px-6 lg:px-8 py-20 text-center">
        <p class="eyebrow"><span class="rule-gold-tiny"></span><span data-en="Our Mission" data-am="ተልዕኳችን">Our Mission</span><span class="rule-gold-tiny"></span></p>
        <h2 class="font-display text-3xl lg:text-4xl text-primary mt-5 mb-6 leading-tight" data-en="Bridging heritage and<br/>everyday efficiency." data-am="ቅርስን ከዕለታዊ<br/>ብቃት ጋር ማገናኘት።">
          Bridging heritage and<br/>everyday efficiency.
        </h2>
        <p class="text-lg text-ink-soft leading-relaxed" data-en="For decades, our Sabbath school has shaped the faith and scholarship of generations. Today we honor that legacy with tools that respect the gravity of the work — and the people who do it." data-am="ለበርካታ አሥርት ዓመታት፣ የሰንበት ት/ቤታችን የብዙ ትውልዶችን እምነት እና ምሁርነት ቀርጿል። ዛሬ ለዚያ ቅርስ የምናከብረው የሥራውን ክብደት — እና ሥራውን የሚሠሩ ሰዎችን — በሚያከብሩ መሣሪያዎች ነው።">
          For decades, our Sabbath school has shaped the faith and scholarship of generations. Today we honor that legacy with tools that respect the gravity of the work — and the people who do it.
        </p>
      </div>
    </section>

    <!-- ============ PILLARS ============ -->
    <section class="max-w-[1280px] mx-auto px-6 lg:px-8 py-20">
      <div class="text-center mb-14">
        <p class="eyebrow"><span class="rule-gold-tiny"></span><span data-en="Three Pillars" data-am="ሦስቱ ምሰሶዎች">Three Pillars</span><span class="rule-gold-tiny"></span></p>
        <h2 class="font-display text-3xl lg:text-4xl text-primary mt-4" data-en="What we hold up." data-am="የምንታመንባቸው።">What we hold up.</h2>
      </div>

      <div class="grid md:grid-cols-3 gap-px bg-outline-soft/30 rounded-lg overflow-hidden border border-outline-soft/40">
        <div class="bg-surface p-8 lg:p-10">
          <div class="w-11 h-11 rounded-sm bg-primary/10 text-primary inline-flex items-center justify-center mb-5">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M12 2.5v19M2.5 12h19M6.5 6.5l11 11M17.5 6.5l-11 11"/></svg>
          </div>
          <h3 class="font-display text-xl text-primary mb-2" data-en="Spiritual Formation" data-am="መንፈሳዊ ምስረታ">Spiritual Formation</h3>
          <p class="text-ink-soft leading-relaxed" data-en="Catechism, scripture, and tradition taught with reverence — for every age, in every track." data-am="ሃይማኖታዊ ትምህርት፣ ቅዱስ መጽሐፍ እና ባህል በክብር — ለሁሉም ዕድሜ፣ በሁሉም ኮርስ።">Catechism, scripture, and tradition taught with reverence — for every age, in every track.</p>
        </div>
        <div class="bg-surface p-8 lg:p-10">
          <div class="w-11 h-11 rounded-sm bg-gold/15 text-gold inline-flex items-center justify-center mb-5">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M2 4h6a4 4 0 0 1 4 4v13M22 4h-6a4 4 0 0 0-4 4v13"/></svg>
          </div>
          <h3 class="font-display text-xl text-primary mb-2" data-en="Scholarly Tradition" data-am="ምሁራዊ ባህል">Scholarly Tradition</h3>
          <p class="text-ink-soft leading-relaxed" data-en="Structured progression through historical, theological, and liturgical study — paced by term." data-am="በታሪካዊ፣ ሥነ መለኮታዊ እና ሥርዓተ አምልኮ ጥናት — በወቅቶች የተደራጀ እድገት።">Structured progression through historical, theological, and liturgical study — paced by term.</p>
        </div>
        <div class="bg-surface p-8 lg:p-10">
          <div class="w-11 h-11 rounded-sm bg-olive/15 text-olive inline-flex items-center justify-center mb-5">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </div>
          <h3 class="font-display text-xl text-primary mb-2" data-en="Community Service" data-am="የማኅበረሰብ አገልግሎት">Community Service</h3>
          <p class="text-ink-soft leading-relaxed" data-en="A school is its people. We make it easier for families, teachers, and clergy to stay woven together." data-am="ት/ቤት ሰዎቹ ናቸው። ለቤተሰቦች፣ ለመምህራን እና ለካህናት እርስ በርስ ለመተሳሰር ቀላል እናደርጋለን።">A school is its people. We make it easier for families, teachers, and clergy to stay woven together.</p>
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
            <div class="pattern-children h-40 relative">
              <div class="absolute inset-0 bg-gradient-to-t from-surface to-transparent"></div>
              <span class="absolute top-5 left-6 text-[11px] font-semibold uppercase tracking-widestest text-gold" data-en="Track 01" data-am="ኮርስ 01">Track 01</span>
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
            <div class="pattern-adult h-40 relative">
              <div class="absolute inset-0 bg-gradient-to-t from-surface to-transparent"></div>
              <span class="absolute top-5 left-6 text-[11px] font-semibold uppercase tracking-widestest text-gold" data-en="Track 02" data-am="ኮርስ 02">Track 02</span>
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
        <p class="eyebrow"><span class="rule-gold-tiny"></span><span data-en="What's Inside" data-am="ምን ይዟል">What's Inside</span><span class="rule-gold-tiny"></span></p>
        <h2 class="font-display text-3xl lg:text-4xl text-primary mt-4 leading-tight" data-en="A reverent operating system for the school year." data-am="ለትምህርት ዓመቱ የተከበረ ሥርዓት።">A reverent operating system for the school year.</h2>
      </div>

      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-px bg-outline-soft/30 rounded-lg overflow-hidden border border-outline-soft/40">
        <?php
          $features = [
            [
              'en_title' => 'Curriculum',
              'am_title' => 'ሥርዓተ ትምህርት',
              'en_desc'  => 'Define tracks, levels, classes, and subjects with the structure your school already uses.',
              'am_desc'  => 'ት/ቤትዎ የሚጠቀምበትን አወቃቀር በመጠቀም ኮርሶችን፣ ደረጃዎችን፣ ክፍሎችን እና ትምህርቶችን ይፍጠሩ።',
              'svg'      => '<path d="M2 4h6a4 4 0 0 1 4 4v13M22 4h-6a4 4 0 0 0-4 4v13"/>',
            ],
            [
              'en_title' => 'Grading & report cards',
              'am_title' => 'ውጤት እና ሪፖርት ካርድ',
              'en_desc'  => 'Teachers enter scores per subject and term. Students and families view results.',
              'am_desc'  => 'መምህራን በትምህርት እና በወቅት ውጤት ያስገባሉ። ተማሪዎች እና ቤተሰቦች ውጤትን ይመለከታሉ።',
              'svg'      => '<path d="M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
            ],
            [
              'en_title' => 'Class scheduling',
              'am_title' => 'የክፍል መርሐ ግብር',
              'en_desc'  => 'Assign teachers as primary or substitute, with date-bounded responsibility.',
              'am_desc'  => 'መምህራንን እንደ ቋሚ ወይም ምትክ ይመድቡ፣ በቀን ወሰን ኃላፊነት።',
              'svg'      => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
            ],
            [
              'en_title' => 'Payments tracking',
              'am_title' => 'የክፍያ መከታተያ',
              'en_desc'  => 'Per-term payment status, partial balances, and one-glance defaulter lists.',
              'am_desc'  => 'በወቅት የክፍያ ሁኔታ፣ ቀሪ ሂሳቦች እና ያልከፈሉ ተማሪዎች ዝርዝር።',
              'svg'      => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/>',
            ],
            [
              'en_title' => 'Events & calendar',
              'am_title' => 'ዝግጅቶች እና የቀን መቁጠሪያ',
              'en_desc'  => 'Recurring services, holy days, and parish events in a unified calendar.',
              'am_desc'  => 'ተደጋጋሚ አገልግሎቶች፣ የበዓል ቀኖች እና የቤተ ክርስቲያን ዝግጅቶች በአንድ መርሐ ግብር።',
              'svg'      => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',
            ],
            [
              'en_title' => 'Announcements',
              'am_title' => 'ማስታወቂያዎች',
              'en_desc'  => 'Targeted notices to a class, a role, or families with outstanding payments.',
              'am_desc'  => 'ለክፍል፣ ለሚና ወይም ላልከፈሉ ቤተሰቦች የተወሰነ ማስታወቂያ።',
              'svg'      => '<path d="M3 11l18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>',
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

    <!-- ============ ROLES ============ -->
    <section class="bg-surface-low border-y border-outline-soft/40">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-8 py-20">
        <div class="text-center max-w-2xl mx-auto mb-14">
          <p class="eyebrow"><span class="rule-gold-tiny"></span><span data-en="Built For Everyone" data-am="ለሁሉም የተሠራ">Built For Everyone</span><span class="rule-gold-tiny"></span></p>
          <h2 class="font-display text-3xl lg:text-4xl text-primary mt-4" data-en="A view for every seat in the room." data-am="ለእያንዳንዱ ሰው የተለየ እይታ።">A view for every seat in the room.</h2>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
          <div class="bg-surface rounded-lg p-7 border border-outline-soft/40">
            <div class="flex items-center gap-3 mb-5">
              <span class="w-2 h-2 rounded-full bg-primary"></span>
              <span class="text-[11px] font-semibold uppercase tracking-widestest text-primary" data-en="Administrator" data-am="አስተዳዳሪ">Administrator</span>
            </div>
            <h3 class="font-display text-xl text-ink mb-4" data-en="For the superintendent." data-am="ለበላይ ኃላፊው።">For the superintendent.</h3>
            <ul class="space-y-3 text-sm text-ink-soft">
              <li class="flex gap-3"><span class="mt-1.5 inline-block w-1 h-1 rounded-full bg-gold flex-shrink-0"></span><span data-en="Manage tracks, levels, classes, subjects, and academic terms." data-am="ኮርሶችን፣ ደረጃዎችን፣ ክፍሎችን፣ ትምህርቶችን እና የትምህርት ወቅቶችን ያስተዳድሩ።">Manage tracks, levels, classes, subjects, and academic terms.</span></li>
              <li class="flex gap-3"><span class="mt-1.5 inline-block w-1 h-1 rounded-full bg-gold flex-shrink-0"></span><span data-en="Onboard teachers and students with auto-generated credentials." data-am="ለመምህራን እና ለተማሪዎች በራስ-ሰር መለያዎችን ይፍጠሩ።">Onboard teachers and students with auto-generated credentials.</span></li>
              <li class="flex gap-3"><span class="mt-1.5 inline-block w-1 h-1 rounded-full bg-gold flex-shrink-0"></span><span data-en="Reconcile payments and broadcast announcements." data-am="ክፍያዎችን ያስተካክሉ እና ማስታወቂያዎችን ይላኩ።">Reconcile payments and broadcast announcements.</span></li>
            </ul>
          </div>

          <div class="bg-surface rounded-lg p-7 border border-outline-soft/40">
            <div class="flex items-center gap-3 mb-5">
              <span class="w-2 h-2 rounded-full bg-gold"></span>
              <span class="text-[11px] font-semibold uppercase tracking-widestest text-gold" data-en="Teacher" data-am="መምህር">Teacher</span>
            </div>
            <h3 class="font-display text-xl text-ink mb-4" data-en="For the instructor." data-am="ለመምህሩ።">For the instructor.</h3>
            <ul class="space-y-3 text-sm text-ink-soft">
              <li class="flex gap-3"><span class="mt-1.5 inline-block w-1 h-1 rounded-full bg-gold flex-shrink-0"></span><span data-en="See only the classes and subjects assigned to you." data-am="የተመደቡልዎትን ክፍሎች እና ትምህርቶች ብቻ ይመልከቱ።">See only the classes and subjects assigned to you.</span></li>
              <li class="flex gap-3"><span class="mt-1.5 inline-block w-1 h-1 rounded-full bg-gold flex-shrink-0"></span><span data-en="Enter and revise grades inline by term." data-am="በወቅት ውጤትን ያስገቡ እና ያስተካክሉ።">Enter and revise grades inline by term.</span></li>
              <li class="flex gap-3"><span class="mt-1.5 inline-block w-1 h-1 rounded-full bg-gold flex-shrink-0"></span><span data-en="Post lesson notes and handouts to your roster." data-am="የትምህርት ማስታወሻዎችን እና ጽሑፎችን ለተማሪዎችዎ ይላኩ።">Post lesson notes and handouts to your roster.</span></li>
            </ul>
          </div>

          <div class="bg-surface rounded-lg p-7 border border-outline-soft/40">
            <div class="flex items-center gap-3 mb-5">
              <span class="w-2 h-2 rounded-full bg-olive"></span>
              <span class="text-[11px] font-semibold uppercase tracking-widestest text-olive" data-en="Student &amp; Family" data-am="ተማሪ እና ቤተሰብ">Student &amp; Family</span>
            </div>
            <h3 class="font-display text-xl text-ink mb-4" data-en="For the learner." data-am="ለተማሪው።">For the learner.</h3>
            <ul class="space-y-3 text-sm text-ink-soft">
              <li class="flex gap-3"><span class="mt-1.5 inline-block w-1 h-1 rounded-full bg-gold flex-shrink-0"></span><span data-en="Review grades, remarks, and printable report cards." data-am="ውጤቶችን፣ አስተያየቶችን እና ሪፖርት ካርዶችን ይመልከቱ።">Review grades, remarks, and printable report cards.</span></li>
              <li class="flex gap-3"><span class="mt-1.5 inline-block w-1 h-1 rounded-full bg-gold flex-shrink-0"></span><span data-en="See upcoming services, exams, and special events." data-am="የሚመጡ አገልግሎቶችን፣ ፈተናዎችን እና ልዩ ዝግጅቶችን ይመልከቱ።">See upcoming services, exams, and special events.</span></li>
              <li class="flex gap-3"><span class="mt-1.5 inline-block w-1 h-1 rounded-full bg-gold flex-shrink-0"></span><span data-en="Track tuition status and read announcements." data-am="የክፍያ ሁኔታን ይከታተሉ እና ማስታወቂያዎችን ያንብቡ።">Track tuition status and read announcements.</span></li>
            </ul>
          </div>
        </div>
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

      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php
          $events = [
            ['en_mo'=>'MAY','am_mo'=>'ግንቦት','day'=>'12','en_t'=>'Term 1 begins','am_t'=>'መጀመሪያ ኮርስ ይጀምራል','en_w'=>'Sunday · 9:00 AM','am_w'=>'እሁድ · 9:00 ጥዋት','en_x'=>'All tracks','am_x'=>'ሁሉም ኮርሶች'],
            ['en_mo'=>'MAY','am_mo'=>'ግንቦት','day'=>'19','en_t'=>'Parent orientation','am_t'=>'የወላጆች መግለጫ','en_w'=>'Sunday · 11:30 AM','am_w'=>'እሁድ · 11:30 ጥዋት','en_x'=>"Children's Track",'am_x'=>'የልጆች ኮርስ'],
            ['en_mo'=>'JUN','am_mo'=>'ሰኔ','day'=>'02','en_t'=>'Liturgical retreat','am_t'=>'መንፈሳዊ ጉባኤ','en_w'=>'Saturday · 9:00 AM','am_w'=>'ቅዳሜ · 9:00 ጥዋት','en_x'=>'Youth & Adult','am_x'=>'ወጣቶች እና አዋቂዎች'],
            ['en_mo'=>'JUN','am_mo'=>'ሰኔ','day'=>'23','en_t'=>'Mid-term assessments','am_t'=>'መካከለኛ ፈተናዎች','en_w'=>'Sunday · 9:00 AM','am_w'=>'እሁድ · 9:00 ጥዋት','en_x'=>'All tracks','am_x'=>'ሁሉም ኮርሶች'],
          ];
          foreach ($events as $e): ?>
            <article class="bg-surface rounded-lg border border-outline-soft/40 p-6 hover:shadow-md transition-shadow flex gap-4">
              <div class="flex-shrink-0 w-14 text-center">
                <div class="text-[10px] font-semibold uppercase tracking-widestest text-gold" data-en="<?= htmlspecialchars($e['en_mo']) ?>" data-am="<?= htmlspecialchars($e['am_mo']) ?>"><?= htmlspecialchars($e['en_mo']) ?></div>
                <div class="font-display text-3xl text-primary leading-none mt-1"><?= htmlspecialchars($e['day']) ?></div>
              </div>
              <div class="border-l border-outline-soft/40 pl-4">
                <h3 class="font-display text-base text-ink leading-tight mb-1" data-en="<?= htmlspecialchars($e['en_t']) ?>" data-am="<?= htmlspecialchars($e['am_t']) ?>"><?= htmlspecialchars($e['en_t']) ?></h3>
                <p class="text-xs text-ink-soft mb-2" data-en="<?= htmlspecialchars($e['en_w']) ?>" data-am="<?= htmlspecialchars($e['am_w']) ?>"><?= htmlspecialchars($e['en_w']) ?></p>
                <span class="text-[10px] uppercase tracking-widestest text-outline" data-en="<?= htmlspecialchars($e['en_x']) ?>" data-am="<?= htmlspecialchars($e['am_x']) ?>"><?= htmlspecialchars($e['en_x']) ?></span>
              </div>
            </article>
          <?php endforeach; ?>
      </div>
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
          <a href="#" class="inline-flex justify-center items-center gap-2 border border-outline text-primary px-7 py-3.5 rounded text-xs font-semibold uppercase tracking-widestest hover:bg-surface transition-colors" data-en="Request enrollment" data-am="ምዝገባ ይጠይቁ">Request enrollment</a>
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
            <span class="font-display text-lg font-semibold text-primary" data-en="Gebriel Senbet" data-am="ገብርኤል ሰንበት">Gebriel Senbet</span>
          </div>
          <p class="text-sm text-ink-soft leading-relaxed mb-4" data-en="Saint Gabriel Sabbath School. A modern home for our community of faith and learning." data-am="ቅዱስ ገብርኤል ሰንበት ት/ቤት። ለእምነት እና ለትምህርት ማኅበረሰባችን ዘመናዊ ቤት።">Saint Gabriel Sabbath School. A modern home for our community of faith and learning.</p>
          <p class="ethiopic text-sm text-outline">ገብርኤል ሰንበት ት/ቤት</p>
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
            <li data-en="Saint Gabriel Church" data-am="የቅዱስ ገብርኤል ቤተ ክርስቲያን">Saint Gabriel Church</li>
            <li data-en="Addis Ababa, Ethiopia" data-am="አዲስ አበባ፣ ኢትዮጵያ">Addis Ababa, Ethiopia</li>
            <li><a href="mailto:hello@gebriel.eagleeyebgp.com" class="hover:text-primary transition-colors">hello@gebriel.eagleeyebgp.com</a></li>
          </ul>
        </div>
      </div>

      <div class="rule-gold mb-6"></div>

      <div class="flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-outline">
        <p class="uppercase tracking-widestest" data-en="© <?= $year ?> Gebriel Senbet · Made with reverence in Addis Ababa" data-am="© <?= $year ?> ገብርኤል ሰንበት · በአዲስ አበባ በክብር የተሠራ">© <?= $year ?> Gebriel Senbet · Made with reverence in Addis Ababa</p>
        <div data-lang-toggle class="flex items-center bg-surface rounded-full p-0.5 border border-outline-soft/50">
          <button data-lang="en" class="seg-active px-3 py-1 text-xs font-semibold rounded-full">EN</button>
          <button data-lang="am" class="px-3 py-1 text-xs font-semibold rounded-full text-ink-soft hover:text-primary ethiopic">አማ</button>
        </div>
      </div>
    </div>
  </footer>

  <script>
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

        try { localStorage.setItem('gs_lang', lang); } catch (e) {}
      }

      document.querySelectorAll('[data-lang-toggle] button').forEach(function (btn) {
        btn.addEventListener('click', function () { applyLang(btn.dataset.lang); });
      });

      var saved = 'en';
      try { saved = localStorage.getItem('gs_lang') || 'en'; } catch (e) {}
      applyLang(saved);
    })();
  </script>
</body>
</html>
