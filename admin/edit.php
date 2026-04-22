<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/md.php';
requireLogin();

$postsDir = dirname(__DIR__) . '/_posts';
$isNew    = true;
$file     = '';
$data     = ['title' => '', 'slug' => '', 'date' => date('Y-m-d'), 'description' => '', 'image' => '', 'image_facebook' => '', 'image_instagram' => '', 'post_facebook' => true, 'post_instagram' => true];
$body     = '';

if (!empty($_GET['file'])) {
    $file = basename((string)$_GET['file']);
    if (!validPostFilename($file)) { http_response_code(400); exit('Bad Request'); }
    $path = $postsDir . '/' . $file;
    if (file_exists($path)) {
        $parsed  = parseFrontmatter(file_get_contents($path));
        $data    = array_merge($data, $parsed['data']);
        $body    = $parsed['body'];
        $isNew   = false;
    }
}

$easymde = '<link rel="stylesheet" href="https://unpkg.com/easymde/dist/easymde.min.css">
<script src="https://unpkg.com/easymde/dist/easymde.min.js"></script>';

adminHead($isNew ? 'บทความใหม่' : 'แก้ไขบทความ', $easymde);
?>
<div class="wrap">
  <div class="card">
    <?php adminNav() ?>
    <div class="topbar">
      <h1><?= $isNew ? 'เขียนบทความใหม่' : 'แก้ไขบทความ' ?></h1>
      <a href="/admin/dashboard.php" class="btn btn-ghost btn-sm">← กลับ</a>
    </div>

    <form method="POST" action="/admin/save.php">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
      <input type="hidden" name="_original_file" value="<?= htmlspecialchars($file) ?>">
      <input type="hidden" name="_is_new" value="<?= $isNew ? '1' : '0' ?>">

      <div class="field-row">
        <label>
          <span>หัวข้อบทความ</span>
          <input type="text" name="title" value="<?= htmlspecialchars($data['title']) ?>" required
                 oninput="autoSlug(this.value)">
        </label>
        <label>
          <span>Slug (URL) <small style="font-weight:400;text-transform:none">— ตัวเล็ก a-z, 0-9, -</small></span>
          <input type="text" name="slug" id="slug" value="<?= htmlspecialchars($data['slug']) ?>" required
                 pattern="[a-z0-9\-]+" title="ตัวพิมพ์เล็กและ - เท่านั้น">
        </label>
      </div>

      <div class="field-row">
        <label>
          <span>วันที่เผยแพร่</span>
          <input type="date" name="date" value="<?= htmlspecialchars($data['date']) ?>" required>
        </label>
        <label>
          <span>คำอธิบาย (SEO & Caption โซเชียล)</span>
          <textarea name="description" rows="2" placeholder="ใช้เป็น meta description และ caption ใน FB/IG"><?= htmlspecialchars($data['description']) ?></textarea>
        </label>
      </div>

      <div style="margin-bottom:1.25rem;padding:1rem 1.15rem;border-radius:var(--r);background:rgba(255,255,255,.52);border:1px solid var(--line)">
        <div style="font-size:.85rem;font-weight:600;color:var(--text);margin-bottom:.9rem">รูปภาพประกอบ — ไม่บังคับทั้ง 3 ขนาด ใส่เฉพาะที่ต้องการใช้ได้</div>
        <label>
          <span>1) รูปสำหรับหน้าเว็บ <small style="font-weight:400;text-transform:none;color:var(--muted)">— แนะนำ 1200×800 (แนวนอน)</small></span>
          <input type="text" name="image" value="<?= htmlspecialchars($data['image'] ?? '') ?>" placeholder="https://...">
        </label>
        <label>
          <span>2) รูปสำหรับ Facebook <small style="font-weight:400;text-transform:none;color:var(--muted)">— แนะนำ 1200×630 (อัตราส่วน 1.91:1)</small></span>
          <input type="text" name="image_facebook" value="<?= htmlspecialchars($data['image_facebook'] ?? '') ?>" placeholder="https://...">
        </label>
        <label style="margin-bottom:0">
          <span>3) รูปสำหรับ Instagram <small style="font-weight:400;text-transform:none;color:var(--muted)">— แนะนำ 1080×1080 (สี่เหลี่ยมจัตุรัส)</small></span>
          <input type="text" name="image_instagram" value="<?= htmlspecialchars($data['image_instagram'] ?? '') ?>" placeholder="https://...">
        </label>
      </div>

      <label>
        <span>เนื้อหาบทความ</span>
        <textarea id="editor" name="body"><?= htmlspecialchars($body) ?></textarea>
      </label>

      <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;padding:1rem;border-radius:var(--r);background:rgba(255,255,255,.52);border:1px solid var(--line)">
        <label class="toggle-row" style="margin:0">
          <input type="checkbox" name="post_facebook" value="1" <?= !empty($data['post_facebook']) ? 'checked' : '' ?>>
          <span style="text-transform:none;font-size:.9rem;color:var(--text);letter-spacing:0">โพสต์ไป Facebook</span>
        </label>
        <label class="toggle-row" style="margin:0">
          <input type="checkbox" name="post_instagram" value="1" <?= !empty($data['post_instagram']) ? 'checked' : '' ?>>
          <span style="text-transform:none;font-size:.9rem;color:var(--text);letter-spacing:0">โพสต์ไป Instagram</span>
        </label>
      </div>

      <div style="display:flex;gap:.75rem">
        <button type="submit" class="btn btn-primary">
          <?= $isNew ? '🚀 เผยแพร่บทความ' : '💾 บันทึกการแก้ไข' ?>
        </button>
        <a href="/admin/dashboard.php" class="btn btn-ghost">ยกเลิก</a>
      </div>
    </form>
  </div>
</div>
<script>
new EasyMDE({
  element: document.getElementById('editor'),
  spellChecker: false,
  autosave: { enabled: true, uniqueId: 'verora-editor' },
  toolbar: ['bold','italic','heading','|','quote','unordered-list','ordered-list','|','link','image','|','preview','guide'],
});

function autoSlug(val) {
  const slugEl = document.getElementById('slug');
  if (slugEl.dataset.manual) return;
  slugEl.value = val.toLowerCase()
    .replace(/[^a-z0-9\s-]/g, '')
    .trim().replace(/\s+/g, '-').replace(/-+/g, '-');
}
document.getElementById('slug').addEventListener('input', function() {
  this.dataset.manual = '1';
});
</script>
</body>
</html>
