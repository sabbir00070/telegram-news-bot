<?php

$botToken = "8227631061:AAGOFVi-R2f-1JZWl73wPMyOAoF8krNNaUk";
$chatId   = "-1003553733506";
$apiUrl   = "https://news-api-kohl-delta.vercel.app/jamuna.tv";
$storage  = "sent.json";

if (!file_exists($storage)) {
    file_put_contents($storage, json_encode([]));
}

$sent = json_decode(file_get_contents($storage), true);
if (!is_array($sent)) {
    $sent = [];
}

$response = @file_get_contents($apiUrl);
if (!$response) {
    exit("API fetch failed");
}

$data = json_decode($response, true);

if (empty($data['success']) || empty($data['news'])) {
    exit("Invalid API response");
}

foreach ($data['news'] as $item) {

    if (in_array($item['id'], $sent)) {
        continue;
    }

    $id       = $item['id'];
    $url      = $item['url'] ?? '';
    $title    = htmlspecialchars($item['title'] ?? '');
    $photo    = $item['image'] ?? '';
    $reporter = htmlspecialchars($item['reporter'] ?? '');
    $time     = str_replace('প্রকাশ: ', '', $item['time'] ?? '');
    $body     = strip_tags($item['body'] ?? '');
    $body     = mb_substr($body, 0, 800);

    $caption  = "<b>$title</b>\n\n";
    $caption .= "🕒 $time\n";
    $caption .= "👤 $reporter\n\n";
    $caption .= "$body";

    sendToTelegram($botToken, $chatId, $photo, $caption, $url);

    $sent[] = $id;
}

file_put_contents($storage, json_encode($sent));

echo "Completed";

function sendToTelegram($token, $chatId, $photo, $caption, $url)
{
    $keyboard = [
        "inline_keyboard" => [
            [
                ["text" => "🌐 Visit Website", "url" => $url]
            ]
        ]
    ];

    $params = [
        "chat_id" => $chatId,
        "photo" => $photo,
        "caption" => $caption,
        "parse_mode" => "HTML",
        "reply_markup" => json_encode($keyboard)
    ];

    $query = http_build_query($params);

    file_get_contents("https://api.telegram.org/bot{$token}/sendPhoto?$query");
}

?>