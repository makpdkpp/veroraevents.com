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

function markdownToHtml(string $md): string
{
    $codes = [];
    $md = preg_replace_callback('/```\w*\n(.*?)```/s', function ($m) use (&$codes) {
        $k = "\x02" . count($codes) . "\x03";
        $codes[$k] = '<pre><code>' . htmlspecialchars($m[1], ENT_QUOTES) . '</code></pre>';
        return "\n$k\n";
    }, $md);

    $md = preg_replace('/`([^`\n]+)`/', '<code>$1</code>', $md);

    $md = preg_replace_callback('/^(#{1,6})\s+(.+?)$/m', function ($m) {
        $n = strlen($m[1]);
        return "<h{$n}>{$m[2]}</h{$n}>";
    }, $md);

    $md = preg_replace('/^[ \t]*[-*_]{3,}[ \t]*$/m', '<hr>', $md);
    $md = preg_replace('/^>\s+(.+)$/m', '<blockquote><p>$1</p></blockquote>', $md);
    $md = preg_replace('/\*\*\*(.+?)\*\*\*/s', '<strong><em>$1</em></strong>', $md);
    $md = preg_replace('/\*\*(.+?)\*\*/s',      '<strong>$1</strong>', $md);
    $md = preg_replace('/\*([^*\n]+?)\*/s',      '<em>$1</em>', $md);
    $md = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1" loading="lazy">', $md);
    $md = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $md);

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
