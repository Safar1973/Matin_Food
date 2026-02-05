<?php
header('Content-Type: application/json');
include dirname(__FILE__) . "/../../db.php";

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['api_key'])) {
    echo json_encode(['success' => false, 'error' => 'Missing API Key']);
    exit;
}

$apiKey = $data['api_key'];
$userQuery = isset($data['query']) ? $data['query'] : 'How does this system work?';

// System Prompt for Admin Help
$systemPrompt = "You are the expert Administrator Guide for the Matin Food Admin Panel.
Your goal is to help the admin understand how to use the system.

System Capabilities:
1. **Lagerverwaltung (Inventory)**: View stock, see expired items (red cards), low stock (yellow cards).
2. **AI Generator**: Generate product descriptions in DE/EN/AR automatically.
3. **Database Setup**: Reset the database (use with caution).
4. **Help (this page)**: Ask questions about the system.

Common Tasks:
- **Add Product**: Go to Lagerverwaltung -> Click '+ Neues Produkt'.
- **Edit Product**: Currently need to edit directly in DB or use future edit features.
- **Generate Text**: Go to AI Generator -> Select Product -> Click Generate -> Save.

Tone: Professional, helpful, concise. Language: German.";

$messages = [
    ['role' => 'system', 'content' => $systemPrompt],
    ['role' => 'user', 'content' => $userQuery]
];

// Call OpenAI API
$ch = curl_init();
$postData = [
    'model' => 'gpt-3.5-turbo',
    'messages' => $messages,
    'temperature' => 0.5
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

if (curl_errno($ch)) {
    echo json_encode(['success' => false, 'error' => 'cURL Error: ' . curl_error($ch)]);
} else {
    $response = json_decode($result, true);
    if (isset($response['choices'][0]['message']['content'])) {
        echo json_encode(['success' => true, 'response' => $response['choices'][0]['message']['content']]);
    } else {
        $err = isset($response['error']['message']) ? $response['error']['message'] : 'Unknown AI Error';
        echo json_encode(['success' => false, 'error' => $err]);
    }
}
curl_close($ch);
?>
