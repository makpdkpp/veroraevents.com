<?php
require __DIR__ . '/auth.php';
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
$caption = trim(($tag ? "#" . preg_replace('/\s+/', '', $tag) . "\n" : '') . $title);

$results = [];

if ($platform === 'fb' || $platform === 'both') {
    if (defined('FB_PAGE_ID') && FB_PAGE_ID) {
        $results['fb'] = socialPost("https://graph.facebook.com/v19.0/" . FB_PAGE_ID . "/photos", [
            'url'          => $image,
            'caption'      => $caption,
            'access_token' => FB_PAGE_ACCESS_TOKEN,
        ]);
    }
}

if ($platform === 'ig' || $platform === 'both') {
    if (defined('IG_USER_ID') && IG_USER_ID) {
        $container = socialPost("https://graph.facebook.com/v19.0/" . IG_USER_ID . "/media", [
            'image_url'    => $image,
            'caption'      => $caption,
            'access_token' => FB_PAGE_ACCESS_TOKEN,
        ]);
        if (!empty($container['id'])) {
            $results['ig'] = socialPost("https://graph.facebook.com/v19.0/" . IG_USER_ID . "/media_publish", [
                'creation_id'  => $container['id'],
                'access_token' => FB_PAGE_ACCESS_TOKEN,
            ]);
        } else {
            $results['ig'] = $container;
        }
    }
}

$ok = false;
foreach ($results as $r) {
    if (!empty($r['id']) || !empty($r['post_id'])) { $ok = true; break; }
}

header('Location: /admin/gallery.php?flash=' . ($ok ? 'posted_' . $platform : 'post_err'));
exit;

function socialPost(string $url, array $body): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($body),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 20,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res ?: '{}', true) ?: [];
}
