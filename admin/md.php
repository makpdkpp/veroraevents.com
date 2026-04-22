<?php
function parseFrontmatter(string $raw): array
{
    $data = [];
    $body = $raw;
    if (preg_match('/^---\r?\n(.*?)\r?\n---\r?\n(.*)/s', ltrim($raw), $m)) {
        $body = $m[2];
        foreach (explode("\n", $m[1]) as $line) {
            $line = rtrim($line, "\r");
            if (preg_match('/^([\w_]+):\s*(.*)$/', $line, $kv)) {
                $val = trim($kv[2], " \t\"'");
                if ($val === 'true')  $val = true;
                if ($val === 'false') $val = false;
                $data[$kv[1]] = $val;
            }
        }
    }
    return ['data' => $data, 'body' => trim($body)];
}

function mdSafeUrl(string $url): string
{
    $url = trim($url);
    if ($url === '') return '';
    // Allow only safe schemes, relative, protocol-relative, or anchor URLs.
    if (preg_match('#^(https?:|mailto:|tel:)#i', $url)) return htmlspecialchars($url, ENT_QUOTES);
    if ($url[0] === '/' || $url[0] === '#' || $url[0] === '?') return htmlspecialchars($url, ENT_QUOTES);
    if (strpos($url, '//') === 0) return htmlspecialchars('https:' . $url, ENT_QUOTES);
    // Anything else (javascript:, data:, vbscript:, file:, ...) is rejected.
    return '';
}

function markdownToHtml(string $md): string
{
    $codes = [];
    // 1) Extract fenced code blocks with sentinels (content already escaped).
    $md = preg_replace_callback('/```\w*\n(.*?)```/s', function ($m) use (&$codes) {
        $k = "\x02" . count($codes) . "\x03";
        $codes[$k] = '<pre><code>' . htmlspecialchars($m[1], ENT_QUOTES) . '</code></pre>';
        return "\n$k\n";
    }, $md);

    // 2) Escape ALL remaining HTML/JS in the body up-front. Markdown transforms
    //    below then produce safe tags on top of escaped text — no raw HTML
    //    from user input can ever reach the browser.
    $md = htmlspecialchars($md, ENT_QUOTES, 'UTF-8');

    // 3) Inline code.
    $md = preg_replace_callback('/`([^`\n]+)`/', function ($m) {
        return '<code>' . $m[1] . '</code>';
    }, $md);

    // 4) Headings.
    $md = preg_replace_callback('/^(#{1,6})\s+(.+?)$/m', function ($m) {
        $n = strlen($m[1]);
        return "<h{$n}>{$m[2]}</h{$n}>";
    }, $md);

    $md = preg_replace('/^[ \t]*[-*_]{3,}[ \t]*$/m', '<hr>', $md);
    $md = preg_replace('/^&gt;\s+(.+)$/m', '<blockquote><p>$1</p></blockquote>', $md);
    $md = preg_replace('/\*\*\*(.+?)\*\*\*/s', '<strong><em>$1</em></strong>', $md);
    $md = preg_replace('/\*\*(.+?)\*\*/s',      '<strong>$1</strong>', $md);
    $md = preg_replace('/\*([^*\n]+?)\*/s',      '<em>$1</em>', $md);

    // 5) Images + links — validate URL scheme, drop unsafe.
    $md = preg_replace_callback('/!\[([^\]]*)\]\(([^)]+)\)/', function ($m) {
        $url = mdSafeUrl(html_entity_decode($m[2], ENT_QUOTES, 'UTF-8'));
        if ($url === '') return $m[1];
        return '<img src="' . $url . '" alt="' . $m[1] . '" loading="lazy">';
    }, $md);
    $md = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', function ($m) {
        $url = mdSafeUrl(html_entity_decode($m[2], ENT_QUOTES, 'UTF-8'));
        if ($url === '') return $m[1];
        return '<a href="' . $url . '" rel="noopener noreferrer">' . $m[1] . '</a>';
    }, $md);

    $md = preg_replace_callback('/((?:^[ \t]*[-*+] .+(?:\n|$))+)/m', function ($m) {
        $items = preg_replace('/^[ \t]*[-*+] (.+)/m', '<li>$1</li>', trim($m[0]));
        return "<ul>\n$items\n</ul>\n";
    }, $md);

    $md = preg_replace_callback('/((?:^[ \t]*\d+\. .+(?:\n|$))+)/m', function ($m) {
        $items = preg_replace('/^[ \t]*\d+\. (.+)/m', '<li>$1</li>', trim($m[0]));
        return "<ol>\n$items\n</ol>\n";
    }, $md);

    $blocks = preg_split('/\n{2,}/', $md);
    $result = [];
    foreach ($blocks as $block) {
        $block = trim($block);
        if ($block === '') continue;
        $isBlock = (bool) preg_match('/^(<h[1-6]|<ul|<ol|<blockquote|<hr|<pre|\x02)/', $block);
        $result[] = $isBlock ? $block : '<p>' . str_replace("\n", ' ', $block) . '</p>';
    }
    return strtr(implode("\n\n", $result), $codes);
}
