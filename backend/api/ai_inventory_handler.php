<?php
header('Content-Type: application/json');
include dirname(__FILE__) . "/../../db.php";

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['api_key'])) {
    echo json_encode(['success' => false, 'error' => 'Missing API Key']);
    exit;
}

$apiKey = $data['api_key'];
$userQuery = isset($data['query']) ? $data['query'] : 'Analyze my inventory health.';

// Fetch Inventory Data Summary
// We want to send a concise summary to the AI to avoid hitting token limits
$sql = "SELECT name_de, stock, expiry FROM products";
$result = $conn->query($sql);

$inventoryData = [];
$stats = [
    'total_items' => 0,
    'low_stock' => 0,
    'out_of_stock' => 0,
    'expired' => 0,
    'expiring_soon' => 0
];

$today = date('Y-m-d');
$soon = date('Y-m-d', strtotime('+30 days'));

while ($row = $result->fetch_assoc()) {
    $stats['total_items']++;
    
    // Check Stock
    $status = 'OK';
    if ($row['stock'] == 0) {
        $stats['out_of_stock']++;
        $status = 'OUT_OF_STOCK';
    } elseif ($row['stock'] < 10) {
        $stats['low_stock']++;
        $status = 'LOW_STOCK';
    }

    // Check Expiry
    if ($row['expiry'] < $today) {
        $stats['expired']++;
        $status = ($status == 'OK') ? 'EXPIRED' : $status . ', EXPIRED';
    } elseif ($row['expiry'] < $soon) {
        $stats['expiring_soon']++;
        $status = ($status == 'OK') ? 'EXPIRING_SOON' : $status . ', EXPIRING_SOON';
    }

    // Only add problematic items or a sample to keep context small
    if ($status != 'OK') {
        $inventoryData[] = [
            'name' => $row['name_de'],
            'stock' => $row['stock'],
            'expiry' => $row['expiry'],
            'status' => $status
        ];
    }
}

// Construct Prompt
$systemPrompt = "You are an expert Inventory Manager AI for Matin Food. 
You have access to the current inventory stats and a list of problematic items (low stock, expired, etc.).
Your goal is to answer the user's questions about the inventory, suggest reorder priorities, and identify waste risks.
Keep answers professional, concise, and helpful. Language: German.";

$context = "Current Inventory Stats:\n" . json_encode($stats) . "\n\nProblematic Items (Sample):\n" . json_encode(array_slice($inventoryData, 0, 50));

$messages = [
    ['role' => 'system', 'content' => $systemPrompt],
    ['role' => 'user', 'content' => "Here is the current inventory situation:\n" . $context . "\n\nUser Question: " . $userQuery]
];

// Call OpenAI API
$ch = curl_init();
$postData = [
    'model' => 'gpt-3.5-turbo',
    'messages' => $messages,
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
