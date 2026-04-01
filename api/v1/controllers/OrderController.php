<?php
class OrderController {
    private $conn;
    private $pdo;

    public function __construct($conn, $pdo) {
        $this->conn = $conn;
        $this->pdo = $pdo;
    }

    public function handleRequest($method, $params) {
        switch ($method) {
            case 'POST':
                $this->placeOrder();
                break;
            case 'GET':
                $this->listOrders();
                break;
            default:
                http_response_code(405);
                echo json_encode(["error" => "Method not allowed"]);
                break;
        }
    }

    private function listOrders() {
        // List orders logic (needs admin auth in real-world scenario)
        $query = "SELECT * FROM orders ORDER BY created_at DESC LIMIT 50";
        $res = mysqli_query($this->conn, $query);

        $data = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $data[] = $row;
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    private function placeOrder() {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid JSON input"]);
            return;
        }

        $items = $data["items"] ?? [];
        if (empty($items)) {
            http_response_code(400);
            echo json_encode(["error" => "No items in order"]);
            return;
        }

        $name = mysqli_real_escape_string($this->conn, $data['customer_name'] ?? 'N/A');
        $address = mysqli_real_escape_string($this->conn, $data['address'] ?? 'N/A');
        $city = mysqli_real_escape_string($this->conn, $data['city'] ?? 'N/A');
        $phone = mysqli_real_escape_string($this->conn, $data['phone'] ?? 'N/A');
        $payment = mysqli_real_escape_string($this->conn, $data['payment_method'] ?? 'cash');
        $total = (float)($data['total_amount'] ?? 0);

        mysqli_begin_transaction($this->conn);

        try {
            $order_query = "INSERT INTO orders (customer_name, address, city, phone, payment_method, total_amount, created_at) 
                            VALUES ('$name', '$address', '$city', '$phone', '$payment', $total, NOW())";
            mysqli_query($this->conn, $order_query);
            $order_id = mysqli_insert_id($this->conn);

            foreach ($items as $item) {
                $pid = (int)$item["product_id"];
                $qty = (int)$item["qty"];
                $price = (float)$item["price"];

                // Stock check
                $q = mysqli_query($this->conn, "SELECT stock FROM products WHERE id=$pid FOR UPDATE");
                $row = mysqli_fetch_assoc($q);

                if (!$row || $row["stock"] < $qty) {
                    throw new Exception("Not enough stock for product ID $pid");
                }

                mysqli_query($this->conn, "UPDATE products SET stock = stock - $qty WHERE id=$pid");
                $new_stock = $row["stock"] - $qty;

                // Log movement
                include_once "../../backend/stock_logger.php";
                log_stock_movement($this->conn, $pid, -$qty, $new_stock, 'ORDER', "Kunde: $name", "Bestellung #$order_id");

                mysqli_query($this->conn,
                    "INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase)
                     VALUES ($order_id, $pid, $qty, $price)"
                );
            }

            mysqli_commit($this->conn);
            echo json_encode(["success" => true, "order_id" => $order_id]);

        } catch (Exception $e) {
            mysqli_rollback($this->conn);
            http_response_code(400);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }
}
?>
