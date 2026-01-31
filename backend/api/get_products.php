<?php
header("Content-Type: application/json; charset=utf-8");
include "../../db.php";

$res = mysqli_query($conn, "SELECT * FROM products");

if ($res === false) {
    echo json_encode([
        "error" => mysqli_error($conn)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = [];
while ($row = mysqli_fetch_assoc($res)) {
    $data[] = $row;
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
$lang = $_GET["lang"] ?? "de";
$nameField = "name_" . $lang;

$res = mysqli_query($conn,
  "SELECT id, $nameField AS name, price, stock FROM products"
);
