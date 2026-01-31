<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "matin_food";

$conn = mysqli_connect($host, $user, $pass, $db);
mysqli_set_charset($conn, "utf8mb4");

if (!$conn) {
    die("فشل الاتصال بقاعدة البيانات");
}
?>
