<?php
require 'db.php';

try {
    // 0. Drop Tables (to ensure clean schema)
    echo "Dropping old tables...<br>";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DROP TABLE IF EXISTS order_items");
    $pdo->exec("DROP TABLE IF EXISTS orders");
    $pdo->exec("DROP TABLE IF EXISTS products");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // 1. Create Tables

    // Products Table
    $pdo->exec("CREATE TABLE products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category VARCHAR(50) NOT NULL,
        img VARCHAR(255) NOT NULL,
        name_en VARCHAR(100) NOT NULL,
        name_de VARCHAR(100) NOT NULL,
        name_ar VARCHAR(100) NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        expiry DATE NOT NULL,
        stock INT DEFAULT 100
    )");

    // Orders Table
    $pdo->exec("CREATE TABLE orders (
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
    $pdo->exec("CREATE TABLE order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price_at_purchase DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id),
        FOREIGN KEY (product_id) REFERENCES products(id)
    )");

    echo "Tables created successfully.<br>";

    // 2. Insert/Reset Sample Data
    echo "Resetting product data...<br>";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE products");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    if (true) { // Always insert fresh data
        $products = [
            ['grains', 'images/bulgur.png', 'Bulgur', 'Bulgur', 'برغل', 2.50, '2025-12-01'],
            ['grains', 'images/شتورة_رز_مصري_10X1_KG.jpg', 'Egyptian Rice', 'Ägyptischer Reis', 'رز مصري', 3.00, '2026-01-15'],
            ['grains', 'images/bulgur_salad.jpg', 'Bulgur Salad', 'Bulgursalat', 'سلطة برغل', 4.50, '2025-11-30'],
            ['canned', 'images/شتورة_غاردن_دبس_بندورة_12X650g.jpg', 'Tomato Paste', 'Tomatenmark', 'دبس بندورة', 1.50, '2025-06-20'],
            ['syrups', 'images/pomegranate.png', 'Pomegranate Molasses', 'Granatapfelsirup', 'دبس رمان', 4.50, '2026-03-10'],
            ['bakery', 'images/arabic_bread.jpg', 'Arabic Bread', 'Arabisches Brot', 'خبز عربي', 1.20, '2025-02-10'],
            ['fresh', 'images/vegetables.jpg', 'Mixed Vegetables (Fresh)', 'Frisches Gemüse', 'خضروات طازجة', 3.50, '2025-02-15'],
            ['sweets', 'images/oriental_sweets.jpg', 'Oriental Sweets', 'Orientalische Süßigkeiten', 'حلويات شرقية', 12.50, '2025-06-01'],
            ['canned', 'images/شتورا_غاردن_حمص_بطحينة_12X850_Gr.jpg', 'Hummus with Tahini', 'Hummus mit Tahini', 'حمص بطحينة', 2.20, '2025-12-15'],
            ['canned', 'images/شتورا_غاردن_فول_خلطة_سورية_24X400_Gr.jpg', 'Fava Beans (Syrian style)', 'Fava-Bohnen (Syr. Art)', 'فول خلطة سورية', 1.80, '2025-10-10'],
            ['canned', 'images/لارا_مكدوس_12x600g.jpg', 'Makdous (Lara)', 'Makdous (Lara)', 'مكدوس لارا', 5.50, '2026-01-01'],
            ['canned', 'images/الدرة_ورق_عنب_محشي_24x400g.jpg', 'Stuffed Grape Leaves', 'Gefüllte Weinblätter', 'ورق عنب محشي', 3.50, '2026-05-15'],
            ['canned', 'images/الدرة_بادنجان_مشوي_12x650g.jpg', 'Grilled Eggplant', 'Gegrillte Aubergine', 'باذنجان مشوي', 3.20, '2026-04-20'],
            ['syrups', 'images/شتورة_غاردن_خل_تفاح_12X500ml.jpg', 'Apple Vinegar', 'Apfelessig', 'خل تفاح', 2.80, '2027-01-01'],
            ['canned', 'images/كامشن_خضروات_12X450_Gr__.jpg', 'Mixed Vegetables', 'Mischgemüse', 'خضروات مشكلة', 2.10, '2026-08-08']
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
