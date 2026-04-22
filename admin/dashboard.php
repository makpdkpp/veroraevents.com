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
    <?php adminNav('posts') ?>

    <div class="topbar">
      <h1>บทความทั้งหมด (<?= count($posts) ?>)</h1>
      <a href="/admin/edit.php" class="btn btn-primary">+ เขียนบทความใหม่</a>
    </div>

    <?php if ($flash === 'saved'):       ?><p class="flash-ok">บันทึกและเผยแพร่เรียบร้อยแล้ว</p><?php endif ?>
    <?php if ($flash === 'deleted'):     ?><p class="flash-ok">ลบบทความเรียบร้อยแล้ว</p><?php endif ?>
    <?php if ($flash === 'posted_fb'):   ?><p class="flash-ok">โพสต์ไป Facebook เรียบร้อยแล้ว</p><?php endif ?>
    <?php if ($flash === 'posted_ig'):   ?><p class="flash-ok">โพสต์ไป Instagram เรียบร้อยแล้ว</p><?php endif ?>
    <?php if ($flash === 'posted_both'): ?><p class="flash-ok">โพสต์ไป Facebook และ Instagram เรียบร้อยแล้ว</p><?php endif ?>
    <?php if ($flash === 'post_err'):    ?><p class="flash-err">โพสต์ไม่สำเร็จ — ดู <code>admin/social-errors.log</code></p><?php endif ?>
    <?php if ($flash === 'noconfig'):    ?><p class="flash-err">ยังไม่ได้ตั้งค่า FB/IG — ไปที่หน้าตั้งค่าก่อน</p><?php endif ?>
    <?php if ($flash === 'err'):         ?><p class="flash-err">ไม่พบบทความ</p><?php endif ?>

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

              <form method="POST" action="/admin/post_social.php" style="display:inline;margin:0"
                    onsubmit="return confirm('โพสต์ \'<?= htmlspecialchars(addslashes($p['title'])) ?>\' ไป Facebook ?')">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
                <input type="hidden" name="file" value="<?= htmlspecialchars($p['file']) ?>">
                <input type="hidden" name="platform" value="fb">
                <button type="submit" class="btn btn-ghost btn-sm">FB</button>
              </form>

              <form method="POST" action="/admin/post_social.php" style="display:inline;margin:0"
                    onsubmit="return confirm('โพสต์ \'<?= htmlspecialchars(addslashes($p['title'])) ?>\' ไป Instagram ?')">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
                <input type="hidden" name="file" value="<?= htmlspecialchars($p['file']) ?>">
                <input type="hidden" name="platform" value="ig">
                <button type="submit" class="btn btn-ghost btn-sm">IG</button>
              </form>

              <form method="POST" action="/admin/post_social.php" style="display:inline;margin:0"
                    onsubmit="return confirm('โพสต์ \'<?= htmlspecialchars(addslashes($p['title'])) ?>\' ไปทั้ง FB และ IG ?')">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
                <input type="hidden" name="file" value="<?= htmlspecialchars($p['file']) ?>">
                <input type="hidden" name="platform" value="both">
                <button type="submit" class="btn btn-primary btn-sm">FB+IG</button>
              </form>

              <form method="POST" action="/admin/delete.php" style="display:inline;margin:0"
                    onsubmit="return confirm('ลบบทความ <?= htmlspecialchars(addslashes($p['title'])) ?> ?')">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
                <input type="hidden" name="file" value="<?= htmlspecialchars($p['file']) ?>">
                <button type="submit" class="btn btn-danger btn-sm">ลบ</button>
              </form>
            </div>
          </div>
        <?php endforeach ?>
      </div>
    <?php endif ?>
  </div>
</div>
</body>
</html>
