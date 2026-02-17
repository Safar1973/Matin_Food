<?php
header('Content-Type: application/json');

$apiKey = isset($_COOKIE['gemini_key']) ? trim($_COOKIE['gemini_key']) : null;

if (!$apiKey) {
    echo json_encode(['error' => 'No Gemini API key found in cookies']);
    exit;
}

$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . urlencode($apiKey);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo json_encode(['error' => curl_error($ch)]);
} else {
    echo $result;
}
curl_close($ch);
?>
