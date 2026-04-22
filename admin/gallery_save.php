<?php
require __DIR__ . '/auth.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
requireCsrf();

$dataDir  = dirname(__DIR__) . '/_data';
$dataFile = $dataDir . '/gallery.json';

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0775, true);
}

$items = [];
if (file_exists($dataFile)) {
    $items = json_decode(file_get_contents($dataFile), true) ?: [];
}

$title = trim($_POST['title'] ?? '');
$id    = trim($_POST['id'] ?? '');
$tag   = trim($_POST['tag'] ?? '');
$order = trim($_POST['order'] ?? '0');
$image = trim($_POST['image'] ?? '');
$alt   = trim($_POST['alt'] ?? '');
$tall  = !empty($_POST['tall']);

$origId = preg_replace('/[^a-z0-9-]/', '', strtolower($_POST['_original_id'] ?? ''));
$isNew  = ($_POST['_is_new'] ?? '0') === '1';

$id = strtolower(preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', $id)));
if (!$id) {
    $id = strtolower(preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', $title)));
}

$orderInt = (int)preg_replace('/[^0-9-]/', '', $order);

$newItem = [
    'id' => $id,
    'tag' => $tag,
    'title' => $title,
    'image' => $image,
    'alt' => $alt,
    'tall' => $tall,
    'order' => $orderInt,
];

$found = false;
for ($i = 0; $i < count($items); $i++) {
    $itId = (string)($items[$i]['id'] ?? '');
    if ($origId && $itId === $origId) {
        $items[$i] = $newItem;
        $found = true;
        break;
    }
}

if (!$found) {
    foreach ($items as $it) {
        if (!empty($it['id']) && $it['id'] === $id) {
            header('Location: /admin/gallery_edit.php?id=' . urlencode($id));
            exit;
        }
    }
    $items[] = $newItem;
}

usort($items, function ($a, $b) {
    $ao = (int)($a['order'] ?? 0);
    $bo = (int)($b['order'] ?? 0);
    if ($ao === $bo) {
        return strcmp((string)($a['id'] ?? ''), (string)($b['id'] ?? ''));
    }
    return $ao <=> $bo;
});

file_put_contents($dataFile, json_encode(array_values($items), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");

header('Location: /admin/gallery.php?flash=saved');
exit;
