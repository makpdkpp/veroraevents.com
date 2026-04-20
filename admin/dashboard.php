<?php
require __DIR__ . '/auth.php';
requireLogin();

$postsDir = dirname(__DIR__) . '/_posts';
$files    = glob($postsDir . '/*.md') ?: [];

$posts = [];
foreach ($files as $file) {
    require_once __DIR__ . '/md.php';
    $parsed = parseFrontmatter(file_get_contents($file));
    $data   = $parsed['data'];
    $posts[] = [
        'file'  => basename($file),
        'slug'  => $data['slug']  ?? pathinfo($file, PATHINFO_FILENAME),
        'title' => $data['title'] ?? '(ไม่มีชื่อ)',
        'date'  => $data['date']  ?? '',
    ];
}
usort($posts, fn($a, $b) => strcmp($b['date'], $a['date']));

$flash = $_GET['flash'] ?? '';

adminHead('Dashboard');
?>
<div class="wrap">
  <div class="card">
    <?php adminNav() ?>

    <div class="topbar">
      <h1>บทความทั้งหมด (<?= count($posts) ?>)</h1>
      <a href="/admin/edit.php" class="btn btn-primary">+ เขียนบทความใหม่</a>
    </div>

    <?php if ($flash === 'saved'):  ?><p class="flash-ok">บันทึกและเผยแพร่เรียบร้อยแล้ว</p><?php endif ?>
    <?php if ($flash === 'deleted'):?><p class="flash-ok">ลบบทความเรียบร้อยแล้ว</p><?php endif ?>

    <?php if (empty($posts)): ?>
      <p style="color:var(--muted);text-align:center;padding:3rem 0">ยังไม่มีบทความ — กด <strong>เขียนบทความใหม่</strong> เพื่อเริ่มต้น</p>
    <?php else: ?>
      <div class="article-list">
        <?php foreach ($posts as $p): ?>
          <div class="article-row">
            <div class="article-meta">
              <strong><?= htmlspecialchars($p['title']) ?></strong>
              <span><?= htmlspecialchars($p['date']) ?> &nbsp;·&nbsp; /blog/<?= htmlspecialchars($p['slug']) ?>/</span>
            </div>
            <div class="article-actions">
              <a href="/blog/<?= urlencode($p['slug']) ?>/" target="_blank" class="btn btn-ghost btn-sm">ดู</a>
              <a href="/admin/edit.php?file=<?= urlencode($p['file']) ?>" class="btn btn-ghost btn-sm">แก้ไข</a>
              <a href="/admin/delete.php?file=<?= urlencode($p['file']) ?>"
                 class="btn btn-danger btn-sm"
                 onclick="return confirm('ลบบทความ <?= htmlspecialchars(addslashes($p['title'])) ?> ?')">ลบ</a>
            </div>
          </div>
        <?php endforeach ?>
      </div>
    <?php endif ?>
  </div>
</div>
</body>
</html>
