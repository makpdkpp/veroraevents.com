<?php
require __DIR__ . '/auth.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
requireCsrf();

$file = requireValidPostFilename((string)($_POST['file'] ?? ''));
$path = dirname(__DIR__) . '/_posts/' . $file;
if (file_exists($path)) unlink($path);

header('Location: /admin/dashboard.php?flash=deleted');
exit;
