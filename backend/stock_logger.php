<?php
/**
 * Log stock movements for Matin Food Audit Log
 */
function log_stock_movement($conn, $product_id, $change, $new_stock, $action_type, $user_name = 'System', $details = '') {
    $product_id = (int)$product_id;
    $change = (int)$change;
    $new_stock = (int)$new_stock;
    $action_type = mysqli_real_escape_string($conn, $action_type);
    $user_name = mysqli_real_escape_string($conn, $user_name);
    $details = mysqli_real_escape_string($conn, $details);

    $sql = "INSERT INTO stock_log (product_id, user_name, action_type, quantity_change, new_stock, details, created_at) 
            VALUES ($product_id, '$user_name', '$action_type', $change, $new_stock, '$details', NOW())";
    
    return mysqli_query($conn, $sql);
}
?>
