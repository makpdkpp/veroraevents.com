<?php
require __DIR__ . '/auth.php';
requireLogin();

$dataFile = dirname(__DIR__) . '/_data/content.json';
$flash = $_GET['flash'] ?? '';
$error = '';

// Section groups for the edit form. Each field: [key, label, type]
// type: 'text' (single-line), 'textarea' (multi-line), 'url' (image URL)
$sections = [
    'Hero (ส่วนแรกของหน้าเว็บ)' => [
        ['hero_eyebrow', 'Eyebrow', 'text'],
        ['hero_headline', 'Headline', 'textarea'],
        ['hero_lede', 'คำอธิบาย', 'textarea'],
        ['hero_cta_primary', 'ปุ่มหลัก', 'text'],
        ['hero_cta_secondary', 'ปุ่มรอง', 'text'],
        ['hero_image', 'รูปหลัก (URL)', 'url'],
    ],
    'Beverage Experience' => [
        ['beverage_eyebrow', 'Eyebrow', 'text'],
        ['beverage_headline', 'หัวข้อ', 'textarea'],
        ['beverage_description', 'คำอธิบาย', 'textarea'],
        ['bev_cocktail_title', 'Signature Cocktails — ชื่อ', 'text'],
        ['bev_cocktail_desc', 'Signature Cocktails — คำอธิบาย', 'textarea'],
        ['bev_cocktail_image', 'Signature Cocktails — รูป', 'url'],
        ['bev_mocktail_title', 'Mocktails — ชื่อ', 'text'],
        ['bev_mocktail_desc', 'Mocktails — คำอธิบาย', 'textarea'],
        ['bev_mocktail_image', 'Mocktails — รูป', 'url'],
        ['bev_coffee_title', 'Coffee Bar — ชื่อ', 'text'],
        ['bev_coffee_desc', 'Coffee Bar — คำอธิบาย', 'textarea'],
        ['bev_coffee_image', 'Coffee Bar — รูป', 'url'],
        ['bev_wine_title', 'Wine — ชื่อ', 'text'],
        ['bev_wine_desc', 'Wine — คำอธิบาย', 'textarea'],
        ['bev_wine_image', 'Wine — รูป', 'url'],
    ],
    'Event Styling & Decoration' => [
        ['styling_eyebrow', 'Eyebrow', 'text'],
        ['styling_headline', 'หัวข้อ', 'textarea'],
        ['styling_description', 'คำอธิบาย', 'textarea'],
        ['styling_highlight_1', 'Highlight 1', 'text'],
        ['styling_highlight_2', 'Highlight 2', 'text'],
        ['styling_highlight_3', 'Highlight 3', 'text'],
        ['styling_highlight_4', 'Highlight 4', 'text'],
        ['styling_image', 'รูปประกอบ', 'url'],
    ],
    'Balloon Design' => [
        ['balloon_eyebrow', 'Eyebrow', 'text'],
        ['balloon_headline', 'หัวข้อ', 'textarea'],
        ['balloon_description', 'คำอธิบาย', 'textarea'],
        ['balloon_service_1_title', 'บริการ 1 — ชื่อ', 'text'],
        ['balloon_service_1_desc', 'บริการ 1 — คำอธิบาย', 'textarea'],
        ['balloon_service_2_title', 'บริการ 2 — ชื่อ', 'text'],
        ['balloon_service_2_desc', 'บริการ 2 — คำอธิบาย', 'textarea'],
        ['balloon_service_3_title', 'บริการ 3 — ชื่อ', 'text'],
        ['balloon_service_3_desc', 'บริการ 3 — คำอธิบาย', 'textarea'],
        ['balloon_service_4_title', 'บริการ 4 — ชื่อ', 'text'],
        ['balloon_service_4_desc', 'บริการ 4 — คำอธิบาย', 'textarea'],
    ],
    'Portfolio' => [
        ['portfolio_eyebrow', 'Eyebrow', 'text'],
        ['portfolio_headline', 'หัวข้อ', 'textarea'],
        ['portfolio_description', 'คำอธิบาย', 'textarea'],
    ],
    'Packages' => [
        ['packages_eyebrow', 'Eyebrow', 'text'],
        ['packages_headline', 'หัวข้อ', 'textarea'],
        ['packages_description', 'คำอธิบาย', 'textarea'],
        ['package_1_title', 'Package 1 — ชื่อ', 'text'],
        ['package_1_desc', 'Package 1 — คำอธิบาย', 'textarea'],
        ['package_2_title', 'Package 2 — ชื่อ', 'text'],
        ['package_2_desc', 'Package 2 — คำอธิบาย', 'textarea'],
        ['package_3_title', 'Package 3 — ชื่อ', 'text'],
        ['package_3_desc', 'Package 3 — คำอธิบาย', 'textarea'],
    ],
    'Contact' => [
        ['contact_eyebrow', 'Eyebrow', 'text'],
        ['contact_headline', 'หัวข้อ', 'textarea'],
        ['contact_description', 'คำอธิบาย', 'textarea'],
    ],
    'LINE OA' => [
        ['line_id',  'LINE ID', 'text'],
        ['line_url', 'LINE URL (สำหรับปุ่มและ Float Button)', 'text'],
        ['line_qr',  'QR Code (URL รูปภาพ — ว่างไว้ = ใช้ไฟล์ assets/line-oa-qr.png)', 'url'],
    ],
];

$content = [];
if (file_exists($dataFile)) {
    $content = json_decode(file_get_contents($dataFile), true) ?: [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    foreach ($sections as $fields) {
        foreach ($fields as $f) {
            [$key] = $f;
            if (isset($_POST[$key])) {
                $content[$key] = trim((string)$_POST[$key]);
            }
        }
    }
    $json = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($json === false || file_put_contents($dataFile, $json) === false) {
        $error = 'บันทึกไม่สำเร็จ — ตรวจสอบสิทธิ์ไฟล์ _data/content.json';
    } else {
        header('Location: /admin/content.php?flash=saved');
        exit;
    }
}

adminHead('เนื้อหาเว็บ');
?>
<?php adminShellStart('content', 'Website Content') ?>
<section class="admin-page stack-lg">
  <?php adminPageHeader(
      'Website',
      'แก้ไขเนื้อหาหน้าเว็บ',
      'รวมข้อความและสื่อหลักของทุก section ไว้ในฟอร์มเดียว เพื่อให้การดูแลหน้าเว็บสม่ำเสมอและค้นหาง่าย',
      '<a href="/" target="_blank" class="btn btn-ghost">ดูหน้าเว็บ</a>'
  ) ?>

    <?php if ($flash === 'saved'): ?><p class="flash-ok">บันทึกเรียบร้อยแล้ว — รีเฟรชหน้าเว็บเพื่อดูผล</p><?php endif ?>
    <?php if ($error): ?><p class="flash-err"><?= htmlspecialchars($error) ?></p><?php endif ?>

    <form method="POST" class="stack-lg">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
      <?php foreach ($sections as $sectionTitle => $fields): ?>
        <details open class="form-section">
          <summary><?= htmlspecialchars($sectionTitle) ?></summary>
          <div class="form-section-body">
            <?php foreach ($fields as $f):
              [$key, $label, $type] = $f;
              $val = (string)($content[$key] ?? '');
            ?>
              <label>
                <span><?= htmlspecialchars($label) ?></span>
                <?php if ($type === 'textarea'): ?>
                  <textarea name="<?= htmlspecialchars($key) ?>" rows="3"><?= htmlspecialchars($val) ?></textarea>
                <?php elseif ($type === 'url'): ?>
                  <?php imagePickerField($key, $val) ?>
                <?php else: ?>
                  <input type="text" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($val) ?>">
                <?php endif ?>
              </label>
            <?php endforeach ?>
          </div>
        </details>
      <?php endforeach ?>

      <div class="sticky-actions">
        <button type="submit" class="btn btn-primary">💾 บันทึกเนื้อหา</button>
        <a href="/admin/dashboard.php" class="btn btn-ghost">ยกเลิก</a>
      </div>
    </form>
</section>
<?php adminShellEnd() ?>
