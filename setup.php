<?php
require 'db.php';

try {
    // 1. Create Tables

    // Products Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category VARCHAR(50) NOT NULL,
        img VARCHAR(255) NOT NULL,
        name_en VARCHAR(100) NOT NULL,
        name_de VARCHAR(100) NOT NULL,
        name_ar VARCHAR(100) NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        expiry DATE NOT NULL
    )");

    // Orders Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_name VARCHAR(100) NOT NULL,
        address VARCHAR(255) NOT NULL,
        city VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        payment_method VARCHAR(20) NOT NULL,
        total_amount DECIMAL(10, 2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Order Items Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price_at_purchase DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id),
        FOREIGN KEY (product_id) REFERENCES products(id)
    )");

    echo "Tables created successfully.<br>";

    // 2. Insert Sample Data (only if empty)
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    if ($stmt->fetchColumn() == 0) {
        $products = [
            ['grains', 'images/bulgur_salad.jpg', 'Bulgur', 'Bulgur', 'برغل', 2.5, '2025-12-01'],
            ['grains', 'https://placehold.co/400x300?text=Rice', 'Rice', 'Reis', 'رز', 3.0, '2026-01-15'],
            ['grains', 'https://placehold.co/400x300?text=Lentils', 'Red Lentils', 'Rote Linsen', 'عدس مجروش', 2.2, '2025-11-30'],
            ['syrups', 'https://placehold.co/400x300?text=Tomato+Paste', 'Tomato Paste', 'Tomatenmark', 'دبس بندورة', 1.5, '2025-06-20'],
            ['syrups', 'images/pomegranate_molasses.jpg', 'Pomegranate Molasses', 'Granatapfelsirup', 'دبس رمان', 4.5, '2026-03-10'],
            ['honey', 'https://placehold.co/400x300?text=Honey', 'Natural Honey', 'Naturhonig', 'عسل طبيعي', 12.0, '2027-01-01'],
            ['honey', 'https://placehold.co/400x300?text=Zaatar', 'Zaatar', 'Zaatar', 'زعتر', 3.5, '2025-08-15'],
            ['dairy', 'https://placehold.co/400x300?text=Cheese', 'White Cheese', 'Weißkäse', 'جبنة بيضاء', 6.0, '2024-02-28'],
            ['dairy', 'https://placehold.co/400x300?text=Yogurt', 'Yogurt', 'Joghurt', 'لبن', 1.8, '2024-02-10'],
            ['meat', 'https://placehold.co/400x300?text=Lamb', 'Lamb Meat', 'Lammfleisch', 'لحم خاروف', 15.0, '2024-01-20'],
            ['meat', 'https://placehold.co/400x300?text=Chicken', 'Chicken Breast', 'Hähnchenbrust', 'صدر دجاج', 8.5, '2024-01-18'],
            ['drinks', 'https://placehold.co/400x300?text=Coffee', 'Arabic Coffee', 'Arabischer Kaffee', 'قهوة عربية', 5.5, '2025-05-05'],
            ['drinks', 'https://placehold.co/400x300?text=Tea', 'Green Tea', 'Grüner Tee', 'شاي أخضر', 3.0, '2026-12-12'],
            ['canned', 'https://placehold.co/400x300?text=Tahini', 'Tahini', 'Tahini', 'طحينة', 4.0, '2025-09-09'],
            ['produce', 'https://placehold.co/400x300?text=Apples', 'Red Apples', 'Rote Äpfel', 'تفاح أحمر', 2.0, '2024-02-01'],
            ['produce', 'https://placehold.co/400x300?text=Tomatoes', 'Tomatoes', 'Tomaten', 'بندورة', 1.5, '2024-01-25'],
            ['canned', 'images/grilled_eggplant_jar.jpg', 'Grilled Eggplant (Jar)', 'Gegrillte Aubergine (Glas)', 'باذنجان مشوي (مرطبان)', 3.5, '2026-05-20'],
            ['canned', 'images/grilled_eggplant_can.jpg', 'Grilled Eggplant (Can)', 'Gegrillte Aubergine (Dose)', 'باذنجان مشوي (علبة)', 4.0, '2026-06-15']
        ];

        $stmt = $pdo->prepare("INSERT INTO products (category, img, name_en, name_de, name_ar, price, expiry) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($products as $p) {
            $stmt->execute($p);
        }
        echo "Sample data inserted successfully.<br>";
    } else {
        echo "Products table already has data.<br>";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
