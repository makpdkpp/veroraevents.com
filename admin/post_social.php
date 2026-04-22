<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/md.php';
require __DIR__ . '/social.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
requireCsrf();

$file     = requireValidPostFilename((string)($_POST['file'] ?? ''));
$platform = $_POST['platform'] ?? '';
if (!in_array($platform, ['fb', 'ig', 'both'], true)) {
    header('Location: /admin/dashboard.php?flash=err');
    exit;
}

$postsDir = dirname(__DIR__) . '/_posts';
$path = $postsDir . '/' . $file;
if (!file_exists($path)) {
    header('Location: /admin/dashboard.php?flash=err');
    exit;
}

$parsed = parseFrontmatter(file_get_contents($path));
$d = $parsed['data'];

$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    header('Location: /admin/dashboard.php?flash=noconfig');
    exit;
}
require $configFile;

$title   = (string)($d['title'] ?? '');
$desc    = (string)($d['description'] ?? '');
$slug    = (string)($d['slug'] ?? pathinfo($file, PATHINFO_FILENAME));
$imgWeb  = (string)($d['image'] ?? '');
$imgFB   = (string)($d['image_facebook'] ?? '');
$imgIG   = (string)($d['image_instagram'] ?? '');

$siteUrl    = defined('SITE_URL') ? SITE_URL : 'https://veroraevents.com';
$articleUrl = rtrim($siteUrl, '/') . '/blog/' . $slug . '/';
$caption    = $title . ($desc ? "\n\n" . $desc : '') . "\n\nอ่านต่อได้ที่ → " . $articleUrl;

$fbOk = null; $igOk = null;

if ($platform === 'fb' || $platform === 'both') {
    if (defined('FB_PAGE_ID') && FB_PAGE_ID && defined('FB_PAGE_ACCESS_TOKEN') && FB_PAGE_ACCESS_TOKEN) {
        $fbImage = $imgFB ?: $imgWeb;
        $r = socialPublishFacebook(FB_PAGE_ID, FB_PAGE_ACCESS_TOKEN, $caption, $fbImage, $articleUrl);
        $fbOk = !empty($r['ok']);
    } else {
        $fbOk = false;
    }
}

if ($platform === 'ig' || $platform === 'both') {
    $igImage = $imgIG ?: $imgWeb;
    if (defined('IG_USER_ID') && IG_USER_ID && defined('FB_PAGE_ACCESS_TOKEN') && FB_PAGE_ACCESS_TOKEN && $igImage) {
        $r = socialPublishInstagram(IG_USER_ID, FB_PAGE_ACCESS_TOKEN, $igImage, $caption);
        $igOk = !empty($r['ok']);
    } else {
        $igOk = false;
    }
}

$allOk = ($fbOk === null || $fbOk) && ($igOk === null || $igOk);
$flash = $allOk ? 'posted_' . $platform : 'post_err';
header('Location: /admin/dashboard.php?flash=' . $flash);
exit;
