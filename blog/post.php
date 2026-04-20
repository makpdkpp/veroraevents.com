<?php
$postsDir = dirname(__DIR__) . '/_posts';
require dirname(__DIR__) . '/admin/md.php';

$slug = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['slug'] ?? ''));
if (!$slug) { http_response_code(404); exit('Not found'); }

// Find matching file
$file = null;
foreach (glob($postsDir . '/*.md') ?: [] as $f) {
    $parsed = parseFrontmatter(file_get_contents($f));
    $fslug  = $parsed['data']['slug'] ?? pathinfo($f, PATHINFO_FILENAME);
    if ($fslug === $slug) { $file = $f; $post = $parsed; break; }
}

if (!$file) { http_response_code(404); exit('Not found'); }

$d       = $post['data'];
$title   = $d['title']       ?? '';
$desc    = $d['description'] ?? '';
$image   = $d['image']       ?? '';
$dateRaw = $d['date']        ?? '';
$dateTH  = $dateRaw ? (new DateTime($dateRaw))->format('j F Y') : '';
$dateISO = $dateRaw ? (new DateTime($dateRaw))->format('c') : '';
$content = markdownToHtml($post['body']);
$ogImage = $image ?: 'https://veroraevents.com/assets/social-preview.svg';
$canon   = 'https://veroraevents.com/blog/' . $slug . '/';
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?> — Verora Balloon Shop</title>
  <meta name="description" content="<?= htmlspecialchars($desc) ?>">
  <meta name="robots" content="index,follow">
  <link rel="canonical" href="<?= $canon ?>">
  <meta property="og:type" content="article">
  <meta property="og:title" content="<?= htmlspecialchars($title) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($desc) ?>">
  <meta property="og:url" content="<?= $canon ?>">
  <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= htmlspecialchars($title) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($desc) ?>">
  <meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>">
  <script type="application/ld+json">
  {"@context":"https://schema.org","@type":"BlogPosting","headline":<?= json_encode($title) ?>,"description":<?= json_encode($desc) ?>,"image":<?= json_encode($ogImage) ?>,"datePublished":<?= json_encode($dateISO) ?>,"url":<?= json_encode($canon) ?>,"author":{"@type":"Organization","name":"Verora Balloon Shop"}}
  </script>
  <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Kanit:wght@300;400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/styles.css">
  <style>
    .article-wrap{width:min(var(--max-width),calc(100% - 2rem));margin:0 auto;padding:2rem 0 5rem}
    .article-back{display:inline-flex;align-items:center;gap:.5rem;color:var(--muted);font-size:.9rem;margin-bottom:2rem;transition:color 200ms}
    .article-back:hover{color:var(--text)}
    .article-header{margin-bottom:2rem}
    .article-header h1{margin:.75rem 0 1rem;max-width:22ch}
    .article-hero{border-radius:var(--radius-xl);overflow:hidden;margin-bottom:2.5rem;box-shadow:var(--shadow)}
    .article-hero img{width:100%;max-height:520px;object-fit:cover;display:block}
    .article-body{padding:2.5rem 3rem}
    .article-body h2{font-size:clamp(1.7rem,3vw,2.4rem);margin:2rem 0 .8rem}
    .article-body h3{font-size:1.5rem;margin:1.5rem 0 .6rem}
    .article-body p{margin:0 0 1.2rem;line-height:1.9;color:var(--muted);font-size:1.04rem}
    .article-body ul,.article-body ol{padding-left:1.5rem;margin:0 0 1.2rem}
    .article-body li{line-height:1.85;color:var(--muted);margin-bottom:.3rem}
    .article-body img{width:100%;border-radius:var(--radius-lg);margin:1.5rem 0;box-shadow:var(--shadow)}
    .article-body blockquote{margin:1.5rem 0;padding:1.1rem 1.4rem;border-left:3px solid var(--pink-strong);background:rgba(247,215,222,.12);border-radius:0 var(--radius-md) var(--radius-md) 0;color:var(--muted)}
    .article-body strong{color:var(--text)}
    .article-body a{color:var(--gold);text-decoration:underline;text-underline-offset:3px}
    .article-body pre{background:rgba(55,39,45,.06);border-radius:var(--radius-md);padding:1.2rem;overflow-x:auto;margin-bottom:1.2rem}
    .article-body code{font-size:.9rem;font-family:monospace}
    .article-cta{margin-top:3rem;padding:2rem;border-radius:var(--radius-xl);text-align:center;background:linear-gradient(135deg,rgba(247,215,222,.28),rgba(237,218,188,.22));border:1px solid rgba(255,255,255,.82)}
    .article-cta h3{font-size:2rem;margin-bottom:.6rem}
    .article-cta p{color:var(--muted);margin:0 0 1.4rem;font-size:1rem}
    .cta-btns{display:flex;gap:.8rem;justify-content:center;flex-wrap:wrap}
    @media(max-width:820px){.article-body{padding:1.4rem}.article-header h1{max-width:100%}}
  </style>
</head>
<body>
<div class="page-shell">
  <header class="site-header">
    <nav class="nav-bar">
      <a class="brand" href="/">
        <img class="brand-logo" src="/assets/verora-balloon-mark.svg" alt="Verora">
        <span class="brand-lockup"><strong>Verora</strong><span>Balloon Shop</span></span>
      </a>
      <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nav-menu" aria-label="เมนู">
        <span></span><span></span>
      </button>
      <div class="nav-menu" id="nav-menu">
        <a href="/#gallery">ผลงาน</a>
        <a href="/#directory">หมวดบริการ</a>
        <a href="/blog/">บทความ</a>
        <a href="/#contact">ติดต่อ</a>
        <a class="button button-soft" href="https://line.me/R/ti/p/@3855tjmjc" target="_blank" rel="noopener">LINE OA</a>
      </div>
    </nav>
  </header>

  <main>
    <div class="article-wrap">
      <a class="article-back" href="/blog/">← บทความทั้งหมด</a>

      <header class="article-header reveal">
        <p class="eyebrow"><time datetime="<?= $dateISO ?>"><?= htmlspecialchars($dateTH) ?></time></p>
        <h1><?= htmlspecialchars($title) ?></h1>
        <p class="lede"><?= htmlspecialchars($desc) ?></p>
      </header>

      <?php if ($image): ?>
        <div class="article-hero reveal">
          <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($title) ?>">
        </div>
      <?php endif ?>

      <article class="article-body glass-panel reveal">
        <?= $content ?>
      </article>

      <div class="article-cta reveal">
        <h3>สนใจตกแต่งงานด้วยลูกโป่ง?</h3>
        <p>ส่งรายละเอียดงานมาให้เราช่วยแนะนำแพ็กเกจที่เหมาะกับงานของคุณ</p>
        <div class="cta-btns">
          <a class="button button-primary" href="/#contact">ติดต่อจองงาน</a>
          <a class="button button-ghost" href="https://line.me/R/ti/p/@3855tjmjc" target="_blank" rel="noopener">LINE OA</a>
        </div>
      </div>
    </div>
  </main>
</div>

<a class="line-float" href="https://line.me/R/ti/p/@3855tjmjc" target="_blank" rel="noopener" aria-label="LINE OA">
  <span class="line-float-label">LINE OA</span><strong>@3855tjmjc</strong>
</a>
<script src="/script.js"></script>
</body>
</html>
