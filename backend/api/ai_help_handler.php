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

$systemPrompt = "You are the expert Administrator Guide for the Matin Food Admin Panel.
Your goal is to help the admin understand how to use the system.

System Capabilities:
1. **Lagerverwaltung Pro**: Zentrale Bestandsführung, automatisierte Verfallsüberwachung und Bestandswarnungen.
2. **AI Generator**: Erstellt Produktbeschreibungen in DE/EN/AR automatisch.
3. **Database Setup**: Zurücksetzen der Datenbank.
4. **Hilfe & Assistenz**: Dieses Modul für Fragen zum System.

Wichtige Funktionen:
- **MHD-Überwachung**: Rote Karten bedeuten abgelaufen, gelb bedeutet bald abgelaufen (30 Tage).
- **Bestandswarnung**: Automatischer Alarm bei niedrigem Bestand (<10 Stück).

Tonfall: Professionell, hilfreich, kurz gefasst. Sprache: Deutsch.";

$contents = [
    [
        'role' => 'user',
        'parts' => [['text' => $userQuery]]
    ]
];

$postData = [
    'system_instruction' => [
        'parts' => [['text' => $systemPrompt]]
    ],
    'contents' => $contents,
    'generationConfig' => [
        'temperature' => 0.4,
        'maxOutputTokens' => 1000
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
        echo json_encode(['success' => true, 'response' => $response['candidates'][0]['content']['parts'][0]['text']]);
    } else {
        $err = isset($response['error']['message']) ? $response['error']['message'] : 'Unbekannter Gemini Fehler';
        echo json_encode(['success' => false, 'error' => $err]);
    }
}
curl_close($ch);
?>
