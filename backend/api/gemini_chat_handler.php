<?php
header('Content-Type: application/json');
include dirname(__FILE__) . "/../../db.php";

// Set time limit for long API calls
set_time_limit(60);

$data = json_decode(file_get_contents('php://input'), true);

// Get API Key from request or cookie
$apiKey = isset($data['api_key']) ? $data['api_key'] : (isset($_COOKIE['gemini_key']) ? $_COOKIE['gemini_key'] : null);
$apiKey = $apiKey ? trim($apiKey) : null;

if (!$apiKey) {
    echo json_encode(['success' => false, 'error' => 'Kein Gemini API Key konfiguriert. Bitte geben Sie Ihren Google API Key ein.']);
    exit;
}

$userQuery = isset($data['query']) ? $data['query'] : 'Hallo';
$history = isset($data['history']) ? $data['history'] : [];

// --- Fetch Product Context (Optimized) ---
$productContext = "";
try {
    $productsByCategory = [];
    $prodRes = $conn->query("SELECT name_de, price, category FROM products WHERE name_de IS NOT NULL AND name_de != '' ORDER BY category, price");
    if ($prodRes) {
        while ($row = $prodRes->fetch_assoc()) {
            $name = trim($row['name_de']);
            if (!empty($name)) {
                $price = number_format((float)$row['price'], 2);
                $cat = trim($row['category']);
                if (!isset($productsByCategory[$cat])) {
                    $productsByCategory[$cat] = [];
                }
                $productsByCategory[$cat][] = "$name ({$price}€)";
            }
        }
    }

    if (!empty($productsByCategory)) {
        $productContext = "Sortiment:\n";
        foreach ($productsByCategory as $cat => $products) {
            $productContext .= "[$cat]: " . implode(", ", $products) . ".\n";
        }
    }
} catch (Exception $e) {
    // Silent fail for context
}

// System Prompt
$systemPrompt = "Du bist Matin AI, der Einkaufsassistent für Matin Food (orientalische Lebensmittel, Oberhausen).

WICHTIG: Durchsuche das Sortiment und empfehle passende Produkte mit Preisen.

$productContext

Regeln:
- Antworte in der Sprache des Kunden (DE/EN/AR)
- Nenne immer Produktnamen und Preise
- Versand: ab 50€ gratis, sonst 4,90€
- Sei höflich und prägnant";

// Build Gemini conversation format (v1beta compatible)
$contents = [];

// Add history
foreach ($history as $msg) {
    if (isset($msg['role']) && isset($msg['content']) && !empty($msg['content'])) {
        $role = ($msg['role'] === 'bot' || $msg['role'] === 'model') ? 'model' : 'user';
        $contents[] = [
            'role' => $role,
            'parts' => [['text' => $msg['content']]]
        ];
    }
}

// Add current query
$contents[] = [
    'role' => 'user',
    'parts' => [['text' => $userQuery]]
];

// Call Google Gemini API
$ch = curl_init();
$postData = [
    'system_instruction' => [
        'parts' => [['text' => $systemPrompt]]
    ],
    'contents' => $contents,
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 1000
    ]
];

// Use v1beta for system_instruction support and gemini-flash-latest (as seen in the model list)
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=" . urlencode($apiKey);

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo json_encode(['success' => false, 'error' => 'cURL Fehler: ' . curl_error($ch)]);
} else {
    $response = json_decode($result, true);
    
    if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
        $aiResponse = $response['candidates'][0]['content']['parts'][0]['text'];
        echo json_encode(['success' => true, 'response' => $aiResponse]);
    } else {
        $err = 'Unbekannter Gemini-Fehler';
        if (isset($response['error']['message'])) {
            $err = $response['error']['message'];
        } elseif ($httpCode !== 200) {
            $err = "Gemini API Fehler (HTTP $httpCode)";
        }
        
        echo json_encode([
            'success' => false, 
            'error' => $err,
            'debug_code' => $httpCode,
            'raw_response' => $response
        ]);
    }
}
curl_close($ch);
?>
