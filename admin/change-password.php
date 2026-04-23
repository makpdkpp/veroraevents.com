<?php
require __DIR__ . '/auth.php';
requireLogin();

$configFile = __DIR__ . '/config.php';
$flash = $_GET['flash'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $current = (string)($_POST['current'] ?? '');
    $new     = (string)($_POST['new'] ?? '');
    $confirm = (string)($_POST['confirm'] ?? '');

    if (!file_exists($configFile)) {
        $error = 'ไม่พบ admin/config.php';
    } else {
        require $configFile;

        $currentOk = false;
        if (defined('ADMIN_PASSWORD_HASH') && ADMIN_PASSWORD_HASH && strpos((string)ADMIN_PASSWORD_HASH, 'REPLACE_ME') === false) {
            $currentOk = password_verify($current, (string)ADMIN_PASSWORD_HASH);
        } elseif (defined('ADMIN_PASSWORD') && ADMIN_PASSWORD !== '') {
            $currentOk = hash_equals((string)ADMIN_PASSWORD, $current);
        } else {
            // Fresh install with placeholder hash — allow first-time set
            $currentOk = true;
        }

        if (!$currentOk) {
            $error = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
        } elseif (strlen($new) < 8) {
            $error = 'รหัสผ่านใหม่ต้องยาวอย่างน้อย 8 ตัวอักษร';
        } elseif ($new !== $confirm) {
            $error = 'รหัสผ่านใหม่กับการยืนยันไม่ตรงกัน';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $content = (string)file_get_contents($configFile);

            // Upsert ADMIN_PASSWORD_HASH, remove legacy ADMIN_PASSWORD
            $replacement = "define('ADMIN_PASSWORD_HASH', " . var_export($hash, true) . ");";
            $pattern = '/define\(\s*[\'"]ADMIN_PASSWORD_HASH[\'"]\s*,\s*[\'"].*?[\'"]\s*\)\s*;/';
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $replacement, $content, 1);
            } else {
                $content = rtrim($content) . "\n" . $replacement . "\n";
            }
            // Remove any legacy ADMIN_PASSWORD (plaintext) line
            $content = preg_replace(
                '/^\s*define\(\s*[\'"]ADMIN_PASSWORD[\'"]\s*,\s*[\'"].*?[\'"]\s*\)\s*;\s*\r?\n/m',
                '',
                $content
            );

            if (@file_put_contents($configFile, $content) === false) {
                $error = 'เขียน config.php ไม่ได้ (permission?)';
            } else {
                header('Location: /admin/change-password.php?flash=saved');
                exit;
            }
        }
    }
}

adminHead('เปลี่ยนรหัสผ่าน');
?>
<?php adminShellStart('password', 'Security') ?>
<section class="admin-page stack-lg">
  <?php adminPageHeader(
      'Security',
      'เปลี่ยนรหัสผ่านผู้ดูแล',
      'อัปเดตรหัสผ่านของบัญชีแอดมินหลักและเก็บไว้ในรูปแบบ hash ภายใน config.php',
      '<a href="/admin/dashboard.php" class="btn btn-ghost">กลับ Dashboard</a>'
  ) ?>

    <?php if ($flash === 'saved'): ?><p class="flash-ok">เปลี่ยนรหัสผ่านเรียบร้อยแล้ว</p><?php endif ?>
    <?php if ($error): ?><p class="flash-err"><?= htmlspecialchars($error) ?></p><?php endif ?>

    <form method="POST" class="password-panel">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
      <label>
        <span>รหัสผ่านปัจจุบัน</span>
        <input type="password" name="current" autocomplete="current-password" required>
      </label>
      <label>
        <span>รหัสผ่านใหม่ (อย่างน้อย 8 ตัวอักษร)</span>
        <input type="password" name="new" autocomplete="new-password" minlength="8" required>
      </label>
      <label>
        <span>ยืนยันรหัสผ่านใหม่</span>
        <input type="password" name="confirm" autocomplete="new-password" minlength="8" required>
      </label>
      <button type="submit" class="btn btn-primary">💾 บันทึกรหัสผ่านใหม่</button>
    </form>

    <div class="note-card" style="font-size:.85rem;color:var(--muted);line-height:1.7">
      รหัสผ่านจะถูกเก็บเป็น bcrypt hash ใน <code>admin/config.php</code> เท่านั้น —
      ถ้ามีบรรทัด <code>ADMIN_PASSWORD</code> (plaintext) เก่าอยู่ ระบบจะลบออกให้อัตโนมัติ
    </div>
<?php adminShellEnd() ?>
