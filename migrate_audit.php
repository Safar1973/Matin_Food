<?php
require_once 'db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        table_name VARCHAR(50) NOT NULL,
        record_id INT NOT NULL,
        action ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
        old_values JSON DEFAULT NULL,
        new_values JSON DEFAULT NULL,
        changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    if (mysqli_query($conn, $sql)) {
        echo "Table `audit_logs` created successfully.\n";
    } else {
        throw new Exception(mysqli_error($conn));
    }

} catch (Exception $e) {
    die("Error creating table: " . $e->getMessage());
}
?>
