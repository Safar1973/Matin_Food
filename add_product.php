<?php
require_once 'db.php';

// Product Details
$category = 'grains';
$img = 'images/Japanische Reis.jpg';
$name_en = 'Japanese Rice';
$name_de = 'Japanische Reis';
$name_ar = 'أرز ياباني';
$price = 5.90;
$weight = '1kg';
$production_date = '2024-02-01';
$expiry = '2026-02-01';
$description_de = 'Hochwertiger japanischer Reis, ideal für Sushi und Beilagen.';
$description_en = 'High quality Japanese rice, ideal for sushi and side dishes.';
$description_ar = 'أرز ياباني عالي الجودة، مثالي للسوشي والأطباق الجانبية.';
$stock = 100;

try {
    // Check if product already exists to avoid duplicates
    $check = mysqli_query($conn, "SELECT id FROM products WHERE name_de = '$name_de' LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        echo "Das Produkt '$name_de' existiert bereits in der Datenbank.";
    } else {
        $sql = "INSERT INTO products (category, img, name_en, name_de, name_ar, description_de, description_en, description_ar, price, weight, production_date, expiry, stock) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssssssssdssi", 
            $category, $img, $name_en, $name_de, $name_ar, 
            $description_de, $description_en, $description_ar, 
            $price, $weight, $production_date, $expiry, $stock
        );

        if (mysqli_stmt_execute($stmt)) {
            echo "Erfolg! Das Produkt '$name_de' wurde zur Datenbank hinzugefügt.<br>";
            echo "Bildpfad: $img";
        } else {
            echo "Fehler beim Einfügen: " . mysqli_error($conn);
        }
    }
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage();
}
?>
