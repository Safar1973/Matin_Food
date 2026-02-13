<?php
header('Content-Type: application/json');
include dirname(__FILE__) . "/../../db.php";

$data = json_decode(file_get_contents('php://input'), true);

// Get API Key from request or cookie
$apiKey = isset($data['api_key']) ? $data['api_key'] : (isset($_COOKIE['openai_key']) ? $_COOKIE['openai_key'] : null);

if (!$apiKey) {
    echo json_encode(['success' => false, 'error' => 'Kein API Key konfiguriert.']);
    exit;
}

$userQuery = isset($data['query']) ? $data['query'] : 'Hallo';
$history = isset($data['history']) ? $data['history'] : [];

// System Prompt for Customer Support
$systemPrompt = "Du bist der freundliche KI-Assistent von Matin Food. 
Deine Aufgabe ist es, Kunden im Online-Shop zu beraten.
Informationen über Matin Food:
- Wir verkaufen hochwertige orientalische Lebensmittel, Gewürze, Reis und Spezialitäten.
- Versandkosten: Ab 50€ kostenlos, darunter 4,90€.
- Zahlungsmethoden: PayPal, Visa, Mastercard.
- Tonfall: Sehr höflich, hilfsbereit und professionell.
- Sprache: Antworte immer in der Sprache des Kunden (meist Deutsch, Englisch oder Arabisch).

Wenn der Kunde nach Rezepten fragt, schlage orientalische Gerichte vor, für die wir Zutaten haben (z.B. Makdous, Hummus, Reisgerichte).";

$messages = [['role' => 'system', 'content' => $systemPrompt]];

// Add history
foreach ($history as $msg) {
    $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
}

// Add current query
$messages[] = ['role' => 'user', 'content' => $userQuery];

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
        $err = isset($response['error']['message']) ? $response['error']['message'] : 'Unbekannter KI-Fehler';
        if (!$response && $result) $err = 'Ungültige Server-Antwort (Bad Request?): ' . substr($result, 0, 150);
        echo json_encode(['success' => false, 'error' => $err, 'raw' => $response ? $response : $result]);
    }
}
curl_close($ch);
?>
