<?php
/**
 * Shared Graph API helpers for Verora admin.
 * Uses form-urlencoded POST (more reliable with FB/IG Graph API)
 * and logs full request/response to admin/social-errors.log.
 */

function socialLog(string $line): void
{
    $logFile = __DIR__ . '/social-errors.log';
    @file_put_contents($logFile, '[' . date('c') . '] ' . $line . "\n", FILE_APPEND);
}

function socialRequest(string $method, string $url, array $body = []): array
{
    $ch = curl_init();
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
    ];
    if ($method === 'POST') {
        $opts[CURLOPT_URL]        = $url;
        $opts[CURLOPT_POST]       = true;
        $opts[CURLOPT_POSTFIELDS] = http_build_query($body);
    } else {
        $qs = $body ? (strpos($url, '?') === false ? '?' : '&') . http_build_query($body) : '';
        $opts[CURLOPT_URL] = $url . $qs;
    }
    curl_setopt_array($ch, $opts);

    $res     = curl_exec($ch);
    $httpRes = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    $decoded = $res ? (json_decode($res, true) ?: []) : [];
    $ok      = $httpRes >= 200 && $httpRes < 300 && empty($decoded['error']);

    if (!$ok) {
        $safeBody = $body;
        if (isset($safeBody['access_token'])) $safeBody['access_token'] = '***';
        socialLog(sprintf(
            "%s %s → HTTP %d | curl:%s | body:%s | resp:%s",
            $method, $url, $httpRes, $curlErr ?: 'ok',
            json_encode($safeBody, JSON_UNESCAPED_UNICODE),
            substr((string)$res, 0, 1200)
        ));
    }

    return [
        'ok'    => $ok,
        'http'  => $httpRes,
        'error' => $curlErr ?: ($decoded['error']['message'] ?? ''),
        'data'  => $decoded,
    ];
}

function socialPost(string $url, array $body): array
{
    $r = socialRequest('POST', $url, $body);
    return $r['data'];
}

/**
 * Publish to FB Page as a photo post (uses $imageUrl) or link post (fallback).
 */
function socialPublishFacebook(string $pageId, string $token, string $caption, string $imageUrl = '', string $link = ''): array
{
    if ($imageUrl !== '') {
        return socialRequest('POST', "https://graph.facebook.com/v19.0/$pageId/photos", [
            'url'          => $imageUrl,
            'caption'      => $caption,
            'access_token' => $token,
        ]);
    }
    return socialRequest('POST', "https://graph.facebook.com/v19.0/$pageId/feed", [
        'message'      => $caption,
        'link'         => $link,
        'access_token' => $token,
    ]);
}

/**
 * Publish to Instagram via two-step container + publish.
 */
function socialPublishInstagram(string $igUserId, string $token, string $imageUrl, string $caption): array
{
    $container = socialRequest('POST', "https://graph.facebook.com/v19.0/$igUserId/media", [
        'image_url'    => $imageUrl,
        'caption'      => $caption,
        'access_token' => $token,
    ]);
    if (!$container['ok'] || empty($container['data']['id'])) {
        return $container;
    }
    return socialRequest('POST', "https://graph.facebook.com/v19.0/$igUserId/media_publish", [
        'creation_id'  => $container['data']['id'],
        'access_token' => $token,
    ]);
}

/**
 * List Pages managed by a User Access Token, with each Page's own
 * page access token and linked Instagram Business Account.
 * Returns [] on failure.
 */
function socialDiscoverPages(string $userToken): array
{
    $r = socialRequest('GET', 'https://graph.facebook.com/v19.0/me/accounts', [
        'fields'       => 'id,name,access_token,instagram_business_account{id,username}',
        'limit'        => 50,
        'access_token' => $userToken,
    ]);
    if (empty($r['ok']) || empty($r['data']['data'])) {
        return ['ok' => false, 'error' => $r['error'] ?: 'ดึงรายการเพจไม่สำเร็จ', 'pages' => []];
    }
    $pages = [];
    foreach ($r['data']['data'] as $p) {
        $pages[] = [
            'id'           => (string)($p['id'] ?? ''),
            'name'         => (string)($p['name'] ?? ''),
            'access_token' => (string)($p['access_token'] ?? ''),
            'ig_id'        => (string)($p['instagram_business_account']['id'] ?? ''),
            'ig_username'  => (string)($p['instagram_business_account']['username'] ?? ''),
        ];
    }
    return ['ok' => true, 'error' => '', 'pages' => $pages];
}

/**
 * Verify config by calling /me on both page token and IG user id.
 */
function socialTestConnection(): array
{
    $out = ['fb' => null, 'ig' => null];

    if (defined('FB_PAGE_ACCESS_TOKEN') && FB_PAGE_ACCESS_TOKEN && defined('FB_PAGE_ID') && FB_PAGE_ID) {
        $out['fb'] = socialRequest('GET', 'https://graph.facebook.com/v19.0/' . FB_PAGE_ID, [
            'fields'       => 'id,name',
            'access_token' => FB_PAGE_ACCESS_TOKEN,
        ]);
    }
    if (defined('IG_USER_ID') && IG_USER_ID && defined('FB_PAGE_ACCESS_TOKEN') && FB_PAGE_ACCESS_TOKEN) {
        $out['ig'] = socialRequest('GET', 'https://graph.facebook.com/v19.0/' . IG_USER_ID, [
            'fields'       => 'id,username',
            'access_token' => FB_PAGE_ACCESS_TOKEN,
        ]);
    }
    return $out;
}
