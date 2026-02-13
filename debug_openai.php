<?php
header('Content-Type: text/plain');

$apiKey = isset($_GET['key']) ? $_GET['key'] : (isset($_COOKIE['openai_key']) ? $_COOKIE['openai_key'] : '');
$model = isset($_GET['model']) ? $_GET['model'] : 'gpt-3.5-turbo';

if (!$apiKey) {
    echo "Error: No API Key provided as ?key=... or in cookie.\n";
    exit;
}

echo "Testing OpenAI Connection...\n";
echo "Model: $model\n";
echo "Key Snippet: " . substr($apiKey, 0, 7) . "...\n\n";

$ch = curl_init();
$postData = [
    'model' => $model,
    'messages' => [
        ['role' => 'user', 'content' => 'Say "Hello Matin Food"']
    ]
];

curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . trim($apiKey)
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

echo "HTTP Code: $httpCode\n";
if ($error) echo "cURL Error: $error\n";
echo "\nRaw Response:\n";
echo $result;

curl_close($ch);
?>
