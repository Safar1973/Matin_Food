<?php
header("Content-Type: application/json; charset=utf-8");
include "../../db.php";

$data = json_decode(file_get_contents("php://input"), true);
$items = $data["items"] ?? [];

if (empty($items)) {
    echo json_encode(["error" => "No items"]);
    exit;
}

mysqli_begin_transaction($conn);

try {
    mysqli_query($conn, "INSERT INTO orders (created_at) VALUES (NOW())");
    $order_id = mysqli_insert_id($conn);

    foreach ($items as $item) {
        $pid = (int)$item["product_id"];
        $qty = (int)$item["qty"];

        // تحقق من المخزون
        $q = mysqli_query($conn, "SELECT stock FROM products WHERE id=$pid FOR UPDATE");
        $row = mysqli_fetch_assoc($q);

        if (!$row || $row["stock"] < $qty) {
            throw new Exception("Not enough stock");
        }

        mysqli_query($conn,
            "UPDATE products SET stock = stock - $qty WHERE id=$pid"
        );

        mysqli_query($conn,
            "INSERT INTO order_items (order_id, product_id, quantity)
             VALUES ($order_id, $pid, $qty)"
        );
    }

    mysqli_commit($conn);
    echo json_encode(["success" => true, "order_id" => $order_id]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
