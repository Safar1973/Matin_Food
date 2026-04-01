<?php
class AiController {
    private $conn;
    private $pdo;

    public function __construct($conn, $pdo) {
        $this->conn = $conn;
        $this->pdo = $pdo;
    }

    public function handleRequest($method, $params) {
        switch ($method) {
            case 'POST':
                $this->generateDescription();
                break;
            default:
                http_response_code(405);
                echo json_encode(["error" => "Method not allowed"]);
                break;
        }
    }

    private function generateDescription() {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['product_name']) || !isset($data['api_key'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing parameters']);
            return;
        }

        $productName = $data['product_name'];
        $apiKey = $data['api_key'];

        $systemPrompt = "You are a helpful assistant for a grocery shop named Matin Food. Write appetizing descriptions.";
        $userPrompt = "Write a short, appetizing product description for '$productName' in German (de), English (en), and Arabic (ar). Return only JSON with keys description_de, description_en, description_ar.";

        $postData = [
            'contents' => [['parts' => [['text' => "$systemPrompt\n$userPrompt"]]]],
            'generationConfig' => ['response_mime_type' => 'application/json']
        ];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . urlencode($apiKey);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $result = curl_exec($ch);
        $response = json_decode($result, true);

        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            $content = $response['candidates'][0]['content']['parts'][0]['text'];
            echo json_encode(['success' => true, 'data' => json_decode($content, true)]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $response['error']['message'] ?? 'API Error']);
        }
        curl_close($ch);
    }
}
?>
