<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "matin_food";

$conn = mysqli_connect($host, $user, $pass, $db);
mysqli_set_charset($conn, "utf8mb4");

if (!$conn) {
    die("فشل الاتصال بقاعدة البيانات (MySQLi)");
}

// PDO connection for scripts like setup.php
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("فشل الاتصال بقاعدة البيانات (PDO): " . $e->getMessage());
}
?>
