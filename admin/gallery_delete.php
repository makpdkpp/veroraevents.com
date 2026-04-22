<?php
require __DIR__ . '/auth.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
requireCsrf();

$dataFile = dirname(__DIR__) . '/_data/gallery.json';
$items = [];
if (file_exists($dataFile)) {
    $items = json_decode(file_get_contents($dataFile), true) ?: [];
}

$id = preg_replace('/[^a-z0-9-]/', '', strtolower((string)($_POST['id'] ?? '')));
if ($id) {
    $items = array_values(array_filter($items, function ($it) use ($id) {
        return (string)($it['id'] ?? '') !== $id;
    }));
    file_put_contents($dataFile, json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");
}

header('Location: /admin/gallery.php?flash=deleted');
exit;
