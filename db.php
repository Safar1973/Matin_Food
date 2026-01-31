<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "mtn_news"; // أو matin_food إذا غيّرت الاسم

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("فشل الاتصال بقاعدة البيانات");
}

mysqli_set_charset($conn, "utf8mb4");
?>
