<?php
header("Content-Type: application/json; charset=utf-8");
include "../../db.php";

$data = json_decode(file_get_contents("php://input"), true);
$items = $data["items"] ?? [];

if (empty($items)) {
    echo json_encode(["error" => "No items"]);
    exit;
}

$name = mysqli_real_escape_string($conn, $data['name'] ?? 'N/A');
$address = mysqli_real_escape_string($conn, $data['address'] ?? 'N/A');
$city = mysqli_real_escape_string($conn, $data['city'] ?? 'N/A');
$phone = mysqli_real_escape_string($conn, $data['phone'] ?? 'N/A');
$payment = mysqli_real_escape_string($conn, $data['payment_method'] ?? 'cash');
$total = (float)($data['total_amount'] ?? 0);

mysqli_begin_transaction($conn);

try {
    $order_query = "INSERT INTO orders (customer_name, address, city, phone, payment_method, total_amount, created_at) 
                    VALUES ('$name', '$address', '$city', '$phone', '$payment', $total, NOW())";
    mysqli_query($conn, $order_query);
    $order_id = mysqli_insert_id($conn);

    foreach ($items as $item) {
        $pid = (int)$item["product_id"];
        $qty = (int)$item["qty"];
        $price = (float)$item["price"];

        // Stock check
        $q = mysqli_query($conn, "SELECT stock FROM products WHERE id=$pid FOR UPDATE");
        $row = mysqli_fetch_assoc($q);

        if (!$row || $row["stock"] < $qty) {
            throw new Exception("Not enough stock for product ID $pid");
        }

        mysqli_query($conn, "UPDATE products SET stock = stock - $qty WHERE id=$pid");

        mysqli_query($conn,
            "INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase)
             VALUES ($order_id, $pid, $qty, $price)"
        );
    }

    mysqli_commit($conn);
    echo json_encode(["success" => true, "order_id" => $order_id]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
