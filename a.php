<?php

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => "https://jamuna.tv/archive",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER => [
        "Upgrade-Insecure-Requests: 1",
        "User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36",
        "sec-ch-ua: \"Chromium\";v=\"137\", \"Not/A)Brand\";v=\"24\"",
        "sec-ch-ua-mobile: ?1",
        "sec-ch-ua-platform: \"Android\""
    ]
]);

$response = curl_exec($ch);
curl_close($ch);

echo $response;