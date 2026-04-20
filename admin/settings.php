<?php
require __DIR__ . '/auth.php';
requireLogin();

$configFile = __DIR__ . '/config.php';
$flash = $_GET['flash'] ?? '';
$error = '';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

      <div style="display:flex;gap:.75rem">
        <button type="submit" class="btn btn-primary">💾 บันทึก</button>
        <a href="/admin/dashboard.php" class="btn btn-ghost">ยกเลิก</a>
      </div>
    </form>
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
