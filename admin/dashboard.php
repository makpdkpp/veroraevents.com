<?php
require __DIR__ . '/auth.php';
requireLogin();

$postsDir = dirname(__DIR__) . '/_posts';
$files    = glob($postsDir . '/*.md') ?: [];
$galleryFile = dirname(__DIR__) . '/_data/gallery.json';
$contentFile = dirname(__DIR__) . '/_data/content.json';

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

$galleryItems = [];
if (file_exists($galleryFile)) {
    $galleryItems = json_decode(file_get_contents($galleryFile), true) ?: [];
}

$content = [];
if (file_exists($contentFile)) {
    $content = json_decode(file_get_contents($contentFile), true) ?: [];
}

$flash = $_GET['flash'] ?? '';
$latestPost = $posts[0] ?? null;
$latestGallery = $galleryItems[0] ?? null;
$contentFields = count(array_filter($content, static fn($value) => trim((string)$value) !== ''));

adminHead('Dashboard');
?>
<?php adminShellStart('dashboard', 'Dashboard') ?>
<section class="admin-page stack-lg">
  <?php adminPageHeader(
      'Overview',
      'Dashboard',
      'จัดการบทความ แกลเลอรี เนื้อหาเว็บ และเช็กความพร้อมของระบบจากจุดเดียว',
      '<a href="/admin/edit.php" class="btn btn-primary">+ เขียนบทความใหม่</a><a href="/admin/gallery_edit.php" class="btn btn-ghost">+ เพิ่มผลงาน</a>'
  ) ?>

  <div class="overview-grid">
    <div class="overview-card">
      <span>Articles</span>
      <strong><?= count($posts) ?></strong>
      <div class="muted">บทความทั้งหมดในระบบ</div>
    </div>
    <div class="overview-card">
      <span>Gallery</span>
      <strong><?= count($galleryItems) ?></strong>
      <div class="muted">ผลงานสำหรับหน้าโชว์เคส</div>
    </div>
    <div class="overview-card">
      <span>Website Content</span>
      <strong><?= $contentFields ?></strong>
      <div class="muted">ฟิลด์ที่มีข้อมูลแล้ว</div>
    </div>
  </div>

    <?php if ($flash === 'saved'):       ?><p class="flash-ok">บันทึกและเผยแพร่เรียบร้อยแล้ว</p><?php endif ?>
    <?php if ($flash === 'deleted'):     ?><p class="flash-ok">ลบบทความเรียบร้อยแล้ว</p><?php endif ?>
    <?php if ($flash === 'posted_fb'):   ?><p class="flash-ok">โพสต์ไป Facebook เรียบร้อยแล้ว</p><?php endif ?>
    <?php if ($flash === 'posted_ig'):   ?><p class="flash-ok">โพสต์ไป Instagram เรียบร้อยแล้ว</p><?php endif ?>
    <?php if ($flash === 'posted_both'): ?><p class="flash-ok">โพสต์ไป Facebook และ Instagram เรียบร้อยแล้ว</p><?php endif ?>
    <?php if ($flash === 'post_err'):    ?><p class="flash-err">โพสต์ไม่สำเร็จ — ดู <code>admin/social-errors.log</code></p><?php endif ?>
    <?php if ($flash === 'noconfig'):    ?><p class="flash-err">ยังไม่ได้ตั้งค่า FB/IG — ไปที่หน้าตั้งค่าก่อน</p><?php endif ?>
    <?php if ($flash === 'err'):         ?><p class="flash-err">ไม่พบบทความ</p><?php endif ?>

  <div class="section-grid">
    <section class="section-card stack-md">
      <div class="section-heading">
        <div>
          <h2>บทความล่าสุด</h2>
          <p>โฟกัสที่งานเขียนและการเผยแพร่ล่าสุด</p>
        </div>
        <a href="/admin/edit.php" class="btn btn-ghost btn-sm">บทความใหม่</a>
      </div>

      <?php if (empty($posts)): ?>
        <div class="empty-state">ยังไม่มีบทความ กด <strong>เขียนบทความใหม่</strong> เพื่อเริ่มต้น workflow ของ CMS</div>
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

              <form method="POST" action="/admin/post_social.php" class="inline-form"
                    onsubmit="return confirm('โพสต์ \'<?= htmlspecialchars(addslashes($p['title'])) ?>\' ไป Facebook ?')">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
                <input type="hidden" name="file" value="<?= htmlspecialchars($p['file']) ?>">
                <input type="hidden" name="platform" value="fb">
                <button type="submit" class="btn btn-ghost btn-sm">FB</button>
              </form>

              <form method="POST" action="/admin/post_social.php" class="inline-form"
                    onsubmit="return confirm('โพสต์ \'<?= htmlspecialchars(addslashes($p['title'])) ?>\' ไป Instagram ?')">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
                <input type="hidden" name="file" value="<?= htmlspecialchars($p['file']) ?>">
                <input type="hidden" name="platform" value="ig">
                <button type="submit" class="btn btn-ghost btn-sm">IG</button>
              </form>

              <form method="POST" action="/admin/post_social.php" class="inline-form"
                    onsubmit="return confirm('โพสต์ \'<?= htmlspecialchars(addslashes($p['title'])) ?>\' ไปทั้ง FB และ IG ?')">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
                <input type="hidden" name="file" value="<?= htmlspecialchars($p['file']) ?>">
                <input type="hidden" name="platform" value="both">
                <button type="submit" class="btn btn-primary btn-sm">FB+IG</button>
              </form>

              <form method="POST" action="/admin/delete.php" class="inline-form"
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
    </section>

    <aside class="stack-md">
      <section class="section-card stack-sm">
        <div class="section-heading">
          <div>
            <h2>Quick Actions</h2>
            <p>งานที่ใช้บ่อยในแต่ละวัน</p>
          </div>
        </div>
        <a href="/admin/content.php" class="btn btn-ghost btn-block">แก้ไขหน้าเว็บไซต์</a>
        <a href="/admin/settings.php" class="btn btn-ghost btn-block">ตรวจ Social Settings</a>
        <a href="/admin/gallery.php" class="btn btn-ghost btn-block">จัดการแกลเลอรี</a>
      </section>

      <section class="note-card stack-sm">
        <div class="section-heading">
          <div>
            <h2>System Snapshot</h2>
            <p>ภาพรวมของงานล่าสุด</p>
          </div>
        </div>
        <div class="meta-list">
          <div><strong>บทความล่าสุด:</strong> <?= htmlspecialchars($latestPost['title'] ?? 'ยังไม่มีข้อมูล') ?></div>
          <div><strong>วันที่:</strong> <?= htmlspecialchars($latestPost['date'] ?? '—') ?></div>
          <div><strong>ผลงานล่าสุด:</strong> <?= htmlspecialchars((string)($latestGallery['title'] ?? 'ยังไม่มีข้อมูล')) ?></div>
        </div>
      </section>
    </aside>
  </div>
</section>
<?php adminShellEnd() ?>
