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
$prompt = "Write a short, appetizing product description (max 2 sentences) for a food item named '$productName' in 3 languages: German (de), English (en), and Arabic (ar). Return ONLY a valid JSON object with keys: description_de, description_en, description_ar.";

$ch = curl_init();

$postData = [
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        ['role' => 'system', 'content' => 'You are a helpful assistant for a grocery shop. You output only JSON.'],
        ['role' => 'user', 'content' => $prompt]
    ],    'temperature' => 0.7
];

curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
// XAMPP Fix: Disable SSL Check
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$result = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(['success' => false, 'error' => 'cURL Error: ' . curl_error($ch)]);
    exit;
}

if (curl_errno($ch)) {
    echo json_encode(['success' => false, 'error' => curl_error($ch)]);
} else {
    $response = json_decode($result, true);
    if (isset($response['choices'][0]['message']['content'])) {
        $content = $response['choices'][0]['message']['content'];
        // Try to parse the content as JSON (sometimes AI adds markdown)
        $cleanContent = str_replace(['```json', '```'], '', $content);
        $json = json_decode($cleanContent, true);
        
        if ($json) {
            echo json_encode(['success' => true, 'data' => $json]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to parse AI response', 'raw' => $content]);
        }
    } else {
        $errorMsg = 'Unbekannter OpenAI Fehler';
        if (isset($response['error']['message'])) {
            $errorMsg = $response['error']['message'];
        }
        // Specific debug info for "Bad Request" or similar
        if (!$response && $result) {
            $errorMsg = 'Ungültige Server-Antwort (evtl. Bad Request): ' . substr($result, 0, 150);
        }
        echo json_encode(['success' => false, 'error' => 'OpenAI API Error: ' . $errorMsg, 'raw' => $response ? $response : $result]);
    }
}

curl_close($ch);
?>
                