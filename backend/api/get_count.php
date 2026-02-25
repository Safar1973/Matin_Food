<?php
header('Content-Type: application/json');
require_once '../../db.php';

$response = ['success' => false];

try {
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM products");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $response['success'] = true;
        $response['count'] = (int)$row['total'];
    } else {
        $response['error'] = mysqli_error($conn);
    }
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>
