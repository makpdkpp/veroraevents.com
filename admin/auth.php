<?php
session_start();

function requireLogin(): void
{
    if (empty($_SESSION['admin'])) {
        header('Location: /admin/');
        exit;
    }
}

function adminHead(string $title = 'Admin', string $extra = ''): void { ?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title><?= htmlspecialchars($title) ?> · Verora Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<?= $extra ?>
<style>
:root{--bg:#fffaf8;--surface:rgba(255,252,250,.88);--text:#37272d;--muted:#725861;--gold:#b8883e;--pink:#f7d7de;--line:rgba(185,137,103,.18);--shadow:0 20px 60px rgba(120,77,86,.11);--r:16px}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Kanit',sans-serif;background:radial-gradient(ellipse 65% 45% at 0% 0%,rgba(247,215,222,.6),transparent 60%),radial-gradient(ellipse 55% 40% at 100% 6%,rgba(237,218,188,.45),transparent 60%),#fffdfb;color:var(--text);min-height:100vh;-webkit-font-smoothing:antialiased}
a{color:inherit;text-decoration:none}
.wrap{max-width:960px;margin:0 auto;padding:1.5rem}
.card{background:var(--surface);border:1px solid rgba(255,255,255,.82);border-top-color:rgba(255,255,255,.96);box-shadow:var(--shadow);backdrop-filter:blur(20px);border-radius:24px;padding:2rem}
.topbar{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.75rem;flex-wrap:wrap}
.topbar h1{font-size:1.5rem;font-weight:600}
.btn{display:inline-flex;align-items:center;gap:.4rem;padding:.65rem 1.25rem;border-radius:999px;border:1px solid transparent;font-family:inherit;font-size:.88rem;font-weight:600;cursor:pointer;transition:transform 180ms,box-shadow 180ms;white-space:nowrap}
.btn:hover{transform:translateY(-1px)}
.btn-primary{background:linear-gradient(135deg,#d9849d,#c79c63);color:#fff;box-shadow:0 10px 28px rgba(180,100,120,.24)}
.btn-ghost{background:rgba(255,255,255,.62);border-color:var(--line);color:var(--text)}
.btn-danger{background:rgba(200,50,50,.08);border-color:rgba(200,50,50,.2);color:#b02020}
.btn-sm{padding:.45rem .9rem;font-size:.82rem}
label{display:block;margin-bottom:1rem}
label>span{display:block;font-size:.78rem;font-weight:600;color:var(--muted);margin-bottom:.4rem;text-transform:uppercase;letter-spacing:.1em}
input[type=text],input[type=password],input[type=email],input[type=date],textarea,select{width:100%;padding:.75rem 1rem;border-radius:var(--r);border:1px solid var(--line);background:rgba(255,255,255,.72);font-family:inherit;font-size:.95rem;color:var(--text);outline:none;transition:border-color 180ms,box-shadow 180ms}
input:focus,textarea:focus,select:focus{border-color:rgba(217,132,157,.7);box-shadow:0 0 0 3px rgba(247,215,222,.25)}
.field-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.flash-ok{padding:.75rem 1rem;border-radius:var(--r);background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);color:#0a6640;margin-bottom:1rem;font-size:.9rem}
.flash-err{padding:.75rem 1rem;border-radius:var(--r);background:rgba(200,50,50,.1);border:1px solid rgba(200,50,50,.2);color:#b02020;margin-bottom:1rem;font-size:.9rem}
.article-list{display:grid;gap:.65rem}
.article-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.9rem 1.15rem;border-radius:var(--r);background:rgba(255,255,255,.52);border:1px solid rgba(255,255,255,.88);transition:box-shadow 180ms}
.article-row:hover{box-shadow:0 6px 20px rgba(120,77,86,.09)}
.article-meta{flex:1;min-width:0}
.article-meta strong{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:.97rem}
.article-meta span{font-size:.78rem;color:var(--muted)}
.article-actions{display:flex;gap:.45rem;flex-shrink:0}
.toggle-row{display:flex;align-items:center;gap:.6rem;font-size:.9rem;cursor:pointer}
.toggle-row input[type=checkbox]{width:1rem;height:1rem;accent-color:#d9849d;cursor:pointer}
@media(max-width:600px){.field-row{grid-template-columns:1fr}.topbar{flex-direction:column;align-items:flex-start}}
</style>
</head>
<body>
<?php }

function adminNav(string $page = ''): void { ?>
<div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:1px solid var(--line)">
  <a href="/admin/dashboard.php" style="display:flex;align-items:center;gap:.5rem;font-weight:600;font-size:.95rem">
    <img src="/assets/verora-balloon-mark.svg" style="width:1.6rem;height:1.6rem" alt="">
    Verora Admin
  </a>
  <div style="display:flex;gap:.5rem;align-items:center">
    <a href="/admin/dashboard.php" class="btn btn-ghost btn-sm" <?= $page === 'posts' ? 'aria-current="page"' : '' ?>>บทความ</a>
    <a href="/admin/gallery.php" class="btn btn-ghost btn-sm" <?= $page === 'gallery' ? 'aria-current="page"' : '' ?>>ผลงาน</a>
    <a href="/admin/settings.php" class="btn btn-ghost btn-sm" <?= $page === 'settings' ? 'aria-current="page"' : '' ?>>ตั้งค่า</a>
    <a href="/admin/edit.php" class="btn btn-primary btn-sm">+ บทความใหม่</a>
    <a href="/admin/logout.php" class="btn btn-ghost btn-sm">ออกจากระบบ</a>
  </div>
</div>
<?php }
