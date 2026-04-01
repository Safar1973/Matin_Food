<?php
class ProductController {
    private $conn;
    private $pdo;

    public function __construct($conn, $pdo) {
        $this->conn = $conn;
        $this->pdo = $pdo;
    }

    public function handleRequest($method, $params) {
        switch ($method) {
            case 'GET':
                if (isset($params[1])) {
                    if ($params[1] === 'count') {
                        $this->getProductCount();
                    } else {
                        $this->getProduct($params[1]);
                    }
                } else {
                    $this->listProducts();
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(["error" => "Method not allowed"]);
                break;
        }
    }

    private function getProductCount() {
        $res = mysqli_query($this->conn, "SELECT COUNT(*) as count FROM products");
        $row = mysqli_fetch_assoc($res);
        echo json_encode(["success" => true, "count" => (int)$row['count']]);
    }

    private function listProducts() {
        $lang = $_GET["lang"] ?? "de";
        $valid_langs = ['de', 'en', 'ar'];
        if (!in_array($lang, $valid_langs)) {
            $lang = 'de';
        }

        $nameField = "name_" . $lang;
        $query = "SELECT id, category, img, name_en, name_de, name_ar, description_de, description_en, description_ar, $nameField AS name, price, weight, expiry, stock, discount FROM products";
        $res = mysqli_query($this->conn, $query);

        if ($res === false) {
            http_response_code(500);
            echo json_encode(["success" => false, "error" => mysqli_error($this->conn)]);
            return;
        }

        $data = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $data[] = $row;
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    private function getProduct($id) {
        $id = (int)$id;
        $query = "SELECT * FROM products WHERE id = $id";
        $res = mysqli_query($this->conn, $query);
        $product = mysqli_fetch_assoc($res);

        if (!$product) {
            http_response_code(404);
            echo json_encode(["error" => "Product not found"]);
            return;
        }

        echo json_encode($product, JSON_UNESCAPED_UNICODE);
    }
}
?>
