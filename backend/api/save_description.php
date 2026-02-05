<?php
header('Content-Type: application/json');
include dirname(__FILE__) . "/../../db.php";

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['product_id']) || !isset($data['descriptions'])) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

$id = (int)$data['product_id'];
$desc = $data['descriptions'];

$sql = "UPDATE products SET 
        description_de = ?, 
        description_en = ?, 
        description_ar = ? 
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi", $desc['de'], $desc['en'], $desc['ar'], $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
?>
