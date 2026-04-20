<?php
/**
 * GitHub OAuth proxy for Decap CMS
 * Setup: copy oauth-config.php.example → oauth-config.php and fill in credentials
 */

$config = __DIR__ . '/oauth-config.php';
if (!file_exists($config)) {
    errorPage('ไม่พบไฟล์ <code>admin/oauth-config.php</code> — กรุณาสร้างไฟล์บน server ตาม oauth-config.php.example');
}
require $config;

$code  = $_GET['code']  ?? '';
$error = $_GET['error'] ?? '';

if ($error) {
    sendResult('error', htmlspecialchars($error));
}

// Step 1 — redirect to GitHub
if (!$code) {
    $redirectUri = (isset($_SERVER['HTTPS']) ? 'https' : 'http')
                 . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

    $url = 'https://github.com/login/oauth/authorize?' . http_build_query([
        'client_id'    => GITHUB_CLIENT_ID,
        'scope'        => 'repo,user',
        'redirect_uri' => $redirectUri,
    ]);
    header('Location: ' . $url);
    exit;
}

// Step 2 — exchange code → access token
$data = httpPost('https://github.com/login/oauth/access_token', [
    'client_id'     => GITHUB_CLIENT_ID,
    'client_secret' => GITHUB_CLIENT_SECRET,
    'code'          => $code,
]);

if (!empty($data['access_token'])) {
    sendResult('success', ['token' => $data['access_token'], 'provider' => 'github']);
} else {
    sendResult('error', $data['error_description'] ?? 'Token exchange failed');
}

// ── Helpers ───────────────────────────────────────────────────────────────

function httpPost(string $url, array $params): array
{
    $body = http_build_query($params);
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
    } else {
        $ctx = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Accept: application/json\r\nContent-Type: application/x-www-form-urlencoded\r\n",
            'content' => $body,
            'timeout' => 15,
        ]]);
        $res = @file_get_contents($url, false, $ctx);
    }
    return json_decode($res ?: '{}', true) ?: [];
}

function sendResult(string $type, $payload): never
{
    $json    = json_encode($payload, JSON_UNESCAPED_UNICODE);
    $message = "authorization:github:{$type}:{$json}";
    ?>
<!DOCTYPE html>
<html lang="th">
<head><meta charset="utf-8"><title>กำลังยืนยัน…</title></head>
<body>
<script>
(function () {
    var msg = <?= json_encode($message, JSON_UNESCAPED_UNICODE) ?>;
    function respond(e) {
        window.opener.postMessage(msg, e.origin);
        window.removeEventListener('message', respond);
    }
    window.addEventListener('message', respond, false);
    window.opener && window.opener.postMessage('authorizing:github', '*');
})();
</script>
<p style="font-family:sans-serif;padding:2rem">กำลังยืนยันตัวตน…</p>
</body>
</html>
    <?php
    exit;
}

function errorPage(string $msg): never
{
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>'
       . '<p style="font-family:sans-serif;padding:2rem;color:#c00">' . $msg . '</p>'
       . '</body></html>';
    exit;
}
