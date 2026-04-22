<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/social.php';
requireLogin();

$id       = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['id'] ?? $_POST['id'] ?? ''));
$platform = $_GET['platform'] ?? $_POST['platform'] ?? '';
if (!$id || !in_array($platform, ['fb', 'ig', 'both'], true)) {
    header('Location: /admin/gallery.php?flash=err');
    exit;
}

$dataFile = dirname(__DIR__) . '/_data/gallery.json';
$items = file_exists($dataFile) ? (json_decode(file_get_contents($dataFile), true) ?: []) : [];

$item = null;
foreach ($items as $it) {
    if (!empty($it['id']) && $it['id'] === $id) { $item = $it; break; }
}
if (!$item || empty($item['image'])) {
    header('Location: /admin/gallery.php?flash=err');
    exit;
}

$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    header('Location: /admin/gallery.php?flash=noconfig');
    exit;
}
require $configFile;

$title   = (string)($item['title'] ?? '');
$tag     = (string)($item['tag'] ?? '');
$image   = (string)$item['image'];
$caption = trim(($tag ? '#' . preg_replace('/\s+/', '', $tag) . "\n" : '') . $title);

$fbOk = null; $igOk = null;

if ($platform === 'fb' || $platform === 'both') {
    if (defined('FB_PAGE_ID') && FB_PAGE_ID && defined('FB_PAGE_ACCESS_TOKEN') && FB_PAGE_ACCESS_TOKEN) {
        $r = socialPublishFacebook(FB_PAGE_ID, FB_PAGE_ACCESS_TOKEN, $caption, $image, '');
        $fbOk = !empty($r['ok']);
    } else {
        $fbOk = false;
    }
}

if ($platform === 'ig' || $platform === 'both') {
    if (defined('IG_USER_ID') && IG_USER_ID && defined('FB_PAGE_ACCESS_TOKEN') && FB_PAGE_ACCESS_TOKEN) {
        $r = socialPublishInstagram(IG_USER_ID, FB_PAGE_ACCESS_TOKEN, $image, $caption);
        $igOk = !empty($r['ok']);
    } else {
        $igOk = false;
    }
}

$allOk = ($fbOk === null || $fbOk) && ($igOk === null || $igOk);
$flash = $allOk ? 'posted_' . $platform : 'post_err';
header('Location: /admin/gallery.php?flash=' . $flash);
exit;
