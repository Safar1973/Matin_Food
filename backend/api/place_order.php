<?php
header('Content-Type: application/json');
require '../db.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Insert Order
    $stmt = $pdo->prepare("INSERT INTO orders (customer_name, address, city, phone, payment_method, total_amount) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $input['customer']['name'],
        $input['customer']['address'],
        $input['customer']['city'],
        $input['customer']['phone'],
        $input['paymentMethod'],
        $input['total']
    ]);
    
    $orderId = $pdo->lastInsertId();

    // 2. Insert Order Items
    $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)");
    
    foreach ($input['items'] as $item) {
        $stmtItem->execute([
            $orderId,
            $item['product']['id'],
            $item['qty'],
            $item['product']['price']
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'orderId' => $orderId]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
