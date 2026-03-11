<?php
header('Content-Type: application/json');
include dirname(__FILE__) . "/../../db.php";

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['product_name']) || !isset($data['api_key'])) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

$productName = $data['product_name'];
$apiKey = $data['api_key'];

// System Prompt
$systemPrompt = "You are a helpful assistant for a grocery shop named Matin Food. 
Your goal is to write appetizing product descriptions. 
Always return ONLY a valid JSON object.";

$userPrompt = "Write a short, appetizing product description (max 2 sentences) for a food item named '$productName' in 3 languages: German (de), English (en), and Arabic (ar). 
Requirement: Return ONLY a valid JSON object with keys: description_de, description_en, description_ar.";

$contents = [
    [
        'role' => 'user',
        'parts' => [['text' => $userPrompt]]
    ]
];

$postData = [
    'system_instruction' => [
        'parts' => [['text' => $systemPrompt]]
    ],
    'contents' => $contents,
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 500,
        'response_mime_type' => 'application/json'
    ]
];

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . urlencode($apiKey);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$result = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(['success' => false, 'error' => 'cURL Error: ' . curl_error($ch)]);
} else {
    $response = json_decode($result, true);
    if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
        $content = $response['candidates'][0]['content']['parts'][0]['text'];
        $json = json_decode($content, true);
        
        if ($json) {
            echo json_encode(['success' => true, 'data' => $json]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to parse AI response', 'raw' => $content]);
        }
    } else {
        $err = isset($response['error']['message']) ? $response['error']['message'] : 'Unbekannter Gemini Fehler';
        echo json_encode(['success' => false, 'error' => 'Gemini API Error: ' . $err]);
    }
}
curl_close($ch);
?>