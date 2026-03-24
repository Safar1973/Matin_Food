<?php
session_start();
header('Content-Type: application/json');
include dirname(__FILE__) . "/../../db.php";
include_once dirname(__FILE__) . "/../stock_logger.php";

if (!isset($_SESSION["admin"])) {
    echo json_encode(['success' => false, 'error' => 'Nicht autorisiert']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['product_id']) || !isset($data['new_stock'])) {
    echo json_encode(['success' => false, 'error' => 'Fehlende Daten']);
    exit;
}

$id = (int)$data['product_id'];
$new_stock = (int)$data['new_stock'];
$admin_name = $_SESSION["admin"];
$reason = isset($data['reason']) ? $data['reason'] : 'Manuelle Korrektur';

// Get current stock
$q = mysqli_query($conn, "SELECT stock FROM products WHERE id = $id");
$row = mysqli_fetch_assoc($q);

if (!$row) {
    echo json_encode(['success' => false, 'error' => 'Produkt nicht gefunden']);
    exit;
}

$old_stock = $row['stock'];
$change = $new_stock - $old_stock;

if ($change == 0) {
    echo json_encode(['success' => true, 'message' => 'Keine Änderung']);
    exit;
}

// Update stock
$sql = "UPDATE products SET stock = $new_stock WHERE id = $id";
if (mysqli_query($conn, $sql)) {
    // Log movement
    log_stock_movement($conn, $id, $change, $new_stock, 'MANUAL_UPDATE', $admin_name, $reason);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>
