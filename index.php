<?php

require 'helper.php';

function newsID(string $url): int
{
    $parts = explode('/', trim($url, '/'));
    return (int) end($parts);
}

function defaultHeaders(): array
{
    return [
        "User-Agent: Mozilla/5.0 (Linux; Android 10)",
        "Accept: text/html,application/xhtml+xml",
        "Upgrade-Insecure-Requests: 1"
    ];
}

function fetchArchiveLinks(): array
{
    $ch = curl_init('https://jamuna.tv/archive');

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => defaultHeaders()
    ]);

    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status !== 200 || empty($response)) {
        return [];
    }

    $html = str_get_html($response);
    if (!$html) {
        return [];
    }

    $links = [];
    foreach ($html->find('a.linkOverlay') as $a) {
        if (!empty($a->href)) {
            $links[] = $a->href;
        }
    }

    $html->clear();
    unset($html);

    return array_values(array_unique($links));
}

function multiFetch(array $urls): array
{
    $multi = curl_multi_init();
    $channels = [];
    $results = [];

    foreach ($urls as $url) {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => defaultHeaders()
        ]);

        curl_multi_add_handle($multi, $ch);
        $channels[$url] = $ch;
    }

    do {
        curl_multi_exec($multi, $running);
        curl_multi_select($multi);
    } while ($running > 0);

    foreach ($channels as $url => $ch) {
        $content = curl_multi_getcontent($ch);

        if (!empty($content)) {
            $parsed = parseNews($url, $content);
            if (!empty($parsed)) {
                $results[] = $parsed;
            }
        }

        curl_multi_remove_handle($multi, $ch);
        curl_close($ch);
    }

    curl_multi_close($multi);

    return $results;
}

function parseNews(string $url, string $htmlContent): array
{
    $html = str_get_html($htmlContent);
    if (!$html) {
        return [];
    }

    $news = [
        'id'  => newsID($url),
        'url' => $url
    ];

    $headline = $html->find('.detailHeadline', 0);
    $photo    = $html->find('.detailPhoto img', 0);
    $body     = $html->find('.desktopDetailBody', 0);
    $reporter = $html->find('.detailReporter', 0);
    $time     = $html->find('.detailPTime', 0);

    $news['title'] = $headline 
        ? html_entity_decode(trim($headline->plaintext)) 
        : null;

    $news['photo'] = $photo 
        ? $photo->src 
        : null;

    $news['reporter'] = $reporter 
        ? html_entity_decode(trim($reporter->plaintext)) 
        : null;

    if ($time) {
        $cleanTime = str_replace('প্রকাশ: ', '', trim($time->plaintext));
        $news['time'] = html_entity_decode($cleanTime);
    } else {
        $news['time'] = null;
    }

    if ($body) {
        $paragraphs = [];

        foreach ($body->find('p') as $p) {
            $text = trim($p->plaintext);

            if ($text === '') {
                continue;
            }

            if (preg_match('/^\/.+$/u', $text)) {
                continue;
            }

            $paragraphs[] = html_entity_decode($text);
        }

        $news['body'] = implode("\n\n", $paragraphs);
    } else {
        $news['body'] = null;
    }

    $html->clear();
    unset($html);

    return $news;
}

function getLatestNews(): array
{
    $links = fetchArchiveLinks();

    if (empty($links)) {
        return [
            'status'  => false,
            'total'   => 0,
            'message' => 'No news found',
            'data'    => []
        ];
    }

    $articles = multiFetch($links);

    return [
        'status' => true,
        'total'  => count($articles),
        'data'   => $articles
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(
    getLatestNews(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);