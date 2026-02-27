<?php
header("Content-Type: application/json; charset=utf-8");
include_once dirname(__FILE__) . "/../../db.php";

$lang = $_GET["lang"] ?? "de";
$valid_langs = ['de', 'en', 'ar'];
if (!in_array($lang, $valid_langs)) {
    $lang = 'de';
}

$nameField = "name_" . $lang;

$query = "SELECT id, category, img, name_en, name_de, name_ar, description_de, description_en, description_ar, $nameField AS name, price, weight, expiry, stock FROM products";
$res = mysqli_query($conn, $query);

if ($res === false) {
    echo json_encode([
        "success" => false,
        "error" => mysqli_error($conn)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = [];
while ($row = mysqli_fetch_assoc($res)) {
    $data[] = $row;
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>
