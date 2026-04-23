<?php
require __DIR__ . '/auth.php';
requireLogin();

$dataFile = dirname(__DIR__) . '/_data/gallery.json';
$items = [];
if (file_exists($dataFile)) {
    $items = json_decode(file_get_contents($dataFile), true) ?: [];
}

$id = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['id'] ?? ''));
$isNew = true;
$item = [
    'id' => '',
    'tag' => '',
    'title' => '',
    'image' => '',
    'alt' => '',
    'tall' => false,
    'order' => 0,
];

if ($id) {
    foreach ($items as $it) {
        if (!empty($it['id']) && $it['id'] === $id) {
            $item = array_merge($item, $it);
            $isNew = false;
            break;
        }
    }
}

adminHead($isNew ? 'เพิ่มผลงาน' : 'แก้ไขผลงาน');
?>
<?php adminShellStart('gallery', $isNew ? 'เพิ่มผลงาน' : 'แก้ไขผลงาน') ?>
<section class="admin-page stack-lg">
  <?php adminPageHeader(
      'Portfolio',
      $isNew ? 'เพิ่มผลงาน' : 'แก้ไขผลงาน',
      'สร้างหรืออัปเดตชิ้นงานใน gallery library พร้อม metadata สำหรับแสดงผลและนำไปโพสต์ต่อ',
      '<a href="/admin/gallery.php" class="btn btn-ghost">กลับรายการผลงาน</a>'
  ) ?>

    <form method="POST" action="/admin/gallery_save.php" class="stack-lg">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
      <input type="hidden" name="_original_id" value="<?= htmlspecialchars((string)($item['id'] ?? '')) ?>">
      <input type="hidden" name="_is_new" value="<?= $isNew ? '1' : '0' ?>">

      <div class="field-row">
        <label>
          <span>ชื่อผลงาน (Headline)</span>
          <input type="text" name="title" value="<?= htmlspecialchars((string)($item['title'] ?? '')) ?>" required oninput="autoId(this.value)">
        </label>
        <label>
          <span>ID <small style="font-weight:400;text-transform:none">— ตัวเล็ก a-z, 0-9, -</small></span>
          <input type="text" name="id" id="gid" value="<?= htmlspecialchars((string)($item['id'] ?? '')) ?>" required pattern="[a-z0-9\-]+" title="ตัวพิมพ์เล็กและ - เท่านั้น">
        </label>
      </div>

      <div class="field-row">
        <label>
          <span>หมวด/แท็ก</span>
          <input type="text" name="tag" value="<?= htmlspecialchars((string)($item['tag'] ?? '')) ?>" placeholder="เช่น งานวันเกิด" required>
        </label>
        <label>
          <span>ลำดับแสดงผล (order)</span>
          <input type="text" name="order" value="<?= htmlspecialchars((string)($item['order'] ?? 0)) ?>" placeholder="1">
        </label>
      </div>

      <label>
        <span>URL รูปภาพ</span>
        <input type="text" name="image" value="<?= htmlspecialchars((string)($item['image'] ?? '')) ?>" placeholder="https://..." required>
      </label>

      <label>
        <span>Alt text (อธิบายรูป)</span>
        <input type="text" name="alt" value="<?= htmlspecialchars((string)($item['alt'] ?? '')) ?>" placeholder="อธิบายภาพสำหรับ SEO/Accessibility" required>
      </label>

      <div class="form-section" style="display:flex;gap:1.5rem;flex-wrap:wrap">
        <label class="toggle-row" style="margin:0">
          <input type="checkbox" name="tall" value="1" <?= !empty($item['tall']) ? 'checked' : '' ?>>
          <span style="text-transform:none;font-size:.9rem;color:var(--text);letter-spacing:0">การ์ดสูง (tall)</span>
        </label>
      </div>

      <div class="sticky-actions">
        <button type="submit" class="btn btn-primary"><?= $isNew ? '💾 บันทึกผลงาน' : '💾 บันทึกการแก้ไข' ?></button>
        <a href="/admin/gallery.php" class="btn btn-ghost">ยกเลิก</a>
      </div>
    </form>
</section>
<script>
function autoId(val) {
  const idEl = document.getElementById('gid');
  if (idEl.dataset.manual) return;
  idEl.value = val.toLowerCase()
    .replace(/[^a-z0-9\s-]/g, '')
    .trim().replace(/\s+/g, '-').replace(/-+/g, '-');
}
document.getElementById('gid').addEventListener('input', function() {
  this.dataset.manual = '1';
});
</script>
<?php adminShellEnd() ?>
