<?php
header('Content-Type: application/json');
require '../db.php';

try {
    $sql = "SELECT * FROM products";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $products = $stmt->fetchAll();

    // Transform data to match frontend structure (if needed) or send as is
    // The frontend expects: { id, category, img, names: { en, de, ar }, price, expiry }
    // The DB has: id, category, img, name_en, name_de, name_ar, price, expiry
    
    $transformed = [];
    foreach ($products as $p) {
        $transformed[] = [
            'id' => $p['id'],
            'category' => $p['category'],
            'img' => $p['img'],
            'names' => [
                'en' => $p['name_en'],
                'de' => $p['name_de'],
                'ar' => $p['name_ar']
            ],
            'price' => (float)$p['price'],
            'expiry' => $p['expiry']
        ];
    }

    echo json_encode($transformed);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
