<?php
// Minimal AI Test Script
header('Content-Type: application/json');

$apiKey = isset($_COOKIE['openai_key']) ? trim($_COOKIE['openai_key']) : null;

if (!$apiKey) {
    echo json_encode(['error' => 'No API key found in cookies']);
    exit;
}

// Minimal test request
$ch = curl_init();
$postData = [
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        ['role' => 'system', 'content' => 'You are a helpful assistant.'],
        ['role' => 'user', 'content' => 'Say hello']
    ],
    'temperature' => 0.7
];

curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo json_encode(['error' => curl_error($ch)]);
} else {
    $response = json_decode($result, true);
    echo json_encode([
        'http_code' => $httpCode,
        'api_key_prefix' => substr($apiKey, 0, 10) . '...',
        'response' => $response
    ]);
}
curl_close($ch);
?>
