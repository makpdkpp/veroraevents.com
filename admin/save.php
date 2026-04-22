<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/social.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
requireCsrf();

$postsDir = dirname(__DIR__) . '/_posts';

$title       = trim($_POST['title']       ?? '');
$slug        = trim($_POST['slug']        ?? '');
$date        = trim($_POST['date']        ?? date('Y-m-d'));
$description = trim($_POST['description'] ?? '');
$image       = trim($_POST['image']       ?? '');
$imageFB     = trim($_POST['image_facebook']  ?? '');
$imageIG     = trim($_POST['image_instagram'] ?? '');
$body        = trim($_POST['body']        ?? '');
$origFileRaw = basename($_POST['_original_file'] ?? '');
$origFile    = ($origFileRaw !== '' && validPostFilename($origFileRaw)) ? $origFileRaw : '';
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

if ($origFile && $origFile !== $newFile) {
    $oldPath = $postsDir . '/' . $origFile;
    if (file_exists($oldPath)) unlink($oldPath);
}

file_put_contents($newPath, $fm);

$socialFlash = '';

// Social posting — only on new publish
if ($isNew) {
    $configFile = __DIR__ . '/config.php';
    if (file_exists($configFile)) {
        require $configFile;
        $siteUrl    = defined('SITE_URL') ? SITE_URL : 'https://veroraevents.com';
        $articleUrl = rtrim($siteUrl, '/') . '/blog/' . $slug . '/';
        $caption    = $title . "\n\n" . $description . "\n\nอ่านต่อได้ที่ → " . $articleUrl;

        $fbOk = true;
        $igOk = true;

        if ($postFB && defined('FB_PAGE_ID') && FB_PAGE_ID && defined('FB_PAGE_ACCESS_TOKEN') && FB_PAGE_ACCESS_TOKEN) {
            $r = socialPublishFacebook(FB_PAGE_ID, FB_PAGE_ACCESS_TOKEN, $caption, $imageFB, $articleUrl);
            $fbOk = !empty($r['ok']);
        }

        $igImage = $imageIG ?: $image;
        if ($postIG && defined('IG_USER_ID') && IG_USER_ID && defined('FB_PAGE_ACCESS_TOKEN') && FB_PAGE_ACCESS_TOKEN && $igImage) {
            $r = socialPublishInstagram(IG_USER_ID, FB_PAGE_ACCESS_TOKEN, $igImage, $caption);
            $igOk = !empty($r['ok']);
        }

        if (!$fbOk || !$igOk) $socialFlash = '&social=err';
    }
}

header('Location: /admin/dashboard.php?flash=saved' . $socialFlash);
exit;
