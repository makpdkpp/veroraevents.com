<?php
require __DIR__ . '/auth.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

requireCsrf();

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'ไม่พบไฟล์หรืออัปโหลดล้มเหลว']);
    exit;
}

$file = $_FILES['file'];

if ($file['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'ไฟล์ใหญ่เกินไป (สูงสุด 5 MB)']);
    exit;
}

// Validate via getimagesize() — reads magic bytes, not the client-supplied MIME
$imgInfo = @getimagesize($file['tmp_name']);
if (!$imgInfo) {
    http_response_code(400);
    echo json_encode(['error' => 'ไฟล์ไม่ใช่รูปภาพที่รองรับ']);
    exit;
}

$allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
$mime = $imgInfo['mime'];

if (!isset($allowedMimes[$mime])) {
    http_response_code(400);
    echo json_encode(['error' => 'รองรับเฉพาะ JPEG, PNG, WebP, GIF']);
    exit;
}

$ext       = $allowedMimes[$mime];
$uploadDir = dirname(__DIR__) . '/assets/uploads';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

// Prevent script execution inside the uploads directory
$htaccess = $uploadDir . '/.htaccess';
if (!file_exists($htaccess)) {
    file_put_contents(
        $htaccess,
        "php_flag engine off\nOptions -ExecCGI\nAddType text/plain .php .php3 .phtml .phar\n"
    );
}

$filename = bin2hex(random_bytes(12)) . '.' . $ext;
$dest     = $uploadDir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    http_response_code(500);
    echo json_encode(['error' => 'บันทึกไฟล์ไม่สำเร็จ — ตรวจสอบสิทธิ์โฟลเดอร์ assets/uploads/']);
    exit;
}

echo json_encode(['url' => '/assets/uploads/' . $filename]);
