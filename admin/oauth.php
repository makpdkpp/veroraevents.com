<?php
/**
 * GitHub OAuth proxy for Decap CMS
 *
 * Setup:
 *   1. Create a GitHub OAuth App at:
 *      https://github.com/settings/developers → OAuth Apps → New OAuth App
 *      Homepage URL:            https://veroraevents.com
 *      Authorization callback:  https://veroraevents.com/admin/oauth.php
 *
 *   2. Copy oauth-config.php.example → oauth-config.php on the SERVER (not in Git).
 *      Fill in GITHUB_CLIENT_ID and GITHUB_CLIENT_SECRET.
 */

session_start();

$config = __DIR__ . '/oauth-config.php';
if (!file_exists($config)) {
    http_response_code(500);
    echo '<p>ไม่พบไฟล์ <code>admin/oauth-config.php</code> — กรุณาสร้างไฟล์บน server ตาม oauth-config.php.example</p>';
    exit;
}
require $config;

$code  = $_GET['code']  ?? '';
$error = $_GET['error'] ?? '';

if ($error) {
    sendResult('error', htmlspecialchars($error));
}

// Step 1 — redirect to GitHub
if (!$code) {
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;

    $url = 'https://github.com/login/oauth/authorize?' . http_build_query([
        'client_id'    => GITHUB_CLIENT_ID,
        'scope'        => 'repo,user',
        'state'        => $state,
        'redirect_uri' => (isset($_SERVER['HTTPS']) ? 'https' : 'http')
                          . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],
    ]);
    header('Location: ' . $url);
    exit;
}

// Step 2 — exchange code → token
$data = httpPost('https://github.com/login/oauth/access_token', [
    'client_id'     => GITHUB_CLIENT_ID,
    'client_secret' => GITHUB_CLIENT_SECRET,
    'code'          => $code,
    'state'         => $_SESSION['oauth_state'] ?? '',
]);

if (!empty($data['access_token'])) {
    sendResult('success', ['token' => $data['access_token'], 'provider' => 'github']);
} else {
    sendResult('error', $data['error_description'] ?? 'OAuth failed');
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
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
    } else {
        $ctx = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Accept: application/json\r\nContent-Type: application/x-www-form-urlencoded\r\n",
            'content' => $body,
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
<p style="font-family:sans-serif;padding:2rem">กำลังยืนยันตัวตน หน้าต่างนี้จะปิดโดยอัตโนมัติ…</p>
</body>
</html>
    <?php
    exit;
}
