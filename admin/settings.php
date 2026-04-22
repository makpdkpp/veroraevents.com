<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/social.php';
requireLogin();

$configFile  = __DIR__ . '/config.php';
$exampleFile = __DIR__ . '/config.php.example';
$flash = $_GET['flash'] ?? '';
$error = '';

if (!file_exists($configFile) && file_exists($exampleFile)) {
    @copy($exampleFile, $configFile);
}

$values = [
    'FB_PAGE_ID' => '',
    'FB_PAGE_ACCESS_TOKEN' => '',
    'IG_USER_ID' => '',
];

if (!file_exists($configFile)) {
    $error = 'ไม่พบไฟล์ admin/config.php — กรุณาสร้างไฟล์ตาม config.php.example';
} else {
    require $configFile;
    $values['FB_PAGE_ID'] = defined('FB_PAGE_ID') ? (string)FB_PAGE_ID : '';
    $values['FB_PAGE_ACCESS_TOKEN'] = defined('FB_PAGE_ACCESS_TOKEN') ? (string)FB_PAGE_ACCESS_TOKEN : '';
    $values['IG_USER_ID'] = defined('IG_USER_ID') ? (string)IG_USER_ID : '';
}

$testResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_action']) && $_POST['_action'] === 'test') {
    if (file_exists($configFile)) require $configFile;
    $testResult = socialTestConnection();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!file_exists($configFile)) {
        $error = 'ไม่พบไฟล์ admin/config.php — กรุณาสร้างไฟล์ตาม config.php.example';
    } else {
        $values['FB_PAGE_ID'] = trim($_POST['FB_PAGE_ID'] ?? '');
        $values['FB_PAGE_ACCESS_TOKEN'] = trim($_POST['FB_PAGE_ACCESS_TOKEN'] ?? '');
        $values['IG_USER_ID'] = trim($_POST['IG_USER_ID'] ?? '');

        $content = file_get_contents($configFile);
        if ($content === false) {
            $error = 'อ่านไฟล์ config.php ไม่ได้';
        } else {
            $content = upsertDefine($content, 'FB_PAGE_ID', $values['FB_PAGE_ID']);
            $content = upsertDefine($content, 'FB_PAGE_ACCESS_TOKEN', $values['FB_PAGE_ACCESS_TOKEN']);
            $content = upsertDefine($content, 'IG_USER_ID', $values['IG_USER_ID']);

            $ok = file_put_contents($configFile, $content);
            if ($ok === false) {
                $error = 'บันทึกไฟล์ config.php ไม่ได้ (permission)';
            } else {
                header('Location: /admin/settings.php?flash=saved');
                exit;
            }
        }
    }
}

adminHead('ตั้งค่า');
?>
<div class="wrap">
  <div class="card">
    <?php adminNav('settings') ?>

    <div class="topbar">
      <h1>ตั้งค่าโซเชียล</h1>
      <a href="/admin/dashboard.php" class="btn btn-ghost btn-sm">← กลับ</a>
    </div>

    <?php if ($flash === 'saved'): ?><p class="flash-ok">บันทึกเรียบร้อยแล้ว</p><?php endif ?>
    <?php if ($error): ?><p class="flash-err"><?= htmlspecialchars($error) ?></p><?php endif ?>

    <?php if ($testResult !== null): ?>
      <div style="margin-bottom:1rem">
        <?php if ($testResult['fb'] !== null): ?>
          <?php if (!empty($testResult['fb']['ok'])): ?>
            <p class="flash-ok">✓ Facebook: เชื่อมต่อได้ (Page: <?= htmlspecialchars($testResult['fb']['data']['name'] ?? '—') ?>)</p>
          <?php else: ?>
            <p class="flash-err">✗ Facebook: <?= htmlspecialchars($testResult['fb']['error'] ?: 'เชื่อมต่อไม่ได้') ?></p>
          <?php endif ?>
        <?php else: ?>
          <p class="flash-err">✗ Facebook: ยังไม่ได้ใส่ FB_PAGE_ID หรือ Access Token</p>
        <?php endif ?>
        <?php if ($testResult['ig'] !== null): ?>
          <?php if (!empty($testResult['ig']['ok'])): ?>
            <p class="flash-ok">✓ Instagram: เชื่อมต่อได้ (@<?= htmlspecialchars($testResult['ig']['data']['username'] ?? '—') ?>)</p>
          <?php else: ?>
            <p class="flash-err">✗ Instagram: <?= htmlspecialchars($testResult['ig']['error'] ?: 'เชื่อมต่อไม่ได้') ?></p>
          <?php endif ?>
        <?php else: ?>
          <p class="flash-err">✗ Instagram: ยังไม่ได้ใส่ IG_USER_ID</p>
        <?php endif ?>
        <p style="font-size:.8rem;color:var(--muted);margin-top:.5rem">ดูรายละเอียด error ได้ที่ <code>admin/social-errors.log</code></p>
      </div>
    <?php endif ?>

    <form method="POST">
      <label>
        <span>Facebook Page ID</span>
        <input type="text" name="FB_PAGE_ID" value="<?= htmlspecialchars($values['FB_PAGE_ID']) ?>" placeholder="1234567890">
      </label>

      <label>
        <span>Facebook Page Access Token</span>
        <input type="password" name="FB_PAGE_ACCESS_TOKEN" value="<?= htmlspecialchars($values['FB_PAGE_ACCESS_TOKEN']) ?>" placeholder="EAAB...">
      </label>

      <label>
        <span>Instagram User ID</span>
        <input type="text" name="IG_USER_ID" value="<?= htmlspecialchars($values['IG_USER_ID']) ?>" placeholder="1784...">
      </label>

      <div style="display:flex;gap:.75rem;flex-wrap:wrap">
        <button type="submit" class="btn btn-primary">💾 บันทึก</button>
        <button type="submit" name="_action" value="test" class="btn btn-ghost" formnovalidate>🔌 ทดสอบการเชื่อมต่อ</button>
        <a href="/admin/dashboard.php" class="btn btn-ghost">ยกเลิก</a>
      </div>
    </form>

    <div style="margin-top:2rem;padding:1rem 1.15rem;border-radius:var(--r);background:rgba(255,255,255,.4);border:1px solid var(--line);font-size:.85rem;color:var(--muted);line-height:1.7">
      <strong style="color:var(--text);display:block;margin-bottom:.4rem">การโพสต์ต้องมีสิทธิ์ (Permissions) ดังนี้</strong>
      • Facebook: Page Access Token (ไม่ใช่ User Token) ที่มีสิทธิ์ <code>pages_manage_posts</code>, <code>pages_read_engagement</code><br>
      • Instagram: บัญชีต้องเป็น <strong>Business/Creator</strong> และผูกกับ Facebook Page — ต้องการสิทธิ์ <code>instagram_basic</code>, <code>instagram_content_publish</code><br>
      • รูปสำหรับ IG ต้องเป็น URL สาธารณะ (public) ในรูปแบบ JPEG — Unsplash/CDN ที่เราใช้ผ่านได้<br>
      • ถ้ากดทดสอบแล้วไม่ผ่าน ให้ดู error ที่ <code>admin/social-errors.log</code>
    </div>
  </div>
</div>
</body>
</html>
<?php
function upsertDefine(string $content, string $name, string $value): string
{
    $escaped = addslashes($value);
    $replacement = "define('" . $name . "', '" . $escaped . "');";

    $pattern = '/define\(\s*[\"\']' . preg_quote($name, '/') . '[\"\']\s*,\s*[\"\'].*?[\"\']\s*\)\s*;/' ;
    if (preg_match($pattern, $content)) {
        return preg_replace($pattern, $replacement, $content, 1) ?? $content;
    }

    // If not found, append near the end (before closing PHP tag if any)
    if (preg_match('/\?>\s*$/', $content)) {
        return preg_replace('/\?>\s*$/', "\n" . $replacement . "\n?>\n", $content, 1) ?? ($content . "\n" . $replacement . "\n");
    }

    return rtrim($content) . "\n" . $replacement . "\n";
}
