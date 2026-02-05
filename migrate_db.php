<?php
include "db.php";

try {
    $sql = "ALTER TABLE products 
            ADD COLUMN description_de TEXT DEFAULT NULL,
            ADD COLUMN description_en TEXT DEFAULT NULL,
            ADD COLUMN description_ar TEXT DEFAULT NULL";
            
    if ($conn->query($sql) === TRUE) {
        echo "Table products updated successfully (columns added).";
    } else {
        echo "Error updating table: " . $conn->error;
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
?>
