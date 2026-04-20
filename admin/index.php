<?php
session_start();
if (!empty($_SESSION['admin'])) {
    header('Location: /admin/dashboard.php');
    exit;
}

$configFile = __DIR__ . '/config.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!file_exists($configFile)) {
        $error = 'ไม่พบไฟล์ admin/config.php — กรุณาสร้างไฟล์ตาม config.php.example';
    } else {
        require $configFile;
        $user = trim($_POST['user'] ?? '');
        $pass = $_POST['pass'] ?? '';
        if ($user === ADMIN_USER && $pass === ADMIN_PASSWORD) {
            $_SESSION['admin'] = true;
            header('Location: /admin/dashboard.php');
            exit;
        }
        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>เข้าสู่ระบบ · Verora Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Kanit',sans-serif;background:radial-gradient(ellipse 65% 45% at 0% 0%,rgba(247,215,222,.6),transparent 60%),radial-gradient(ellipse 55% 40% at 100% 6%,rgba(237,218,188,.45),transparent 60%),#fffdfb;min-height:100vh;display:grid;place-items:center;-webkit-font-smoothing:antialiased;color:#37272d}
.box{width:min(100%,380px);padding:1.25rem}
.card{background:rgba(255,252,250,.88);border:1px solid rgba(255,255,255,.82);border-top-color:rgba(255,255,255,.96);box-shadow:0 20px 60px rgba(120,77,86,.12);backdrop-filter:blur(20px);border-radius:28px;padding:2.5rem 2rem}
.logo{display:flex;align-items:center;gap:.75rem;justify-content:center;margin-bottom:1.75rem}
.logo img{width:2.6rem;height:2.6rem}
.logo-text strong{display:block;font-size:1rem;letter-spacing:.08em;text-transform:uppercase}
.logo-text span{font-size:.72rem;color:#725861;letter-spacing:.16em;text-transform:uppercase}
h2{text-align:center;font-size:1.3rem;font-weight:600;margin-bottom:1.5rem}
label{display:block;margin-bottom:1rem}
label>span{display:block;font-size:.78rem;font-weight:600;color:#725861;margin-bottom:.4rem;text-transform:uppercase;letter-spacing:.1em}
input{width:100%;padding:.8rem 1rem;border-radius:14px;border:1px solid rgba(185,137,103,.2);background:rgba(255,255,255,.72);font-family:inherit;font-size:.95rem;color:#37272d;outline:none;transition:border-color 180ms,box-shadow 180ms}
input:focus{border-color:rgba(217,132,157,.7);box-shadow:0 0 0 3px rgba(247,215,222,.25)}
.btn{width:100%;padding:.85rem;border-radius:999px;border:none;background:linear-gradient(135deg,#d9849d,#c79c63);color:#fff;font-family:inherit;font-size:.97rem;font-weight:600;cursor:pointer;margin-top:.5rem;box-shadow:0 10px 28px rgba(180,100,120,.24);transition:transform 180ms}
.btn:hover{transform:translateY(-1px)}
.err{padding:.7rem 1rem;border-radius:12px;background:rgba(200,50,50,.1);border:1px solid rgba(200,50,50,.2);color:#b02020;font-size:.88rem;margin-bottom:1rem}
</style>
</head>
<body>
<div class="box">
  <div class="card">
    <div class="logo">
      <img src="/assets/verora-balloon-mark.svg" alt="Verora">
      <div class="logo-text">
        <strong>Verora</strong>
        <span>Admin Panel</span>
      </div>
    </div>
    <h2>เข้าสู่ระบบ</h2>
    <?php if ($error): ?>
      <p class="err"><?= htmlspecialchars($error) ?></p>
    <?php endif ?>
    <form method="POST">
      <label>
        <span>ชื่อผู้ใช้</span>
        <input type="text" name="user" autocomplete="username" required>
      </label>
      <label>
        <span>รหัสผ่าน</span>
        <input type="password" name="pass" autocomplete="current-password" required>
      </label>
      <button class="btn" type="submit">เข้าสู่ระบบ</button>
    </form>
  </div>
</div>
</body>
</html>
