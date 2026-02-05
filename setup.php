<?php
// require 'db.php'; // We will handle connection manually to create DB if needed
$host = "localhost";
$user = "root";
$pass = "";
$db   = "matin_food";

try {
    // 0. Create Database if not exists
    $pdo_init = new PDO("mysql:host=$host", $user, $pass);
    $pdo_init->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database `$db` checked/created.<br>";
    
    // Connect to the specific DB
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<div style='font-family: sans-serif; padding: 40px; line-height: 1.6; max-width: 800px; margin: 0 auto; background: #f1f8e9; border-radius: 12px; border: 1px solid #c5e1a5;'>";
    echo "<h2 style='color: #2e7d32; text-align: center; font-weight: 800;'>🌿 Matin Food Database Setup</h2>";
    echo "<hr style='border: 0; border-top: 1px solid #c5e1a5; margin-bottom: 20px;'>";
    
    // 0. Drop Tables (to ensure clean schema)
    echo "<div style='background: white; padding: 15px; border-radius: 8px; margin-bottom: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);'>";
    echo "<strong>Step 1: Cleaning Schema</strong><br>";
    echo "<span style='color: #555;'>Dropping old tables... </span>";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DROP TABLE IF EXISTS order_items");
    $pdo->exec("DROP TABLE IF EXISTS orders");
    $pdo->exec("DROP TABLE IF EXISTS products");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "<span style='color: #2e7d32;'>✔ Done</span></div>";

    // 1. Create Tables
    echo "<div style='background: white; padding: 15px; border-radius: 8px; margin-bottom: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);'>";
    echo "<strong>Step 2: Creating Tables</strong><br>";

    // Products Table
    $pdo->exec("CREATE TABLE products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category VARCHAR(50) NOT NULL,
        img VARCHAR(255) NOT NULL,
        name_en VARCHAR(100) NOT NULL,
        name_de VARCHAR(100) NOT NULL,
        name_ar VARCHAR(100) NOT NULL,
        description_de TEXT,
        description_en TEXT,
        description_ar TEXT,
        price DECIMAL(10, 2) NOT NULL,
        production_date DATE DEFAULT NULL,
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

    echo "<span style='color: #2e7d32;'>✔ Tables created successfully.</span></div>";

    // 2. Insert/Reset Sample Data
    echo "<div style='background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);'>";
    echo "<strong>Step 3: Seeding Data</strong><br>";
    echo "<span style='color: #555;'>Resetting product data... </span>";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE products");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    if (true) { // Always insert fresh data
        $products = [
            ['grains', 'images/bulgur.png', 'Bulgur', 'Bulgur', 'برغل', 2.50, '2024-06-01', '2025-12-01'],
            ['grains', 'images/شتورة_رز_مصري_10X1_KG.jpg', 'Egyptian Rice (1kg)', 'Ägyptischer Reis (1kg)', 'رز مصري (1 كيلو)', 3.00, '2024-07-15', '2026-01-15'],
            ['grains', 'images/شتورة_رز_مصري_4X5_KG.jpg', 'Egyptian Rice (5kg)', 'Ägyptischer Reis (5kg)', 'رز مصري (5 كيلو)', 13.50, '2024-07-15', '2026-01-15'],
            ['grains', 'images/bulgur_salad.jpg', 'Bulgur Salad', 'Bulgursalat', 'سلطة برغل', 4.50, '2024-05-30', '2025-11-30'],
            ['canned', 'images/شتورة_غاردن_دبس_بندورة_12X650g.jpg', 'Tomato Paste (650g)', 'Tomatenmark (650g)', 'دبس بندورة (650غ)', 1.50, '2024-01-20', '2025-06-20'],
            ['canned', 'images/شتورة_غاردن_دبس_بندورة_6x1100g.jpg', 'Tomato Paste (1.1kg)', 'Tomatenmark (1.1kg)', 'دبس بندورة (1.1 كيلو)', 2.80, '2024-01-20', '2025-06-20'],
            ['syrups', 'images/pomegranate.png', 'Pomegranate Molasses', 'Granatapfelsirup', 'دبس رمان', 4.50, '2024-03-10', '2026-03-10'],
            ['bakery', 'images/arabic_bread.jpg', 'Arabic Bread', 'Arabisches Brot', 'خبز عربي', 1.20, '2025-02-01', '2025-02-10'],
            ['fresh', 'images/vegetables.jpg', 'Mixed Vegetables (Fresh)', 'Frisches Gemüse', 'خضروات طازجة', 3.50, '2025-02-05', '2025-02-15'],
            ['sweets', 'images/oriental_sweets.jpg', 'Oriental Sweets', 'Orientalische Süßigkeiten', 'حلويات شرقية', 12.50, '2024-06-01', '2025-06-01'],
            ['canned', 'images/شتورا_غاردن_حمص_بطحينة_12X850_Gr.jpg', 'Hummus with Tahini (850g)', 'Hummus mit Tahini (850g)', 'حمص بطحينة (850غ)', 2.20, '2024-06-15', '2025-12-15'],
            ['canned', 'images/شتورا_غاردن_حمص_بطحينة_24X185_Gr.jpg', 'Hummus with Tahini (185g)', 'Hummus mit Tahini (185g)', 'حمص بطحينة (185غ)', 0.90, '2024-06-15', '2025-12-15'],
            ['canned', 'images/شتورا_غاردن_فول_خلطة_سورية_24X400_Gr.jpg', 'Fava Beans (Syrian style)', 'Fava-Bohnen (Syr. Art)', 'فول خلطة سورية', 1.80, '2024-04-10', '2025-10-10'],
            ['canned', 'images/شتورا_غاردن_فول_كمون_24X400_Gr.jpg', 'Fava Beans with Cumin', 'Fava-Bohnen mit Kreuzkümmel', 'فول بالكمون', 1.80, '2024-04-10', '2025-10-10'],
            ['canned', 'images/شتورا_غاردن_فول_مدمس_24X475_Gr.jpg', 'Broad Beans (Fava)', 'Ackerbohnen (Moudammas)', 'فول مدمس', 1.90, '2024-04-10', '2025-10-10'],
            ['canned', 'images/لارا_مكدوس_12x600g.jpg', 'Makdous (Lara) 600g', 'Makdous (Lara) 600g', 'مكدوس لارا 600غ', 5.50, '2024-07-01', '2026-01-01'],
            ['canned', 'images/الدرة_مكدوس_6x1250g.jpg', 'Makdous (Al Durra) 1.25kg', 'Makdous (Al Durra) 1.25kg', 'مكدوس الدرة 1.25 كيلو', 9.50, '2024-07-01', '2026-01-01'],
            ['canned', 'images/الدرة_ورق_عنب_محشي_24x400g.jpg', 'Stuffed Grape Leaves', 'Gefüllte Weinblätter', 'ورق عنب محشي', 3.50, '2024-11-15', '2026-05-15'],
            ['canned', 'images/الدرة_بادنجان_مشوي_12x650g.jpg', 'Grilled Eggplant (Jar)', 'Gegrillte Aubergine (Glas)', 'باذنجان مشوي (مرطبان)', 3.20, '2024-10-20', '2026-04-20'],
            ['canned', 'images/الدرة_باذنجان_مشوي_تنك_4x2750g.jpg', 'Grilled Eggplant (Large Can)', 'Gegrillte Aubergine (Große Dose)', 'باذنجان مشوي (تنكة كبيرة)', 11.20, '2024-10-20', '2026-04-20'],
            ['canned', 'images/لارا_باذنجان_مشوي_مقطع_12x610g.jpg', 'Chopped Grilled Eggplant', 'Gegrillte Aubergine geschnitten', 'باذنجان مشوي مقطع', 3.40, '2024-10-20', '2026-04-20'],
            ['syrups', 'images/شتورة_غاردن_خل_تفاح_12X500ml.jpg', 'Apple Vinegar', 'Apfelessig', 'خل تفاح', 2.80, '2025-01-01', '2027-01-01'],
            ['canned', 'images/كامشن_خضروات_12X450_Gr__.jpg', 'Mixed Vegetables', 'Mischgemüse', 'خضروات مشكلة', 2.10, '2024-08-08', '2026-08-08']
        ];

        $stmt = $pdo->prepare("INSERT INTO products (category, img, name_en, name_de, name_ar, price, production_date, expiry, description_de, description_en, description_ar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Leckeres Produkt', 'Delicious product', 'منتج لذيذ')");
        
        foreach ($products as $p) {
            $stmt->execute($p);
        }
        echo "<span style='color: #2e7d32;'>✔ Sample data inserted successfully.</span></div>";
    } else {
        echo "<div style='color: #f57f17;'>Products table already has data.</div>";
    }
    
    echo "<div style='text-align: center; margin-top: 30px;'><a href='index.html' style='background: #fdd835; color: #333; padding: 12px 25px; text-decoration: none; border-radius: 50px; font-weight: 800; box-shadow: 0 4px 0 #fbc02d;'>ZURÜCK ZUM SHOP</a></div>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div style='color: #d32f2f; background: #ffebee; padding: 15px; border-radius: 8px; border: 1px solid #ffcdd2;'><strong>Error:</strong> " . $e->getMessage() . "</div>";
}
?>
