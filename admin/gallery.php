<?php
require __DIR__ . '/auth.php';
requireLogin();

$dataFile = dirname(__DIR__) . '/_data/gallery.json';
$items = [];
if (file_exists($dataFile)) {
    $items = json_decode(file_get_contents($dataFile), true) ?: [];
}

usort($items, function ($a, $b) {
    $ao = (int)($a['order'] ?? 0);
    $bo = (int)($b['order'] ?? 0);
    if ($ao === $bo) {
        return strcmp((string)($a['id'] ?? ''), (string)($b['id'] ?? ''));
    }
    return $ao <=> $bo;
});

$flash = $_GET['flash'] ?? '';

adminHead('ผลงาน');
?>
<div class="wrap">
  <div class="card">
    <?php adminNav('gallery') ?>

    <div class="topbar">
      <h1>ผลงานทั้งหมด (<?= count($items) ?>)</h1>
      <a href="/admin/gallery_edit.php" class="btn btn-primary">+ เพิ่มผลงาน</a>
    </div>

    <?php if ($flash === 'saved'):       ?><p class="flash-ok">บันทึกเรียบร้อยแล้ว</p><?php endif ?>
    <?php if ($flash === 'deleted'):     ?><p class="flash-ok">ลบเรียบร้อยแล้ว</p><?php endif ?>
    <?php if ($flash === 'posted_fb'):   ?><p class="flash-ok">โพสต์ไป Facebook เรียบร้อยแล้ว</p><?php endif ?>
    <?php if ($flash === 'posted_ig'):   ?><p class="flash-ok">โพสต์ไป Instagram เรียบร้อยแล้ว</p><?php endif ?>
    <?php if ($flash === 'posted_both'): ?><p class="flash-ok">โพสต์ไป Facebook และ Instagram เรียบร้อยแล้ว</p><?php endif ?>
    <?php if ($flash === 'post_err'):    ?><p class="flash-err">โพสต์ไม่สำเร็จ — ตรวจสอบ Token/Page ID ที่หน้าตั้งค่า</p><?php endif ?>
    <?php if ($flash === 'noconfig'):    ?><p class="flash-err">ยังไม่ได้ตั้งค่า FB/IG — ไปที่หน้าตั้งค่าก่อน</p><?php endif ?>
    <?php if ($flash === 'err'):         ?><p class="flash-err">ไม่พบผลงานหรือข้อมูลไม่ครบ</p><?php endif ?>

    <?php if (empty($items)): ?>
      <p style="color:var(--muted);text-align:center;padding:3rem 0">ยังไม่มีผลงาน — กด <strong>เพิ่มผลงาน</strong> เพื่อเริ่มต้น</p>
    <?php else: ?>
      <div class="article-list">
        <?php foreach ($items as $it):
          $id = (string)($it['id'] ?? '');
          $title = (string)($it['title'] ?? '');
          $tag = (string)($it['tag'] ?? '');
          $order = (int)($it['order'] ?? 0);
          $image = (string)($it['image'] ?? '');
        ?>
          <div class="article-row">
            <div class="article-meta">
              <strong><?= htmlspecialchars($title ?: '(ไม่มีชื่อ)') ?></strong>
              <span>#<?= htmlspecialchars($tag) ?> &nbsp;·&nbsp; order <?= htmlspecialchars((string)$order) ?> &nbsp;·&nbsp; <?= htmlspecialchars($id) ?></span>
            </div>
            <div class="article-actions">
              <?php if ($image): ?>
                <a href="<?= htmlspecialchars($image) ?>" target="_blank" class="btn btn-ghost btn-sm">รูป</a>
              <?php endif ?>
              <a href="/admin/gallery_edit.php?id=<?= urlencode($id) ?>" class="btn btn-ghost btn-sm">แก้ไข</a>
              <?php if ($image): ?>
                <a href="/admin/gallery_post.php?id=<?= urlencode($id) ?>&platform=fb"
                   class="btn btn-ghost btn-sm"
                   onclick="return confirm('โพสต์ \'<?= htmlspecialchars(addslashes($title)) ?>\' ไป Facebook ?')">FB</a>
                <a href="/admin/gallery_post.php?id=<?= urlencode($id) ?>&platform=ig"
                   class="btn btn-ghost btn-sm"
                   onclick="return confirm('โพสต์ \'<?= htmlspecialchars(addslashes($title)) ?>\' ไป Instagram ?')">IG</a>
                <a href="/admin/gallery_post.php?id=<?= urlencode($id) ?>&platform=both"
                   class="btn btn-primary btn-sm"
                   onclick="return confirm('โพสต์ \'<?= htmlspecialchars(addslashes($title)) ?>\' ไปทั้ง FB และ IG ?')">FB+IG</a>
              <?php endif ?>
              <a href="/admin/gallery_delete.php?id=<?= urlencode($id) ?>"
                 class="btn btn-danger btn-sm"
                 onclick="return confirm('ลบผลงาน <?= htmlspecialchars(addslashes($title)) ?> ?')">ลบ</a>
            </div>
          </div>
        <?php endforeach ?>
      </div>
    <?php endif ?>
  </div>
</div>
</body>
</html>
