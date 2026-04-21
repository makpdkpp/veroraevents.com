<?php
require __DIR__ . '/auth.php';
requireLogin();

$postsDir = dirname(__DIR__) . '/_posts';

$title       = trim($_POST['title']       ?? '');
$slug        = trim($_POST['slug']        ?? '');
$date        = trim($_POST['date']        ?? date('Y-m-d'));
$description = trim($_POST['description'] ?? '');
$image       = trim($_POST['image']       ?? '');
$imageFB     = trim($_POST['image_facebook']  ?? '');
$imageIG     = trim($_POST['image_instagram'] ?? '');
$body        = trim($_POST['body']        ?? '');
$origFile    = basename($_POST['_original_file'] ?? '');
$isNew       = ($_POST['_is_new'] ?? '0') === '1';
$postFB      = !empty($_POST['post_facebook']);
$postIG      = !empty($_POST['post_instagram']);

// Sanitize slug
$slug = strtolower(preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', $slug)));
if (!$slug) $slug = strtolower(preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', $title)));

// Build frontmatter
$fm  = "---\n";
$fm .= "title: \"" . str_replace('"', '\\"', $title) . "\"\n";
$fm .= "slug: $slug\n";
$fm .= "date: $date\n";
$fm .= "description: \"" . str_replace('"', '\\"', $description) . "\"\n";
if ($image)   $fm .= "image: \"$image\"\n";
if ($imageFB) $fm .= "image_facebook: \"$imageFB\"\n";
if ($imageIG) $fm .= "image_instagram: \"$imageIG\"\n";
$fm .= "post_facebook: " . ($postFB ? 'true' : 'false') . "\n";
$fm .= "post_instagram: " . ($postIG ? 'true' : 'false') . "\n";
$fm .= "---\n\n$body\n";

$newFile = $date . '-' . $slug . '.md';
$newPath = $postsDir . '/' . $newFile;

// Delete old file if slug/date changed
if ($origFile && $origFile !== $newFile) {
    $oldPath = $postsDir . '/' . $origFile;
    if (file_exists($oldPath)) unlink($oldPath);
}

file_put_contents($newPath, $fm);

// Social posting — only on new publish
if ($isNew) {
    $configFile = __DIR__ . '/config.php';
    if (file_exists($configFile)) {
        require $configFile;
        $siteUrl    = defined('SITE_URL') ? SITE_URL : 'https://veroraevents.com';
        $articleUrl = rtrim($siteUrl, '/') . '/blog/' . $slug . '/';
        $caption    = $title . "\n\n" . $description . "\n\nอ่านต่อได้ที่ → " . $articleUrl;

        if ($postFB && defined('FB_PAGE_ID') && FB_PAGE_ID) {
            if ($imageFB) {
                socialPost("https://graph.facebook.com/v19.0/" . FB_PAGE_ID . "/photos", [
                    'url'          => $imageFB,
                    'caption'      => $caption,
                    'access_token' => FB_PAGE_ACCESS_TOKEN,
                ]);
            } else {
                socialPost("https://graph.facebook.com/v19.0/" . FB_PAGE_ID . "/feed", [
                    'message'      => $caption,
                    'link'         => $articleUrl,
                    'access_token' => FB_PAGE_ACCESS_TOKEN,
                ]);
            }
        }

        $igImage = $imageIG ?: $image;
        if ($postIG && defined('IG_USER_ID') && IG_USER_ID && $igImage) {
            $container = socialPost("https://graph.facebook.com/v19.0/" . IG_USER_ID . "/media", [
                'image_url'    => $igImage,
                'caption'      => $caption,
                'access_token' => FB_PAGE_ACCESS_TOKEN,
            ]);
            if (!empty($container['id'])) {
                socialPost("https://graph.facebook.com/v19.0/" . IG_USER_ID . "/media_publish", [
                    'creation_id'  => $container['id'],
                    'access_token' => FB_PAGE_ACCESS_TOKEN,
                ]);
            }
        }
    }
}

header('Location: /admin/dashboard.php?flash=saved');
exit;

function socialPost(string $url, array $body): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($body),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res ?: '{}', true) ?: [];
}
