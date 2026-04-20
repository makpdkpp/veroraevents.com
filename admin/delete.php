<?php
require __DIR__ . '/auth.php';
requireLogin();

$file = basename($_GET['file'] ?? '');
if ($file) {
    $path = dirname(__DIR__) . '/_posts/' . $file;
    if (file_exists($path)) unlink($path);
}
header('Location: /admin/dashboard.php?flash=deleted');
exit;
