<?php
$postsDir = dirname(__DIR__) . '/_posts';
require dirname(__DIR__) . '/admin/md.php';

$files = glob($postsDir . '/*.md') ?: [];
$posts = [];
foreach ($files as $file) {
    $parsed = parseFrontmatter(file_get_contents($file));
    $d = $parsed['data'];
    $posts[] = [
        'slug'        => $d['slug'] ?? pathinfo($file, PATHINFO_FILENAME),
        'title'       => $d['title'] ?? '',
        'description' => $d['description'] ?? '',
        'image'       => $d['image'] ?? '',
        'date'        => $d['date'] ?? '',
    ];
}
usort($posts, fn($a, $b) => strcmp($b['date'], $a['date']));
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>บทความ — Verora Balloon Shop</title>
  <meta name="description" content="รวมบทความ ไอเดีย และเคล็ดลับตกแต่งลูกโป่งสำหรับทุกประเภทงาน">
  <meta name="robots" content="index,follow">
  <link rel="canonical" href="https://veroraevents.com/blog/">
  <meta property="og:title" content="บทความ — Verora Balloon Shop">
  <meta property="og:url" content="https://veroraevents.com/blog/">
  <meta property="og:image" content="https://veroraevents.com/assets/social-preview.svg">
  <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Kanit:wght@300;400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/styles.css">
  <style>
    .blog-wrap{width:min(var(--max-width),calc(100% - 2rem));margin:0 auto;padding:2rem 0 5rem}
    .blog-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,340px),1fr));gap:1.4rem;margin-top:2.5rem}
    .blog-card{border-radius:var(--radius-xl);overflow:hidden;display:flex;flex-direction:column;transition:transform 240ms var(--ease),box-shadow 240ms var(--ease)}
    .blog-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-lg)}
    .blog-card-img{width:100%;height:210px;object-fit:cover;display:block}
    .blog-card-img--empty{height:210px;background:linear-gradient(135deg,rgba(247,215,222,.5),rgba(237,218,188,.4))}
    .blog-card-body{padding:1.5rem;display:flex;flex-direction:column;gap:.55rem;flex:1}
    .blog-card-body h2{font-size:1.7rem;line-height:1.3}
    .blog-card-body h2 a{color:inherit}
    .blog-card-body h2 a:hover{color:var(--gold)}
    .blog-card-body p{color:var(--muted);line-height:1.75;font-size:.95rem;flex:1}
    .blog-card-body .button{align-self:flex-start;margin-top:.4rem;font-size:.85rem;padding:.65rem 1.15rem}
    .no-posts{text-align:center;padding:4rem 0;color:var(--muted)}
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
        <a href="/blog/" aria-current="page">บทความ</a>
        <a href="/#contact">ติดต่อ</a>
        <a class="button button-soft" href="https://line.me/R/ti/p/@3855tjmjc" target="_blank" rel="noopener">LINE OA</a>
      </div>
    </nav>
  </header>

  <main>
    <div class="blog-wrap">
      <div class="section-heading reveal">
        <div>
          <p class="eyebrow">บทความและไอเดีย</p>
          <h1>ไอเดียและเคล็ดลับตกแต่งลูกโป่ง</h1>
          <p>เรื่องราว ไอเดีย และแรงบันดาลใจสำหรับงานที่คุณกำลังวางแผน</p>
        </div>
      </div>

      <?php if (empty($posts)): ?>
        <p class="no-posts">ยังไม่มีบทความ</p>
      <?php else: ?>
        <div class="blog-grid">
          <?php foreach ($posts as $p):
            $date = $p['date'] ? (new DateTime($p['date']))->format('j F Y') : '';
          ?>
          <article class="blog-card glass-panel reveal">
            <?php if ($p['image']): ?>
              <img class="blog-card-img" src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['title']) ?>" loading="lazy">
            <?php else: ?>
              <div class="blog-card-img--empty"></div>
            <?php endif ?>
            <div class="blog-card-body">
              <time class="eyebrow"><?= htmlspecialchars($date) ?></time>
              <h2><a href="/blog/<?= urlencode($p['slug']) ?>/"><?= htmlspecialchars($p['title']) ?></a></h2>
              <p><?= htmlspecialchars($p['description']) ?></p>
              <a class="button button-ghost" href="/blog/<?= urlencode($p['slug']) ?>/">อ่านต่อ →</a>
            </div>
          </article>
          <?php endforeach ?>
        </div>
      <?php endif ?>
    </div>
  </main>
</div>

<a class="line-float" href="https://line.me/R/ti/p/@3855tjmjc" target="_blank" rel="noopener" aria-label="LINE OA">
  <span class="line-float-label">LINE OA</span><strong>@3855tjmjc</strong>
</a>
<script src="/script.js"></script>
</body>
</html>
