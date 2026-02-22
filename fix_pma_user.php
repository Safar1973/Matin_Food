<?php
$host = "127.0.0.1";
$user = "root";
$pass = "";

$conn = @mysqli_connect($host, $user, $pass);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$queries = [
    "CREATE USER IF NOT EXISTS 'pma'@'127.0.0.1' IDENTIFIED BY ''",
    "GRANT SELECT, INSERT, UPDATE, DELETE ON `phpmyadmin`.* TO 'pma'@'127.0.0.1'",
    "FLUSH PRIVILEGES"
];

foreach ($queries as $q) {
    echo "Running: $q\n";
    if (!mysqli_query($conn, $q)) {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
}

mysqli_close($conn);
?>
